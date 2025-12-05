<?php

namespace App\Filament\Resources\UnpaidOrderNotifications\Tables;

use App\Enums\NotificationType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UnpaidOrderNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record->order,
                    ]) : null),

                BadgeColumn::make('notification_type')
                    ->label('Type')
                    ->colors([
                        'warning' => fn ($state) => $state === NotificationType::WARNING,
                        'danger' => fn ($state) => $state === NotificationType::CANCELLATION,
                    ])
                    ->icons([
                        'heroicon-o-exclamation-triangle' => fn ($state) => $state === NotificationType::WARNING,
                        'heroicon-o-x-circle' => fn ($state) => $state === NotificationType::CANCELLATION,
                    ]),

                TextColumn::make('triggered_at')
                    ->label('Triggered')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->triggered_at->diffForHumans()),

                TextColumn::make('hours_unpaid')
                    ->label('Hours Unpaid')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('h')
                    ->color(fn ($state) => match (true) {
                        $state >= 72 => 'danger',
                        $state >= 48 => 'warning',
                        default => 'gray',
                    }),

                BadgeColumn::make('sent_successfully')
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

                TextColumn::make('response_status')
                    ->label('HTTP Status')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state >= 200 && $state < 300,
                        'warning' => fn ($state) => $state >= 300 && $state < 400,
                        'danger' => fn ($state) => $state >= 400,
                    ])
                    ->formatStateUsing(fn ($state) => $state ?: 'N/A'),

                TextColumn::make('retry_count')
                    ->label('Retries')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'success',
                        $state <= 2 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('order.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('order.customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('endpoint_url')
                    ->label('Endpoint')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('None'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('notification_type')
                    ->label('Type')
                    ->options([
                        'warning' => 'Warning',
                        'cancellation' => 'Cancellation',
                    ]),

                TernaryFilter::make('sent_successfully')
                    ->label('Status')
                    ->placeholder('All notifications')
                    ->trueLabel('Successfully sent')
                    ->falseLabel('Failed'),

                SelectFilter::make('response_status')
                    ->label('HTTP Status')
                    ->options([
                        '200-299' => '2xx Success',
                        '400-499' => '4xx Client Error',
                        '500-599' => '5xx Server Error',
                    ])
                    ->query(function ($query, $state) {
                        return match ($state['value'] ?? null) {
                            '200-299' => $query->whereBetween('response_status', [200, 299]),
                            '400-499' => $query->whereBetween('response_status', [400, 499]),
                            '500-599' => $query->whereBetween('response_status', [500, 599]),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn ($record) => "Notification Details: {$record->order->increment_id}")
                    ->modalContent(fn ($record) => view('filament.resources.unpaid-order-notification.view-details', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->poll('30s');
    }
}
