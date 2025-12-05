<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Sla\SlaCalculatorService;

class OrderObserver
{
    public function __construct(private SlaCalculatorService $calculator)
    {
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Calculate SLA deadline if not already set
        if (!$order->sla_deadline && $order->ordered_at) {
            $deadline = $this->calculator->calculateDeadline($order);
            if ($deadline) {
                $order->updateQuietly(['sla_deadline' => $deadline]);
            }
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Mark as shipped when shipment is created
        if (!$order->shipped_at && $order->shipments()->exists()) {
            $firstShipment = $order->shipments()->oldest()->first();
            if ($firstShipment) {
                $order->updateQuietly(['shipped_at' => $firstShipment->created_at]);
            }
        }
    }
}
