<?php

namespace App\Filament\Resources\LoungeBookings;

use App\Filament\Resources\LoungeBookings\Pages\ListLoungeBookings;
use App\Filament\Resources\LoungeBookings\Pages\ViewLoungeBooking;
use App\Filament\Resources\LoungeBookings\Schemas\LoungeBookingInfolist;
use App\Filament\Resources\LoungeBookings\Tables\LoungeBookingsTable;
use App\Models\LoungeBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LoungeBookingResource extends Resource
{
    protected static ?string $model = LoungeBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Lounge';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Lounge Bookings';

    protected static ?string $recordTitleAttribute = 'trans_id';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoungeBookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoungeBookingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoungeBookings::route('/'),
            'view' => ViewLoungeBooking::route('/{record}'),
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
