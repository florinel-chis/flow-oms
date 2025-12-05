<?php

namespace App\Filament\Resources\UnpaidOrderNotifications\Pages;

use App\Filament\Resources\UnpaidOrderNotifications\UnpaidOrderNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnpaidOrderNotification extends EditRecord
{
    protected static string $resource = UnpaidOrderNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
