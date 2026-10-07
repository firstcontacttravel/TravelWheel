<?php

namespace App\Filament\Resources\SupportExtraLuggages\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\SupportExtraLuggages\SupportExtraLuggageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportExtraLuggage extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = SupportExtraLuggageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
