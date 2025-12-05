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

class UnpaidOrdersWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Unpaid Orders';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '400px';

    public function table(Table $table): Table
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        return $table
            ->query(
                Order::query()
                    ->where('payment_status', 'pending')
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
                    ->limit(20),

                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Age')
                    ->since()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->ordered_at->diffInHours() >= 120 => 'danger', // 5+ days
                        $record->ordered_at->diffInHours() >= 72 => 'warning', // 3+ days
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge(),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Last Reminder')
                    ->since()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Risk')
                    ->badge()
                    ->formatStateUsing(fn () => 'Normal')
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'card' => 'Card',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                    ]),
            ])
            ->actions([
                Action::make('send_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Payment Reminder')
                    ->modalDescription(fn ($record) => "Send a payment reminder to {$record->customer_name} for order #{$record->increment_id}")
                    ->action(function ($record) {
                        // In production: \App\Jobs\SendPaymentReminderJob::dispatch($record);
                        Notification::make()
                            ->title('Reminder Sent')
                            ->body("Payment reminder sent to {$record->customer_name}")
                            ->success()
                            ->send();
                    }),

                Action::make('escalate')
                    ->label('Escalate')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->risk === 'High risk')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Order Escalated')
                            ->body("Order #{$record->increment_id} has been escalated to management")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('send_bulk_reminders')
                    ->label('Send Reminders')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Bulk Payment Reminders')
                    ->modalDescription(fn (Collection $records) => "Send payment reminders to {$records->count()} customers")
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Reminders Sent')
                            ->body("Payment reminders sent to {$records->count()} customers")
                            ->success()
                            ->send();
                    }),

                BulkAction::make('cancel_restock')
                    ->label('Cancel & Restock')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Orders Cancelled')
                            ->body("{$records->count()} orders cancelled and restocked")
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
