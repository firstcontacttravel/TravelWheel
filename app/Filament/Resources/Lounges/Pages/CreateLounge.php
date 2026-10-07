<?php

namespace App\Filament\Resources\Lounges\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\Lounges\LoungeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLounge extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = LoungeResource::class;
}
