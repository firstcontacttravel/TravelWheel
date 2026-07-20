<?php

namespace App\Filament\Resources\FleetCars\Pages;

use App\Filament\Resources\FleetCars\FleetCarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFleetCar extends EditRecord
{
    protected static string $resource = FleetCarResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
