<?php

namespace App\Filament\Resources\AirCargoBookings\Pages;

use App\Filament\Resources\AirCargoBookings\AirCargoBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListAirCargoBookings extends ListRecords
{
    protected static string $resource = AirCargoBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
