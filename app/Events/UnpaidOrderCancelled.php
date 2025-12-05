<?php

namespace App\Events;

use App\Models\Order;
use App\Models\UnpaidOrderNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an unpaid order is cancelled
 */
class UnpaidOrderCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Order $order The order that was cancelled
     * @param float $hoursUnpaid Number of hours the order was unpaid
     * @param array $magentoResponse Response from Magento cancellation API
     * @param array $notificationResponse Response from the notification endpoint
     * @param UnpaidOrderNotification|null $notification The notification record (if created)
     */
    public function __construct(
        public Order $order,
        public float $hoursUnpaid,
        public array $magentoResponse,
        public array $notificationResponse,
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
     * Check if the Magento cancellation was successful.
     */
    public function magentoCancellationSuccessful(): bool
    {
        return isset($this->magentoResponse['success'])
            && $this->magentoResponse['success'] === true;
    }

    /**
     * Check if the notification was sent successfully.
     */
    public function notificationSuccessful(): bool
    {
        return isset($this->notificationResponse['status_code'])
            && $this->notificationResponse['status_code'] >= 200
            && $this->notificationResponse['status_code'] < 300;
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
            'magento_order_id' => $this->order->magento_order_id,
            'hours_unpaid' => $this->hoursUnpaid,
            'customer_email' => $this->order->customer_email,
            'grand_total' => $this->order->grand_total,
            'magento_cancelled' => $this->magentoCancellationSuccessful(),
            'notification_sent' => $this->notificationSuccessful(),
        ];
    }
}
