<?php

namespace App\Filament\Resources\MagentoProducts\Pages;

use App\Filament\Resources\MagentoProducts\MagentoProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMagentoProduct extends EditRecord
{
    protected static string $resource = MagentoProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load the stockItem relationship
        $this->record->load('stockItem');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
