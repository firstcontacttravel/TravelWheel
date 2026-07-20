<?php

namespace App\Filament\Resources\AirCargoBookings\Pages;

use App\Filament\Resources\AirCargoBookings\AirCargoBookingResource;
use App\Filament\Resources\AirCargoBookings\Tables\AirCargoBookingsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewAirCargoBooking extends ViewRecord
{
    protected static string $resource = AirCargoBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AirCargoBookingsTable::changeStatusAction(),
            AirCargoBookingsTable::downloadDocumentAction(),
        ];
    }
}
