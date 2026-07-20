<?php

namespace App\Filament\Resources\CargoPackagePrices\Pages;

use App\Filament\Resources\CargoPackagePrices\CargoPackagePriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCargoPackagePrices extends ListRecords
{
    protected static string $resource = CargoPackagePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
