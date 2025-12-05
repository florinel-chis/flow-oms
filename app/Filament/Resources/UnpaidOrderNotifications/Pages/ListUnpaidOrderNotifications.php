<?php

namespace App\Filament\Resources\UnpaidOrderNotifications\Pages;

use App\Filament\Resources\UnpaidOrderNotifications\UnpaidOrderNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnpaidOrderNotifications extends ListRecords
{
    protected static string $resource = UnpaidOrderNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
