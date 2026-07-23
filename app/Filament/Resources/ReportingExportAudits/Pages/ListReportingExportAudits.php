<?php
namespace App\Filament\Resources\ReportingExportAudits\Pages;
use App\Filament\Resources\ReportingExportAudits\ReportingExportAuditResource;
use Filament\Resources\Pages\ListRecords;
class ListReportingExportAudits extends ListRecords
{
    protected static string $resource = ReportingExportAuditResource::class;
    protected function getHeaderActions(): array { return []; }
}
