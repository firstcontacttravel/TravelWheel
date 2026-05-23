<?php

namespace App\Filament\Resources\TravelFlexApplications\Pages;

use App\Models\TravelFlexApplication;
use App\Filament\Resources\TravelFlexApplications\TravelFlexApplicationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTravelFlexApplications extends ListRecords
{
    protected static string $resource = TravelFlexApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn (): int => TravelFlexApplication::query()->count()),
            'submitted' => Tab::make('Submitted')
                ->badge(fn (): int => TravelFlexApplication::query()->where('application_status', 'submitted')->count())
                ->badgeColor('warning')
                ->query(fn (Builder $query): Builder => $query->where('application_status', 'submitted')),
            'provider_failed' => Tab::make('Provider failed')
                ->badge(fn (): int => TravelFlexApplication::query()->where('provider_status', 'failed')->count())
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => $query->where('provider_status', 'failed')),
            'approved' => Tab::make('Approved')
                ->badge(fn (): int => TravelFlexApplication::query()->where('application_status', 'approved')->count())
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query->where('application_status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->badge(fn (): int => TravelFlexApplication::query()->where('application_status', 'rejected')->count())
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => $query->where('application_status', 'rejected')),
        ];
    }
}
