<?php

namespace App\Filament\Resources\CountryGroups\Pages;

use App\Filament\Resources\CountryGroups\CountryGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountryGroups extends ListRecords
{
    protected static string $resource = CountryGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
