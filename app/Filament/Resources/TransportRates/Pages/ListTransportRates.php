<?php

namespace App\Filament\Resources\TransportRates\Pages;

use App\Filament\Resources\TransportRates\TransportRateResource;
use Filament\Resources\Pages\ListRecords;

class ListTransportRates extends ListRecords
{
    protected static string $resource = TransportRateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
