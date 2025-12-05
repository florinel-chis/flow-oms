<?php

namespace App\Filament\Widgets;

use App\Enums\NotificationType;
use App\Models\UnpaidOrderNotification;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class UnpaidOrderNotificationsActivityWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Recent Notification Activity';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    protected static ?string $maxHeight = '500px';

    public function table(Table $table): Table
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        return $table
            ->query(
                UnpaidOrderNotification::query()
                    ->whereBetween('triggered_at', [$dateRange['start'], $dateRange['end']])
                    ->when($storeId, function ($q) use ($storeId) {
                        return $q->whereHas('order', fn($q) => $q->where('magento_store_id', $storeId));
                    })
                    ->with(['order'])
                    ->orderByDesc('triggered_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('triggered_at')
                    ->label('Time')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->triggered_at->diffForHumans()),

                Tables\Columns\TextColumn::make('order.increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record->order,
                    ]) : null),

                Tables\Columns\BadgeColumn::make('notification_type')
                    ->label('Type')
                    ->colors([
                        'warning' => fn ($state) => $state === NotificationType::WARNING,
                        'danger' => fn ($state) => $state === NotificationType::CANCELLATION,
                    ])
                    ->icons([
                        'heroicon-o-exclamation-triangle' => fn ($state) => $state === NotificationType::WARNING,
                        'heroicon-o-x-circle' => fn ($state) => $state === NotificationType::CANCELLATION,
                    ]),

                Tables\Columns\TextColumn::make('order.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('hours_unpaid')
                    ->label('Hours Unpaid')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('h')
                    ->color(fn ($state) => match (true) {
                        $state >= 72 => 'danger',
                        $state >= 48 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('sent_successfully')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Sent' : 'Failed')
                    ->colors([
                        'success' => fn ($state) => $state === true,
                        'danger' => fn ($state) => $state === false,
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => fn ($state) => $state === true,
                        'heroicon-o-x-circle' => fn ($state) => $state === false,
                    ]),

                Tables\Columns\TextColumn::make('response_status')
                    ->label('HTTP')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state >= 200 && $state < 300,
                        'warning' => fn ($state) => $state >= 300 && $state < 400,
                        'danger' => fn ($state) => $state >= 400,
                    ])
                    ->formatStateUsing(fn ($state) => $state ?: 'N/A'),

                Tables\Columns\TextColumn::make('retry_count')
                    ->label('Retries')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'success',
                        $state <= 2 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('endpoint_url')
                    ->label('Endpoint')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('notification_type')
                    ->label('Type')
                    ->options([
                        'warning' => 'Warning',
                        'cancellation' => 'Cancellation',
                    ]),

                Tables\Filters\TernaryFilter::make('sent_successfully')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Sent')
                    ->falseLabel('Failed'),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Notification Details: {$record->order->increment_id}")
                    ->modalContent(fn ($record) => view('filament.resources.unpaid-order-notification.view-details', ['record' => $record]))
                    ->modalWidth('4xl')
                    ->slideOver(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('30s');
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
