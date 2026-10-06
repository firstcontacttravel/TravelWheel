<?php

namespace App\Filament\Resources\Drivers\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Drivers\DriverResource;
use Filament\Resources\Pages\EditRecord;

class EditDriver extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;
    use RecordsPageActivity;

    protected static string $resource = DriverResource::class;
}
