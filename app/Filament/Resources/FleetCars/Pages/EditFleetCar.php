<?php

namespace App\Filament\Resources\FleetCars\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\FleetCars\FleetCarResource;
use Filament\Resources\Pages\EditRecord;

class EditFleetCar extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = FleetCarResource::class;
}
