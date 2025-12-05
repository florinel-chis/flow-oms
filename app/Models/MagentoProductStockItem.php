<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagentoProductStockItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'magento_product_id',
        'magento_item_id',
        'qty',
        'is_in_stock',
        'manage_stock',
        'use_config_manage_stock',
        'backorders',
        'use_config_backorders',
        'min_qty',
        'min_sale_qty',
        'max_sale_qty',
        'notify_stock_qty',
        'enable_qty_increments',
        'qty_increments',
        'raw_data',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'is_in_stock' => 'boolean',
        'manage_stock' => 'boolean',
        'use_config_manage_stock' => 'boolean',
        'backorders' => 'integer',
        'use_config_backorders' => 'boolean',
        'min_qty' => 'decimal:4',
        'min_sale_qty' => 'decimal:4',
        'max_sale_qty' => 'decimal:4',
        'notify_stock_qty' => 'decimal:4',
        'enable_qty_increments' => 'boolean',
        'qty_increments' => 'decimal:4',
        'raw_data' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MagentoProduct::class, 'magento_product_id');
    }

    /**
     * Get stock status label.
     */
    public function getStockStatusAttribute(): string
    {
        if (!$this->is_in_stock) {
            return 'Out of Stock';
        }

        if ($this->qty <= $this->min_qty) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    /**
     * Check if product allows backorders.
     */
    public function allowsBackorders(): bool
    {
        return $this->backorders > 0;
    }
}
