<?php

namespace App\Filament\Resources\UnpaidOrderNotifications\Pages;

use App\Filament\Resources\UnpaidOrderNotifications\UnpaidOrderNotificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnpaidOrderNotification extends CreateRecord
{
    protected static string $resource = UnpaidOrderNotificationResource::class;
}
