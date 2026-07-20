<?php

namespace App\Filament\Resources\SupportFlightAssists\Pages;

use App\Filament\Resources\SupportFlightAssists\SupportFlightAssistResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportFlightAssists extends ListRecords
{
    protected static string $resource = SupportFlightAssistResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
