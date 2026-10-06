<?php

namespace App\Filament\Resources\SupportVisaConfirmations\Pages;

use App\Filament\Concerns\HasWorkPanel;
use App\Filament\Resources\SupportVisaConfirmations\SupportVisaConfirmationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportVisaConfirmation extends ViewRecord
{
    use HasWorkPanel;

    protected static string $resource = SupportVisaConfirmationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->workHeaderActions(),
        ];
    }
}
