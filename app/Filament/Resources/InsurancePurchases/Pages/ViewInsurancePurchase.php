<?php

namespace App\Filament\Resources\InsurancePurchases\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\InsurancePurchases\InsurancePurchaseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInsurancePurchase extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = InsurancePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
