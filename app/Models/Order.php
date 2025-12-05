<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'magento_store_id',
        'magento_order_id',
        'increment_id',
        'status',
        'payment_status',
        'priority',
        'source',
        'customer_name',
        'customer_email',
        'grand_total',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'currency_code',
        'payment_method',
        'shipping_method',
        'ordered_at',
        'synced_at',
        'sla_deadline',
        'shipped_at',
        'sla_breached',
    ];

    protected $casts = [
        'grand_total' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'ordered_at' => 'datetime',
        'synced_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'shipped_at' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    // Relationships

    public function magentoStore(): BelongsTo
    {
        return $this->belongsTo(MagentoStore::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UnpaidOrderNotification::class);
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeComplete($query)
    {
        return $query->where('status', 'complete');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['pending', 'failed', 'partially_paid']);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeRefunded($query)
    {
        return $query->where('payment_status', 'refunded');
    }

    public function scopeOrderedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('ordered_at', [$startDate, $endDate]);
    }

    public function scopeOrderedToday($query)
    {
        return $query->whereDate('ordered_at', today());
    }

    public function scopeReadyToShip($query)
    {
        $paymentStatuses = Setting::get('ready_to_ship', 'payment_statuses', ['paid']);
        $orderStatuses = Setting::get('ready_to_ship', 'order_statuses', ['processing']);
        $checkShipments = Setting::get('ready_to_ship', 'check_shipments', true);

        $query->whereIn('payment_status', $paymentStatuses)
            ->whereIn('status', $orderStatuses);

        if ($checkShipments) {
            $query->whereDoesntHave('shipments');
        }

        return $query;
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($q) {
                $q->where('payment_status', 'pending')
                    ->where('ordered_at', '<', now()->subHours(48));
            })->orWhere(function ($q) {
                $q->where('status', 'processing')
                    ->where('ordered_at', '<', now()->subHours(24));
            });
        });
    }

    // Accessors

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getIsUnpaidAttribute(): bool
    {
        return in_array($this->payment_status, ['pending', 'failed', 'partially_paid']);
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->payment_status === 'refunded';
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->status === 'complete';
    }

    public function getIsCanceledAttribute(): bool
    {
        return $this->status === 'canceled';
    }

    public function getHasShipmentsAttribute(): bool
    {
        // Use loaded relationship if available to prevent N+1
        if ($this->relationLoaded('shipments')) {
            return $this->shipments->isNotEmpty();
        }

        return $this->shipments()->exists();
    }

    public function getTotalItemsCountAttribute(): int
    {
        // Use loaded relationship if available to prevent N+1
        if ($this->relationLoaded('items')) {
            return $this->items->sum('qty_ordered');
        }

        return $this->items()->sum('qty_ordered');
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        return $this->currency_code.' '.number_format($this->grand_total, 2);
    }

    public function getAgeInHoursAttribute(): int
    {
        return $this->ordered_at ? (int) now()->diffInHours($this->ordered_at) : 0;
    }

    public function getAgeHumanAttribute(): string
    {
        if (! $this->ordered_at) {
            return 'Unknown';
        }

        $hours = $this->age_in_hours;
        $days = (int) floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($days > 0) {
            return "{$days}d {$remainingHours}h";
        }

        return "{$hours}h";
    }

    // Methods

    public function isFullyShipped(): bool
    {
        // Use loaded relationship if available to prevent N+1
        if ($this->relationLoaded('items')) {
            $items = $this->items;
        } else {
            $items = $this->items()->get();
        }

        if ($items->isEmpty()) {
            return false;
        }

        return $items->every(fn ($item) => $item->is_fully_shipped);
    }

    public function hasBackorderedItems(): bool
    {
        return $this->items()
            ->where('qty_shipped', '<', 'qty_ordered')
            ->exists();
    }

    public function getLatestShipment(): ?Shipment
    {
        return $this->shipments()
            ->latest('shipped_at')
            ->first();
    }
}
