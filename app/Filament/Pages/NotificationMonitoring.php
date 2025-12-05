<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\UnpaidOrderNotificationsActivityWidget;
use App\Filament\Widgets\UnpaidOrderNotificationsStatsWidget;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class NotificationMonitoring extends Page
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notification Monitoring';

    protected static ?string $title = 'Unpaid Order Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'Automation';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.notification-monitoring';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('date_range')
                    ->label('Date Range')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'last_7_days' => 'Last 7 Days',
                        'last_30_days' => 'Last 30 Days',
                        'this_month' => 'This Month',
                    ])
                    ->default('today')
                    ->native(false),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            UnpaidOrderNotificationsStatsWidget::class,
            UnpaidOrderNotificationsActivityWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 1,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
