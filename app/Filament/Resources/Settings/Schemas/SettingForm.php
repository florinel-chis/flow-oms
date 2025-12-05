<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Select::make('group')
                    ->label('Setting Group')
                    ->options([
                        'ready_to_ship' => 'Ready to Ship',
                        'sla' => 'SLA Management',
                        'notifications' => 'Notifications',
                        'dashboard' => 'Dashboard',
                        'system' => 'System',
                    ])
                    ->required()
                    ->searchable()
                    ->columnSpan(1),

                TextInput::make('key')
                    ->label('Setting Key')
                    ->placeholder('e.g., payment_statuses, order_statuses')
                    ->required()
                    ->columnSpan(1),

                Select::make('type')
                    ->label('Value Type')
                    ->options([
                        'string' => 'Text',
                        'json' => 'Array/JSON',
                        'boolean' => 'Boolean',
                        'integer' => 'Number',
                    ])
                    ->required()
                    ->default('string')
                    ->live()
                    ->columnSpan(1),

                TagsInput::make('value')
                    ->label('Value (Array)')
                    ->placeholder('Add items...')
                    ->helperText('Enter values and press Enter to add each item')
                    ->visible(fn ($get) => $get('type') === 'json')
                    ->columnSpanFull(),

                Textarea::make('value')
                    ->label('Value')
                    ->rows(3)
                    ->visible(fn ($get) => $get('type') === 'string')
                    ->columnSpanFull(),

                TextInput::make('value')
                    ->label('Value')
                    ->numeric()
                    ->visible(fn ($get) => $get('type') === 'integer')
                    ->columnSpanFull(),

                Toggle::make('value')
                    ->label('Enabled')
                    ->visible(fn ($get) => $get('type') === 'boolean')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Explain what this setting controls...')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
