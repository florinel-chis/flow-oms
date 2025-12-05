<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn ($state) => match ($state) {
                        'ready_to_ship' => 'info',
                        'sla' => 'warning',
                        'notifications' => 'success',
                        'dashboard' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title())
                    ->width('150px'),

                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->width('200px'),

                TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->wrap()
                    ->formatStateUsing(function ($record) {
                        $value = $record->getValue();

                        return match ($record->type) {
                            'json' => is_array($value) ? json_encode($value) : $value,
                            'boolean' => $value ? 'Yes' : 'No',
                            default => $value,
                        };
                    })
                    ->tooltip(function ($record) {
                        $value = $record->getValue();

                        if ($record->type === 'json' && is_array($value)) {
                            return json_encode($value, JSON_PRETTY_PRINT);
                        }

                        return null;
                    })
                    ->width('200px'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'string' => 'Text',
                        'json' => 'Array',
                        'boolean' => 'Boolean',
                        'integer' => 'Number',
                        default => $state,
                    })
                    ->width('100px'),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->grow(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->width('120px')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'ready_to_ship' => 'Ready to Ship',
                        'sla' => 'SLA Management',
                        'notifications' => 'Notifications',
                        'dashboard' => 'Dashboard',
                        'system' => 'System',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'string' => 'Text',
                        'json' => 'Array/JSON',
                        'boolean' => 'Boolean',
                        'integer' => 'Number',
                    ]),
            ])
            ->defaultSort('group')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
