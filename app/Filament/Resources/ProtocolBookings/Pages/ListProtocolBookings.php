<?php

namespace App\Filament\Resources\ProtocolBookings\Pages;

use App\Filament\Resources\ProtocolBookings\ProtocolBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListProtocolBookings extends ListRecords
{
    protected static string $resource = ProtocolBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
