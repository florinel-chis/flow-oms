<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks notifications sent for unpaid orders (warnings and cancellations)
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property NotificationType $notification_type
 * @property \Carbon\Carbon $triggered_at
 * @property float $hours_unpaid
 * @property string $endpoint_url
 * @property array $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property bool $sent_successfully
 * @property int $retry_count
 * @property \Carbon\Carbon|null $last_retry_at
 * @property string|null $error_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Order $order
 */
class UnpaidOrderNotification extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'notification_type',
        'triggered_at',
        'hours_unpaid',
        'endpoint_url',
        'payload',
        'response_status',
        'response_body',
        'sent_successfully',
        'retry_count',
        'last_retry_at',
        'error_message',
    ];

    protected $casts = [
        'notification_type' => NotificationType::class,
        'triggered_at' => 'datetime',
        'hours_unpaid' => 'decimal:2',
        'payload' => 'array',
        'sent_successfully' => 'boolean',
        'retry_count' => 'integer',
        'last_retry_at' => 'datetime',
    ];

    // Relationships

    /**
     * Get the order associated with this notification.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes

    /**
     * Scope to filter warning notifications only.
     */
    public function scopeWarnings(Builder $query): Builder
    {
        return $query->where('notification_type', NotificationType::WARNING);
    }

    /**
     * Scope to filter cancellation notifications only.
     */
    public function scopeCancellations(Builder $query): Builder
    {
        return $query->where('notification_type', NotificationType::CANCELLATION);
    }

    /**
     * Scope to filter successfully sent notifications.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('sent_successfully', true);
    }

    /**
     * Scope to filter failed notifications.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('sent_successfully', false);
    }

    /**
     * Scope to filter notifications that can be retried.
     */
    public function scopeRetryable(Builder $query, int $maxRetries = 3): Builder
    {
        return $query->where('sent_successfully', false)
            ->where('retry_count', '<', $maxRetries);
    }

    /**
     * Scope to filter notifications triggered within a date range.
     */
    public function scopeTriggeredBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('triggered_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter notifications triggered today.
     */
    public function scopeTriggeredToday(Builder $query): Builder
    {
        return $query->whereDate('triggered_at', today());
    }

    // Accessors

    /**
     * Check if this notification has failed.
     */
    public function getHasFailedAttribute(): bool
    {
        return !$this->sent_successfully;
    }

    /**
     * Check if this notification can be retried.
     */
    public function getCanRetryAttribute(): bool
    {
        return !$this->sent_successfully && $this->retry_count < 3;
    }

    /**
     * Check if this is a warning notification.
     */
    public function getIsWarningAttribute(): bool
    {
        return $this->notification_type === NotificationType::WARNING;
    }

    /**
     * Check if this is a cancellation notification.
     */
    public function getIsCancellationAttribute(): bool
    {
        return $this->notification_type === NotificationType::CANCELLATION;
    }

    // Methods

    /**
     * Record a successful notification send.
     */
    public function markAsSent(int $responseStatus, ?string $responseBody = null): void
    {
        $this->update([
            'sent_successfully' => true,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Record a failed notification attempt.
     */
    public function markAsFailed(string $errorMessage, ?int $responseStatus = null, ?string $responseBody = null): void
    {
        $this->update([
            'sent_successfully' => false,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Check if a notification of this type already exists for the order.
     */
    public static function existsForOrder(int $orderId, NotificationType $type, ?int $tenantId = null): bool
    {
        $query = static::withoutGlobalScope('tenant')
            ->where('order_id', $orderId)
            ->where('notification_type', $type);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->exists();
    }
}
