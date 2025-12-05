<?php

namespace App\Filament\Widgets;

use App\Models\UnpaidOrderNotification;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class UnpaidOrderNotificationsStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    protected ?string $heading = 'Notification Activity';

    protected function getStats(): array
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        // Single optimized query with conditional aggregation
        $stats = UnpaidOrderNotification::query()
            ->whereBetween('triggered_at', [$dateRange['start'], $dateRange['end']])
            ->when($storeId, function ($q) use ($storeId) {
                return $q->whereHas('order', fn($q) => $q->where('magento_store_id', $storeId));
            })
            ->selectRaw('
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN notification_type = "warning" THEN 1 END) as warning_count,
                COUNT(CASE WHEN notification_type = "cancellation" THEN 1 END) as cancellation_count,
                COUNT(CASE WHEN sent_successfully = 1 THEN 1 END) as successful_count,
                COUNT(CASE WHEN sent_successfully = 0 THEN 1 END) as failed_count,
                AVG(retry_count) as avg_retry_count,
                COUNT(CASE WHEN retry_count > 0 THEN 1 END) as retry_count_gt_zero
            ')
            ->first();

        $totalNotifications = $stats->total_notifications ?? 0;
        $warningCount = $stats->warning_count ?? 0;
        $cancellationCount = $stats->cancellation_count ?? 0;
        $successfulCount = $stats->successful_count ?? 0;
        $failedCount = $stats->failed_count ?? 0;
        $avgRetryCount = $stats->avg_retry_count ?? 0;
        $retriedCount = $stats->retry_count_gt_zero ?? 0;

        $successRate = $totalNotifications > 0
            ? round(($successfulCount / $totalNotifications) * 100, 1)
            : 100;

        return [
            Stat::make('Total Notifications', Number::format($totalNotifications))
                ->description($warningCount.' warnings, '.$cancellationCount.' cancellations')
                ->descriptionIcon('heroicon-m-bell')
                ->color('primary'),

            Stat::make('Success Rate', $successRate.'%')
                ->description($successfulCount.' sent, '.$failedCount.' failed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($successRate >= 95 ? 'success' : ($successRate >= 85 ? 'warning' : 'danger')),

            Stat::make('Warnings Sent', Number::format($warningCount))
                ->description('Cancellation warnings')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($warningCount > 0 ? 'warning' : 'gray'),

            Stat::make('Orders Cancelled', Number::format($cancellationCount))
                ->description('Automatically cancelled')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancellationCount > 10 ? 'danger' : ($cancellationCount > 5 ? 'warning' : 'gray')),

            Stat::make('Failed Notifications', Number::format($failedCount))
                ->description($retriedCount.' required retries')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($failedCount > 5 ? 'danger' : ($failedCount > 0 ? 'warning' : 'success')),

            Stat::make('Avg Retries', Number::format($avgRetryCount, maxPrecision: 1))
                ->description('Average retry attempts')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($avgRetryCount > 1 ? 'warning' : 'success'),
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
