<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VisaApplications\VisaApplicationResource;
use App\Models\VisaAdditionalDocumentRequest;
use App\Models\VisaApplication;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisaOperationsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Visa Operations';

    protected ?string $description = 'Live application queues and applicant actions.';

    public static function canView(): bool
    {
        return auth()->user()?->canViewVisaOperations() ?? false;
    }

    protected function getStats(): array
    {
        $url = VisaApplicationResource::getUrl();

        return [
            Stat::make('Unassigned submissions', VisaApplication::query()->where('status', 'submitted')->whereNull('assigned_to')->count())->description('Waiting in shared queue')->descriptionIcon(Heroicon::OutlinedInbox)->color('warning')->url($url),
            Stat::make('Documents to review', VisaAdditionalDocumentRequest::query()->where('status', 'submitted')->count())->description('Applicant uploads received')->descriptionIcon(Heroicon::OutlinedDocumentCheck)->color('info')->url($url),
            Stat::make('Action required', VisaApplication::query()->where('status', 'action_required')->count())->description('Waiting on applicants')->descriptionIcon(Heroicon::OutlinedExclamationTriangle)->color('warning')->url($url),
            Stat::make('In processing', VisaApplication::query()->where('status', 'processing')->count())->description('Active processing queue')->descriptionIcon(Heroicon::OutlinedArrowPath)->color('primary')->url($url),
            Stat::make('Awaiting issuance', VisaApplication::query()->where('status', 'approved')->count())->description('Approved, document not issued')->descriptionIcon(Heroicon::OutlinedShieldCheck)->color('success')->url($url),
            Stat::make('Issued in 30 days', VisaApplication::query()->where('status', 'issued')->where('issued_at', '>=', now()->subDays(30))->count())->description('Completed recently')->descriptionIcon(Heroicon::OutlinedCheckBadge)->color('success')->url($url),
        ];
    }
}
