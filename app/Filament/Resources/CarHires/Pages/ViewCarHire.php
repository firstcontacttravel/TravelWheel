<?php

namespace App\Filament\Resources\CarHires\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\CarHires\CarHireResource;
use App\Filament\Resources\CarHires\Tables\CarHiresTable;
use Filament\Resources\Pages\ViewRecord;

class ViewCarHire extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = CarHireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
            CarHiresTable::assignDriverAction(),
        ];
    }
}
