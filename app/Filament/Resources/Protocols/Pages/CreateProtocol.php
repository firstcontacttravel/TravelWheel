<?php

namespace App\Filament\Resources\Protocols\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Protocols\ProtocolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProtocol extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = ProtocolResource::class;
}
