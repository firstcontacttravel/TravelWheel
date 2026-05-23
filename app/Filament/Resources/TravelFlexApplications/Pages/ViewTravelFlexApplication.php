<?php

namespace App\Filament\Resources\TravelFlexApplications\Pages;

use App\Filament\Resources\TravelFlexApplications\Tables\TravelFlexApplicationsTable;
use App\Filament\Resources\TravelFlexApplications\TravelFlexApplicationResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewTravelFlexApplication extends ViewRecord
{
    protected static string $resource = TravelFlexApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                TravelFlexApplicationsTable::markReviewedAction(),
                TravelFlexApplicationsTable::approveAction(),
                TravelFlexApplicationsTable::rejectAction(),
            ])
                ->label('Review')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->button(),
            ActionGroup::make([
                TravelFlexApplicationsTable::resendProviderEmailAction(),
            ])
                ->label('Provider')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->button(),
        ];
    }
}
