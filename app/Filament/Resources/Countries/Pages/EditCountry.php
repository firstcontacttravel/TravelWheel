<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Countries\CountryResource;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;
    use RecordsPageActivity;

    protected static string $resource = CountryResource::class;
}
