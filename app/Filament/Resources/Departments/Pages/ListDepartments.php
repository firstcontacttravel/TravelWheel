<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    public function getSubheading(): ?string
    {
        return 'The teams work is escalated between, and where each one lands in Linear.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
