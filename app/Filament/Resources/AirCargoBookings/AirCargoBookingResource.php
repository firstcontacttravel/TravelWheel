<?php

namespace App\Filament\Resources\AirCargoBookings;

use App\Filament\Resources\AirCargoBookings\Pages\ListAirCargoBookings;
use App\Filament\Resources\AirCargoBookings\Pages\ViewAirCargoBooking;
use App\Filament\Resources\AirCargoBookings\Schemas\AirCargoBookingInfolist;
use App\Filament\Resources\AirCargoBookings\Tables\AirCargoBookingsTable;
use App\Models\AirCargoModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AirCargoBookingResource extends Resource
{
    protected static ?string $model = AirCargoModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Air Cargo';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Cargo Bookings';

    protected static ?string $recordTitleAttribute = 'shipping_id';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return AirCargoBookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AirCargoBookingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAirCargoBookings::route('/'),
            'view' => ViewAirCargoBooking::route('/{record}'),
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
