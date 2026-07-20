<?php

namespace App\Filament\Resources\CargoDocumentPrices\Pages;

use App\Filament\Resources\CargoDocumentPrices\CargoDocumentPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCargoDocumentPrice extends EditRecord
{
    protected static string $resource = CargoDocumentPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
