<?php
namespace App\Filament\Resources\ReportingSchedules\Pages;
use App\Filament\Concerns\RecordsPageActivity;
use App\Filament\Resources\ReportingSchedules\ReportingScheduleResource;
use Filament\Resources\Pages\CreateRecord;
class CreateReportingSchedule extends CreateRecord
{
    use RecordsPageActivity;

    protected static string $resource = ReportingScheduleResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array { $data['user_id'] = auth()->id(); $data['filters'] ??= []; return $data; }
}
