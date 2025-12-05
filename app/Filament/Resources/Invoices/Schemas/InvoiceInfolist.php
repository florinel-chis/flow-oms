<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->schema([
                        Placeholder::make('increment_id')
                            ->label('Invoice #')
                            ->content(fn ($record) => $record->increment_id),

                        Placeholder::make('state')
                            ->label('State')
                            ->content(fn ($record) => $record->state_label),

                        Placeholder::make('order.increment_id')
                            ->label('Order #')
                            ->content(fn ($record) => $record->order?->increment_id ?? 'N/A'),

                        Placeholder::make('magentoStore.name')
                            ->label('Store')
                            ->content(fn ($record) => $record->magentoStore?->name ?? 'N/A'),

                        Placeholder::make('invoiced_at')
                            ->label('Invoiced At')
                            ->content(fn ($record) => $record->invoiced_at?->format('Y-m-d H:i:s') ?? 'N/A'),

                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
                    ])
                    ->columns(2),

                Section::make('Customer')
                    ->schema([
                        Placeholder::make('customer_name')
                            ->label('Customer Name')
                            ->content(fn ($record) => $record->customer_name),

                        Placeholder::make('customer_email')
                            ->label('Customer Email')
                            ->content(fn ($record) => $record->customer_email),
                    ])
                    ->columns(2),

                Section::make('Totals')
                    ->schema([
                        Placeholder::make('subtotal')
                            ->label('Subtotal')
                            ->content(fn ($record) => $record->formatted_subtotal),

                        Placeholder::make('tax_amount')
                            ->label('Tax Amount')
                            ->content(fn ($record) => $record->formatted_tax_amount),

                        Placeholder::make('shipping_amount')
                            ->label('Shipping Amount')
                            ->content(fn ($record) => $record->formatted_shipping_amount),

                        Placeholder::make('discount_amount')
                            ->label('Discount Amount')
                            ->content(fn ($record) => $record->formatted_discount_amount),

                        Placeholder::make('grand_total')
                            ->label('Grand Total')
                            ->content(fn ($record) => $record->formatted_grand_total),
                    ])
                    ->columns(3),

                Section::make('Invoice Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Placeholder::make('sku')
                                    ->label('SKU')
                                    ->content(fn ($record) => $record->sku),

                                Placeholder::make('product_name')
                                    ->label('Product')
                                    ->content(fn ($record) => $record->product_name),

                                Placeholder::make('qty')
                                    ->label('Quantity')
                                    ->content(fn ($record) => $record->qty),

                                Placeholder::make('price')
                                    ->label('Unit Price')
                                    ->content(fn ($record) => '$'.number_format($record->price, 2)),

                                Placeholder::make('tax_amount')
                                    ->label('Tax')
                                    ->content(fn ($record) => '$'.number_format($record->tax_amount, 2)),

                                Placeholder::make('row_total')
                                    ->label('Total')
                                    ->content(fn ($record) => '$'.number_format($record->row_total, 2)),
                            ])
                            ->columns(6)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ]);
    }
}
