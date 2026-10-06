<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = StaffResource::class;
}
