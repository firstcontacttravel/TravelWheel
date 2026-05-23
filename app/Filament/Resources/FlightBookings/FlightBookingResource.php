<?php

namespace App\Filament\Resources\FlightBookings;

use App\Filament\Resources\FlightBookings\Pages\ListFlightBookings;
use App\Filament\Resources\FlightBookings\Pages\ViewFlightBooking;
use App\Filament\Resources\FlightBookings\Schemas\FlightBookingInfolist;
use App\Filament\Resources\FlightBookings\Tables\FlightBookingsTable;
use App\Models\FlightBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FlightBookingResource extends Resource
{
    protected static ?string $model = FlightBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'booking_ref';

    protected static ?string $navigationLabel = 'Flight Bookings';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Flight Booking';

    protected static ?string $pluralModelLabel = 'Flight Bookings';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return FlightBookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FlightBookingsTable::configure($table);
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
            'index' => ListFlightBookings::route('/'),
            'view' => ViewFlightBooking::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
