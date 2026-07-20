<?php

namespace App\Filament\Resources\InsurancePurchases\Pages;

use App\Filament\Resources\InsurancePurchases\InsurancePurchaseResource;
use App\Filament\Resources\InsurancePurchases\Tables\InsurancePurchasesTable;
use Filament\Resources\Pages\ViewRecord;

class ViewInsurancePurchase extends ViewRecord
{
    protected static string $resource = InsurancePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            InsurancePurchasesTable::changeStatusAction(),
        ];
    }
}
