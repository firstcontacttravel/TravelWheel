<?php

namespace App\Filament\Resources\SupportExtraLuggages;

use App\Filament\Resources\SupportExtraLuggages\Pages\ListSupportExtraLuggages;
use App\Filament\Resources\SupportExtraLuggages\Pages\ViewSupportExtraLuggage;
use App\Filament\Resources\SupportExtraLuggages\Tables\SupportExtraLuggagesTable;
use App\Models\SupportExtraLuggage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportExtraLuggageResource extends Resource
{
    protected static ?string $model = SupportExtraLuggage::class;

    protected static ?string $slug = 'support-extra-luggages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Requests';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Extra Luggage';

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return SupportExtraLuggagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportExtraLuggages::route('/'),
            'view' => ViewSupportExtraLuggage::route('/{record}'),
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
