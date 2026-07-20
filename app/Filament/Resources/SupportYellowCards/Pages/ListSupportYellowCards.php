<?php

namespace App\Filament\Resources\SupportYellowCards\Pages;

use App\Filament\Resources\SupportYellowCards\SupportYellowCardResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportYellowCards extends ListRecords
{
    protected static string $resource = SupportYellowCardResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
