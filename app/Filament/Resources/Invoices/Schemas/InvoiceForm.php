<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Information')
                    ->description('Core invoice details from Magento')
                    ->schema([
                        TextInput::make('increment_id')
                            ->label('Invoice #')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('magento_invoice_id')
                            ->label('Magento Invoice ID')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('state')
                            ->label('State')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('invoiced_at')
                            ->label('Invoiced At')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Customer')
                    ->description('Customer information')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('customer_email')
                            ->label('Customer Email')
                            ->email()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Totals')
                    ->description('Financial breakdown')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('shipping_amount')
                            ->label('Shipping Amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),
            ]);
    }
}
