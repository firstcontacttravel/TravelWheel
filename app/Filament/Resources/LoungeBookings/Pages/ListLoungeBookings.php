<?php

namespace App\Filament\Resources\LoungeBookings\Pages;

use App\Filament\Resources\LoungeBookings\LoungeBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListLoungeBookings extends ListRecords
{
    protected static string $resource = LoungeBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
