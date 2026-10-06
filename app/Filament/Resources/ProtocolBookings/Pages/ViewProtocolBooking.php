<?php

namespace App\Filament\Resources\ProtocolBookings\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\ProtocolBookings\ProtocolBookingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProtocolBooking extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = ProtocolBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
