<?php

namespace App\Filament\Resources\VisaVendors\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\VisaVendors\VisaVendorResource;
use Filament\Resources\Pages\EditRecord;

class EditVisaVendor extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = VisaVendorResource::class;
}
