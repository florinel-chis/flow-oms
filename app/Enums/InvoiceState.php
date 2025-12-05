<?php

namespace App\Enums;

enum InvoiceState: string
{
    case OPEN = 'open';
    case PAID = 'paid';
    case CANCELED = 'canceled';

    /**
     * Get human-readable label for the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::PAID => 'Paid',
            self::CANCELED => 'Canceled',
        };
    }

    /**
     * Get Filament badge color for the state.
     */
    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::PAID => 'success',
            self::CANCELED => 'danger',
        };
    }

    /**
     * Get icon for the state.
     */
    public function icon(): string
    {
        return match ($this) {
            self::OPEN => 'heroicon-o-clock',
            self::PAID => 'heroicon-o-check-circle',
            self::CANCELED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Check if invoice can be voided/canceled.
     */
    public function canCancel(): bool
    {
        return $this === self::OPEN;
    }

    /**
     * Check if invoice is finalized (paid or canceled).
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::PAID, self::CANCELED]);
    }
}
