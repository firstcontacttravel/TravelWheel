<?php

namespace App\Filament\Resources\SupportYellowCards\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\SupportYellowCards\SupportYellowCardResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportYellowCard extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = SupportYellowCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
