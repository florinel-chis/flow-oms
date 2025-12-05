<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\UnpaidOrderNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Core service for processing unpaid orders
 *
 * Handles finding eligible orders for warnings and cancellations,
 * building notification payloads, and calculating unpaid hours.
 */
class UnpaidOrderProcessor
{
    /**
     * Status values that indicate an order should not be processed
     */
    private const EXCLUDED_STATUSES = ['canceled', 'complete', 'closed'];

    /**
     * Payment status values that qualify an order as unpaid
     */
    private const UNPAID_PAYMENT_STATUSES = ['pending', 'failed'];

    /**
     * Find orders that need a warning notification.
     *
     * Criteria:
     * - Payment status is pending or failed
     * - Order status is not canceled, complete, or closed
     * - Order age exceeds warning threshold
     * - No warning notification has been sent yet
     *
     * @param Tenant $tenant The tenant to check orders for
     * @return Collection<Order> Collection of orders needing warnings
     */
    public function findOrdersNeedingWarning(Tenant $tenant): Collection
    {
        $config = $this->getConfig($tenant);

        if (!$config['enabled']) {
            Log::debug('Unpaid order processing disabled for tenant', [
                'tenant_id' => $tenant->id,
            ]);
            return new Collection();
        }

        $warningThreshold = $config['warning_threshold_hours'];
        $cutoffTime = now()->subHours($warningThreshold);

        // Get order IDs that already have warning notifications
        $ordersWithWarnings = UnpaidOrderNotification::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('notification_type', NotificationType::WARNING)
            ->pluck('order_id')
            ->toArray();

        return Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereIn('payment_status', self::UNPAID_PAYMENT_STATUSES)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->where('ordered_at', '<=', $cutoffTime)
            ->whereNotIn('id', $ordersWithWarnings)
            ->with(['items', 'tenant'])
            ->get();
    }

    /**
     * Find orders that need to be cancelled.
     *
     * Criteria:
     * - Payment status is pending or failed
     * - Order status is not canceled, complete, or closed
     * - Order age exceeds cancellation threshold
     * - Warning has been sent (unless warning is disabled)
     * - No cancellation notification has been sent yet
     *
     * @param Tenant $tenant The tenant to check orders for
     * @return Collection<Order> Collection of orders needing cancellation
     */
    public function findOrdersNeedingCancellation(Tenant $tenant): Collection
    {
        $config = $this->getConfig($tenant);

        if (!$config['enabled']) {
            Log::debug('Unpaid order processing disabled for tenant', [
                'tenant_id' => $tenant->id,
            ]);
            return new Collection();
        }

        $cancellationThreshold = $config['cancellation_threshold_hours'];
        $cutoffTime = now()->subHours($cancellationThreshold);

        // Get order IDs that already have cancellation notifications
        $ordersWithCancellations = UnpaidOrderNotification::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('notification_type', NotificationType::CANCELLATION)
            ->pluck('order_id')
            ->toArray();

        // Get order IDs that have received warnings (required before cancellation)
        $ordersWithWarnings = UnpaidOrderNotification::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('notification_type', NotificationType::WARNING)
            ->where('sent_successfully', true)
            ->pluck('order_id')
            ->toArray();

        return Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereIn('payment_status', self::UNPAID_PAYMENT_STATUSES)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->where('ordered_at', '<=', $cutoffTime)
            ->whereIn('id', $ordersWithWarnings) // Must have received warning
            ->whereNotIn('id', $ordersWithCancellations)
            ->with(['items', 'tenant', 'magentoStore'])
            ->get();
    }

    /**
     * Build the warning notification payload.
     *
     * @param Order $order The order to build payload for
     * @return array The complete payload for external notification
     */
    public function buildWarningPayload(Order $order): array
    {
        $hoursUnpaid = $this->calculateHoursUnpaid($order);
        $config = $this->getConfig($order->tenant);

        $hoursRemaining = $config['cancellation_threshold_hours'] - $hoursUnpaid;

        return [
            'event_type' => NotificationType::WARNING->eventType(),
            'timestamp' => now()->toIso8601String(),
            'tenant' => [
                'id' => $order->tenant_id,
                'name' => $order->tenant->name,
            ],
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'magento_order_id' => $order->magento_order_id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'grand_total' => (float) $order->grand_total,
                'currency_code' => $order->currency_code ?? 'USD',
                'ordered_at' => $order->ordered_at?->toIso8601String(),
                'hours_unpaid' => round($hoursUnpaid, 2),
            ],
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
            ],
            'items' => $this->buildItemsPayload($order),
            'warning' => [
                'threshold_hours' => $config['warning_threshold_hours'],
                'cancellation_hours' => $config['cancellation_threshold_hours'],
                'hours_remaining' => round(max(0, $hoursRemaining), 2),
                'message' => sprintf(
                    'This order will be automatically cancelled if payment is not received within %.0f hours.',
                    max(0, $hoursRemaining)
                ),
            ],
        ];
    }

    /**
     * Build the cancellation notification payload.
     *
     * @param Order $order The order to build payload for
     * @return array The complete payload for external notification
     */
    public function buildCancellationPayload(Order $order): array
    {
        $hoursUnpaid = $this->calculateHoursUnpaid($order);
        $config = $this->getConfig($order->tenant);

        return [
            'event_type' => NotificationType::CANCELLATION->eventType(),
            'timestamp' => now()->toIso8601String(),
            'tenant' => [
                'id' => $order->tenant_id,
                'name' => $order->tenant->name,
            ],
            'order' => [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'magento_order_id' => $order->magento_order_id,
                'status' => 'canceled',
                'payment_status' => 'failed',
                'grand_total' => (float) $order->grand_total,
                'currency_code' => $order->currency_code ?? 'USD',
                'ordered_at' => $order->ordered_at?->toIso8601String(),
                'cancelled_at' => now()->toIso8601String(),
                'hours_unpaid' => round($hoursUnpaid, 2),
            ],
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
            ],
            'items' => $this->buildItemsPayload($order),
            'cancellation' => [
                'reason' => 'automatic_unpaid_timeout',
                'threshold_hours' => $config['cancellation_threshold_hours'],
                'message' => sprintf(
                    'Order automatically cancelled due to non-payment after %d hours.',
                    $config['cancellation_threshold_hours']
                ),
            ],
        ];
    }

    /**
     * Calculate the number of hours an order has been unpaid.
     *
     * @param Order $order The order to calculate for
     * @return float Number of hours since order was placed
     */
    public function calculateHoursUnpaid(Order $order): float
    {
        if (!$order->ordered_at) {
            return 0.0;
        }

        return (float) now()->diffInMinutes($order->ordered_at) / 60;
    }

    /**
     * Get the unpaid order configuration for a tenant.
     *
     * @param Tenant $tenant The tenant to get config for
     * @return array Configuration values with defaults
     */
    public function getConfig(Tenant $tenant): array
    {
        return [
            'enabled' => Setting::get('unpaid_orders', 'enabled', false, $tenant),
            'warning_threshold_hours' => Setting::get('unpaid_orders', 'warning_threshold_hours', 24, $tenant),
            'cancellation_threshold_hours' => Setting::get('unpaid_orders', 'cancellation_threshold_hours', 72, $tenant),
            'warning_endpoint_url' => Setting::get('unpaid_orders', 'warning_endpoint_url', null, $tenant),
            'cancellation_endpoint_url' => Setting::get('unpaid_orders', 'cancellation_endpoint_url', null, $tenant),
            'max_retries' => Setting::get('unpaid_orders', 'max_retries', 3, $tenant),
            'retry_delay_minutes' => Setting::get('unpaid_orders', 'retry_delay_minutes', 30, $tenant),
        ];
    }

    /**
     * Check if the unpaid order automation is enabled for a tenant.
     *
     * @param Tenant $tenant The tenant to check
     * @return bool Whether the feature is enabled
     */
    public function isEnabled(Tenant $tenant): bool
    {
        return (bool) Setting::get('unpaid_orders', 'enabled', false, $tenant);
    }

    /**
     * Check if the configuration is valid for a tenant.
     *
     * @param Tenant $tenant The tenant to validate
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public function validateConfig(Tenant $tenant): array
    {
        $config = $this->getConfig($tenant);
        $errors = [];

        if (!$config['enabled']) {
            return ['valid' => true, 'errors' => []]; // Not enabled, no validation needed
        }

        if ($config['warning_threshold_hours'] <= 0) {
            $errors[] = 'Warning threshold must be greater than 0 hours.';
        }

        if ($config['cancellation_threshold_hours'] <= 0) {
            $errors[] = 'Cancellation threshold must be greater than 0 hours.';
        }

        if ($config['cancellation_threshold_hours'] <= $config['warning_threshold_hours']) {
            $errors[] = 'Cancellation threshold must be greater than warning threshold.';
        }

        if (empty($config['warning_endpoint_url'])) {
            $errors[] = 'Warning endpoint URL is required.';
        } elseif (!filter_var($config['warning_endpoint_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Warning endpoint URL is not a valid URL.';
        }

        if (empty($config['cancellation_endpoint_url'])) {
            $errors[] = 'Cancellation endpoint URL is required.';
        } elseif (!filter_var($config['cancellation_endpoint_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Cancellation endpoint URL is not a valid URL.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Build the items array for the notification payload.
     *
     * @param Order $order The order to extract items from
     * @return array Array of item data
     */
    private function buildItemsPayload(Order $order): array
    {
        // Ensure items are loaded
        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        return $order->items
            ->filter(fn ($item) => !$item->is_child) // Exclude child items (simple products of configurables)
            ->map(fn ($item) => [
                'sku' => $item->sku,
                'name' => $item->name,
                'qty_ordered' => (float) $item->qty_ordered,
                'price' => (float) $item->price,
                'row_total' => (float) $item->row_total,
            ])
            ->values()
            ->toArray();
    }
}
