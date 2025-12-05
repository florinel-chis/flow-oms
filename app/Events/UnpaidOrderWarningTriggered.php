<?php

namespace App\Events;

use App\Models\Order;
use App\Models\UnpaidOrderNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a warning notification is sent for an unpaid order
 */
class UnpaidOrderWarningTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Order $order The order that received the warning
     * @param float $hoursUnpaid Number of hours the order has been unpaid
     * @param array $response The response from the notification endpoint
     * @param UnpaidOrderNotification|null $notification The notification record (if created)
     */
    public function __construct(
        public Order $order,
        public float $hoursUnpaid,
        public array $response,
        public ?UnpaidOrderNotification $notification = null,
    ) {}

    /**
     * Get the order increment ID for logging.
     */
    public function getIncrementId(): string
    {
        return $this->order->increment_id;
    }

    /**
     * Check if the notification was sent successfully.
     */
    public function wasSuccessful(): bool
    {
        return isset($this->response['status_code'])
            && $this->response['status_code'] >= 200
            && $this->response['status_code'] < 300;
    }

    /**
     * Get context for logging.
     */
    public function getContext(): array
    {
        return [
            'order_id' => $this->order->id,
            'increment_id' => $this->order->increment_id,
            'tenant_id' => $this->order->tenant_id,
            'hours_unpaid' => $this->hoursUnpaid,
            'customer_email' => $this->order->customer_email,
            'grand_total' => $this->order->grand_total,
            'response_status' => $this->response['status_code'] ?? null,
            'success' => $this->wasSuccessful(),
        ];
    }
}
