<?php

namespace App\Filament\Resources\MagentoStoreResource\Pages;

use App\Filament\Resources\MagentoStoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMagentoStore extends EditRecord
{
    protected static string $resource = MagentoStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
