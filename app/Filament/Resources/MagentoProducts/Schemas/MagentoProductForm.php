<?php

namespace App\Filament\Resources\MagentoProducts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MagentoProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('product_type')
                            ->label('Product Type')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('name')
                            ->label('Product Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('price')
                            ->label('Price')
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn ($state) => $state == 1 ? 'Enabled' : 'Disabled')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Stock Information')
                    ->schema([
                        TextInput::make('stockItem.qty')
                            ->label('Quantity')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.stock_status')
                            ->label('Stock Status')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.is_in_stock')
                            ->label('In Stock')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.min_qty')
                            ->label('Minimum Quantity')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.min_sale_qty')
                            ->label('Min Sale Qty')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.max_sale_qty')
                            ->label('Max Sale Qty')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.manage_stock')
                            ->label('Manage Stock')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('stockItem.backorders')
                            ->label('Backorders')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                0 => 'No Backorders',
                                1 => 'Allow Qty Below 0',
                                2 => 'Allow Qty Below 0 and Notify',
                                default => 'Unknown',
                            })
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Raw Data')
                    ->description('Complete product data from Magento API')
                    ->schema([
                        Textarea::make('raw_data')
                            ->label('')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->rows(20)
                            ->disabled()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'font-mono text-xs']),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Stock Raw Data')
                    ->description('Complete stock data from Magento API')
                    ->schema([
                        Textarea::make('stockItem.raw_data')
                            ->label('')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->rows(15)
                            ->disabled()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'font-mono text-xs']),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
