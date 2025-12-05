<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\EmailNotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class DelayedShipmentsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Delayed Shipments';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '400px';

    public function table(Table $table): Table
    {
        $storeId = $this->filters['magento_store_id'] ?? null;
        $dateRange = $this->getDateRange();

        return $table
            ->query(
                Order::query()
                    ->whereHas('shipments')
                    ->whereBetween('ordered_at', [$dateRange['start'], $dateRange['end']])
                    ->when($storeId, fn ($q) => $q->where('magento_store_id', $storeId))
                    ->with('shipments')
                    ->orderByDesc('ordered_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn ($record) => '#order-'.$record->increment_id),

                Tables\Columns\TextColumn::make('shipments.carrier_code')
                    ->label('Courier')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->color(fn ($state) => match (strtolower($state)) {
                        'ups' => 'warning',
                        'dhl' => 'info',
                        'fedex' => 'success',
                        'usps' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('shipments.tracking_number')
                    ->label('Tracking #')
                    ->copyable()
                    ->copyMessage('Tracking number copied')
                    ->limit(15),

                Tables\Columns\TextColumn::make('shipments.estimated_delivery_at')
                    ->label('Est. Delivery')
                    ->date()
                    ->color(fn ($record) => $record->shipments->first()?->estimated_delivery_at < now() ? 'danger' : null
                    ),

                Tables\Columns\TextColumn::make('shipments.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str($state)->title())
                    ->color(fn ($state) => match ($state) {
                        'exception' => 'danger',
                        'in_transit' => 'warning',
                        'out_for_delivery' => 'info',
                        'delivered' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('delay')
                    ->label('Delay')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $shipment = $record->shipments->first();
                        if (! $shipment || ! $shipment->estimated_delivery_at) {
                            return 'N/A';
                        }
                        $days = now()->diffInDays($shipment->estimated_delivery_at, false);
                        if ($days >= 0) {
                            return 'On time';
                        }

                        return '+'.abs($days).'d';
                    })
                    ->color(function ($record) {
                        $shipment = $record->shipments->first();
                        if (! $shipment || ! $shipment->estimated_delivery_at) {
                            return 'gray';
                        }
                        $days = now()->diffInDays($shipment->estimated_delivery_at, false);
                        if ($days >= 0) {
                            return 'success';
                        }

                        return abs($days) >= 2 ? 'danger' : 'warning';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('carrier_code')
                    ->label('Courier')
                    ->options([
                        'ups' => 'UPS',
                        'dhl' => 'DHL',
                        'fedex' => 'FedEx',
                        'usps' => 'USPS',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'],
                            fn ($query) => $query->whereHas('shipments', function ($q) use ($data) {
                                $q->where('carrier_code', $data['value']);
                            })
                        );
                    }),
            ])
            ->actions([
                Action::make('notify_customer')
                    ->label('Notify Customer')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Delay Notification')
                    ->modalDescription(fn ($record) => "Notify customer about delayed shipment for order #{$record->increment_id}")
                    ->action(function ($record) {
                        $shipment = $record->shipments->first();

                        if (! $shipment) {
                            Notification::make()
                                ->title('No Shipment Found')
                                ->body("Order #{$record->increment_id} has no shipment to notify about")
                                ->warning()
                                ->send();

                            return;
                        }

                        $sent = app(EmailNotificationService::class)->sendDelayedShipmentNotification($shipment);

                        if ($sent) {
                            Notification::make()
                                ->title('Customer Notified')
                                ->body("Delay notification sent for order #{$record->increment_id}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Notification Failed')
                                ->body("Failed to send notification for order #{$record->increment_id}. Check logs.")
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('open_tracking')
                    ->label('Open Tracking')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(function ($record) {
                        $shipment = $record->shipments->first();
                        if (! $shipment) {
                            return null;
                        }

                        return $this->getTrackingUrl($shipment->carrier_code, $shipment->tracking_number);
                    })
                    ->openUrlInNewTab()
                    ->visible(function ($record) {
                        $shipment = $record->shipments->first();

                        return $shipment && $shipment->carrier_code && $shipment->tracking_number;
                    }),

                Action::make('create_ticket')
                    ->label('Create Ticket')
                    ->icon('heroicon-o-ticket')
                    ->color('danger')
                    ->visible(fn ($record) => $record->shipments->first()?->status === 'exception')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Support Ticket Created')
                            ->body("Ticket created for order #{$record->increment_id}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('notify_customers')
                    ->label('Notify Affected Customers')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Bulk Delay Notifications')
                    ->modalDescription(fn (Collection $records) => "Send delay notifications to {$records->count()} customers")
                    ->action(function (Collection $records) {
                        // Get all shipments from the orders
                        $shipments = $records->flatMap(fn ($order) => $order->shipments);

                        $results = app(EmailNotificationService::class)->sendBulkDelayedShipmentNotifications($shipments);

                        if ($results['sent'] > 0) {
                            Notification::make()
                                ->title('Notifications Sent')
                                ->body("Sent {$results['sent']} notification(s). Failed: {$results['failed']}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to send notifications')
                                ->body("All {$results['failed']} notification(s) failed. Check logs for details.")
                                ->warning()
                                ->send();
                        }
                    }),

                BulkAction::make('create_tickets')
                    ->label('Create Support Tickets')
                    ->icon('heroicon-o-ticket')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Tickets Created')
                            ->body("{$records->count()} support tickets created")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('60s');
    }

    protected function getTrackingUrl(?string $carrier, ?string $trackingNumber): ?string
    {
        if (! $carrier || ! $trackingNumber) {
            return null;
        }

        return match (strtolower($carrier)) {
            'ups' => "https://www.ups.com/track?track=yes&trackNums={$trackingNumber}",
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$trackingNumber}",
            'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$trackingNumber}",
            'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$trackingNumber}",
            default => "#tracking-{$trackingNumber}",
        };
    }

    protected function getDateRange(): array
    {
        return match ($this->filters['date_range'] ?? 'today') {
            'today' => ['start' => today(), 'end' => now()],
            'yesterday' => ['start' => today()->subDay(), 'end' => today()->subDay()->endOfDay()],
            'last_7_days' => ['start' => now()->subDays(7), 'end' => now()],
            'last_30_days' => ['start' => now()->subDays(30), 'end' => now()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()],
            default => ['start' => today(), 'end' => now()],
        };
    }
}
