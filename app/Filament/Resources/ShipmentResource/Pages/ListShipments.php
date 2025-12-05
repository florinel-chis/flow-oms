<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Resources\Pages\ListRecords;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Shipments are synced from Magento, not created manually
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
