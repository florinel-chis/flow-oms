<?php

namespace App\Filament\Resources\MagentoStoreResource\Pages;

use App\Filament\Resources\MagentoStoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMagentoStores extends ListRecords
{
    protected static string $resource = MagentoStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
