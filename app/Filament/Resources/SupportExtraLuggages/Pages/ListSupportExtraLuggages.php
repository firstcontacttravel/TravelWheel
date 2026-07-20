<?php

namespace App\Filament\Resources\SupportExtraLuggages\Pages;

use App\Filament\Resources\SupportExtraLuggages\SupportExtraLuggageResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportExtraLuggages extends ListRecords
{
    protected static string $resource = SupportExtraLuggageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
