<?php

namespace App\Filament\Resources\CountryGroups\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\CountryGroups\CountryGroupResource;
use Filament\Resources\Pages\EditRecord;

class EditCountryGroup extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = CountryGroupResource::class;

    protected function afterSave(): void
    {
        $this->record->updateQuietly(['version' => (int) $this->record->version + 1]);
    }
}
