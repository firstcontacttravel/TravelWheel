<?php

namespace App\Filament\Resources\Drivers\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Drivers\DriverResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDriver extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = DriverResource::class;
}
