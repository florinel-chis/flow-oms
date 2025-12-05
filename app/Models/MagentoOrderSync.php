<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagentoOrderSync extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'magento_store_id',
        'entity_id',
        'increment_id',
        'order_status',
        'has_invoice',
        'has_shipment',
        'raw_data',
        'sync_batch_id',
        'synced_at',
    ];

    protected $casts = [
        'has_invoice' => 'boolean',
        'has_shipment' => 'boolean',
        'raw_data' => 'array',
        'synced_at' => 'datetime',
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Get the Magento store this sync belongs to.
     */
    public function magentoStore(): BelongsTo
    {
        return $this->belongsTo(MagentoStore::class, 'magento_store_id');
    }

    /**
     * Get the tenant this sync belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Filter by Magento store.
     */
    public function scopeForStore(Builder $query, MagentoStore|int $store): Builder
    {
        $storeId = $store instanceof MagentoStore ? $store->id : $store;

        return $query->where('magento_store_id', $storeId);
    }

    /**
     * Filter by order status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('order_status', $status);
    }

    /**
     * Filter orders with invoices.
     */
    public function scopeWithInvoices(Builder $query): Builder
    {
        return $query->where('has_invoice', true);
    }

    /**
     * Filter orders with shipments.
     */
    public function scopeWithShipments(Builder $query): Builder
    {
        return $query->where('has_shipment', true);
    }

    /**
     * Filter orders without invoices.
     */
    public function scopeWithoutInvoices(Builder $query): Builder
    {
        return $query->where('has_invoice', false);
    }

    /**
     * Filter orders without shipments.
     */
    public function scopeWithoutShipments(Builder $query): Builder
    {
        return $query->where('has_shipment', false);
    }

    /**
     * Filter by sync batch.
     */
    public function scopeInBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('sync_batch_id', $batchId);
    }

    /**
     * Filter synced within date range.
     */
    public function scopeSyncedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('synced_at', [$startDate, $endDate]);
    }

    /**
     * Filter synced today.
     */
    public function scopeSyncedToday(Builder $query): Builder
    {
        return $query->whereDate('synced_at', today());
    }

    /**
     * Order by most recently synced first.
     */
    public function scopeLatestSync(Builder $query): Builder
    {
        return $query->orderBy('synced_at', 'desc');
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Get customer name from raw data.
     */
    public function getCustomerNameAttribute(): ?string
    {
        $data = $this->raw_data;
        if (! $data) {
            return null;
        }

        $firstname = $data['customer_firstname'] ?? '';
        $lastname = $data['customer_lastname'] ?? '';

        return trim("{$firstname} {$lastname}") ?: null;
    }

    /**
     * Get customer email from raw data.
     */
    public function getCustomerEmailAttribute(): ?string
    {
        return $this->raw_data['customer_email'] ?? null;
    }

    /**
     * Get grand total from raw data.
     */
    public function getGrandTotalAttribute(): ?float
    {
        return isset($this->raw_data['grand_total'])
            ? (float) $this->raw_data['grand_total']
            : null;
    }

    /**
     * Get order currency from raw data.
     */
    public function getCurrencyCodeAttribute(): ?string
    {
        return $this->raw_data['order_currency_code'] ?? 'USD';
    }

    /**
     * Get formatted grand total.
     */
    public function getFormattedGrandTotalAttribute(): string
    {
        if (! $this->grand_total) {
            return 'N/A';
        }

        return $this->currency_code.' '.number_format($this->grand_total, 2);
    }

    /**
     * Get Magento order created date.
     */
    public function getMagentoCreatedAtAttribute(): ?string
    {
        return $this->raw_data['created_at'] ?? null;
    }

    /**
     * Get Magento order updated date.
     */
    public function getMagentoUpdatedAtAttribute(): ?string
    {
        return $this->raw_data['updated_at'] ?? null;
    }

    /**
     * Get order state from raw data.
     */
    public function getOrderStateAttribute(): ?string
    {
        return $this->raw_data['state'] ?? null;
    }

    /**
     * Get total quantity ordered.
     */
    public function getTotalQtyOrderedAttribute(): ?float
    {
        return isset($this->raw_data['total_qty_ordered'])
            ? (float) $this->raw_data['total_qty_ordered']
            : null;
    }

    /**
     * Get total quantity invoiced.
     */
    public function getTotalQtyInvoicedAttribute(): ?float
    {
        return isset($this->raw_data['total_qty_invoiced'])
            ? (float) $this->raw_data['total_qty_invoiced']
            : null;
    }

    /**
     * Get total quantity shipped.
     */
    public function getTotalQtyShippedAttribute(): ?float
    {
        return isset($this->raw_data['total_qty_shipped'])
            ? (float) $this->raw_data['total_qty_shipped']
            : null;
    }

    /**
     * Get payment method from raw data.
     */
    public function getPaymentMethodAttribute(): ?string
    {
        return $this->raw_data['payment']['method'] ?? null;
    }

    /**
     * Get shipping method from raw data.
     */
    public function getShippingDescriptionAttribute(): ?string
    {
        return $this->raw_data['shipping_description'] ?? null;
    }

    /**
     * Check if order is fully invoiced.
     */
    public function getIsFullyInvoicedAttribute(): bool
    {
        if (! $this->has_invoice) {
            return false;
        }

        $qtyOrdered = $this->total_qty_ordered ?? 0;
        $qtyInvoiced = $this->total_qty_invoiced ?? 0;

        return $qtyInvoiced >= $qtyOrdered;
    }

    /**
     * Check if order is fully shipped.
     */
    public function getIsFullyShippedAttribute(): bool
    {
        if (! $this->has_shipment) {
            return false;
        }

        $qtyOrdered = $this->total_qty_ordered ?? 0;
        $qtyShipped = $this->total_qty_shipped ?? 0;

        return $qtyShipped >= $qtyOrdered;
    }

    /**
     * Get status badge color for Filament.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match (strtolower($this->order_status)) {
            'pending' => 'gray',
            'pending_payment' => 'warning',
            'processing' => 'info',
            'complete' => 'success',
            'closed' => 'success',
            'canceled' => 'danger',
            'holded' => 'warning',
            'payment_review' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get invoice status badge color.
     */
    public function getInvoiceStatusColorAttribute(): string
    {
        if (! $this->has_invoice) {
            return 'gray';
        }

        return $this->is_fully_invoiced ? 'success' : 'warning';
    }

    /**
     * Get shipment status badge color.
     */
    public function getShipmentStatusColorAttribute(): string
    {
        if (! $this->has_shipment) {
            return 'gray';
        }

        return $this->is_fully_shipped ? 'success' : 'warning';
    }

    // ============================================
    // Methods
    // ============================================

    /**
     * Get the full Magento order URL.
     */
    public function getMagentoUrl(): ?string
    {
        if (! $this->magentoStore) {
            return null;
        }

        $baseUrl = rtrim($this->magentoStore->base_url, '/');

        return "{$baseUrl}/admin/sales/order/view/order_id/{$this->entity_id}";
    }
}
