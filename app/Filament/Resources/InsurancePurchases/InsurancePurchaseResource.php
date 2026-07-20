<?php

namespace App\Filament\Resources\InsurancePurchases;

use App\Filament\Resources\InsurancePurchases\Pages\ListInsurancePurchases;
use App\Filament\Resources\InsurancePurchases\Pages\ViewInsurancePurchase;
use App\Filament\Resources\InsurancePurchases\Tables\InsurancePurchasesTable;
use App\Models\InsurancePurchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InsurancePurchaseResource extends Resource
{
    protected static ?string $model = InsurancePurchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Insurance';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Purchases';

    protected static ?string $recordTitleAttribute = 'trans_id';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return InsurancePurchasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsurancePurchases::route('/'),
            'view' => ViewInsurancePurchase::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
