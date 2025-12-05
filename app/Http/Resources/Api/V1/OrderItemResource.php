<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'parent_item_id' => $this->parent_item_id,
            'product_type' => $this->product_type,
            'quantity' => [
                'ordered' => (int) $this->qty_ordered,
                'invoiced' => (int) $this->qty_invoiced,
                'shipped' => (int) $this->qty_shipped,
                'canceled' => (int) $this->qty_canceled,
                'refunded' => (int) $this->qty_refunded,
            ],
            'pricing' => [
                'price' => (float) $this->price,
                'tax_amount' => (float) $this->tax_amount,
                'discount_amount' => (float) $this->discount_amount,
                'row_total' => (float) $this->row_total,
            ],
        ];
    }
}
