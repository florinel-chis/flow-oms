<?php

namespace App\Jobs;

use App\Contracts\ExternalNotificationClientInterface;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendUnpaidOrderWarningJob implements ShouldQueue
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
        public float $hoursRemaining,
        public int $warningThreshold,
        public int $cancellationThreshold,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExternalNotificationClientInterface $client): void
    {
        Log::info('Processing unpaid order warning', [
            'order_id' => $this->order->id,
            'increment_id' => $this->order->increment_id,
            'hours_unpaid' => $this->hoursUnpaid,
        ]);

        // Build the warning payload
        $payload = $this->buildWarningPayload();

        // Send the notification
        $response = $client->sendWarningNotification($this->endpoint, $payload);

        // Record the notification in database
        DB::table('unpaid_order_notifications')->insert([
            'tenant_id' => $this->order->tenant_id,
            'order_id' => $this->order->id,
            'notification_type' => 'warning',
            'triggered_at' => now(),
            'hours_unpaid' => $this->hoursUnpaid,
            'endpoint_url' => $this->endpoint,
            'payload' => json_encode($payload),
            'response_status' => $response['status_code'],
            'response_body' => $response['body'],
            'sent_successfully' => $response['success'],
            'retry_count' => $response['retry_count'],
            'last_retry_at' => $response['retry_count'] > 0 ? now() : null,
            'error_message' => $response['error'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$response['success']) {
            Log::error('Failed to send unpaid order warning', [
                'order_id' => $this->order->id,
                'error' => $response['error'],
                'retry_count' => $response['retry_count'],
            ]);

            throw new \RuntimeException(
                "Failed to send warning notification: {$response['error']}"
            );
        }

        Log::info('Unpaid order warning sent successfully', [
            'order_id' => $this->order->id,
            'status_code' => $response['status_code'],
            'retry_count' => $response['retry_count'],
        ]);
    }

    /**
     * Build the warning notification payload
     */
    private function buildWarningPayload(): array
    {
        // Load relationships
        $this->order->load(['items', 'magentoStore.tenant']);

        return [
            'event_type' => 'order_cancellation_warning',
            'timestamp' => now()->toIso8601String(),
            'tenant' => [
                'id' => $this->order->tenant_id,
                'name' => $this->order->magentoStore->tenant->name ?? 'Unknown',
            ],
            'order' => [
                'id' => $this->order->id,
                'increment_id' => $this->order->increment_id,
                'magento_order_id' => $this->order->magento_order_id,
                'status' => $this->order->status,
                'payment_status' => $this->order->payment_status,
                'grand_total' => (float) $this->order->grand_total,
                'currency_code' => $this->order->currency_code,
                'ordered_at' => $this->order->ordered_at?->toIso8601String(),
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
            'warning' => [
                'threshold_hours' => $this->warningThreshold,
                'cancellation_hours' => $this->cancellationThreshold,
                'hours_remaining' => $this->hoursRemaining,
                'message' => sprintf(
                    'This order will be automatically cancelled if payment is not received within %.1f hours.',
                    $this->hoursRemaining
                ),
            ],
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendUnpaidOrderWarningJob failed permanently', [
            'order_id' => $this->order->id,
            'increment_id' => $this->order->increment_id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
