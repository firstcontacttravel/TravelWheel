<?php

namespace App\Filament\Resources\CountryGroups\Pages;

use App\Filament\Resources\CountryGroups\CountryGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountryGroup extends EditRecord
{
    protected static string $resource = CountryGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        $this->record->updateQuietly(['version' => (int) $this->record->version + 1]);
    }
}
