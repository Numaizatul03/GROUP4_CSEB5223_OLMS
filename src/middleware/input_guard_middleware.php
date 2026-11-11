<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/../services/input_guard.php';

/**
 * Class Name:  InputGuardMiddleware
 * Purpose:     Refuse requests whose input has an impossible shape before any code reads it.
 * Description: The forms only ever send plain text. A list value (?keyword[]=x),
 *              a value longer than 5000 characters, or broken JSON can only come
 *              from someone editing the request by hand, so it is refused with
 *              "400 Bad Request" instead of causing warnings deeper in the code.
 */
final class InputGuardMiddleware implements Middleware
{
    /**
     * Function/Method Name: handle
     * Purpose:      Check every query and body value, then continue.
     * Parameters:   Request $request, callable $next
     * Return Value: void
     * Description:  API cart requests may send numbers in JSON, which are allowed.
     */
    public function handle(Request $request, callable $next): void
    {
        if ($request->hasInvalidJson()) {
            MiddlewareResponder::respondWithError($request, 400, 'invalid_json', 'Bad Request', 'The request body is not valid JSON.');
            return;
        }

        foreach (['query' => $request->getQuery(), 'body' => $request->getBody()] as $inputValues) {
            $problemField = $this->findProblemField($inputValues);
            if ($problemField !== null) {
                MiddlewareResponder::respondWithError(
                    $request,
                    400,
                    'invalid_input',
                    'Bad Request',
                    'The value sent for "' . mb_substr($problemField, 0, 40) . '" is not allowed. Please use the forms on the website.'
                );
                return;
            }
        }

        $next($request);
    }

    /**
     * Function/Method Name: findProblemField
     * Purpose:      Find the first input value that is not plain text or is too long.
     * Parameters:   array $inputValues - field name => value
     * Return Value: ?string - the field name, or null when every value is fine
     * Description:  Booleans and null from JSON are allowed; lists and objects are not.
     */
    private function findProblemField(array $inputValues): ?string
    {
        foreach ($inputValues as $fieldName => $fieldValue) {
            $isAllowedType = InputGuard::isSafeScalar($fieldValue) || is_bool($fieldValue) || $fieldValue === null;
            if (!$isAllowedType || mb_strlen((string) $fieldValue) > InputGuard::MAX_TEXT_LENGTH) {
                return (string) $fieldName;
            }
        }

        return null;
    }
}
