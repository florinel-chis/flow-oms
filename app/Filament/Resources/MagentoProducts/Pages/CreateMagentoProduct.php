<?php

namespace App\Filament\Resources\MagentoProducts\Pages;

use App\Filament\Resources\MagentoProducts\MagentoProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMagentoProduct extends CreateRecord
{
    protected static string $resource = MagentoProductResource::class;
}
