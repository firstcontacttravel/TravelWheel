<?php

namespace App\Filament\Resources\CompanyInvoices\Pages;

use App\Filament\Resources\CompanyInvoices\CompanyInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyInvoices extends ListRecords
{
    protected static string $resource = CompanyInvoiceResource::class;
}
