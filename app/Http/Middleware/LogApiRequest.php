<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * Sensitive headers that should not be logged.
     */
    protected array $sensitiveHeaders = [
        'authorization',
        'cookie',
        'x-xsrf-token',
        'x-csrf-token',
    ];

    /**
     * Sensitive request body fields that should be masked.
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credential',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    /**
     * Log the API request and response.
     */
    protected function logRequest(Request $request, Response $response, float $startTime): void
    {
        try {
            $token = $this->getTokenFromRequest($request);
            $tenantId = $this->getTenantId($request, $token);
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $responseBody = $this->parseResponseBody($response);
            $isSuccess = $response->isSuccessful();
            $errorCode = null;
            $errorMessage = null;

            if (! $isSuccess && is_array($responseBody)) {
                $errorCode = $responseBody['error']['code'] ?? null;
                $errorMessage = $responseBody['error']['message'] ?? null;
            }

            ApiLog::logRequest(
                method: $request->method(),
                endpoint: $request->path(),
                responseStatus: $response->getStatusCode(),
                isSuccess: $isSuccess,
                tenantId: $tenantId,
                tokenId: $token?->id,
                tokenName: $token?->name,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                requestHeaders: $this->filterHeaders($request->headers->all()),
                requestBody: $this->filterSensitiveData($request->all()),
                responseBody: $responseBody,
                responseTimeMs: $responseTimeMs,
                errorCode: $errorCode,
                errorMessage: $errorMessage,
                resourceType: $this->extractResourceType($request),
                resourceId: $this->extractResourceId($request),
            );
        } catch (\Throwable $e) {
            // Log the error but don't break the request
            \Log::error('Failed to log API request', [
                'error' => $e->getMessage(),
                'endpoint' => $request->path(),
            ]);
        }
    }

    /**
     * Get the token from the request.
     */
    protected function getTokenFromRequest(Request $request): ?PersonalAccessToken
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $user->currentAccessToken();
    }

    /**
     * Get the tenant ID from the request or token.
     */
    protected function getTenantId(Request $request, ?PersonalAccessToken $token): ?int
    {
        // First check if the token has a tenant_id
        if ($token && isset($token->tenant_id)) {
            return $token->tenant_id;
        }

        // Try to get from route parameter
        if ($request->route('tenant')) {
            return $request->route('tenant')->id ?? $request->route('tenant');
        }

        return null;
    }

    /**
     * Filter out sensitive headers.
     */
    protected function filterHeaders(array $headers): array
    {
        return array_filter(
            $headers,
            fn (string $key) => ! in_array(strtolower($key), $this->sensitiveHeaders),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Filter sensitive data from request body.
     */
    protected function filterSensitiveData(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $filtered[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Check if a field is sensitive.
     */
    protected function isSensitiveField(string $key): bool
    {
        $lowercaseKey = strtolower($key);

        foreach ($this->sensitiveFields as $sensitiveField) {
            if (str_contains($lowercaseKey, $sensitiveField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse the response body.
     */
    protected function parseResponseBody(Response $response): ?array
    {
        $content = $response->getContent();

        if (empty($content)) {
            return null;
        }

        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Extract the resource type from the request.
     */
    protected function extractResourceType(Request $request): ?string
    {
        $path = $request->path();

        // Match common API patterns
        if (preg_match('#api/v\d+/(\w+)#', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract the resource ID from the request.
     */
    protected function extractResourceId(Request $request): ?string
    {
        // Check for tracking_number in route
        if ($trackingNumber = $request->route('tracking_number')) {
            return $trackingNumber;
        }

        // Check for id in route
        if ($id = $request->route('id')) {
            return (string) $id;
        }

        return null;
    }
}
