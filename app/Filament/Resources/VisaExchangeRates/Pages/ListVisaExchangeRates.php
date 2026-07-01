<?php

namespace App\Filament\Resources\VisaExchangeRates\Pages;

use App\Filament\Resources\VisaExchangeRates\VisaExchangeRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVisaExchangeRates extends ListRecords
{
    protected static string $resource = VisaExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
