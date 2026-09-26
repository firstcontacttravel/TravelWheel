<?php

namespace App\Filament\Resources\FlightSuppliers\Pages;

use App\Filament\Resources\FlightSuppliers\FlightSupplierResource;
use App\Services\Flights\FlightSupplierControl;
use Filament\Resources\Pages\ListRecords;

class ListFlightSuppliers extends ListRecords
{
    protected static string $resource = FlightSupplierResource::class;

    public function mount(): void
    {
        // An API just added to config/flights.php gets its (switched-off)
        // row here, so it appears on this screen ready to be turned on.
        app(FlightSupplierControl::class)->ensureRows();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
