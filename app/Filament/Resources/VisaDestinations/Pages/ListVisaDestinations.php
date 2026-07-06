<?php

namespace App\Filament\Resources\VisaDestinations\Pages;

use App\Filament\Resources\VisaDestinations\VisaDestinationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVisaDestinations extends ListRecords
{
    protected static string $resource = VisaDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
