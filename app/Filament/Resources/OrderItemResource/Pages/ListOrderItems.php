<?php

namespace App\Filament\Resources\OrderItemResource\Pages;

use App\Filament\Resources\OrderItemResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderItems extends ListRecords
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Order items are synced from Magento, not created manually
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
