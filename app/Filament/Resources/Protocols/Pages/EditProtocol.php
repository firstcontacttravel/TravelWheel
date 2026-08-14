<?php

namespace App\Filament\Resources\Protocols\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\Protocols\ProtocolResource;
use Filament\Resources\Pages\EditRecord;

class EditProtocol extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = ProtocolResource::class;
}
