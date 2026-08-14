<?php

namespace App\Filament\Resources\CargoPackagePrices\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\CargoPackagePrices\CargoPackagePriceResource;
use Filament\Resources\Pages\EditRecord;

class EditCargoPackagePrice extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = CargoPackagePriceResource::class;
}
