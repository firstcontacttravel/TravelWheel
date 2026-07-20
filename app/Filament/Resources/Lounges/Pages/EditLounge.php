<?php

namespace App\Filament\Resources\Lounges\Pages;

use App\Filament\Resources\Lounges\LoungeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLounge extends EditRecord
{
    protected static string $resource = LoungeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
