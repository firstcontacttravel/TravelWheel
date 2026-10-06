<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\Transfers\Tables\TransfersTable;
use App\Filament\Resources\Transfers\TransferResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransfer extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
            TransfersTable::assignDriverAction(),
        ];
    }
}
