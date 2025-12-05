<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case INFO_RECEIVED = 'info_received';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED_ATTEMPT = 'failed_attempt';
    case EXCEPTION = 'exception';
    case EXPIRED = 'expired';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::INFO_RECEIVED => 'Info Received',
            self::IN_TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::FAILED_ATTEMPT => 'Failed Attempt',
            self::EXCEPTION => 'Exception',
            self::EXPIRED => 'Expired',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get Filament badge color for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::INFO_RECEIVED => 'info',
            self::IN_TRANSIT => 'primary',
            self::OUT_FOR_DELIVERY => 'warning',
            self::DELIVERED => 'success',
            self::FAILED_ATTEMPT => 'danger',
            self::EXCEPTION => 'danger',
            self::EXPIRED => 'gray',
            self::UNKNOWN => 'gray',
        };
    }

    /**
     * Get icon for the status.
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::INFO_RECEIVED => 'heroicon-o-document-text',
            self::IN_TRANSIT => 'heroicon-o-truck',
            self::OUT_FOR_DELIVERY => 'heroicon-o-map-pin',
            self::DELIVERED => 'heroicon-o-check-circle',
            self::FAILED_ATTEMPT => 'heroicon-o-exclamation-triangle',
            self::EXCEPTION => 'heroicon-o-x-circle',
            self::EXPIRED => 'heroicon-o-archive-box-x-mark',
            self::UNKNOWN => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Check if this status represents a final/terminal state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::EXCEPTION,
            self::EXPIRED,
        ]);
    }

    /**
     * Check if this status represents a successful delivery.
     */
    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    /**
     * Check if this status represents an active shipment.
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::INFO_RECEIVED,
            self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY,
            self::FAILED_ATTEMPT,
        ]);
    }
}
