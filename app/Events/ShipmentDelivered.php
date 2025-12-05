<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public string $source = 'api',
    ) {}

    /**
     * Get context for logging.
     */
    public function getContext(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'tracking_number' => $this->shipment->tracking_number,
            'carrier_code' => $this->shipment->carrier_code,
            'delivered_at' => $this->shipment->actual_delivery_at?->toIso8601String(),
            'source' => $this->source,
        ];
    }
}
