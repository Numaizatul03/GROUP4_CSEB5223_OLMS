<?php

declare(strict_types=1);

/**
 * File:    defensive_middleware_api_test.php
 * Purpose: Lab 7 & 8 tests - defensive input handling, rate limiting, the middleware
 *          chain and the JSON API.
 */

require_once __DIR__ . '/../src/middleware/application_pipeline.php';

/**
 * Function/Method Name: runMiddlewareAndCapture
 * Purpose:      Run one middleware with a test request and capture what it printed.
 * Parameters:   Middleware $middleware, Request $request
 * Return Value: array - ['passedOn' => bool, 'output' => string]
 * Description:  "passedOn" tells whether the middleware let the request continue.
 */
function runMiddlewareAndCapture(Middleware $middleware, Request $request): array
{
    $passedOn = false;
    ob_start();
    $middleware->handle($request, function () use (&$passedOn): void {
        $passedOn = true;
    });

    return ['passedOn' => $passedOn, 'output' => (string) ob_get_clean()];
}

function makeApiRequest(string $httpMethod, string $path, array $query = [], array $body = [], array $headers = []): Request
{
    return new Request($httpMethod, true, $path, $query, $body, $headers, '10.0.0.7');
}

function makeApiRequestWithRoute(PDO $connection, string $routeKey, array $routeParams = [], array $query = [], array $body = []): Request
{
    [$httpMethod, $path] = explode(' ', $routeKey, 2);
    $request = makeApiRequest($httpMethod, $path, $query, $body);
    $authService = new AuthService(new UserService($connection));
    $request->setAttribute('connection', $connection);
    $request->setAttribute('authService', $authService);
    $request->setAttribute('currentUser', $authService->getCurrentUser());
    $request->setAttribute('route', ['name' => $routeKey, 'params' => $routeParams]);

    return $request;
}

// ---------------------------------------------------------------- InputGuard

function testInputGuardReadsOnlySafeText(): void
{
    assertSame('', InputGuard::readText(['keyword' => ['x']], 'keyword'), 'A list value is read as empty text');
    assertSame('eggs', InputGuard::readText(['keyword' => "  eg\0gs \x07"], 'keyword'), 'Spaces and hidden control characters are removed');
    assertSame('abc', InputGuard::readText(['keyword' => 'abcdef'], 'keyword', 3), 'Text is cut to the maximum length');
    assertSame(12, InputGuard::readId(['productId' => '12'], 'productId'), 'A normal ID is read');
    assertSame(null, InputGuard::readId(['productId' => '0'], 'productId'), 'ID 0 is refused');
    assertSame(null, InputGuard::readId(['productId' => '-3'], 'productId'), 'A negative ID is refused');
    assertSame(null, InputGuard::readId(['productId' => str_repeat('9', 30)], 'productId'), 'A 30-digit ID is refused instead of overflowing');
}

function testSearchWildcardsAreMatchedLiterally(): void
{
    $productService = new ProductService(createTestConnection());

    assertSame('%a!%b!_c!!%', InputGuard::makeContainsPattern('a%b_c!'), '%, _ and ! are escaped');
    assertSame([], $productService->searchProduct(['keyword' => '%']), 'Searching "%" no longer returns every product');
    assertSame([], $productService->searchProduct(['keyword' => '_']), 'Searching "_" no longer matches any character');
    assertSame(1, count($productService->searchProduct(['keyword' => 'egg'])), 'Normal searches still work');
}

function testTooLongSearchWordsAndPasswordsAreRefused(): void
{
    $longKeyword = ['keyword' => str_repeat('a', InputGuard::MAX_SEARCH_LENGTH + 1)];
    assertTrue(isset((new ProductValidator())->validateSearchInput($longKeyword)['keyword']), 'Product search refuses a 101-character keyword');
    assertTrue(isset((new CategoryValidator())->validateSearchInput($longKeyword)['keyword']), 'Category search refuses it too');
    assertTrue(isset((new UserValidator())->validateSearchInput($longKeyword)['keyword']), 'Customer search refuses it too');
    assertTrue(isset((new OrderValidator())->validateOrderSearchInput($longKeyword)['keyword']), 'Order search refuses it too');

    $longPassword = str_repeat('a1', 40);
    $passwordErrors = (new UserValidator())->validateNewPassword(['password' => $longPassword, 'passwordConfirmation' => $longPassword]);
    assertTrue(str_contains($passwordErrors['password'] ?? '', '72 characters or fewer'), 'An 80-character password is refused (bcrypt would cut it)');
}

function testDuplicateEmailRaceGivesClearMessage(): void
{
    $userService = new UserService(createTestConnection());
    $userData = ['name' => 'Copy', 'email' => 'siti@olms.test', 'password' => 'Copy12345', 'shippingAddress' => 'x', 'phoneNumber' => '0123456789'];

    assertThrows(fn () => $userService->createUser($userData), 'already registered', 'The unique email index is turned into a clear message');
}

// ---------------------------------------------------------------- RateLimiter

function testRateLimiterLocksAfterTooManyAttempts(): void
{
    $rateLimiter = new RateLimiter(createTestConnection());
    $loginKey = RateLimitMiddleware::makeLoginKey('Siti@olms.test ', '127.0.0.1');

    for ($attempt = 1; $attempt < RateLimitMiddleware::LOGIN_MAX_FAILURES; $attempt++) {
        $rateLimiter->hit($loginKey, RateLimitMiddleware::LOGIN_WINDOW_SECONDS);
    }
    assertTrue(!$rateLimiter->tooManyAttempts($loginKey, RateLimitMiddleware::LOGIN_MAX_FAILURES, RateLimitMiddleware::LOGIN_WINDOW_SECONDS), '4 failures are still allowed');

    assertSame(5, $rateLimiter->hit($loginKey, RateLimitMiddleware::LOGIN_WINDOW_SECONDS), 'The 5th failure is counted');
    assertTrue($rateLimiter->tooManyAttempts('login:siti@olms.test|127.0.0.1', 5, 900), 'After 5 failures the email is locked (key ignores case and spaces)');
    assertTrue($rateLimiter->secondsUntilReset($loginKey, 900) > 890, 'The lock lasts about 15 minutes');

    $rateLimiter->clear($loginKey);
    assertTrue(!$rateLimiter->tooManyAttempts($loginKey, 5, 900), 'A successful login clears the lock');
}

function testRateLimitMiddlewareBlocksApiFlooding(): void
{
    $connection = createTestConnection();
    $request = makeApiRequestWithRoute($connection, 'GET /health');
    $rateLimitMiddleware = new RateLimitMiddleware();

    for ($requestNumber = 1; $requestNumber <= RateLimitMiddleware::API_MAX_REQUESTS; $requestNumber++) {
        runMiddlewareAndCapture($rateLimitMiddleware, $request);
    }
    $blockedResult = runMiddlewareAndCapture($rateLimitMiddleware, $request);

    assertTrue(!$blockedResult['passedOn'], 'Request 61 within a minute is stopped');
    assertSame('too_many_requests', json_decode($blockedResult['output'], true)['error']['code'], 'The API explains it with code too_many_requests');
}

// ---------------------------------------------------------------- Middleware

function testPipelineRunsMiddlewareInOrderAndCanStop(): void
{
    $steps = [];
    $makeStep = static function (string $stepName, bool $isStopping) use (&$steps): Middleware {
        return new class ($stepName, $isStopping, $steps) implements Middleware {
            public function __construct(private string $stepName, private bool $isStopping, private array &$steps)
            {
            }

            public function handle(Request $request, callable $next): void
            {
                $this->steps[] = $this->stepName;
                if (!$this->isStopping) {
                    $next($request);
                }
            }
        };
    };

    (new MiddlewarePipeline())->add($makeStep('first', false))->add($makeStep('second', false))
        ->run(makeApiRequest('GET', '/'), function () use (&$steps): void {
            $steps[] = 'controller';
        });
    assertSame(['first', 'second', 'controller'], $steps, 'Middleware run in the order they were added, then the controller');

    $steps = [];
    (new MiddlewarePipeline())->add($makeStep('guard', true))->add($makeStep('never', false))
        ->run(makeApiRequest('GET', '/'), function () use (&$steps): void {
            $steps[] = 'controller';
        });
    assertSame(['guard'], $steps, 'A middleware that stops the request prevents everything after it');

    $realOrder = ApplicationPipeline::build([], false)->describe();
    assertSame('ErrorHandlingMiddleware', $realOrder[0], 'Error handling is always the outermost step');
    assertSame('CsrfMiddleware', end($realOrder), 'The CSRF check is the last step before the controller');
}

function testInputGuardMiddlewareRefusesTamperedInput(): void
{
    $inputGuardMiddleware = new InputGuardMiddleware();

    $listResult = runMiddlewareAndCapture($inputGuardMiddleware, makeApiRequest('GET', '/products', ['keyword' => ['x']]));
    assertTrue(!$listResult['passedOn'] && str_contains($listResult['output'], 'invalid_input'), 'A list value (?keyword[]=x) is refused with 400');

    $longResult = runMiddlewareAndCapture($inputGuardMiddleware, makeApiRequest('GET', '/products', ['keyword' => str_repeat('a', 5001)]));
    assertTrue(!$longResult['passedOn'], 'A value over 5000 characters is refused');

    $jsonResult = runMiddlewareAndCapture($inputGuardMiddleware, new Request('POST', true, '/cart/items', [], [], [], '1.1.1.1', true));
    assertTrue(!$jsonResult['passedOn'] && str_contains($jsonResult['output'], 'invalid_json'), 'Broken JSON is refused with a clear code');

    $normalResult = runMiddlewareAndCapture($inputGuardMiddleware, makeApiRequest('POST', '/cart/items', [], ['productId' => 3, 'quantity' => '2']));
    assertTrue($normalResult['passedOn'], 'Normal values (including JSON numbers) pass');
}

function testCsrfMiddlewareProtectsChanges(): void
{
    $_SESSION = [];
    $csrfMiddleware = new CsrfMiddleware();

    assertTrue(runMiddlewareAndCapture($csrfMiddleware, makeApiRequest('GET', '/cart'))['passedOn'], 'Reading (GET) needs no token');
    $missingToken = runMiddlewareAndCapture($csrfMiddleware, makeApiRequest('POST', '/cart/items'));
    assertTrue(!$missingToken['passedOn'] && str_contains($missingToken['output'], 'csrf_token_invalid'), 'A change without a token is refused');

    $validToken = SessionService::getCsrfToken();
    $withToken = runMiddlewareAndCapture($csrfMiddleware, makeApiRequest('POST', '/cart/items', [], [], ['X-CSRF-Token' => $validToken]));
    assertTrue($withToken['passedOn'], 'A change with the session token passes');
}

function testRoutingMiddlewareMatchesApiPaths(): void
{
    $routingMiddleware = new RoutingMiddleware(require __DIR__ . '/../src/config/api_routes.php');

    $request = makeApiRequest('GET', '/products/FRP-002/stock');
    assertTrue(runMiddlewareAndCapture($routingMiddleware, $request)['passedOn'], 'A known path is matched');
    assertSame(['productKey' => 'FRP-002'], $request->getAttribute('route')['params'], 'The path value is read');

    $wrongMethod = runMiddlewareAndCapture($routingMiddleware, makeApiRequest('DELETE', '/products/3'));
    assertTrue(!$wrongMethod['passedOn'] && str_contains($wrongMethod['output'], 'method_not_allowed'), 'Wrong method gives 405');

    $unknownPath = runMiddlewareAndCapture($routingMiddleware, makeApiRequest('GET', '/admin/secrets'));
    assertTrue(!$unknownPath['passedOn'] && str_contains($unknownPath['output'], 'not_found'), 'Unknown path gives 404');
}

function testErrorHandlingMiddlewareHidesTechnicalDetails(): void
{
    $result = (function (): array {
        ob_start();
        (new ErrorHandlingMiddleware(false))->handle(makeApiRequest('GET', '/health'), function (): void {
            echo 'half a page';
            throw new RuntimeException('SQLSTATE[42S02]: table missing in C:\\secret\\path.php');
        });

        return ['output' => (string) ob_get_clean()];
    })();

    $errorBody = json_decode($result['output'], true)['error'] ?? [];
    assertSame('server_error', $errorBody['code'] ?? null, 'An unexpected error becomes a 500 server_error');
    assertTrue(!str_contains($result['output'], 'SQLSTATE') && !str_contains($result['output'], 'half a page'), 'No SQL, file path or half-printed page reaches the user');
    assertTrue((bool) preg_match('/ERR-[0-9A-F]{6}/', $errorBody['message'] ?? ''), 'The user gets a reference code to report');
}

// ---------------------------------------------------------------- JSON API

function testApiListsAndFindsProducts(): void
{
    $connection = createTestConnection();
    $apiCatalogController = ControllerFactory::createController('ApiCatalogController', makeApiRequestWithRoute($connection, 'GET /health'));

    $searchResponse = $apiCatalogController->listProducts(makeApiRequestWithRoute($connection, 'GET /products', [], ['keyword' => 'eggs']));
    assertSame(200, $searchResponse->getStatusCode(), 'Search answers 200');
    assertSame('FRP-001', $searchResponse->getBody()['data'][0]['code'], 'Search returns the egg product with its code');

    $pagedResponse = $apiCatalogController->listProducts(makeApiRequestWithRoute($connection, 'GET /products', [], ['perPage' => '3', 'page' => '2']));
    assertSame(['total' => 10, 'page' => 2, 'perPage' => 3, 'totalPages' => 4], $pagedResponse->getBody()['meta'], 'Paging facts are correct');

    $badPaging = $apiCatalogController->listProducts(makeApiRequestWithRoute($connection, 'GET /products', [], ['perPage' => '500']));
    assertSame(422, $badPaging->getStatusCode(), 'perPage above 50 is refused with 422');

    $byCode = $apiCatalogController->showProduct(makeApiRequestWithRoute($connection, 'GET /products/{productKey}', ['productKey' => 'bev-002']));
    assertSame('Mineral Water 1.5L', $byCode->getBody()['data']['name'], 'A product can be found by code, ignoring case');

    $badKey = $apiCatalogController->showProduct(makeApiRequestWithRoute($connection, 'GET /products/{productKey}', ['productKey' => "1' OR 1=1"]));
    assertSame(400, $badKey->getStatusCode(), 'A key that is neither ID nor code is refused before reaching the database');

    $missing = $apiCatalogController->showProduct(makeApiRequestWithRoute($connection, 'GET /products/{productKey}', ['productKey' => '999']));
    assertSame(404, $missing->getStatusCode(), 'An unknown product gives 404');
}

function testApiStockCheck(): void
{
    $connection = createTestConnection();
    $apiCatalogController = ControllerFactory::createController('ApiCatalogController', makeApiRequestWithRoute($connection, 'GET /health'));
    $stockFor = fn (string $quantity) => $apiCatalogController->showStock(
        makeApiRequestWithRoute($connection, 'GET /products/{productKey}/stock', ['productKey' => '3'], ['quantity' => $quantity])
    );

    assertSame(true, $stockFor('3')->getBody()['data']['isAvailable'], '3 tomatoes are available');
    assertSame(false, $stockFor('4')->getBody()['data']['isAvailable'], '4 tomatoes are not (only 3 left)');
    assertSame(422, $stockFor('abc')->getStatusCode(), 'A quantity that is not a number is refused');
    assertSame(422, $stockFor('21')->getStatusCode(), 'A quantity above the cart limit (20) is refused');
}

function testApiCartAddsForTheLoggedInCustomerOnly(): void
{
    $connection = createTestConnection();
    $_SESSION = [SessionService::LOGGED_IN_USER_KEY => 4];
    $addRequest = fn (array $body) => makeApiRequestWithRoute($connection, 'POST /cart/items', [], [], $body);
    $apiCartController = ControllerFactory::createController('ApiCartController', $addRequest([]));

    $added = $apiCartController->addItem($addRequest(['productId' => 1, 'quantity' => 2, 'userId' => 2]));
    assertSame(201, $added->getStatusCode(), 'Adding answers 201 Created');
    assertSame(2, $added->getBody()['data']['itemCount'], 'The cart now holds 2 units');
    assertTrue((new CartService($connection))->getCartForUser(2)->isEmpty(), 'A userId in the body is ignored - the cart owner comes from the session');

    assertSame(409, $apiCartController->addItem($addRequest(['productId' => 3, 'quantity' => 4]))->getStatusCode(), 'More than the stock gives 409');
    assertSame(404, $apiCartController->addItem($addRequest(['productId' => 999]))->getStatusCode(), 'An unknown product gives 404');
    assertSame(422, $apiCartController->addItem($addRequest(['productId' => 'x', 'quantity' => '-1']))->getStatusCode(), 'Bad values give 422');
    $_SESSION = [];
}

function testApiOrdersNeedAdministrator(): void
{
    $connection = createTestConnection();
    $authorizationMiddleware = new AuthorizationMiddleware();

    $_SESSION = [];
    $visitorRequest = makeApiRequestWithRoute($connection, 'GET /orders');
    $visitorRequest->setAttribute('route', ['name' => 'GET /orders', 'accessLevel' => 'administrator', 'params' => []]);
    assertTrue(str_contains(runMiddlewareAndCapture($authorizationMiddleware, $visitorRequest)['output'], 'login_required'), 'A visitor gets 401 login_required');

    $_SESSION = [SessionService::LOGGED_IN_USER_KEY => 2];
    $customerRequest = makeApiRequestWithRoute($connection, 'GET /orders');
    $customerRequest->setAttribute('route', ['name' => 'GET /orders', 'accessLevel' => 'administrator', 'params' => []]);
    assertTrue(str_contains(runMiddlewareAndCapture($authorizationMiddleware, $customerRequest)['output'], 'forbidden'), 'A customer gets 403 forbidden');
    $_SESSION = [];
}

return [
    'testInputGuardReadsOnlySafeText',
    'testSearchWildcardsAreMatchedLiterally',
    'testTooLongSearchWordsAndPasswordsAreRefused',
    'testDuplicateEmailRaceGivesClearMessage',
    'testRateLimiterLocksAfterTooManyAttempts',
    'testRateLimitMiddlewareBlocksApiFlooding',
    'testPipelineRunsMiddlewareInOrderAndCanStop',
    'testInputGuardMiddlewareRefusesTamperedInput',
    'testCsrfMiddlewareProtectsChanges',
    'testRoutingMiddlewareMatchesApiPaths',
    'testErrorHandlingMiddlewareHidesTechnicalDetails',
    'testApiListsAndFindsProducts',
    'testApiStockCheck',
    'testApiCartAddsForTheLoggedInCustomerOnly',
    'testApiOrdersNeedAdministrator',
];
