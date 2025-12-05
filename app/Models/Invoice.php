<?php

namespace App\Models;

use App\Enums\InvoiceState;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'order_id',
        'magento_store_id',
        'magento_invoice_id',
        'increment_id',
        'state',
        'grand_total',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'base_grand_total',
        'base_subtotal',
        'billing_address_id',
        'customer_name',
        'customer_email',
        'invoiced_at',
    ];

    protected $casts = [
        'state' => InvoiceState::class,
        'grand_total' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'base_grand_total' => 'decimal:4',
        'base_subtotal' => 'decimal:4',
        'invoiced_at' => 'datetime',
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the order that owns this invoice.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the invoice items for this invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the Magento store through the order.
     */
    public function magentoStore(): BelongsTo
    {
        return $this->belongsTo(MagentoStore::class, 'magento_store_id');
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Filter invoices by state.
     */
    public function scopeByState(Builder $query, InvoiceState|string $state): Builder
    {
        $stateValue = $state instanceof InvoiceState ? $state->value : $state;

        return $query->where('state', $stateValue);
    }

    /**
     * Filter invoices for a specific order.
     */
    public function scopeForOrder(Builder $query, Order|int $order): Builder
    {
        $orderId = $order instanceof Order ? $order->id : $order;

        return $query->where('order_id', $orderId);
    }

    /**
     * Filter invoices created today.
     */
    public function scopeInvoicedToday(Builder $query): Builder
    {
        return $query->whereDate('invoiced_at', today());
    }

    /**
     * Filter paid invoices.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::PAID->value);
    }

    /**
     * Filter open invoices.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::OPEN->value);
    }

    /**
     * Filter canceled invoices.
     */
    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('state', InvoiceState::CANCELED->value);
    }

    /**
     * Filter invoices within a date range.
     */
    public function scopeInvoicedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('invoiced_at', [$startDate, $endDate]);
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Get formatted grand total with currency symbol.
     */
    public function getFormattedGrandTotalAttribute(): string
    {
        $currency = $this->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->grand_total, 2);
    }

    /**
     * Get formatted subtotal with currency symbol.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        $currency = $this->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->subtotal, 2);
    }

    /**
     * Get formatted tax amount with currency symbol.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        $currency = $this->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->tax_amount, 2);
    }

    /**
     * Get formatted shipping amount with currency symbol.
     */
    public function getFormattedShippingAmountAttribute(): string
    {
        $currency = $this->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->shipping_amount, 2);
    }

    /**
     * Get formatted discount amount with currency symbol.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        $currency = $this->order?->currency_code ?? 'USD';

        return $currency.' '.number_format($this->discount_amount, 2);
    }

    /**
     * Get state badge color for Filament.
     */
    public function getStateBadgeColorAttribute(): string
    {
        return $this->state->color();
    }

    /**
     * Get state label for display.
     */
    public function getStateLabelAttribute(): string
    {
        return $this->state->label();
    }

    /**
     * Get state icon.
     */
    public function getStateIconAttribute(): string
    {
        return $this->state->icon();
    }

    /**
     * Check if the invoice is paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->state === InvoiceState::PAID;
    }

    /**
     * Check if the invoice is open.
     */
    public function getIsOpenAttribute(): bool
    {
        return $this->state === InvoiceState::OPEN;
    }

    /**
     * Check if the invoice is canceled.
     */
    public function getIsCanceledAttribute(): bool
    {
        return $this->state === InvoiceState::CANCELED;
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsCountAttribute(): int
    {
        return $this->items()->sum('qty');
    }

    // ============================================
    // Methods
    // ============================================

    /**
     * Check if the invoice can be canceled.
     */
    public function canCancel(): bool
    {
        return $this->state->canCancel();
    }

    /**
     * Check if the invoice is finalized.
     */
    public function isFinalized(): bool
    {
        return $this->state->isFinalized();
    }
}
