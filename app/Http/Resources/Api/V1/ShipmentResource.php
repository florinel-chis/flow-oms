<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shipment
 */
class ShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'magento_shipment_id' => $this->magento_shipment_id,
            'tracking_number' => $this->tracking_number,
            'carrier_code' => $this->carrier_code,
            'carrier_title' => $this->carrier_title,
            'status' => $this->status,
            'delivery' => [
                'estimated_at' => $this->estimated_delivery_at?->toIso8601String(),
                'actual_at' => $this->actual_delivery_at?->toIso8601String(),
                'signature' => $this->delivery_signature,
                'notes' => $this->delivery_notes,
                'photo_url' => $this->delivery_photo_url,
            ],
            'dates' => [
                'shipped_at' => $this->shipped_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
        ];
    }
}
