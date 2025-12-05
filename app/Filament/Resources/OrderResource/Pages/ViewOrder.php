<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Details')
                    ->schema([
                        TextEntry::make('increment_id')
                            ->label('Order #'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'complete' => 'success',
                                'processing' => 'primary',
                                'pending' => 'warning',
                                'canceled' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('payment_status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'partially_paid' => 'info',
                                'failed' => 'danger',
                                'refunded' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('magentoStore.name')
                            ->label('Store'),

                        TextEntry::make('ordered_at')
                            ->dateTime(),

                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer_name'),

                        TextEntry::make('customer_email')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),
                    ])
                    ->columns(2),

                Section::make('Totals')
                    ->schema([
                        TextEntry::make('subtotal')
                            ->money('USD'),

                        TextEntry::make('tax_amount')
                            ->money('USD'),

                        TextEntry::make('shipping_amount')
                            ->money('USD'),

                        TextEntry::make('discount_amount')
                            ->money('USD'),

                        TextEntry::make('grand_total')
                            ->money('USD')
                            ->weight('bold')
                            ->color('success'),
                    ])
                    ->columns(3),

                Section::make('Invoices')
                    ->schema([
                        RepeatableEntry::make('invoices')
                            ->schema([
                                TextEntry::make('increment_id')
                                    ->label('Invoice #')
                                    ->url(fn ($record) => route('filament.admin.resources.invoices.view', [
                                        'tenant' => filament()->getTenant(),
                                        'record' => $record,
                                    ])),

                                TextEntry::make('state')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'paid' => 'success',
                                        'open' => 'warning',
                                        'canceled' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('grand_total')
                                    ->money('USD')
                                    ->weight('bold'),

                                TextEntry::make('invoiced_at')
                                    ->dateTime(),

                                TextEntry::make('customer_email')
                                    ->copyable()
                                    ->icon('heroicon-m-envelope'),
                            ])
                            ->columns(5),
                    ])
                    ->visible(fn ($record) => $record->invoices()->exists()),

                Section::make('Shipments')
                    ->schema([
                        RepeatableEntry::make('shipments')
                            ->schema([
                                TextEntry::make('increment_id')
                                    ->label('Shipment #'),

                                TextEntry::make('carrier')
                                    ->label('Carrier'),

                                TextEntry::make('tracking_number')
                                    ->copyable(),

                                TextEntry::make('shipped_at')
                                    ->dateTime(),
                            ])
                            ->columns(4),
                    ])
                    ->visible(fn ($record) => $record->shipments()->exists()),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('sku')
                                    ->label('SKU'),

                                TextEntry::make('name')
                                    ->label('Product'),

                                TextEntry::make('qty_ordered')
                                    ->label('Quantity'),

                                TextEntry::make('price')
                                    ->money('USD')
                                    ->label('Unit Price'),

                                TextEntry::make('row_total')
                                    ->money('USD')
                                    ->label('Total')
                                    ->weight('bold'),
                            ])
                            ->columns(5),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
