<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'order_id',
        'magento_shipment_id',
        'tracking_number',
        'carrier_code',
        'carrier_title',
        'status',
        'shipped_at',
        'estimated_delivery_at',
        'actual_delivery_at',
        'last_tracking_update_at',
        'delivery_signature',
        'delivery_notes',
        'delivery_photo_url',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'actual_delivery_at' => 'datetime',
        'last_tracking_update_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->status === 'delivered' && $this->actual_delivery_at !== null;
    }

    public function getIsDelayedAttribute(): bool
    {
        if (! $this->estimated_delivery_at || $this->isDelivered) {
            return false;
        }

        return now()->isAfter($this->estimated_delivery_at);
    }

    public function getDelayDaysAttribute(): ?int
    {
        if (! $this->isDelayed) {
            return null;
        }

        return now()->diffInDays($this->estimated_delivery_at);
    }

    public function getFormattedCarrierAttribute(): string
    {
        return $this->carrier_title ?? strtoupper($this->carrier_code ?? 'Unknown');
    }

    /**
     * Scope to find by tracking number (case-insensitive).
     */
    public function scopeByTrackingNumber(Builder $query, string $trackingNumber): Builder
    {
        return $query->whereRaw('LOWER(tracking_number) = ?', [strtolower($trackingNumber)]);
    }

    /**
     * Scope to filter by carrier code.
     */
    public function scopeForCarrier(Builder $query, string $carrierCode): Builder
    {
        return $query->where('carrier_code', $carrierCode);
    }

    /**
     * Scope to filter pending deliveries.
     */
    public function scopePendingDelivery(Builder $query): Builder
    {
        return $query->whereNull('actual_delivery_at');
    }

    /**
     * Mark the shipment as delivered.
     */
    public function markAsDelivered(
        \DateTimeInterface $deliveredAt,
        ?string $signature = null,
        ?string $notes = null,
        ?string $photoUrl = null,
    ): bool {
        return $this->update([
            'status' => ShipmentStatus::DELIVERED->value,
            'actual_delivery_at' => $deliveredAt,
            'delivery_signature' => $signature,
            'delivery_notes' => $notes,
            'delivery_photo_url' => $photoUrl,
            'last_tracking_update_at' => now(),
        ]);
    }

    /**
     * Get the status as an enum.
     */
    public function getStatusEnumAttribute(): ?ShipmentStatus
    {
        return ShipmentStatus::tryFrom($this->status);
    }
}
