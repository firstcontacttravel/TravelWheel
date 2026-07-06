<?php

namespace App\Filament\Resources\VisaVendors\Pages;

use App\Filament\Resources\VisaVendors\VisaVendorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVisaVendor extends EditRecord
{
    protected static string $resource = VisaVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
