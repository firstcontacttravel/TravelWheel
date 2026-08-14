<?php

namespace App\Filament\Resources\ShippingZones\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\ShippingZones\ShippingZoneResource;
use Filament\Resources\Pages\EditRecord;

class EditShippingZone extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = ShippingZoneResource::class;
}
