<?php

namespace App\Services;

use App\Contracts\ExternalNotificationClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalNotificationClient implements ExternalNotificationClientInterface
{
    /**
     * HTTP timeout in seconds
     */
    private const HTTP_TIMEOUT_SECONDS = 30;

    /**
     * Maximum number of retry attempts
     */
    private const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Base delay for exponential backoff (in milliseconds)
     */
    private const BASE_RETRY_DELAY_MS = 1000;

    /**
     * Exponential backoff multiplier
     */
    private const BACKOFF_MULTIPLIER = 2;

    /**
     * Circuit breaker threshold (number of consecutive failures)
     */
    private const CIRCUIT_BREAKER_THRESHOLD = 5;

    /**
     * Circuit breaker timeout (in seconds)
     */
    private const CIRCUIT_BREAKER_TIMEOUT = 300; // 5 minutes

    /**
     * Send warning notification to external endpoint
     *
     * @param  string  $endpoint  The external endpoint URL
     * @param  array  $payload  The notification payload
     * @return array Response containing success, status_code, body, error, retry_count
     */
    public function sendWarningNotification(string $endpoint, array $payload): array
    {
        Log::info('Sending warning notification to external endpoint', [
            'endpoint' => $endpoint,
            'order_id' => $payload['order']['id'] ?? null,
            'increment_id' => $payload['order']['increment_id'] ?? null,
        ]);

        return $this->sendNotification($endpoint, $payload, 'warning');
    }

    /**
     * Send cancellation notification to external endpoint
     *
     * @param  string  $endpoint  The external endpoint URL
     * @param  array  $payload  The notification payload
     * @return array Response containing success, status_code, body, error, retry_count
     */
    public function sendCancellationNotification(string $endpoint, array $payload): array
    {
        Log::info('Sending cancellation notification to external endpoint', [
            'endpoint' => $endpoint,
            'order_id' => $payload['order']['id'] ?? null,
            'increment_id' => $payload['order']['increment_id'] ?? null,
        ]);

        return $this->sendNotification($endpoint, $payload, 'cancellation');
    }

    /**
     * Verify endpoint is reachable and secure
     *
     * @param  string  $endpoint  The endpoint URL to verify
     * @return bool
     */
    public function verifyEndpoint(string $endpoint): bool
    {
        // Validate HTTPS for production
        if (app()->environment('production') && ! str_starts_with($endpoint, 'https://')) {
            Log::warning('Endpoint must use HTTPS in production', [
                'endpoint' => $endpoint,
            ]);

            return false;
        }

        // Validate URL format
        if (! filter_var($endpoint, FILTER_VALIDATE_URL)) {
            Log::warning('Invalid endpoint URL format', [
                'endpoint' => $endpoint,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send notification to external endpoint with retry logic
     *
     * @param  string  $endpoint  The external endpoint URL
     * @param  array  $payload  The notification payload
     * @param  string  $type  Notification type (warning|cancellation)
     * @return array Response containing success, status_code, body, error, retry_count
     */
    private function sendNotification(string $endpoint, array $payload, string $type): array
    {
        // Validate endpoint
        if (! $this->verifyEndpoint($endpoint)) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'Invalid or insecure endpoint URL',
                'retry_count' => 0,
            ];
        }

        // Check circuit breaker
        if ($this->isCircuitBreakerOpen($endpoint)) {
            Log::warning('Circuit breaker open for endpoint', [
                'endpoint' => $endpoint,
                'type' => $type,
            ]);

            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'Circuit breaker open - endpoint temporarily disabled',
                'retry_count' => 0,
            ];
        }

        $retryCount = 0;
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRY_ATTEMPTS; $attempt++) {
            try {
                // Calculate exponential backoff delay
                if ($attempt > 1) {
                    $delay = $this->calculateBackoffDelay($attempt - 1);
                    usleep($delay * 1000); // Convert to microseconds
                    $retryCount++;

                    Log::info('Retrying notification send', [
                        'endpoint' => $endpoint,
                        'type' => $type,
                        'attempt' => $attempt,
                        'delay_ms' => $delay,
                    ]);
                }

                $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'MagentoOMS/1.0',
                        'X-Notification-Type' => $type,
                        'X-Event-Type' => $payload['event_type'] ?? 'unknown',
                    ])
                    ->post($endpoint, $payload);

                // Check if successful (2xx status codes)
                if ($response->successful()) {
                    $this->recordSuccess($endpoint);

                    Log::info('Notification sent successfully', [
                        'endpoint' => $endpoint,
                        'type' => $type,
                        'status_code' => $response->status(),
                        'retry_count' => $retryCount,
                    ]);

                    return [
                        'success' => true,
                        'status_code' => $response->status(),
                        'body' => $this->sanitizeResponseBody($response->body()),
                        'error' => null,
                        'retry_count' => $retryCount,
                    ];
                }

                // Handle client errors (4xx) - don't retry
                if ($response->clientError()) {
                    $this->recordFailure($endpoint);

                    Log::error('Client error sending notification', [
                        'endpoint' => $endpoint,
                        'type' => $type,
                        'status_code' => $response->status(),
                        'response' => $this->sanitizeResponseBody($response->body()),
                    ]);

                    return [
                        'success' => false,
                        'status_code' => $response->status(),
                        'body' => $this->sanitizeResponseBody($response->body()),
                        'error' => 'Client error: '.$response->status(),
                        'retry_count' => $retryCount,
                    ];
                }

                // Handle server errors (5xx) - will retry
                if ($response->serverError()) {
                    $lastException = new \RuntimeException(
                        "Server error: {$response->status()}"
                    );

                    Log::warning('Server error sending notification, will retry', [
                        'endpoint' => $endpoint,
                        'type' => $type,
                        'status_code' => $response->status(),
                        'attempt' => $attempt,
                        'max_attempts' => self::MAX_RETRY_ATTEMPTS,
                    ]);

                    continue; // Retry on server errors
                }

            } catch (ConnectionException $e) {
                // Network/connection errors - will retry
                $lastException = $e;

                Log::warning('Connection error sending notification, will retry', [
                    'endpoint' => $endpoint,
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_attempts' => self::MAX_RETRY_ATTEMPTS,
                ]);

                continue;

            } catch (RequestException $e) {
                // Request errors - will retry
                $lastException = $e;

                Log::warning('Request error sending notification, will retry', [
                    'endpoint' => $endpoint,
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_attempts' => self::MAX_RETRY_ATTEMPTS,
                ]);

                continue;

            } catch (\Exception $e) {
                // Unexpected errors - will retry
                $lastException = $e;

                Log::error('Unexpected error sending notification, will retry', [
                    'endpoint' => $endpoint,
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_attempts' => self::MAX_RETRY_ATTEMPTS,
                ]);

                continue;
            }
        }

        // All retries exhausted
        $this->recordFailure($endpoint);

        Log::error('Failed to send notification after all retries', [
            'endpoint' => $endpoint,
            'type' => $type,
            'retry_count' => $retryCount,
            'error' => $lastException?->getMessage() ?? 'Unknown error',
        ]);

        return [
            'success' => false,
            'status_code' => null,
            'body' => null,
            'error' => $lastException?->getMessage() ?? 'Failed after all retries',
            'retry_count' => $retryCount,
        ];
    }

    /**
     * Calculate exponential backoff delay
     *
     * @param  int  $retryAttempt  Current retry attempt (0-indexed)
     * @return int Delay in milliseconds
     */
    private function calculateBackoffDelay(int $retryAttempt): int
    {
        // Exponential backoff: base_delay * (multiplier ^ retry_attempt)
        // Attempt 1: 1000ms (1s)
        // Attempt 2: 2000ms (2s)
        // Attempt 3: 4000ms (4s)
        return (int) (self::BASE_RETRY_DELAY_MS * pow(self::BACKOFF_MULTIPLIER, $retryAttempt));
    }

    /**
     * Sanitize response body for logging and storage
     *
     * @param  string  $body  Raw response body
     * @return string Sanitized response body
     */
    private function sanitizeResponseBody(string $body): string
    {
        // Limit response body length for storage
        $maxLength = 1000;

        if (strlen($body) > $maxLength) {
            return substr($body, 0, $maxLength).'... (truncated)';
        }

        return $body;
    }

    /**
     * Check if circuit breaker is open for an endpoint
     *
     * @param  string  $endpoint  The endpoint URL
     * @return bool True if circuit breaker is open
     */
    private function isCircuitBreakerOpen(string $endpoint): bool
    {
        $cacheKey = $this->getCircuitBreakerKey($endpoint);
        $failures = cache()->get($cacheKey, 0);

        return $failures >= self::CIRCUIT_BREAKER_THRESHOLD;
    }

    /**
     * Record successful request (reset circuit breaker)
     *
     * @param  string  $endpoint  The endpoint URL
     */
    private function recordSuccess(string $endpoint): void
    {
        $cacheKey = $this->getCircuitBreakerKey($endpoint);
        cache()->forget($cacheKey);
    }

    /**
     * Record failed request (increment circuit breaker counter)
     *
     * @param  string  $endpoint  The endpoint URL
     */
    private function recordFailure(string $endpoint): void
    {
        $cacheKey = $this->getCircuitBreakerKey($endpoint);
        $failures = cache()->get($cacheKey, 0);
        cache()->put($cacheKey, $failures + 1, self::CIRCUIT_BREAKER_TIMEOUT);

        if ($failures + 1 >= self::CIRCUIT_BREAKER_THRESHOLD) {
            Log::alert('Circuit breaker opened for endpoint', [
                'endpoint' => $endpoint,
                'failures' => $failures + 1,
                'timeout_seconds' => self::CIRCUIT_BREAKER_TIMEOUT,
            ]);
        }
    }

    /**
     * Get circuit breaker cache key for an endpoint
     *
     * @param  string  $endpoint  The endpoint URL
     * @return string Cache key
     */
    private function getCircuitBreakerKey(string $endpoint): string
    {
        return 'circuit_breaker:'.md5($endpoint);
    }
}
