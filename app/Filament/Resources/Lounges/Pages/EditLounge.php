<?php

namespace App\Filament\Resources\Lounges\Pages;

use App\Filament\Concerns\HasBackHeaderActionAndInlineDelete;
use App\Filament\Resources\Lounges\LoungeResource;
use Filament\Resources\Pages\EditRecord;

class EditLounge extends EditRecord
{
    use HasBackHeaderActionAndInlineDelete;

    protected static string $resource = LoungeResource::class;
}
