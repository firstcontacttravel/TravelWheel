<?php

namespace App\Filament\Resources\InsurancePurchases\Pages;

use App\Filament\Resources\InsurancePurchases\InsurancePurchaseResource;
use Filament\Resources\Pages\ListRecords;

class ListInsurancePurchases extends ListRecords
{
    protected static string $resource = InsurancePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
