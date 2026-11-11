<?php

declare(strict_types=1);

/**
 * Class Name:  ApiResponse
 * Purpose:     One JSON answer from the OLMS API (status code + body + headers).
 * Description: Every API answer has the same shape, so apps using the API can
 *              handle them the same way:
 *                success: { "data": ..., "meta": { ... } }
 *                error:   { "error": { "code": "...", "message": "...", "fields": { ... } } }
 *              API controllers return ApiResponse objects instead of printing,
 *              which also lets the automated tests check them directly.
 */
final class ApiResponse
{
    private int $statusCode;
    private array $body;
    private array $headers;

    public function __construct(int $statusCode, array $body, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Function/Method Name: success
     * Purpose:      Build a successful answer.
     * Parameters:   mixed $data - the result; array $meta - extra facts (counts, pages)
     *               int $statusCode - 200 by default, 201 after creating something
     * Return Value: ApiResponse
     * Description:  "meta" is left out when empty.
     */
    public static function success(mixed $data, array $meta = [], int $statusCode = 200): ApiResponse
    {
        return new ApiResponse($statusCode, ['data' => $data] + ($meta === [] ? [] : ['meta' => $meta]));
    }

    /**
     * Function/Method Name: error
     * Purpose:      Build an error answer with a code a program can check.
     * Parameters:   int $statusCode - e.g. 404, 422, 429
     *               string $errorCode - e.g. "not_found", "validation_failed"
     *               string $message - plain sentence for people
     *               array $fields - field name => message (validation errors only)
     *               array $headers - e.g. ["Retry-After" => "60"]
     * Return Value: ApiResponse
     * Description:  Never contains technical details such as SQL or file paths.
     */
    public static function error(int $statusCode, string $errorCode, string $message, array $fields = [], array $headers = []): ApiResponse
    {
        $errorBody = ['code' => $errorCode, 'message' => $message] + ($fields === [] ? [] : ['fields' => $fields]);

        return new ApiResponse($statusCode, ['error' => $errorBody], $headers);
    }

    /**
     * Function/Method Name: send
     * Purpose:      Send the answer to the browser or app.
     * Parameters:   none
     * Return Value: void
     * Description:  JSON_THROW_ON_ERROR means a body that cannot be encoded is
     *               caught by ErrorHandlingMiddleware instead of sending broken JSON.
     */
    public function send(): void
    {
        // Headers can only be set before any output; this check stops a late answer adding PHP warnings to the JSON
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            foreach ($this->headers as $headerName => $headerValue) {
                header($headerName . ': ' . $headerValue);
            }
        }

        echo json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
