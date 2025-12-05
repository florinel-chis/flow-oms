<?php

namespace App\Filament\Resources\UnpaidOrderNotifications\Schemas;

use App\Enums\NotificationType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnpaidOrderNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('notification_type')
                    ->options(NotificationType::class)
                    ->required(),
                DateTimePicker::make('triggered_at')
                    ->required(),
                TextInput::make('hours_unpaid')
                    ->required()
                    ->numeric(),
                TextInput::make('endpoint_url')
                    ->url()
                    ->required(),
                TextInput::make('payload')
                    ->required(),
                TextInput::make('response_status')
                    ->numeric(),
                Textarea::make('response_body')
                    ->columnSpanFull(),
                Toggle::make('sent_successfully')
                    ->required(),
                TextInput::make('retry_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('last_retry_at'),
                Textarea::make('error_message')
                    ->columnSpanFull(),
            ]);
    }
}
