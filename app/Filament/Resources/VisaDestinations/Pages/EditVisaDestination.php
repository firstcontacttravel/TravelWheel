<?php

namespace App\Filament\Resources\VisaDestinations\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\VisaDestinations\VisaDestinationResource;
use Filament\Resources\Pages\EditRecord;

class EditVisaDestination extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = VisaDestinationResource::class;
}
