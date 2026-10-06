<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;
    use RecordsPageActivity;

    protected static string $resource = DepartmentResource::class;
}
