<?php

namespace App\Filament\Resources\MagentoProducts\Pages;

use App\Filament\Resources\MagentoProducts\MagentoProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMagentoProducts extends ListRecords
{
    protected static string $resource = MagentoProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
