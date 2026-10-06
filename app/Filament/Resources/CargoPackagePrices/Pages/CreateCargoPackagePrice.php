<?php

namespace App\Filament\Resources\CargoPackagePrices\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\CargoPackagePrices\CargoPackagePriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCargoPackagePrice extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = CargoPackagePriceResource::class;
}
