<?php

namespace App\Filament\Resources\TransferWearItems\Pages;

use App\Filament\Resources\TransferWearItems\TransferWearItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransferWearItems extends ListRecords
{
    protected static string $resource = TransferWearItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
