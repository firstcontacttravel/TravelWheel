<?php

namespace App\Filament\Resources\ExchangeRates\Pages;

use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use Filament\Resources\Pages\EditRecord;

class EditExchangeRate extends EditRecord
{
    use HasBackHeaderAction;
    use RecordsPageActivity;

    protected static string $resource = ExchangeRateResource::class;
}
