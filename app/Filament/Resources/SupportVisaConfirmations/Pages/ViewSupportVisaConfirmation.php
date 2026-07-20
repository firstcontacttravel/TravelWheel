<?php

namespace App\Filament\Resources\SupportVisaConfirmations\Pages;

use App\Filament\Resources\SupportVisaConfirmations\SupportVisaConfirmationResource;
use App\Filament\Resources\SupportVisaConfirmations\Tables\SupportVisaConfirmationsTable;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportVisaConfirmation extends ViewRecord
{
    protected static string $resource = SupportVisaConfirmationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SupportVisaConfirmationsTable::changeStatusAction(),
        ];
    }
}
