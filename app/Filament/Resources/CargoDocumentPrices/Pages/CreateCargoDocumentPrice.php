<?php

namespace App\Filament\Resources\CargoDocumentPrices\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\CargoDocumentPrices\CargoDocumentPriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCargoDocumentPrice extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = CargoDocumentPriceResource::class;
}
