<?php

namespace App\Filament\Resources\FleetCars\Pages;

use App\Filament\Resources\FleetCars\FleetCarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFleetCars extends ListRecords
{
    protected static string $resource = FleetCarResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
