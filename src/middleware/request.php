<?php

declare(strict_types=1);

/**
 * Class Name:  Request
 * Purpose:     Everything about the current request in one object.
 * Description: Built once by index.php (web pages) or api.php (JSON API) and
 *              passed through every middleware. Middleware add facts to it
 *              (the route, the logged-in user) as "attributes", so later steps
 *              do not have to work them out again.
 */
final class Request
{
    private string $httpMethod;
    private bool $isApi;
    private string $path;
    private array $query;
    private array $body;
    private array $headers;
    private string $clientIp;
    private bool $hasInvalidJson;
    private array $attributes = [];

    public function __construct(
        string $httpMethod,
        bool $isApi,
        string $path,
        array $query,
        array $body,
        array $headers = [],
        string $clientIp = '127.0.0.1',
        bool $hasInvalidJson = false
    ) {
        $this->httpMethod = strtoupper($httpMethod);
        $this->isApi = $isApi;
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->clientIp = $clientIp;
        $this->hasInvalidJson = $hasInvalidJson;
    }

    /**
     * Function/Method Name: fromGlobals
     * Purpose:      Build the Request from PHP's $_SERVER, $_GET and $_POST.
     * Parameters:   bool $isApi - true when called from api.php
     * Return Value: Request
     * Description:  For the API, the path comes from the URL after api.php
     *               (api.php/products/3) or from ?path= on servers without
     *               PATH_INFO. A JSON body is decoded; invalid JSON is flagged
     *               so InputGuardMiddleware can refuse it with a clear message.
     */
    public static function fromGlobals(bool $isApi): Request
    {
        $httpMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $isApi
            ? '/' . trim((string) ($_SERVER['PATH_INFO'] ?? (is_string($_GET['path'] ?? null) ? $_GET['path'] : '')), '/')
            : (is_string($_GET['action'] ?? null) ? $_GET['action'] : '');

        $body = $_POST;
        $hasInvalidJson = false;
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if ($isApi && str_contains($contentType, 'application/json')) {
            $rawBody = (string) file_get_contents('php://input');
            $decodedBody = $rawBody === '' ? [] : json_decode($rawBody, true);
            $hasInvalidJson = !is_array($decodedBody);
            $body = is_array($decodedBody) ? $decodedBody : [];
        }

        $headers = [];
        foreach ($_SERVER as $serverKey => $serverValue) {
            if (str_starts_with($serverKey, 'HTTP_') && is_string($serverValue)) {
                $headers[str_replace('_', '-', substr($serverKey, 5))] = $serverValue;
            }
        }

        return new Request($httpMethod, $isApi, $path, $_GET, $body, $headers, (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), $hasInvalidJson);
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function isApi(): bool
    {
        return $this->isApi;
    }

    /** Web: the ?action= value. API: the path such as "/products/3". */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeader(string $headerName): string
    {
        return (string) ($this->headers[strtolower($headerName)] ?? '');
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function hasInvalidJson(): bool
    {
        return $this->hasInvalidJson;
    }

    public function setAttribute(string $attributeName, mixed $attributeValue): void
    {
        $this->attributes[$attributeName] = $attributeValue;
    }

    public function getAttribute(string $attributeName, mixed $defaultValue = null): mixed
    {
        return $this->attributes[$attributeName] ?? $defaultValue;
    }

    /** Short description for the error log, e.g. "POST index.php?action=login_submit". */
    public function describe(): string
    {
        return $this->httpMethod . ' ' . ($this->isApi ? 'api.php' . $this->path : 'index.php?action=' . $this->path);
    }
}
