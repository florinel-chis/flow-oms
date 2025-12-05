<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Orders are synced from Magento, not created manually
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
