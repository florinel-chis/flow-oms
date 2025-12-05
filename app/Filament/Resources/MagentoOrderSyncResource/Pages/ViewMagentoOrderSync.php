<?php

namespace App\Filament\Resources\MagentoOrderSyncResource\Pages;

use App\Filament\Resources\MagentoOrderSyncResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewMagentoOrderSync extends ViewRecord
{
    protected static string $resource = MagentoOrderSyncResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->schema([
                        Placeholder::make('id')
                            ->label('Sync Record ID')
                            ->content(fn ($record) => $record->id),

                        Placeholder::make('entity_id')
                            ->label('Magento Entity ID')
                            ->content(fn ($record) => $record->entity_id),

                        Placeholder::make('increment_id')
                            ->label('Order Number')
                            ->content(fn ($record) => $record->increment_id),

                        Placeholder::make('order_status')
                            ->label('Order Status')
                            ->content(fn ($record) => $record->order_status),

                        Placeholder::make('order_state')
                            ->label('Order State')
                            ->content(fn ($record) => $record->order_state ?? 'N/A'),

                        Placeholder::make('magentoStore.name')
                            ->label('Magento Store')
                            ->content(fn ($record) => $record->magentoStore?->name ?? 'N/A'),
                    ])
                    ->columns(3),

                Section::make('Customer Information')
                    ->schema([
                        Placeholder::make('customer_name')
                            ->label('Customer Name')
                            ->content(fn ($record) => $record->customer_name ?? 'N/A'),

                        Placeholder::make('customer_email')
                            ->label('Customer Email')
                            ->content(fn ($record) => $record->customer_email ?? 'N/A'),
                    ])
                    ->columns(2),

                Section::make('Order Details')
                    ->schema([
                        Placeholder::make('formatted_grand_total')
                            ->label('Grand Total')
                            ->content(fn ($record) => $record->formatted_grand_total),

                        Placeholder::make('currency_code')
                            ->label('Currency')
                            ->content(fn ($record) => $record->currency_code),

                        Placeholder::make('total_qty_ordered')
                            ->label('Total Qty Ordered')
                            ->content(fn ($record) => $record->total_qty_ordered ?? 'N/A'),

                        Placeholder::make('total_qty_invoiced')
                            ->label('Total Qty Invoiced')
                            ->content(fn ($record) => $record->total_qty_invoiced ?? '0'),

                        Placeholder::make('total_qty_shipped')
                            ->label('Total Qty Shipped')
                            ->content(fn ($record) => $record->total_qty_shipped ?? '0'),

                        Placeholder::make('payment_method')
                            ->label('Payment Method')
                            ->content(fn ($record) => $record->payment_method ?? 'N/A'),

                        Placeholder::make('shipping_description')
                            ->label('Shipping Method')
                            ->content(fn ($record) => $record->shipping_description ?? 'N/A'),
                    ])
                    ->columns(3),

                Section::make('Sync Status')
                    ->schema([
                        Placeholder::make('has_invoice')
                            ->label('Has Invoice')
                            ->content(fn ($record) => $record->has_invoice ? 'Yes ✓' : 'No ✗'),

                        Placeholder::make('has_shipment')
                            ->label('Has Shipment')
                            ->content(fn ($record) => $record->has_shipment ? 'Yes ✓' : 'No ✗'),

                        Placeholder::make('is_fully_invoiced')
                            ->label('Fully Invoiced')
                            ->content(fn ($record) => $record->is_fully_invoiced ? 'Yes ✓' : 'No ✗'),

                        Placeholder::make('is_fully_shipped')
                            ->label('Fully Shipped')
                            ->content(fn ($record) => $record->is_fully_shipped ? 'Yes ✓' : 'No ✗'),

                        Placeholder::make('sync_batch_id')
                            ->label('Sync Batch ID')
                            ->content(fn ($record) => $record->sync_batch_id ?? 'N/A'),

                        Placeholder::make('synced_at')
                            ->label('Synced At')
                            ->content(fn ($record) => $record->synced_at?->format('Y-m-d H:i:s') ?? 'N/A'),
                    ])
                    ->columns(3),

                Section::make('Dates')
                    ->schema([
                        Placeholder::make('magento_created_at')
                            ->label('Created in Magento')
                            ->content(fn ($record) => $record->magento_created_at ?? 'N/A'),

                        Placeholder::make('magento_updated_at')
                            ->label('Updated in Magento')
                            ->content(fn ($record) => $record->magento_updated_at ?? 'N/A'),

                        Placeholder::make('created_at')
                            ->label('First Synced At')
                            ->content(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),

                        Placeholder::make('updated_at')
                            ->label('Last Updated At')
                            ->content(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Raw Order Data')
                    ->schema([
                        Placeholder::make('raw_data')
                            ->label('JSON Data')
                            ->content(fn ($record) => '<pre class="text-xs overflow-auto">'.json_encode($record->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).'</pre>')
                            ->extraAttributes(['class' => 'font-mono']),
                    ])
                    ->description('Complete JSON data from Magento API')
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_json')
                ->label('View JSON')
                ->icon('heroicon-o-code-bracket')
                ->color('success')
                ->url(fn () => route('magento-order-sync.json', $this->record->id))
                ->openUrlInNewTab(),

            Action::make('open_in_magento')
                ->label('Open in Magento')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('primary')
                ->url(fn () => $this->record->getMagentoUrl())
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->getMagentoUrl() !== null),

            Action::make('copy_json')
                ->label('Copy JSON')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->action(function () {
                    $this->js('navigator.clipboard.writeText('.json_encode(json_encode($this->record->raw_data, JSON_PRETTY_PRINT)).')');

                    \Filament\Notifications\Notification::make()
                        ->title('JSON Copied')
                        ->body('Raw order data has been copied to clipboard')
                        ->success()
                        ->send();
                }),
        ];
    }
}
