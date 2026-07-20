<?php

namespace App\Filament\Resources\SupportYellowCards;

use App\Filament\Resources\SupportYellowCards\Pages\ListSupportYellowCards;
use App\Filament\Resources\SupportYellowCards\Pages\ViewSupportYellowCard;
use App\Filament\Resources\SupportYellowCards\Tables\SupportYellowCardsTable;
use App\Models\SupportYellowCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportYellowCardResource extends Resource
{
    protected static ?string $model = SupportYellowCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Requests';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Yellow Card';

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return SupportYellowCardsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportYellowCards::route('/'),
            'view' => ViewSupportYellowCard::route('/{record}'),
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
