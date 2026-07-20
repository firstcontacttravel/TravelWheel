<?php

namespace App\Filament\Resources\CargoPackagePrices\Pages;

use App\Filament\Resources\CargoPackagePrices\CargoPackagePriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCargoPackagePrice extends EditRecord
{
    protected static string $resource = CargoPackagePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
