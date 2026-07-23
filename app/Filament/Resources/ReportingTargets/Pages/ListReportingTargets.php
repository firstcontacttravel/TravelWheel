<?php
namespace App\Filament\Resources\ReportingTargets\Pages;
use App\Filament\Resources\ReportingTargets\ReportingTargetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListReportingTargets extends ListRecords
{
    protected static string $resource = ReportingTargetResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
