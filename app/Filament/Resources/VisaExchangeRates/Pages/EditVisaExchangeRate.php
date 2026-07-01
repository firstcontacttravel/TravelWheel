<?php

namespace App\Filament\Resources\VisaExchangeRates\Pages;

use App\Filament\Resources\VisaExchangeRates\VisaExchangeRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVisaExchangeRate extends EditRecord
{
    protected static string $resource = VisaExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
