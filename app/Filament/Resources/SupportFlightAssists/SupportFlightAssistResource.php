<?php

namespace App\Filament\Resources\SupportFlightAssists;

use App\Filament\Resources\SupportFlightAssists\Pages\ListSupportFlightAssists;
use App\Filament\Resources\SupportFlightAssists\Pages\ViewSupportFlightAssist;
use App\Filament\Resources\SupportFlightAssists\Tables\SupportFlightAssistsTable;
use App\Models\SupportFlightAssist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportFlightAssistResource extends Resource
{
    protected static ?string $model = SupportFlightAssist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Requests';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Flight Assist';

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return SupportFlightAssistsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportFlightAssists::route('/'),
            'view' => ViewSupportFlightAssist::route('/{record}'),
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
