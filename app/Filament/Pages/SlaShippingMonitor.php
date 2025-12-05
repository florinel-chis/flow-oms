<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SlaShippingOrdersWidget;
use App\Filament\Widgets\SlaShippingStatsWidget;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class SlaShippingMonitor extends Page
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'SLA Shipping Monitor';

    protected static ?string $title = 'SLA Shipping Monitor';

    protected static string|UnitEnum|null $navigationGroup = 'Automation';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.sla-shipping-monitor';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('urgency')
                    ->label('Urgency Level')
                    ->options([
                        'all' => 'All Orders',
                        'immediate' => 'Immediate (<2h)',
                        'urgent' => 'Urgent (2-6h)',
                        'at_risk' => 'At Risk (6-24h)',
                        'breached' => 'Breached',
                    ])
                    ->default('all')
                    ->native(false),

                Select::make('shipping_priority')
                    ->label('Shipping Priority')
                    ->options([
                        'all' => 'All Priorities',
                        'same_day' => 'Same Day',
                        'overnight' => 'Overnight',
                        'express' => 'Express',
                        'standard' => 'Standard',
                    ])
                    ->default('all')
                    ->native(false),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            SlaShippingStatsWidget::class,
            SlaShippingOrdersWidget::class,
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
