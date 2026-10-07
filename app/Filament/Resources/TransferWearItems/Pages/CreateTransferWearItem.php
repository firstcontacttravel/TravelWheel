<?php

namespace App\Filament\Resources\TransferWearItems\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\TransferWearItems\TransferWearItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransferWearItem extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = TransferWearItemResource::class;
}
