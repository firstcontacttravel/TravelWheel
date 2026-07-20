<?php

namespace App\Filament\Resources\InsuranceQuotes\Pages;

use App\Filament\Resources\InsuranceQuotes\InsuranceQuoteResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInsuranceQuote extends ViewRecord
{
    protected static string $resource = InsuranceQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
