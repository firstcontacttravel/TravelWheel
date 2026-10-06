<?php

namespace App\Filament\Resources\ShippingZones\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\ShippingZones\ShippingZoneResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingZone extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = ShippingZoneResource::class;
}
