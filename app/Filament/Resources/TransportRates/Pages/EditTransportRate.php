<?php

namespace App\Filament\Resources\TransportRates\Pages;

use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\TransportRates\TransportRateResource;
use Filament\Resources\Pages\EditRecord;

class EditTransportRate extends EditRecord
{
    use HasBackHeaderAction;
    use RecordsPageActivity;

    protected static string $resource = TransportRateResource::class;
}
