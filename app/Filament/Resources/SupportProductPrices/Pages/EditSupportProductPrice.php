<?php

namespace App\Filament\Resources\SupportProductPrices\Pages;

use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\SupportProductPrices\SupportProductPriceResource;
use Filament\Resources\Pages\EditRecord;

class EditSupportProductPrice extends EditRecord
{
    use HasBackHeaderAction;
    use RecordsPageActivity;

    protected static string $resource = SupportProductPriceResource::class;
}
