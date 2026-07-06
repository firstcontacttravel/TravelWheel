<?php

namespace App\Filament\Resources\VisaDestinations\Pages;

use App\Filament\Resources\VisaDestinations\VisaDestinationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVisaDestination extends EditRecord
{
    protected static string $resource = VisaDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
