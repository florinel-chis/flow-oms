<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an order is successfully synced from Magento
 */
class OrderSynced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new OrderSynced event instance
     *
     * @param  Order  $order  The synchronized order
     * @param  bool  $isNewOrder  Whether this is a newly created order (true) or an update (false)
     */
    public function __construct(
        public Order $order,
        public bool $isNewOrder,
    ) {}

    /**
     * Get the order increment ID for logging
     */
    public function getIncrementId(): string
    {
        return $this->order->increment_id;
    }

    /**
     * Get context for logging
     */
    public function getContext(): array
    {
        return [
            'order_id' => $this->order->id,
            'increment_id' => $this->order->increment_id,
            'magento_order_id' => $this->order->magento_order_id,
            'is_new_order' => $this->isNewOrder,
            'status' => $this->order->status,
            'grand_total' => $this->order->grand_total,
        ];
    }
}
