<?php

namespace App\Filament\Resources\FlightServiceCharges\Pages;

use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Resources\FlightServiceCharges\FlightServiceChargeResource;
use Filament\Resources\Pages\EditRecord;

class EditFlightServiceCharge extends EditRecord
{
    use HasBackHeaderAction;

    protected static string $resource = FlightServiceChargeResource::class;
}
