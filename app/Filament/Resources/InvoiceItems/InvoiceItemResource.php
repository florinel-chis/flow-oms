<?php

namespace App\Filament\Resources\InvoiceItems;

use App\Filament\Resources\InvoiceItems\Pages\ListInvoiceItems;
use App\Filament\Resources\InvoiceItems\Schemas\InvoiceItemForm;
use App\Filament\Resources\InvoiceItems\Tables\InvoiceItemsTable;
use App\Models\InvoiceItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InvoiceItemResource extends Resource
{
    protected static ?string $model = InvoiceItem::class;

    // InvoiceItem is scoped to tenant through BelongsToTenant trait
    protected static bool $isScopedToTenant = true;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-list-bullet';
    }

    public static function getNavigationGroup(): string|BackedEnum|null
    {
        return 'Orders';
    }

    public static function getNavigationLabel(): string
    {
        return 'Invoice Items';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceItemsTable::configure($table);
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
            'index' => ListInvoiceItems::route('/'),
        ];
    }
}
