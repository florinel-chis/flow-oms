<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
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
            'magento_invoice_id' => $this->magento_invoice_id,
            'state' => $this->state->value ?? $this->state,
            
            'order' => [
                'id' => $this->order->id,
                'increment_id' => $this->order->increment_id,
            ],
            
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
                'base_grand_total' => (float) $this->base_grand_total,
                'base_subtotal' => (float) $this->base_subtotal,
            ],
            
            'dates' => [
                'invoiced_at' => $this->invoiced_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            
            'store' => [
                'id' => $this->magentoStore->id,
                'name' => $this->magentoStore->name,
            ],
            
            // Include items when loaded
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
