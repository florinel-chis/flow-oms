<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class ReadyToShipWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Ready to Ship';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '400px';

    public function table(Table $table): Table
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        return $table
            ->query(
                Order::readyToShip()
                    ->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
                    ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId))
                    ->withCount('items')
                    ->orderByDesc('ordered_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn ($record) => '#order-'.$record->increment_id),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('age')
                    ->label('Age (since ready)')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->age_hours >= 2 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('shipping_method')
                    ->label('Shipping')
                    ->badge()
                    ->color(fn ($record) => match ($record->shipping_method) {
                        'Express' => 'info',
                        'Standard' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($record) => match ($record->priority) {
                        'SLA risk' => 'danger',
                        'VIP' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('destination')
                    ->label('Dest.'),

                Tables\Columns\TextColumn::make('picker')
                    ->placeholder('-')
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipping_method')
                    ->options([
                        'express' => 'Express',
                        'standard' => 'Standard',
                        'overnight' => 'Overnight',
                    ]),
            ])
            ->actions([
                Action::make('print_ship')
                    ->label('Print & Ship')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Print Label and Create Shipment')
                    ->modalDescription(fn ($record) => "Create shipment and print shipping label for order #{$record->increment_id}")
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Shipment Created')
                            ->body("Shipping label printed for order #{$record->increment_id}")
                            ->success()
                            ->send();
                    }),

                Action::make('print_picklist')
                    ->label('Print Pick List')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Pick List Printed')
                            ->body("Pick list generated for order #{$record->increment_id}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('assign_picker')
                    ->label('Assign Picker')
                    ->icon('heroicon-o-user')
                    ->color('primary')
                    ->form([
                        Select::make('picker')
                            ->label('Picker')
                            ->options([
                                'Tom' => 'Tom',
                                'Anna' => 'Anna',
                                'Mike' => 'Mike',
                            ])
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        Notification::make()
                            ->title('Picker Assigned')
                            ->body("{$records->count()} orders assigned to {$data['picker']}")
                            ->success()
                            ->send();
                    }),

                BulkAction::make('print_batch_labels')
                    ->label('Print Batch Labels')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Batch Labels Printed')
                            ->body("Shipping labels printed for {$records->count()} orders")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('60s');
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
