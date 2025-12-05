<?php

namespace App\Enums;

/**
 * Notification types for unpaid order automation
 */
enum NotificationType: string
{
    case WARNING = 'warning';
    case CANCELLATION = 'cancellation';

    /**
     * Get human-readable label for the notification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::WARNING => 'Warning',
            self::CANCELLATION => 'Cancellation',
        };
    }

    /**
     * Get Filament badge color for the notification type.
     */
    public function color(): string
    {
        return match ($this) {
            self::WARNING => 'warning',
            self::CANCELLATION => 'danger',
        };
    }

    /**
     * Get icon for the notification type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::WARNING => 'heroicon-o-exclamation-triangle',
            self::CANCELLATION => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get the event type string for JSON payloads.
     */
    public function eventType(): string
    {
        return match ($this) {
            self::WARNING => 'order_cancellation_warning',
            self::CANCELLATION => 'order_cancelled',
        };
    }
}
