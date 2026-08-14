<?php
namespace App\Filament\Resources\ReportingTargets\Pages;
use App\Filament\Concerns\HasBackHeaderAction;
use App\Filament\Resources\ReportingTargets\ReportingTargetResource;
use Filament\Resources\Pages\EditRecord;
class EditReportingTarget extends EditRecord { use HasBackHeaderAction; protected static string $resource = ReportingTargetResource::class; }
