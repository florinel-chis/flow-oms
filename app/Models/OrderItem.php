<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'order_id',
        'magento_item_id',
        'product_id',
        'product_type',
        'parent_item_id',
        'sku',
        'name',
        'qty_ordered',
        'qty_shipped',
        'qty_canceled',
        'price',
        'row_total',
        'tax_amount',
        'discount_amount',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:2',
        'qty_shipped' => 'decimal:2',
        'qty_canceled' => 'decimal:2',
        'price' => 'decimal:4',
        'row_total' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MagentoProduct::class, 'product_id');
    }

    /**
     * Parent item relationship (for bundle/configurable products)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'parent_item_id');
    }

    /**
     * Child items relationship (for bundle/configurable products)
     */
    public function children(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'parent_item_id');
    }

    public function getQtyRemainingAttribute(): float
    {
        return $this->qty_ordered - $this->qty_shipped - $this->qty_canceled;
    }

    public function getIsFullyShippedAttribute(): bool
    {
        return $this->qty_shipped >= $this->qty_ordered;
    }

    public function getIsPartiallyCanceledAttribute(): bool
    {
        return $this->qty_canceled > 0 && $this->qty_canceled < $this->qty_ordered;
    }

    /**
     * Check if this is a parent item (bundle/configurable)
     */
    public function getIsParentAttribute(): bool
    {
        return in_array($this->product_type, ['bundle', 'configurable']);
    }

    /**
     * Check if this is a child item
     */
    public function getIsChildAttribute(): bool
    {
        return $this->parent_item_id !== null;
    }

    /**
     * Check if this is a simple standalone product
     */
    public function getIsStandaloneAttribute(): bool
    {
        return $this->product_type === 'simple' && $this->parent_item_id === null;
    }
}
