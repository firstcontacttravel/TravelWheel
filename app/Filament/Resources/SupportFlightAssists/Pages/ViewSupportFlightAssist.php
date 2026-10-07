<?php

namespace App\Filament\Resources\SupportFlightAssists\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\SupportFlightAssists\SupportFlightAssistResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportFlightAssist extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = SupportFlightAssistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
