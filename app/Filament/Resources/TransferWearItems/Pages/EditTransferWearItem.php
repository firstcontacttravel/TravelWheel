<?php

namespace App\Filament\Resources\TransferWearItems\Pages;

use App\Filament\Resources\TransferWearItems\TransferWearItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransferWearItem extends EditRecord
{
    protected static string $resource = TransferWearItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
