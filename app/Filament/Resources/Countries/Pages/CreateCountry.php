<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Countries\CountryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = CountryResource::class;
}
