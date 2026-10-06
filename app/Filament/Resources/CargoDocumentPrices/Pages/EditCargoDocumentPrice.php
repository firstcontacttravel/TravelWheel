<?php

namespace App\Filament\Resources\CargoDocumentPrices\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\CargoDocumentPrices\CargoDocumentPriceResource;
use Filament\Resources\Pages\EditRecord;

class EditCargoDocumentPrice extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;
    use RecordsPageActivity;

    protected static string $resource = CargoDocumentPriceResource::class;
}
