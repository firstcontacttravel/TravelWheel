<?php

namespace App\Filament\Resources\CarHires\Pages;

use App\Filament\Resources\CarHires\CarHireResource;
use Filament\Resources\Pages\ListRecords;

class ListCarHires extends ListRecords
{
    protected static string $resource = CarHireResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
