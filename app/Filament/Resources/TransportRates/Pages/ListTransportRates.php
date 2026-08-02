<?php

namespace App\Filament\Resources\TransportRates\Pages;

use App\Filament\Resources\TransportRates\TransportRateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListTransportRates extends ListRecords
{
    protected static string $resource = TransportRateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'car_hire' => Tab::make('Car Hire'),
            'pickup_dropoff' => Tab::make('Pickup & Dropoff'),
        ];
    }
}
