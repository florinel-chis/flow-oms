<?php

namespace App\Filament\Resources\MagentoStoreResource\Pages;

use App\Filament\Resources\MagentoStoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMagentoStore extends CreateRecord
{
    protected static string $resource = MagentoStoreResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
