<?php

namespace App\Filament\Resources\ProtocolBookings\Pages;

use App\Filament\Resources\ProtocolBookings\ProtocolBookingResource;
use App\Filament\Resources\ProtocolBookings\Tables\ProtocolBookingsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewProtocolBooking extends ViewRecord
{
    protected static string $resource = ProtocolBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProtocolBookingsTable::changeStatusAction(),
        ];
    }
}
