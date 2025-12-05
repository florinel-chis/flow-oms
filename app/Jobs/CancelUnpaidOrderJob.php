<?php

namespace App\Jobs;

use App\Contracts\ExternalNotificationClientInterface;
use App\Contracts\Magento\MagentoApiClientInterface;
use App\Events\UnpaidOrderCancelled;
use App\Models\MagentoStore;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying
     */
    public int $backoff = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $endpoint,
        public float $hoursUnpaid,
        public int $cancellationThreshold,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExternalNotificationClientInterface $notificationClient): void
    {
        Log::info('Processing unpaid order cancellation', [
            'order_id' => $this->order->id,
            'increment_id' => $this->order->increment_id,
            'magento_order_id' => $this->order->magento_order_id,
            'hours_unpaid' => $this->hoursUnpaid,
        ]);

        // Use transaction to ensure atomic operations
        DB::transaction(function () use ($notificationClient) {
            // Step 1: Cancel order in Magento
            $magentoResponse = $this->cancelOrderInMagento();

            // Step 2: Update local order status
            $this->order->update([
                'status' => 'canceled',
                'payment_status' => 'failed',
            ]);

            Log::info('Order status updated to canceled', [
                'order_id' => $this->order->id,
                'increment_id' => $this->order->increment_id,
            ]);

            // Step 3: Build and send cancellation notification
            $payload = $this->buildCancellationPayload();
            $notificationResponse = $notificationClient->sendCancellationNotification($this->endpoint, $payload);

            // Step 4: Record the notification in database
            DB::table('unpaid_order_notifications')->insert([
                'tenant_id' => $this->order->tenant_id,
                'order_id' => $this->order->id,
                'notification_type' => 'cancellation',
                'triggered_at' => now(),
                'hours_unpaid' => $this->hoursUnpaid,
                'endpoint_url' => $this->endpoint,
                'payload' => json_encode($payload),
                'response_status' => $notificationResponse['status_code'],
                'response_body' => $notificationResponse['body'],
                'sent_successfully' => $notificationResponse['success'],
                'retry_count' => $notificationResponse['retry_count'],
                'last_retry_at' => $notificationResponse['retry_count'] > 0 ? now() : null,
                'error_message' => $notificationResponse['error'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Step 5: Fire event
            event(new UnpaidOrderCancelled(
                $this->order,
                $this->hoursUnpaid,
                $magentoResponse,
                $notificationResponse
            ));

            if (!$notificationResponse['success']) {
                Log::warning('Order cancelled in Magento but notification failed', [
                    'order_id' => $this->order->id,
                    'error' => $notificationResponse['error'],
                ]);
            }

            Log::info('Unpaid order cancellation completed', [
                'order_id' => $this->order->id,
                'magento_cancelled' => true,
                'notification_sent' => $notificationResponse['success'],
            ]);
        });
    }

    /**
     * Cancel the order in Magento via REST API
     */
    private function cancelOrderInMagento(): array
    {
        try {
            // Get the Magento store for this order
            $store = MagentoStore::find($this->order->magento_store_id);

            if (!$store) {
                throw new \RuntimeException("Magento store not found for order {$this->order->id}");
            }

            // Create Magento API client
            $magentoClient = app(MagentoApiClientInterface::class, ['store' => $store]);

            // Cancel the order
            $response = $magentoClient->cancelOrder($this->order->magento_order_id);

            Log::info('Order successfully cancelled in Magento', [
                'order_id' => $this->order->id,
                'magento_order_id' => $this->order->magento_order_id,
                'response' => $response,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to cancel order in Magento', [
                'order_id' => $this->order->id,
                'magento_order_id' => $this->order->magento_order_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build the cancellation notification payload
     */
    private function buildCancellationPayload(): array
    {
        // Load relationships
        $this->order->load(['items', 'magentoStore.tenant']);

        return [
            'event_type' => 'order_cancelled',
            'timestamp' => now()->toIso8601String(),
            'tenant' => [
                'id' => $this->order->tenant_id,
                'name' => $this->order->magentoStore->tenant->name ?? 'Unknown',
            ],
            'order' => [
                'id' => $this->order->id,
                'increment_id' => $this->order->increment_id,
                'magento_order_id' => $this->order->magento_order_id,
                'status' => 'canceled',
                'payment_status' => 'failed',
                'grand_total' => (float) $this->order->grand_total,
                'currency_code' => $this->order->currency_code,
                'ordered_at' => $this->order->ordered_at?->toIso8601String(),
                'cancelled_at' => now()->toIso8601String(),
                'hours_unpaid' => $this->hoursUnpaid,
            ],
            'customer' => [
                'name' => $this->order->customer_name,
                'email' => $this->order->customer_email,
            ],
            'items' => $this->order->items->map(fn ($item) => [
                'sku' => $item->sku,
                'name' => $item->name,
                'qty_ordered' => (float) $item->qty_ordered,
                'price' => (float) $item->price,
                'row_total' => (float) $item->row_total,
            ])->toArray(),
            'cancellation' => [
                'reason' => 'automatic_unpaid_timeout',
                'threshold_hours' => $this->cancellationThreshold,
                'message' => sprintf(
                    'Order automatically cancelled due to non-payment after %d hours.',
                    $this->cancellationThreshold
                ),
            ],
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CancelUnpaidOrderJob failed permanently', [
            'order_id' => $this->order->id,
            'increment_id' => $this->order->increment_id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // If Magento cancellation failed, we should NOT mark the order as cancelled locally
        // This prevents data inconsistency between Magento and FlowOMS
    }
}
