<?php

namespace App\Filament\Resources\SupportProductPrices\Pages;

use App\Filament\Resources\SupportProductPrices\SupportProductPriceResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportProductPrices extends ListRecords
{
    protected static string $resource = SupportProductPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
