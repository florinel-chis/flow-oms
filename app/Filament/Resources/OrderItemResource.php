<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderItemResource\Pages;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    // OrderItem belongs to tenant indirectly through Order
    protected static bool $isScopedToTenant = false;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-queue-list';
    }

    public static function getNavigationGroup(): string|BackedEnum|null
    {
        return 'Orders';
    }

    public static function getNavigationLabel(): string
    {
        return 'Order Items';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
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
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Simple'),

                        TextInput::make('name')
                            ->label('Product Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('magento_item_id')
                            ->label('Magento Item ID')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('parent.name')
                            ->label('Parent Product')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('None (standalone or parent item)'),
                    ])
                    ->columns(2),

                Section::make('Quantities')
                    ->schema([
                        TextInput::make('qty_ordered')
                            ->label('Qty Ordered')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('qty_shipped')
                            ->label('Qty Shipped')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('qty_canceled')
                            ->label('Qty Canceled')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('row_total')
                            ->label('Row Total')
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

                        TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Bundle/Configurable Items')
                    ->description('This product contains the following child items')
                    ->schema([
                        Repeater::make('children')
                            ->label('')
                            ->relationship('children')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Product Name')
                                    ->disabled()
                                    ->columnSpan(2),

                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->disabled(),

                                TextInput::make('product_type')
                                    ->label('Type')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'simple')),

                                TextInput::make('qty_ordered')
                                    ->label('Quantity')
                                    ->disabled()
                                    ->numeric(),

                                TextInput::make('price')
                                    ->label('Price')
                                    ->disabled()
                                    ->prefix('$')
                                    ->numeric()
                                    ->visible(fn ($state) => $state > 0),
                            ])
                            ->columns(5)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0),
                    ])
                    ->visible(fn (?OrderItem $record): bool => $record && $record->children->isNotEmpty())
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn (OrderItem $record): string => route('filament.admin.resources.orders.view', [
                        'tenant' => filament()->getTenant(),
                        'record' => $record->order_id,
                    ]))
                    ->width('120px'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->width('150px')
                    ->wrap(),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->grow()
                    ->wrap()
                    ->formatStateUsing(fn (OrderItem $record): string => $record->is_child ? '  └─ '.$record->name : $record->name
                    ),

                BadgeColumn::make('product_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'bundle',
                        'success' => 'configurable',
                        'secondary' => 'simple',
                    ])
                    ->icons([
                        'heroicon-o-cube' => 'bundle',
                        'heroicon-o-cog-6-tooth' => 'configurable',
                        'heroicon-o-cube-transparent' => 'simple',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Simple')
                    ->sortable()
                    ->width('120px'),

                TextColumn::make('parent.name')
                    ->label('Parent Product')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('180px'),

                TextColumn::make('qty_ordered')
                    ->label('Ordered')
                    ->numeric()
                    ->alignCenter()
                    ->width('90px'),

                TextColumn::make('qty_shipped')
                    ->label('Shipped')
                    ->numeric()
                    ->alignCenter()
                    ->color(fn (OrderItem $record): string => match (true) {
                        $record->is_fully_shipped => 'success',
                        $record->qty_shipped > 0 => 'warning',
                        default => 'gray',
                    })
                    ->width('90px'),

                TextColumn::make('qty_canceled')
                    ->label('Canceled')
                    ->numeric()
                    ->alignCenter()
                    ->color(fn (OrderItem $record): string => $record->qty_canceled > 0 ? 'danger' : 'gray')
                    ->width('100px'),

                TextColumn::make('price')
                    ->money('USD')
                    ->width('100px')
                    ->alignEnd(),

                TextColumn::make('row_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->width('110px')
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('120px'),
            ])
            ->filters([
                SelectFilter::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'increment_id')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('product_type')
                    ->label('Product Type')
                    ->options([
                        'simple' => 'Simple',
                        'bundle' => 'Bundle',
                        'configurable' => 'Configurable',
                    ]),

                SelectFilter::make('has_parent')
                    ->label('Hierarchy')
                    ->options([
                        'parent' => 'Parent Items Only',
                        'child' => 'Child Items Only',
                        'standalone' => 'Standalone Items Only',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'parent' => $query->whereIn('product_type', ['bundle', 'configurable']),
                            'child' => $query->whereNotNull('parent_item_id'),
                            'standalone' => $query->whereNull('parent_item_id')->where('product_type', 'simple'),
                            default => $query,
                        };
                    }),

                Filter::make('fulfillment_status')
                    ->label('Fulfillment Status')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'fully_shipped' => 'Fully Shipped',
                                'partially_shipped' => 'Partially Shipped',
                                'unfulfilled' => 'Unfulfilled',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['status'])) {
                            return $query;
                        }

                        return match ($data['status']) {
                            'fully_shipped' => $query->whereRaw('qty_shipped >= qty_ordered'),
                            'partially_shipped' => $query->where('qty_shipped', '>', 0)
                                ->whereRaw('qty_shipped < qty_ordered'),
                            'unfulfilled' => $query->where('qty_shipped', 0),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListOrderItems::route('/'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
        ];
    }
}
