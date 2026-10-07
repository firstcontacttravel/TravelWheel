<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = DepartmentResource::class;
}
