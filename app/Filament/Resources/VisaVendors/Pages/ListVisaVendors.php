<?php

namespace App\Filament\Resources\VisaVendors\Pages;

use App\Filament\Resources\VisaVendors\VisaVendorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVisaVendors extends ListRecords
{
    protected static string $resource = VisaVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
