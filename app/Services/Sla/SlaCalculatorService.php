<?php

namespace App\Services\Sla;

use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;

class SlaCalculatorService
{
    /**
     * Calculate SLA deadline for an order based on shipping method and settings.
     */
    public function calculateDeadline(Order $order): ?Carbon
    {
        if (!$order->ordered_at) {
            return null;
        }

        $hours = $this->getHoursForShippingMethod($order->shipping_method, $order->tenant_id);
        $operateAllDay = Setting::get('sla', 'operate_24_7', false, $order->tenant_id);

        if ($operateAllDay) {
            // Simple calculation: ordered_at + hours
            return $order->ordered_at->copy()->addHours($hours);
        }

        // Calculate with business hours
        return $this->addBusinessHours($order->ordered_at, $hours, $order->tenant_id);
    }

    /**
     * Match shipping method to SLA hours using pattern matching.
     */
    private function getHoursForShippingMethod(?string $method, ?int $tenantId): int
    {
        if (!$method) {
            return Setting::get('sla', 'standard_shipping_hours', 48, $tenantId);
        }

        $patterns = Setting::get('sla', 'shipping_method_patterns', [], $tenantId);

        // Iterate through patterns in order (same_day, overnight, express, standard)
        foreach ($patterns as $settingKey => $regexPatterns) {
            foreach ($regexPatterns as $pattern) {
                if (preg_match("/{$pattern}/i", $method)) {
                    return Setting::get('sla', $settingKey, 48, $tenantId);
                }
            }
        }

        return Setting::get('sla', 'standard_shipping_hours', 48, $tenantId);
    }

    /**
     * Add business hours to a start time, accounting for weekends and holidays.
     */
    private function addBusinessHours(Carbon $start, int $hours, ?int $tenantId): Carbon
    {
        $businessStart = Setting::get('sla', 'business_hours_start', 9, $tenantId);
        $businessEnd = Setting::get('sla', 'business_hours_end', 17, $tenantId);
        $businessDays = Setting::get('sla', 'business_days', [1, 2, 3, 4, 5], $tenantId);
        $holidays = Setting::get('sla', 'holidays', [], $tenantId);

        $deadline = $start->copy();
        $remainingHours = $hours;

        while ($remainingHours > 0) {
            // Skip to next business day if needed
            while (!$this->isBusinessDay($deadline, $businessDays, $holidays)) {
                $deadline->addDay()->setTime($businessStart, 0);
            }

            // If before business hours, move to start
            if ($deadline->hour < $businessStart) {
                $deadline->setTime($businessStart, 0);
            }

            // If after business hours, move to next day
            if ($deadline->hour >= $businessEnd) {
                $deadline->addDay()->setTime($businessStart, 0);
                continue;
            }

            // Calculate hours available today
            $hoursAvailableToday = $businessEnd - $deadline->hour;
            $hoursToAdd = min($remainingHours, $hoursAvailableToday);

            $deadline->addHours($hoursToAdd);
            $remainingHours -= $hoursToAdd;

            // If we've used up today's hours, move to next business day
            if ($remainingHours > 0 && $deadline->hour >= $businessEnd) {
                $deadline->addDay()->setTime($businessStart, 0);
            }
        }

        return $deadline;
    }

    /**
     * Check if a date is a business day (not weekend or holiday).
     */
    private function isBusinessDay(Carbon $date, array $businessDays, array $holidays): bool
    {
        // Check if it's a weekend
        if (!in_array($date->dayOfWeek, $businessDays)) {
            return false;
        }

        // Check if it's a holiday
        $dateString = $date->format('Y-m-d');

        return !in_array($dateString, $holidays);
    }

    /**
     * Recalculate and update SLA deadline for an order.
     */
    public function recalculateForOrder(Order $order): void
    {
        $deadline = $this->calculateDeadline($order);
        if ($deadline) {
            $order->update(['sla_deadline' => $deadline]);
        }
    }
}
