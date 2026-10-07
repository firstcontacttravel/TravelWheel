<?php

namespace App\Filament\Resources\CountryGroups\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\CountryGroups\CountryGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountryGroup extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = CountryGroupResource::class;
}
