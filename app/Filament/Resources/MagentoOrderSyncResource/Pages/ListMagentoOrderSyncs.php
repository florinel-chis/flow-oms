<?php

namespace App\Filament\Resources\MagentoOrderSyncResource\Pages;

use App\Filament\Resources\MagentoOrderSyncResource;
use App\Jobs\SyncMagentoOrdersJob;
use App\Models\MagentoStore;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMagentoOrderSyncs extends ListRecords
{
    protected static string $resource = MagentoOrderSyncResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_orders')
                ->label('Sync Orders')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->form([
                    Select::make('magento_store_id')
                        ->label('Magento Store')
                        ->options(fn () => MagentoStore::query()
                            ->where('is_active', true)
                            ->where('sync_enabled', true)
                            ->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->helperText('Select the Magento store to sync orders from'),

                    TextInput::make('days')
                        ->label('Days to Sync')
                        ->helperText('Number of days to sync from today (1-30)')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(30)
                        ->required(),

                    TextInput::make('page_size')
                        ->label('Page Size')
                        ->helperText('Number of orders to fetch per page (1-100)')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $store = MagentoStore::findOrFail($data['magento_store_id']);

                        SyncMagentoOrdersJob::dispatch(
                            store: $store,
                            days: $data['days'],
                            pageSize: $data['page_size']
                        );

                        Notification::make()
                            ->title('Sync Started')
                            ->body("Order sync has been queued for {$store->name}. This may take a few minutes depending on the number of orders.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Sync Orders from Magento')
                ->modalDescription('This will sync orders from the selected Magento store based on your criteria.')
                ->modalSubmitActionLabel('Start Sync')
                ->modalWidth('md'),
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
