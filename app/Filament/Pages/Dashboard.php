<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BackorderedWidget;
use App\Filament\Widgets\DelayedShipmentsWidget;
use App\Filament\Widgets\OmsStatsOverview;
use App\Filament\Widgets\ReadyToShipWidget;
use App\Filament\Widgets\UnpaidOrdersWidget;
use App\Models\MagentoStore;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'OMS Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('magento_store_id')
                    ->label('Store')
                    ->options(function () {
                        $stores = MagentoStore::query()
                            ->pluck('name', 'id')
                            ->toArray();

                        return ['' => 'All Stores'] + $stores;
                    })
                    ->default('')
                    ->native(false),

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

                // TODO: Implement channel filter when orders.channel column is added
                // Select::make('channel')
                //     ->label('Sales Channel')
                //     ->options([
                //         '' => 'All Channels',
                //         'web' => 'Web',
                //         'amazon' => 'Amazon',
                //         'ebay' => 'eBay',
                //     ])
                //     ->default('')
                //     ->native(false),
            ])
            ->columns(2);
    }

    public function getWidgets(): array
    {
        return [
            OmsStatsOverview::class,
            UnpaidOrdersWidget::class,
            ReadyToShipWidget::class,
            BackorderedWidget::class,
            DelayedShipmentsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
