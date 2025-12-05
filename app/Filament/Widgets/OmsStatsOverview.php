<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class OmsStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected ?string $heading = '';

    protected function getStats(): array
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        // Optimize: Use single query with conditional aggregation
        $stats = Order::query()
            ->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
            ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId))
            ->selectRaw('
                COUNT(*) as orders_count,
                COALESCE(SUM(grand_total), 0) as revenue,
                COUNT(CASE WHEN payment_status = "pending" THEN 1 END) as unpaid_count,
                COALESCE(SUM(CASE WHEN payment_status = "pending" THEN grand_total END), 0) as unpaid_amount,
                COUNT(CASE WHEN status = "holded" THEN 1 END) as backorders_count,
                COUNT(CASE WHEN status IN ("complete", "closed") AND shipped_at IS NOT NULL THEN 1 END) as shipped_count
            ')
            ->first();

        $ordersCount = $stats->orders_count ?? 0;
        $revenue = $stats->revenue ?? 0;
        $unpaidCount = $stats->unpaid_count ?? 0;
        $unpaidAmount = $stats->unpaid_amount ?? 0;
        $backordersCount = $stats->backorders_count ?? 0;
        $shippedCount = $stats->shipped_count ?? 0;

        // Calculate Average Order Value
        $aov = $ordersCount > 0 ? $revenue / $ordersCount : 0;

        // Separate query for ready to ship (complex scope with settings)
        $readyToShipCount = Order::readyToShip()
            ->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
            ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId))
            ->count();

        // Real SLA calculation
        $slaStats = Order::query()
            ->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
            ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId))
            ->whereNotNull('sla_deadline')
            ->selectRaw('
                COUNT(*) as total_with_sla,
                COUNT(CASE WHEN shipped_at IS NOT NULL AND shipped_at <= sla_deadline THEN 1 END) as shipped_on_time,
                COUNT(CASE WHEN sla_deadline <= NOW() AND shipped_at IS NULL THEN 1 END) as breached,
                COUNT(CASE WHEN sla_deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR) AND shipped_at IS NULL THEN 1 END) as urgent
            ')
            ->first();

        $totalWithSla = $slaStats->total_with_sla ?? 0;
        $shippedOnTime = $slaStats->shipped_on_time ?? 0;
        $breached = $slaStats->breached ?? 0;
        $urgent = $slaStats->urgent ?? 0;

        $slaCompliance = $totalWithSla > 0
            ? round(($shippedOnTime / $totalWithSla) * 100, 1)
            : 100;

        $readyToShipUrgent = $urgent; // Orders ready to ship with < 2 hours to deadline

        // Calculate delayed shipments (past estimated delivery date and not yet delivered)
        $delayedShipmentsCount = Shipment::query()
            ->whereHas('order', function ($q) use ($dateRange, $storeId) {
                $q->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
                    ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId));
            })
            ->whereNotNull('estimated_delivery_at')
            ->where('estimated_delivery_at', '<', now())
            ->whereNull('actual_delivery_at')
            ->whereNotIn('status', ['delivered', 'canceled'])
            ->count();

        // Calculate active shipments (in transit or out for delivery)
        $activeShipmentsCount = Shipment::query()
            ->whereHas('order', function ($q) use ($dateRange, $storeId) {
                $q->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
                    ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId));
            })
            ->whereIn('status', ['in_transit', 'out_for_delivery'])
            ->whereNull('actual_delivery_at')
            ->count();

        // Get settings for thresholds
        $unpaidWarningThreshold = Setting::get('dashboard', 'unpaid_warning_threshold', 10);
        $unpaidInfoThreshold = Setting::get('dashboard', 'unpaid_info_threshold', 5);
        $targetSlaCompliance = Setting::get('dashboard', 'target_sla_compliance', 95);

        return [
            Stat::make('Orders', Number::format($ordersCount))
                ->description('Placed in selected range')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Revenue', '$'.Number::format($revenue, 2))
                ->description('Captured / invoiced')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('AOV', '$'.Number::format($aov, 2))
                ->description('Average order value')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Shipped', $shippedCount)
                ->description($activeShipmentsCount.' active in transit')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Unpaid', $unpaidCount)
                ->description('$$'.Number::format($unpaidAmount, 2).' outstanding')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($unpaidCount > $unpaidWarningThreshold ? 'danger' : ($unpaidCount > $unpaidInfoThreshold ? 'warning' : 'success'))
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Ready to Ship', $readyToShipCount)
                ->description($readyToShipUrgent.' urgent')
                ->descriptionIcon('heroicon-m-truck')
                ->color($readyToShipUrgent > 5 ? 'warning' : 'info'),

            Stat::make('Exceptions', $backordersCount + $delayedShipmentsCount)
                ->description($backordersCount.' backorders, '.$delayedShipmentsCount.' delayed')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('SLA', $slaCompliance.'%')
                ->description("Target: {$targetSlaCompliance}%")
                ->descriptionIcon('heroicon-m-clock')
                ->color($slaCompliance >= $targetSlaCompliance ? 'success' : ($slaCompliance >= ($targetSlaCompliance - 5) ? 'warning' : 'danger')),
        ];
    }

    protected function getDateRange(): array
    {
        return match ($this->filters['date_range'] ?? 'today') {
            'today' => ['start' => today(), 'end' => now()],
            'yesterday' => ['start' => today()->subDay(), 'end' => today()->subDay()->endOfDay()],
            'last_7_days' => ['start' => now()->subDays(7), 'end' => now()],
            'last_30_days' => ['start' => now()->subDays(30), 'end' => now()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()],
            default => ['start' => today(), 'end' => now()],
        };
    }
}
