<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SlaShippingOrdersWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('increment_id')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Order $record) => route('filament.admin.resources.orders.view', ['tenant' => $record->tenant, 'record' => $record]))
                    ->openUrlInNewTab(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                TextColumn::make('shipping_method')
                    ->label('Shipping')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'same') => 'danger',
                        str_contains(strtolower($state), 'overnight') => 'warning',
                        str_contains(strtolower($state), 'express') => 'info',
                        default => 'gray',
                    })
                    ->limit(20),

                TextColumn::make('sla_deadline')
                    ->label('SLA Deadline')
                    ->dateTime('M d, H:i')
                    ->sortable()
                    ->description(fn (Order $record) => $this->getSlaCountdown($record)),

                BadgeColumn::make('urgency_level')
                    ->label('Urgency')
                    ->getStateUsing(fn (Order $record) => $this->getUrgencyLevel($record))
                    ->colors([
                        'danger' => 'Immediate',
                        'warning' => 'Urgent',
                        'info' => 'At Risk',
                        'success' => 'On Track',
                        'gray' => 'Breached',
                    ])
                    ->icons([
                        'heroicon-o-exclamation-triangle' => 'Immediate',
                        'heroicon-o-clock' => 'Urgent',
                        'heroicon-o-clock' => 'At Risk',
                        'heroicon-o-check-circle' => 'On Track',
                        'heroicon-o-x-circle' => 'Breached',
                    ]),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'complete' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('ordered_at')
                    ->label('Ordered')
                    ->dateTime('M d, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'complete' => 'Complete',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->toolbarActions([
                Action::make('refresh')
                    ->label('Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn () => $this->dispatch('$refresh')),
            ])
            ->bulkActions([
                BulkAction::make('mark_high_priority')
                    ->label('Mark High Priority')
                    ->icon('heroicon-o-flag')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each->update(['priority' => 'high']);

                        Notification::make()
                            ->success()
                            ->title('Orders marked as high priority')
                            ->body(count($records) . ' orders updated.')
                            ->send();
                    }),

                BulkAction::make('send_notification')
                    ->label('Send Notification')
                    ->icon('heroicon-o-bell')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $results = app(EmailNotificationService::class)->sendBulkSlaBreachNotifications($records);

                        if ($results['sent'] > 0) {
                            Notification::make()
                                ->success()
                                ->title('Notifications sent successfully')
                                ->body("Sent {$results['sent']} notification(s). Failed: {$results['failed']}")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Failed to send notifications')
                                ->body("All {$results['failed']} notification(s) failed to send. Check logs for details.")
                                ->send();
                        }
                    }),

                BulkAction::make('assign_picker')
                    ->label('Assign Picker')
                    ->icon('heroicon-o-user-plus')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        // TODO: Implement picker assignment logic
                        Notification::make()
                            ->info()
                            ->title('Picker assignment')
                            ->body('Orders assigned to picker for ' . count($records) . ' orders.')
                            ->send();
                    }),
            ])
            ->defaultSort('sla_deadline', 'asc')
            ->striped()
            ->heading('Orders Requiring Attention');
    }

    protected function getTableQuery(): Builder
    {
        $filters = $this->filters ?? [];
        $urgency = $filters['urgency'] ?? 'all';
        $shippingPriority = $filters['shipping_priority'] ?? 'all';

        $query = Order::query()
            ->whereNotNull('sla_deadline')
            ->whereNull('shipped_at');

        // Apply urgency filter
        if ($urgency !== 'all') {
            $now = now();

            match ($urgency) {
                'immediate' => $query->whereBetween('sla_deadline', [$now, $now->copy()->addHours(2)]),
                'urgent' => $query->whereBetween('sla_deadline', [$now->copy()->addHours(2), $now->copy()->addHours(6)]),
                'at_risk' => $query->whereBetween('sla_deadline', [$now->copy()->addHours(6), $now->copy()->addHours(24)]),
                'breached' => $query->where('sla_deadline', '<=', $now),
                default => null,
            };
        }

        // Apply shipping priority filter
        if ($shippingPriority !== 'all') {
            $pattern = match ($shippingPriority) {
                'same_day' => '%same%day%',
                'overnight' => '%overnight%',
                'express' => '%express%',
                'standard' => '%standard%',
                default => '%',
            };
            $query->where('shipping_method', 'like', $pattern);
        }

        return $query;
    }

    protected function getSlaCountdown(Order $record): string
    {
        if (!$record->sla_deadline) {
            return 'No SLA';
        }

        $now = now();
        $deadline = Carbon::parse($record->sla_deadline);

        if ($deadline->isPast()) {
            $diff = $now->diff($deadline);
            return sprintf('⚠️ Breached %dd %dh %dm ago', $diff->days, $diff->h, $diff->i);
        }

        $diff = $now->diff($deadline);

        if ($diff->days > 0) {
            return sprintf('%dd %dh %dm remaining', $diff->days, $diff->h, $diff->i);
        }

        if ($diff->h > 0) {
            return sprintf('%dh %dm remaining', $diff->h, $diff->i);
        }

        return sprintf('%dm remaining', $diff->i);
    }

    protected function getUrgencyLevel(Order $record): string
    {
        if (!$record->sla_deadline) {
            return 'Unknown';
        }

        $now = now();
        $deadline = Carbon::parse($record->sla_deadline);

        if ($deadline->isPast()) {
            return 'Breached';
        }

        $hoursRemaining = $now->diffInHours($deadline);

        return match (true) {
            $hoursRemaining < 2 => 'Immediate',
            $hoursRemaining < 6 => 'Urgent',
            $hoursRemaining < 24 => 'At Risk',
            default => 'On Track',
        };
    }
}
