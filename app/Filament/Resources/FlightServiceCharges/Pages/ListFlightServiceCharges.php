<?php

namespace App\Filament\Resources\FlightServiceCharges\Pages;

use App\Filament\Resources\FlightServiceCharges\FlightServiceChargeResource;
use Filament\Resources\Pages\ListRecords;

class ListFlightServiceCharges extends ListRecords
{
    protected static string $resource = FlightServiceChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
