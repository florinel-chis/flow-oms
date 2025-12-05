<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MagentoProduct extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'sku',
        'product_type',
        'name',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function stockItem(): HasOne
    {
        return $this->hasOne(MagentoProductStockItem::class);
    }

    /**
     * Get product price from raw data.
     */
    public function getPriceAttribute(): ?float
    {
        return $this->raw_data['price'] ?? null;
    }

    /**
     * Get product status from raw data.
     */
    public function getStatusAttribute(): ?int
    {
        return $this->raw_data['status'] ?? null;
    }

    /**
     * Check if product is enabled.
     */
    public function isEnabled(): bool
    {
        return ($this->raw_data['status'] ?? 0) == 1;
    }
}
