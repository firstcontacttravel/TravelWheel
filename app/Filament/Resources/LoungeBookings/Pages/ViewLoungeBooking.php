<?php

namespace App\Filament\Resources\LoungeBookings\Pages;

use App\Filament\Resources\LoungeBookings\LoungeBookingResource;
use App\Filament\Resources\LoungeBookings\Tables\LoungeBookingsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewLoungeBooking extends ViewRecord
{
    protected static string $resource = LoungeBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LoungeBookingsTable::changeStatusAction(),
        ];
    }
}
