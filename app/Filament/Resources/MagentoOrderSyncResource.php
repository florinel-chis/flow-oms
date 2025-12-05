<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MagentoOrderSyncResource\Pages;
use App\Models\MagentoOrderSync;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MagentoOrderSyncResource extends Resource
{
    protected static ?string $model = MagentoOrderSync::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-arrow-path-rounded-square';
    }

    public static function getNavigationLabel(): string
    {
        return 'Order Syncs';
    }

    public static function getNavigationGroup(): string|BackedEnum|null
    {
        return 'Magento';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // This resource is primarily read-only
                // Forms are not used for creating/editing
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('magentoStore.name')
                    ->label('Magento Store')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->width('140px'),

                TextColumn::make('entity_id')
                    ->label('Entity ID')
                    ->sortable()
                    ->copyable()
                    ->tooltip('Magento Order Entity ID')
                    ->width('100px'),

                TextColumn::make('increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->width('130px'),

                TextColumn::make('order_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (MagentoOrderSync $record) => $record->status_badge_color)
                    ->sortable()
                    ->width('130px'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->grow()
                    ->wrap(),

                TextColumn::make('formatted_grand_total')
                    ->label('Grand Total')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("CAST(JSON_EXTRACT(raw_data, '$.grand_total') AS DECIMAL(10,2)) {$direction}");
                    })
                    ->width('120px')
                    ->alignEnd(),

                IconColumn::make('has_invoice')
                    ->label('Invoice')
                    ->boolean()
                    ->sortable()
                    ->trueColor(fn (MagentoOrderSync $record) => $record->invoice_status_color)
                    ->falseColor('gray')
                    ->tooltip(fn (MagentoOrderSync $record) => $record->has_invoice
                        ? ($record->is_fully_invoiced ? 'Fully Invoiced' : 'Partially Invoiced')
                        : 'No Invoice')
                    ->width('90px'),

                IconColumn::make('has_shipment')
                    ->label('Shipment')
                    ->boolean()
                    ->sortable()
                    ->trueColor(fn (MagentoOrderSync $record) => $record->shipment_status_color)
                    ->falseColor('gray')
                    ->tooltip(fn (MagentoOrderSync $record) => $record->has_shipment
                        ? ($record->is_fully_shipped ? 'Fully Shipped' : 'Partially Shipped')
                        : 'No Shipment')
                    ->width('100px'),

                TextColumn::make('sync_batch_id')
                    ->label('Batch ID')
                    ->tooltip(fn (MagentoOrderSync $record) => $record->sync_batch_id)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->width('150px')
                    ->wrap(),

                TextColumn::make('synced_at')
                    ->label('Synced At')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn (MagentoOrderSync $record) => $record->synced_at?->format('Y-m-d H:i:s'))
                    ->width('140px'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('150px'),
            ])
            ->filters([
                SelectFilter::make('magento_store_id')
                    ->label('Magento Store')
                    ->relationship('magentoStore', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('order_status')
                    ->label('Order Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'complete' => 'Complete',
                        'canceled' => 'Canceled',
                        'holded' => 'Holded',
                    ])
                    ->multiple(),

                TernaryFilter::make('has_invoice')
                    ->label('Has Invoice')
                    ->placeholder('All orders')
                    ->trueLabel('With Invoice')
                    ->falseLabel('Without Invoice'),

                TernaryFilter::make('has_shipment')
                    ->label('Has Shipment')
                    ->placeholder('All orders')
                    ->trueLabel('With Shipment')
                    ->falseLabel('Without Shipment'),

                \Filament\Tables\Filters\Filter::make('synced_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('synced_from')
                            ->label('Synced From'),
                        \Filament\Forms\Components\DatePicker::make('synced_until')
                            ->label('Synced Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['synced_from'],
                                fn ($query, $date) => $query->whereDate('synced_at', '>=', $date)
                            )
                            ->when(
                                $data['synced_until'],
                                fn ($query, $date) => $query->whereDate('synced_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['synced_from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Synced from '.\Carbon\Carbon::parse($data['synced_from'])->toFormattedDateString())
                                ->removeField('synced_from');
                        }
                        if ($data['synced_until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Synced until '.\Carbon\Carbon::parse($data['synced_until'])->toFormattedDateString())
                                ->removeField('synced_until');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('open_magento')
                    ->label('Open in Magento')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (MagentoOrderSync $record) => $record->getMagentoUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (MagentoOrderSync $record) => $record->getMagentoUrl() !== null),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('synced_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMagentoOrderSyncs::route('/'),
            'view' => Pages\ViewMagentoOrderSync::route('/{record}'),
        ];
    }
}
