<?php

namespace App\Filament\Resources\VisaProducts;

use App\Filament\Resources\VisaProducts\Pages\CreateVisaProduct;
use App\Filament\Resources\VisaProducts\Pages\EditVisaProduct;
use App\Filament\Resources\VisaProducts\Pages\ListVisaProducts;
use App\Filament\Resources\VisaProducts\Schemas\VisaProductForm;
use App\Filament\Resources\VisaProducts\Tables\VisaProductsTable;
use App\Models\VisaProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VisaProductResource extends Resource
{
    protected static ?string $model = VisaProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Catalogue';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VisaProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisaProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListVisaProducts::route('/'), 'create' => CreateVisaProduct::route('/create'), 'edit' => EditVisaProduct::route('/{record}/edit')];
    }
}
