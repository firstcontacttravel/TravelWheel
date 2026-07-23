<?php
namespace App\Filament\Resources\ReportingSchedules\Pages;
use App\Filament\Resources\ReportingSchedules\ReportingScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListReportingSchedules extends ListRecords
{
    protected static string $resource = ReportingScheduleResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
