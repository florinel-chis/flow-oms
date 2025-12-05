<?php

namespace App\Contracts;

interface ExternalNotificationClientInterface
{
    /**
     * Send warning notification to external endpoint
     *
     * @param  string  $endpoint  The external endpoint URL
     * @param  array  $payload  The notification payload
     * @return array Response containing success, status_code, body, error, retry_count
     */
    public function sendWarningNotification(string $endpoint, array $payload): array;

    /**
     * Send cancellation notification to external endpoint
     *
     * @param  string  $endpoint  The external endpoint URL
     * @param  array  $payload  The notification payload
     * @return array Response containing success, status_code, body, error, retry_count
     */
    public function sendCancellationNotification(string $endpoint, array $payload): array;

    /**
     * Verify endpoint is reachable and secure
     *
     * @param  string  $endpoint  The endpoint URL to verify
     * @return bool
     */
    public function verifyEndpoint(string $endpoint): bool;
}
