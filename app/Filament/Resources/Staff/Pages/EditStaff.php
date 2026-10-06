<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    use HasBackHeaderAction;
    use RecordsPageActivity;

    protected static string $resource = StaffResource::class;
}
