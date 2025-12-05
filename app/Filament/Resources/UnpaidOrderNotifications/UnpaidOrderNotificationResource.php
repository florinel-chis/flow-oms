<?php

namespace App\Filament\Resources\UnpaidOrderNotifications;

use App\Filament\Resources\UnpaidOrderNotifications\Pages\CreateUnpaidOrderNotification;
use App\Filament\Resources\UnpaidOrderNotifications\Pages\EditUnpaidOrderNotification;
use App\Filament\Resources\UnpaidOrderNotifications\Pages\ListUnpaidOrderNotifications;
use App\Filament\Resources\UnpaidOrderNotifications\Schemas\UnpaidOrderNotificationForm;
use App\Filament\Resources\UnpaidOrderNotifications\Tables\UnpaidOrderNotificationsTable;
use App\Models\UnpaidOrderNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnpaidOrderNotificationResource extends Resource
{
    protected static ?string $model = UnpaidOrderNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Order Notifications';

    protected static ?string $modelLabel = 'Unpaid Order Notification';

    protected static ?string $pluralModelLabel = 'Unpaid Order Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'Automation';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return UnpaidOrderNotificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnpaidOrderNotificationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // Read-only resource
    }

    public static function canEdit($record): bool
    {
        return false; // Read-only resource
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnpaidOrderNotifications::route('/'),
        ];
    }
}
