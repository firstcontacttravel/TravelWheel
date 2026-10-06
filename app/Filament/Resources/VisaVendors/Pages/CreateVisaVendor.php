<?php

namespace App\Filament\Resources\VisaVendors\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\VisaVendors\VisaVendorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisaVendor extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = VisaVendorResource::class;
}
