<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class ApiLog extends Model
{
    protected $fillable = [
        'token_id',
        'token_name',
        'method',
        'endpoint',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'response_time_ms',
        'is_success',
        'error_code',
        'error_message',
        'resource_type',
        'resource_id',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'is_success' => 'boolean',
        'response_time_ms' => 'integer',
    ];

    /**
     * Get the tenant that this log belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the token that was used for this request.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    /**
     * Scope to filter by successful requests.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('is_success', true);
    }

    /**
     * Scope to filter by failed requests.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('is_success', false);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant(Builder $query, Tenant|int $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by token.
     */
    public function scopeForToken(Builder $query, int $tokenId): Builder
    {
        return $query->where('token_id', $tokenId);
    }

    /**
     * Scope to filter by endpoint.
     */
    public function scopeForEndpoint(Builder $query, string $endpoint): Builder
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeFromIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to filter by HTTP status code.
     */
    public function scopeWithStatus(Builder $query, int $status): Builder
    {
        return $query->where('response_status', $status);
    }

    /**
     * Scope to filter by error code.
     */
    public function scopeWithErrorCode(Builder $query, string $errorCode): Builder
    {
        return $query->where('error_code', $errorCode);
    }

    /**
     * Create a new log entry for an API request.
     */
    public static function logRequest(
        string $method,
        string $endpoint,
        int $responseStatus,
        bool $isSuccess,
        ?int $tenantId = null,
        ?int $tokenId = null,
        ?string $tokenName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $requestHeaders = null,
        ?array $requestBody = null,
        ?array $responseBody = null,
        ?int $responseTimeMs = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'token_id' => $tokenId,
            'token_name' => $tokenName,
            'method' => $method,
            'endpoint' => $endpoint,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'request_headers' => $requestHeaders,
            'request_body' => $requestBody,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'response_time_ms' => $responseTimeMs,
            'is_success' => $isSuccess,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }
}
