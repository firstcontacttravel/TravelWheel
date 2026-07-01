<?php

namespace App\Filament\Resources\CountryGroups\Pages;

use App\Filament\Resources\CountryGroups\CountryGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountryGroup extends CreateRecord
{
    protected static string $resource = CountryGroupResource::class;
}
