<?php

namespace App\Filament\Resources\SupportVisaConfirmations\Pages;

use App\Filament\Resources\SupportVisaConfirmations\SupportVisaConfirmationResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportVisaConfirmations extends ListRecords
{
    protected static string $resource = SupportVisaConfirmationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
