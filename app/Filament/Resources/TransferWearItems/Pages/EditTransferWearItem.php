<?php

namespace App\Filament\Resources\TransferWearItems\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\TransferWearItems\TransferWearItemResource;
use Filament\Resources\Pages\EditRecord;

class EditTransferWearItem extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = TransferWearItemResource::class;
}
