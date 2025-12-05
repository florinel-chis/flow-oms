<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): string|BackedEnum|null
    {
        return 'Orders';
    }

    public static function getNavigationLabel(): string
    {
        return 'Shipments';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shipment Information')
                    ->schema([
                        TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->required()
                            ->maxLength(255),

                        Select::make('carrier_code')
                            ->label('Carrier Code')
                            ->options([
                                'ups' => 'UPS',
                                'fedex' => 'FedEx',
                                'dhl' => 'DHL',
                                'usps' => 'USPS',
                                'other' => 'Other',
                            ])
                            ->required(),

                        TextInput::make('carrier_title')
                            ->label('Carrier Title')
                            ->maxLength(255),

                        TextInput::make('magento_shipment_id')
                            ->label('Magento Shipment ID')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Status & Dates')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_transit' => 'In Transit',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered' => 'Delivered',
                                'exception' => 'Exception',
                                'failed_attempt' => 'Failed Attempt',
                            ])
                            ->required(),

                        DateTimePicker::make('shipped_at')
                            ->label('Shipped At'),

                        DateTimePicker::make('estimated_delivery_at')
                            ->label('Estimated Delivery'),

                        DateTimePicker::make('actual_delivery_at')
                            ->label('Actual Delivery'),
                    ])
                    ->columns(2),

                Section::make('Tracking Updates')
                    ->schema([
                        DateTimePicker::make('last_tracking_update_at')
                            ->label('Last Tracking Update')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
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
                    ->url(fn (Shipment $record): string => route('filament.admin.resources.orders.view', [
                        'tenant' => filament()->getTenant(),
                        'record' => $record->order_id,
                    ]))
                    ->width('120px'),

                TextColumn::make('magento_shipment_id')
                    ->label('Shipment ID')
                    ->searchable()
                    ->toggleable()
                    ->width('120px'),

                TextColumn::make('tracking_number')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-m-link')
                    ->grow()
                    ->wrap(),

                BadgeColumn::make('carrier_code')
                    ->label('Carrier')
                    ->colors([
                        'warning' => 'ups',
                        'success' => 'fedex',
                        'info' => 'dhl',
                        'primary' => 'usps',
                        'gray' => 'other',
                    ])
                    ->width('100px'),

                TextColumn::make('carrier_title')
                    ->label('Carrier Name')
                    ->toggleable()
                    ->width('120px'),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'in_transit',
                        'info' => 'out_for_delivery',
                        'success' => 'delivered',
                        'danger' => 'exception',
                        'danger' => 'failed_attempt',
                    ])
                    ->width('140px'),

                TextColumn::make('shipped_at')
                    ->label('Shipped')
                    ->date()
                    ->sortable()
                    ->width('120px'),

                TextColumn::make('estimated_delivery_at')
                    ->label('Est. Delivery')
                    ->date()
                    ->sortable()
                    ->color(fn (Shipment $record): string => match (true) {
                        ! $record->estimated_delivery_at => 'gray',
                        $record->is_delayed => 'danger',
                        default => 'gray',
                    })
                    ->width('130px'),

                TextColumn::make('actual_delivery_at')
                    ->label('Delivered')
                    ->date()
                    ->sortable()
                    ->placeholder('Not delivered')
                    ->width('120px'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('150px'),
            ])
            ->filters([
                SelectFilter::make('carrier_code')
                    ->label('Carrier')
                    ->options([
                        'ups' => 'UPS',
                        'fedex' => 'FedEx',
                        'dhl' => 'DHL',
                        'usps' => 'USPS',
                        'other' => 'Other',
                    ]),

                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'exception' => 'Exception',
                        'failed_attempt' => 'Failed Attempt',
                    ]),

                Filter::make('shipped_at')
                    ->form([
                        DatePicker::make('shipped_from')
                            ->label('Shipped From'),
                        DatePicker::make('shipped_until')
                            ->label('Shipped Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['shipped_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shipped_at', '>=', $date),
                            )
                            ->when(
                                $data['shipped_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shipped_at', '<=', $date),
                            );
                    }),

                TernaryFilter::make('is_delayed')
                    ->label('Delayed')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('estimated_delivery_at')
                            ->where('status', '!=', 'delivered')
                            ->whereRaw('estimated_delivery_at < NOW()'),
                        false: fn (Builder $query) => $query->whereNull('estimated_delivery_at')
                            ->orWhere('status', 'delivered')
                            ->orWhereRaw('estimated_delivery_at >= NOW()'),
                    ),
            ])
            ->actions([
                Action::make('track')
                    ->label('Track')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(fn (Shipment $record): ?string => self::getTrackingUrl($record), shouldOpenInNewTab: true)
                    ->visible(fn (Shipment $record): bool => ! empty($record->tracking_number)),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'in_transit' => 'In Transit',
                                    'out_for_delivery' => 'Out for Delivery',
                                    'delivered' => 'Delivered',
                                    'exception' => 'Exception',
                                    'failed_attempt' => 'Failed Attempt',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (Shipment $record) use ($data) {
                                $record->update(['status' => $data['status']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('shipped_at', 'desc');
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
            'index' => Pages\ListShipments::route('/'),
            'view' => Pages\ViewShipment::route('/{record}'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }

    protected static function getTrackingUrl(Shipment $shipment): ?string
    {
        if (empty($shipment->tracking_number)) {
            return null;
        }

        return match ($shipment->carrier_code) {
            'ups' => "https://www.ups.com/track?tracknum={$shipment->tracking_number}",
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$shipment->tracking_number}",
            'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$shipment->tracking_number}",
            'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$shipment->tracking_number}",
            default => null,
        };
    }
}
