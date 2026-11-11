<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

/**
 * Class Name:  MiddlewarePipeline
 * Purpose:     Run a list of middleware in order, then the final handler (the controller).
 * Description: The first middleware added is the outermost one: it runs first
 *              and can catch problems from everything after it. That is why
 *              ErrorHandlingMiddleware is always added first.
 */
final class MiddlewarePipeline
{
    /** @var Middleware[] */
    private array $middlewareList = [];

    public function add(Middleware $middleware): MiddlewarePipeline
    {
        $this->middlewareList[] = $middleware;

        return $this;
    }

    /**
     * Function/Method Name: run
     * Purpose:      Send the request through every middleware, then to the controller.
     * Parameters:   Request $request
     *               callable $finalHandler - called with the request when every middleware agreed
     * Return Value: void
     * Description:  Builds a chain of $next functions from the last middleware back to
     *               the first, so each middleware decides whether the chain continues.
     */
    public function run(Request $request, callable $finalHandler): void
    {
        $next = $finalHandler;
        foreach (array_reverse($this->middlewareList) as $middleware) {
            $next = static fn (Request $currentRequest) => $middleware->handle($currentRequest, $next);
        }

        $next($request);
    }

    /** Class names in running order - shown on the system guide and checked by the tests. */
    public function describe(): array
    {
        return array_map(static fn (Middleware $middleware) => $middleware::class, $this->middlewareList);
    }
}
