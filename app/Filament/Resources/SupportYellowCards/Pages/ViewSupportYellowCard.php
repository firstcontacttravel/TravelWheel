<?php

namespace App\Filament\Resources\SupportYellowCards\Pages;

use App\Filament\Resources\SupportYellowCards\SupportYellowCardResource;
use App\Filament\Resources\SupportYellowCards\Tables\SupportYellowCardsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportYellowCard extends ViewRecord
{
    protected static string $resource = SupportYellowCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SupportYellowCardsTable::changeStatusAction(),
        ];
    }
}
