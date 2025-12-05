<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MagentoStoreResource\Pages;
use App\Jobs\SyncMagentoOrdersJob;
use App\Models\MagentoStore;
use App\Services\Magento\MagentoApiClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MagentoStoreResource extends Resource
{
    protected static ?string $model = MagentoStore::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-server-stack';
    }

    public static function getNavigationLabel(): string
    {
        return 'Magento Stores';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public static function getNavigationGroup(): string|BackedEnum|null
    {
        return 'Settings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Store Connection')
                    ->description('Configure your Magento 2 store connection')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('My Magento Store'),

                        TextInput::make('base_url')
                            ->label('Magento Base URL')
                            ->required()
                            ->url()
                            ->placeholder('https://your-store.com')
                            ->helperText('The base URL of your Magento 2 store'),

                        TextInput::make('access_token')
                            ->label('Integration Access Token')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Generate from Magento Admin > System > Integrations'),

                        Select::make('api_version')
                            ->options([
                                'V1' => 'REST API V1',
                            ])
                            ->default('V1')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Sync Settings')
                    ->schema([
                        Toggle::make('sync_enabled')
                            ->label('Enable Automatic Sync')
                            ->default(true)
                            ->helperText('Automatically sync orders from this store'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Disable to pause all operations'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->width('200px')
                    ->wrap(),

                TextColumn::make('base_url')
                    ->label('URL')
                    ->grow()
                    ->wrap()
                    ->copyable(),

                IconColumn::make('sync_enabled')
                    ->label('Sync')
                    ->boolean()
                    ->width('80px'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->width('80px'),

                TextColumn::make('last_sync_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable()
                    ->width('160px'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('150px'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('sync_enabled')
                    ->label('Sync Enabled'),
            ])
            ->actions([
                Action::make('test_connection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Test Connection')
                    ->modalDescription(fn (MagentoStore $record) => "Test connection to {$record->name}")
                    ->action(function (MagentoStore $record) {
                        try {
                            $client = new MagentoApiClient($record);
                            $result = $client->testConnection();

                            Notification::make()
                                ->title('Connection Successful')
                                ->body('Successfully connected to Magento store.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Connection Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('sync_orders')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Orders')
                    ->modalDescription(fn (MagentoStore $record) => "Sync orders from {$record->name}. This will fetch orders from Magento and store them in the sync table.")
                    ->visible(fn (MagentoStore $record) => $record->is_active && $record->sync_enabled)
                    ->form([
                        TextInput::make('days')
                            ->label('Days to Sync')
                            ->helperText('Number of days to look back for orders')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(30)
                            ->required(),
                        TextInput::make('page_size')
                            ->label('Page Size')
                            ->helperText('Number of orders per page (max 100)')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),
                    ])
                    ->action(function (MagentoStore $record, array $data) {
                        try {
                            SyncMagentoOrdersJob::dispatch(
                                store: $record,
                                days: $data['days'],
                                pageSize: $data['page_size']
                            );

                            Notification::make()
                                ->title('Sync Started')
                                ->body("Order sync has been queued. Fetching orders from last {$data['days']} days.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListMagentoStores::route('/'),
            'create' => Pages\CreateMagentoStore::route('/create'),
            'edit' => Pages\EditMagentoStore::route('/{record}/edit'),
        ];
    }
}
