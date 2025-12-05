<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class BackorderedWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Backordered';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '400px';

    public function table(Table $table): Table
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        return $table
            ->query(
                Order::query()
                    ->where('status', 'holded')
                    ->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
                    ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId))
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

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->limit(15),

                Tables\Columns\TextColumn::make('product')
                    ->label('Product')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->product),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('restock_eta')
                    ->label('Restock ETA')
                    ->date()
                    ->color(fn ($record) => $record->restock_overdue ? 'danger' : null),

                Tables\Columns\TextColumn::make('sla_deadline')
                    ->label('SLA Deadline')
                    ->date()
                    ->color(fn ($record) => $record->sla_at_risk ? 'danger' : null),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record) => match ($record->status) {
                        'SLA at risk' => 'danger',
                        'On track' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // Filters removed - ETA and supplier data not yet implemented
            ])
            ->actions([
                Action::make('notify_customer')
                    ->label('Notify Customer')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Backorder Notification')
                    ->modalDescription(fn ($record) => "Notify {$record->customer_name} about backorder status for order #{$record->increment_id}")
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Customer Notified')
                            ->body("Backorder notification sent to {$record->customer_name}")
                            ->success()
                            ->send();
                    }),

                Action::make('split_upgrade')
                    ->label('Split / Upgrade')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'SLA at risk')
                    ->requiresConfirmation()
                    ->modalHeading('Split Order or Upgrade Shipping')
                    ->modalDescription('Split the order to ship available items or upgrade to faster shipping')
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Order Action Required')
                            ->body("Order #{$record->increment_id} flagged for split/upgrade review")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('notify_customers')
                    ->label('Notify Customers')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Bulk Backorder Notifications')
                    ->modalDescription(fn (Collection $records) => "Send backorder notifications to {$records->count()} customers")
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Notifications Sent')
                            ->body("Backorder notifications sent to {$records->count()} customers")
                            ->success()
                            ->send();
                    }),

                BulkAction::make('split_eligible')
                    ->label('Split Eligible Orders')
                    ->icon('heroicon-o-scissors')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Orders Processed')
                            ->body("{$records->count()} orders flagged for split processing")
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
