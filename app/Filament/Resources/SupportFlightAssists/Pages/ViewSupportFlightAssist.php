<?php

namespace App\Filament\Resources\SupportFlightAssists\Pages;

use App\Filament\Resources\SupportFlightAssists\SupportFlightAssistResource;
use App\Filament\Resources\SupportFlightAssists\Tables\SupportFlightAssistsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportFlightAssist extends ViewRecord
{
    protected static string $resource = SupportFlightAssistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SupportFlightAssistsTable::changeStatusAction(),
        ];
    }
}
