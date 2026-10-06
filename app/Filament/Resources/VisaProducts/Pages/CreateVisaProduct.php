<?php

namespace App\Filament\Resources\VisaProducts\Pages;

use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\VisaProducts\VisaProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisaProduct extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = VisaProductResource::class;
}
