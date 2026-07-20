<?php

namespace App\Filament\Resources\CargoDocumentPrices\Pages;

use App\Filament\Resources\CargoDocumentPrices\CargoDocumentPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCargoDocumentPrices extends ListRecords
{
    protected static string $resource = CargoDocumentPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
