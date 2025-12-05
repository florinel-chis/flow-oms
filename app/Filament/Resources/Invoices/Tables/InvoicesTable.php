<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('increment_id')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.view', [
                        'tenant' => filament()->getTenant(),
                        'record' => $record,
                    ])),

                TextColumn::make('order.increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Invoice $record) => $record->order
                        ? route('filament.admin.resources.orders.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record->order,
                        ])
                        : null),

                TextColumn::make('magentoStore.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (Invoice $record): string => $record->customer_name ?? ''),

                BadgeColumn::make('state')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'open',
                        'danger' => 'canceled',
                    ]),

                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('subtotal')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tax_amount')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('invoiced_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options([
                        'paid' => 'Paid',
                        'open' => 'Open',
                        'canceled' => 'Canceled',
                    ]),

                SelectFilter::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'increment_id')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('magento_store_id')
                    ->label('Store')
                    ->relationship('magentoStore', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('invoiced_at')
                    ->form([
                        DatePicker::make('invoiced_from')
                            ->label('Invoiced From'),
                        DatePicker::make('invoiced_until')
                            ->label('Invoiced Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['invoiced_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoiced_at', '>=', $date),
                            )
                            ->when(
                                $data['invoiced_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoiced_at', '<=', $date),
                            );
                    }),

                Filter::make('amount_range')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('Min Amount')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('max_amount')
                            ->label('Max Amount')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('grand_total', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('grand_total', '<=', $amount),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_selected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            Notification::make()
                                ->title('Invoices Exported')
                                ->body("Exported {$records->count()} invoices")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('invoiced_at', 'desc');
    }
}
