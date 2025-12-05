<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SlaShippingStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $filters = $this->filters ?? [];
        $urgency = $filters['urgency'] ?? 'all';
        $shippingPriority = $filters['shipping_priority'] ?? 'all';

        // Base query for orders with SLA that haven't shipped yet
        $baseQuery = Order::query()
            ->whereNotNull('sla_deadline')
            ->whereNull('shipped_at');

        // Apply shipping priority filter
        if ($shippingPriority !== 'all') {
            $baseQuery->where(function ($q) use ($shippingPriority) {
                $pattern = match ($shippingPriority) {
                    'same_day' => '%same%day%',
                    'overnight' => '%overnight%',
                    'express' => '%express%',
                    'standard' => '%standard%',
                    default => '%',
                };
                $q->where('shipping_method', 'like', $pattern);
            });
        }

        // Get counts for different urgency levels
        $stats = DB::table('orders')
            ->whereNotNull('sla_deadline')
            ->whereNull('shipped_at')
            ->when($shippingPriority !== 'all', function ($q) use ($shippingPriority) {
                $pattern = match ($shippingPriority) {
                    'same_day' => '%same%day%',
                    'overnight' => '%overnight%',
                    'express' => '%express%',
                    'standard' => '%standard%',
                    default => '%',
                };
                $q->where('shipping_method', 'like', $pattern);
            })
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN sla_deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 1 END) as immediate,
                COUNT(CASE WHEN sla_deadline BETWEEN DATE_ADD(NOW(), INTERVAL 2 HOUR) AND DATE_ADD(NOW(), INTERVAL 6 HOUR) THEN 1 END) as urgent,
                COUNT(CASE WHEN sla_deadline BETWEEN DATE_ADD(NOW(), INTERVAL 6 HOUR) AND DATE_ADD(NOW(), INTERVAL 24 HOUR) THEN 1 END) as at_risk,
                COUNT(CASE WHEN sla_deadline <= NOW() THEN 1 END) as breached
            ')
            ->first();

        // Get total orders with SLA (including shipped)
        $totalWithSla = Order::whereNotNull('sla_deadline')->count();

        // Get total shipped on time
        $shippedOnTime = Order::whereNotNull('sla_deadline')
            ->whereNotNull('shipped_at')
            ->whereRaw('shipped_at <= sla_deadline')
            ->count();

        // Calculate compliance rate
        $complianceRate = $totalWithSla > 0
            ? round((($shippedOnTime / $totalWithSla) * 100), 1)
            : 100;

        return [
            Stat::make('Immediate Action', $stats->immediate ?? 0)
                ->description('Ships in <2 hours')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([7, 5, 3, 2, $stats->immediate ?? 0]),

            Stat::make('Urgent', $stats->urgent ?? 0)
                ->description('Ships in 2-6 hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([5, 4, 3, 2, $stats->urgent ?? 0]),

            Stat::make('At Risk', $stats->at_risk ?? 0)
                ->description('Ships in 6-24 hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->chart([10, 8, 6, 4, $stats->at_risk ?? 0]),

            Stat::make('SLA Breached', $stats->breached ?? 0)
                ->description('Missed shipping deadline')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([1, 2, 3, 4, $stats->breached ?? 0]),

            Stat::make('SLA Compliance', $complianceRate . '%')
                ->description('Overall on-time ship rate')
                ->descriptionIcon($complianceRate >= 95 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($complianceRate >= 95 ? 'success' : ($complianceRate >= 85 ? 'warning' : 'danger'))
                ->chart([90, 92, 94, 96, $complianceRate]),

            Stat::make('Pending Shipments', $stats->total ?? 0)
                ->description('Orders awaiting shipment')
                ->descriptionIcon('heroicon-m-truck')
                ->color('gray')
                ->chart([20, 18, 15, 12, $stats->total ?? 0]),
        ];
    }
}
