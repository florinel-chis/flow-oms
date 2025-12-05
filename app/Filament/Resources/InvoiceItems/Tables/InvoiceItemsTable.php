<?php

namespace App\Filament\Resources\InvoiceItems\Tables;

use App\Models\InvoiceItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.increment_id')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->url(fn (InvoiceItem $record) => $record->invoice
                        ? route('filament.admin.resources.invoices.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record->invoice,
                        ])
                        : null),

                TextColumn::make('invoice.order.increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn (InvoiceItem $record) => $record->invoice?->order
                        ? route('filament.admin.resources.orders.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record->invoice->order,
                        ])
                        : null),

                TextColumn::make('product_name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (InvoiceItem $record): string => $record->product_name),

                TextColumn::make('sku')
                    ->searchable()
                    ->limit(20)
                    ->fontFamily('mono')
                    ->tooltip(fn (InvoiceItem $record): string => $record->sku),

                TextColumn::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('price')
                    ->label('Unit Price')
                    ->money('USD'),

                TextColumn::make('tax_amount')
                    ->label('Tax')
                    ->money('USD'),

                TextColumn::make('row_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('invoice_id')
                    ->label('Invoice')
                    ->relationship('invoice', 'increment_id')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('order_id')
                    ->label('Order')
                    ->relationship('invoice.order', 'increment_id')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('magento_store_id')
                    ->label('Store')
                    ->relationship('invoice.magentoStore', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
