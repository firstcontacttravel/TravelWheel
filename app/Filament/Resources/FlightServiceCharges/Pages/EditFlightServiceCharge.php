<?php

namespace App\Filament\Resources\FlightServiceCharges\Pages;

use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\FlightServiceCharges\FlightServiceChargeResource;
use Filament\Resources\Pages\EditRecord;

class EditFlightServiceCharge extends EditRecord
{
    use HasBackHeaderAction;
    use RecordsPageActivity;

    protected static string $resource = FlightServiceChargeResource::class;
}
