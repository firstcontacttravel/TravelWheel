<?php
namespace App\Filament\Resources\ReportingSchedules\Pages;
use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Resources\ReportingSchedules\ReportingScheduleResource;
use Filament\Resources\Pages\EditRecord;
class EditReportingSchedule extends EditRecord { use HasBackHeaderAction; protected static string $resource = ReportingScheduleResource::class; }
