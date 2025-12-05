<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
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
            'increment_id' => $this->increment_id,
            'magento_order_id' => $this->magento_order_id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'priority' => $this->priority,
            'source' => $this->source,
            
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
            ],
            
            'amounts' => [
                'grand_total' => (float) $this->grand_total,
                'subtotal' => (float) $this->subtotal,
                'tax_amount' => (float) $this->tax_amount,
                'shipping_amount' => (float) $this->shipping_amount,
                'discount_amount' => (float) $this->discount_amount,
                'currency_code' => $this->currency_code,
            ],
            
            'payment_method' => $this->payment_method,
            'shipping_method' => $this->shipping_method,
            
            'sla' => [
                'deadline' => $this->sla_deadline?->toIso8601String(),
                'breached' => (bool) $this->sla_breached,
            ],
            
            'dates' => [
                'ordered_at' => $this->ordered_at?->toIso8601String(),
                'shipped_at' => $this->shipped_at?->toIso8601String(),
                'synced_at' => $this->synced_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            
            'store' => [
                'id' => $this->magentoStore->id,
                'name' => $this->magentoStore->name,
            ],
            
            // Include relationships when loaded
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipments' => ShipmentResource::collection($this->whenLoaded('shipments')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
        ];
    }
}
