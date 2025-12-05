<?php

namespace App\Filament\Resources\MagentoProducts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MagentoProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('product_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'simple' => 'success',
                        'configurable' => 'info',
                        'bundle' => 'warning',
                        'grouped' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                BadgeColumn::make('stockItem.stock_status')
                    ->label('Stock Status')
                    ->colors([
                        'success' => 'In Stock',
                        'warning' => 'Low Stock',
                        'danger' => 'Out of Stock',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'In Stock',
                        'heroicon-o-exclamation-triangle' => 'Low Stock',
                        'heroicon-o-x-circle' => 'Out of Stock',
                    ]),

                TextColumn::make('stockItem.qty')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($record) => match (true) {
                        $record->stockItem && $record->stockItem->qty <= 0 => 'danger',
                        $record->stockItem && $record->stockItem->qty <= ($record->stockItem->min_qty ?? 0) => 'warning',
                        default => 'success',
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Enabled' : 'Disabled')
                    ->colors([
                        'success' => fn ($state) => $state == 1,
                        'danger' => fn ($state) => $state != 1,
                    ]),

                TextColumn::make('created_at')
                    ->label('Synced At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_type')
                    ->label('Product Type')
                    ->options([
                        'simple' => 'Simple',
                        'configurable' => 'Configurable',
                        'bundle' => 'Bundle',
                        'grouped' => 'Grouped',
                        'virtual' => 'Virtual',
                        'downloadable' => 'Downloadable',
                    ]),

                TernaryFilter::make('stockItem.is_in_stock')
                    ->label('Stock Status')
                    ->placeholder('All products')
                    ->trueLabel('In Stock')
                    ->falseLabel('Out of Stock'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        1 => 'Enabled',
                        2 => 'Disabled',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            return $query->whereRaw("JSON_EXTRACT(raw_data, '$.status') = ?", [$data['value']]);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
