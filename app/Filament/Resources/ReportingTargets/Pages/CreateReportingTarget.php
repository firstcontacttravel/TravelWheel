<?php
namespace App\Filament\Resources\ReportingTargets\Pages;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\ReportingTargets\ReportingTargetResource;
use Filament\Resources\Pages\CreateRecord;
class CreateReportingTarget extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = ReportingTargetResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array { $data['created_by'] = auth()->id(); return $data; }
}
