<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'invoice_id',
        'order_item_id',
        'magento_item_id',
        'product_name',
        'sku',
        'qty',
        'price',
        'row_total',
        'tax_amount',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:4',
        'row_total' => 'decimal:4',
        'tax_amount' => 'decimal:4',
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the invoice that owns this item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the original order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Filter items by SKU.
     */
    public function scopeBySku(Builder $query, string $sku): Builder
    {
        return $query->where('sku', $sku);
    }

    /**
     * Filter items for a specific invoice.
     */
    public function scopeForInvoice(Builder $query, Invoice|int $invoice): Builder
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : $invoice;

        return $query->where('invoice_id', $invoiceId);
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Get formatted unit price.
     */
    public function getFormattedPriceAttribute(): string
    {
        $currency = $this->invoice?->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->price, 2);
    }

    /**
     * Get formatted row total.
     */
    public function getFormattedRowTotalAttribute(): string
    {
        $currency = $this->invoice?->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->row_total, 2);
    }

    /**
     * Get formatted tax amount.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        $currency = $this->invoice?->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->tax_amount, 2);
    }

    /**
     * Get row total including tax.
     */
    public function getRowTotalWithTaxAttribute(): float
    {
        return (float) $this->row_total + (float) $this->tax_amount;
    }

    /**
     * Get formatted row total including tax.
     */
    public function getFormattedRowTotalWithTaxAttribute(): string
    {
        $currency = $this->invoice?->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->row_total_with_tax, 2);
    }
}
