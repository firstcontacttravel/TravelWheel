<?php

namespace App\Filament\Resources\FleetCars\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\FleetCars\FleetCarResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFleetCar extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = FleetCarResource::class;
}
