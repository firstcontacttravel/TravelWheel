<?php
namespace App\Filament\Resources\ReportingAlerts\Pages;
use App\Filament\Resources\ReportingAlerts\ReportingAlertResource;
use Filament\Resources\Pages\ListRecords;
class ListReportingAlerts extends ListRecords
{
    protected static string $resource = ReportingAlertResource::class;
    protected function getHeaderActions(): array { return []; }
}
