<?php

namespace App\Filament\Resources\ProtocolBookings;

use App\Filament\Resources\ProtocolBookings\Pages\ListProtocolBookings;
use App\Filament\Resources\ProtocolBookings\Pages\ViewProtocolBooking;
use App\Filament\Resources\ProtocolBookings\Schemas\ProtocolBookingInfolist;
use App\Filament\Resources\ProtocolBookings\Tables\ProtocolBookingsTable;
use App\Models\ProtocolBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProtocolBookingResource extends Resource
{
    protected static ?string $model = ProtocolBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Protocol';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Protocol Bookings';

    protected static ?string $recordTitleAttribute = 'trans_id';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProtocolBookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProtocolBookingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProtocolBookings::route('/'),
            'view' => ViewProtocolBooking::route('/{record}'),
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
