<?php

namespace App\Filament\Resources\VisaDestinations\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\VisaDestinations\VisaDestinationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisaDestination extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = VisaDestinationResource::class;
}
