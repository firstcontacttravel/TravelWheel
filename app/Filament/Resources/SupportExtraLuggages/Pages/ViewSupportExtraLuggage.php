<?php

namespace App\Filament\Resources\SupportExtraLuggages\Pages;

use App\Filament\Resources\SupportExtraLuggages\SupportExtraLuggageResource;
use App\Filament\Resources\SupportExtraLuggages\Tables\SupportExtraLuggagesTable;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportExtraLuggage extends ViewRecord
{
    protected static string $resource = SupportExtraLuggageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SupportExtraLuggagesTable::changeStatusAction(),
        ];
    }
}
