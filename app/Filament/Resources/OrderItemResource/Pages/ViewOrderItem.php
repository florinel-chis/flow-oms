<?php

namespace App\Filament\Resources\OrderItemResource\Pages;

use App\Filament\Resources\OrderItemResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewOrderItem extends ViewRecord
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Order items cannot be edited directly
        ];
    }

    protected function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load(['children', 'parent', 'order']);
    }
}
