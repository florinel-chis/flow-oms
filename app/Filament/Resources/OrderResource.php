<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationGroup(): string|BackedEnum|null
    {
        return 'Orders';
    }

    public static function getNavigationLabel(): string
    {
        return 'Orders';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->description('Core order details from Magento')
                    ->schema([
                        TextInput::make('increment_id')
                            ->label('Order #')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('magento_order_id')
                            ->label('Magento Order ID')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'complete' => 'Complete',
                                'canceled' => 'Canceled',
                                'holded' => 'On Hold',
                            ])
                            ->required(),

                        Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'partially_paid' => 'Partially Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Customer Information')
                    ->schema([
                        TextInput::make('customer_name')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('customer_email')
                            ->email()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('shipping_amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('discount_amount')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->width('120px'),

                BadgeColumn::make('magentoStore.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable()
                    ->width('120px'),

                TextColumn::make('customer_name')
                    ->searchable()
                    ->grow()
                    ->wrap(),

                TextColumn::make('customer_email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->grow()
                    ->wrap(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'complete',
                        'danger' => 'canceled',
                        'gray' => 'holded',
                    ])
                    ->width('140px'),

                BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'info' => 'partially_paid',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ])
                    ->width('100px'),

                BadgeColumn::make('payment_method')
                    ->label('Payment Method')
                    ->width('150px'),

                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable()
                    ->width('120px')
                    ->alignEnd(),

                TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable()
                    ->width('150px'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('150px'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'complete' => 'Complete',
                        'canceled' => 'Canceled',
                        'holded' => 'On Hold',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'partially_paid' => 'Partially Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'checkmo' => 'Check/Money Order',
                        'banktransfer' => 'Bank Transfer',
                        'cashondelivery' => 'Cash on Delivery',
                        'paypal_express' => 'PayPal Express',
                        'authorizenet_directpost' => 'Authorize.net',
                        'stripe' => 'Stripe',
                    ]),

                SelectFilter::make('magento_store_id')
                    ->label('Magento Store')
                    ->relationship('magentoStore', 'name'),

                Filter::make('ordered_at')
                    ->form([
                        DatePicker::make('ordered_from')
                            ->label('Ordered From'),
                        DatePicker::make('ordered_until')
                            ->label('Ordered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ordered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['ordered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
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
            ->defaultSort('ordered_at', 'desc');
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
