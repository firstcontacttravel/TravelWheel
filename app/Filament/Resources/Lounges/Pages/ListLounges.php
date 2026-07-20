<?php

namespace App\Filament\Resources\Lounges\Pages;

use App\Filament\Resources\Lounges\LoungeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLounges extends ListRecords
{
    protected static string $resource = LoungeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
