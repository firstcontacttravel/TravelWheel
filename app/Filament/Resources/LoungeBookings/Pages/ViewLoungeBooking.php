<?php

namespace App\Filament\Resources\LoungeBookings\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\LoungeBookings\LoungeBookingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLoungeBooking extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = LoungeBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
