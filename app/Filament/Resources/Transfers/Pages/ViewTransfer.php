<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Filament\Resources\Transfers\TransferResource;
use App\Filament\Resources\Transfers\Tables\TransfersTable;
use Filament\Resources\Pages\ViewRecord;

class ViewTransfer extends ViewRecord
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TransfersTable::assignDriverAction(),
            TransfersTable::changeStatusAction(),
        ];
    }
}
