<?php

namespace App\Filament\Resources\MagentoProducts;

use App\Filament\Resources\MagentoProducts\Pages\CreateMagentoProduct;
use App\Filament\Resources\MagentoProducts\Pages\EditMagentoProduct;
use App\Filament\Resources\MagentoProducts\Pages\ListMagentoProducts;
use App\Filament\Resources\MagentoProducts\Schemas\MagentoProductForm;
use App\Filament\Resources\MagentoProducts\Tables\MagentoProductsTable;
use App\Models\MagentoProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MagentoProductResource extends Resource
{
    protected static ?string $model = MagentoProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Products';

    protected static string|\UnitEnum|null $navigationGroup = 'Magento';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('stockItem');
    }

    public static function form(Schema $schema): Schema
    {
        return MagentoProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MagentoProductsTable::configure($table);
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
            'index' => ListMagentoProducts::route('/'),
            'create' => CreateMagentoProduct::route('/create'),
            'edit' => EditMagentoProduct::route('/{record}/edit'),
        ];
    }
}
