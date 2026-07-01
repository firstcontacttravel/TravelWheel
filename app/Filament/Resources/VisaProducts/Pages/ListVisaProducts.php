<?php

namespace App\Filament\Resources\VisaProducts\Pages;

use App\Filament\Resources\VisaProducts\VisaProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVisaProducts extends ListRecords
{
    protected static string $resource = VisaProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
