<?php

namespace App\Filament\Resources\CarHires;

use App\Filament\Resources\CarHires\Pages\ListCarHires;
use App\Filament\Resources\CarHires\Pages\ViewCarHire;
use App\Filament\Resources\CarHires\Tables\CarHiresTable;
use App\Models\CarHire;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CarHireResource extends Resource
{
    protected static ?string $model = CarHire::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'Car Hire & Transfer';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Car Hire Bookings';

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return CarHiresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCarHires::route('/'),
            'view' => ViewCarHire::route('/{record}'),
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
