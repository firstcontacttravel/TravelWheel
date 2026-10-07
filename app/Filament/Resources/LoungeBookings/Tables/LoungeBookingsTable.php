<?php

namespace App\Filament\Resources\LoungeBookings\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\LoungeBooking;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoungeBookingsTable
{
    private const STATUS_OPTIONS = [
        'Pending' => 'Pending',
        'Successful' => 'Successful',
        'Failed' => 'Failed',
        'Cancelled' => 'Cancelled',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('workItem.owner'))
            ->columns([
                WorkItemTable::ownerColumn(),
                TextColumn::make('trans_id')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('fullname')->label('Customer')->searchable()->description(fn (LoungeBooking $record): string => $record->email),
                TextColumn::make('phone_no')->copyable(),
                TextColumn::make('lounge_name')->badge()->description(fn (LoungeBooking $record): string => (string) $record->terminal),
                IconColumn::make('requires_manual_provider_booking')
                    ->label('Needs LoungePair booking')
                    ->getStateUsing(fn (LoungeBooking $record): bool => $record->requiresManualProviderBooking())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-minus'),
                TextColumn::make('travel_date')->label('Travel Date')->date()->sortable(),
                TextColumn::make('nop')
                    ->label('Pax')
                    ->formatStateUsing(fn (LoungeBooking $record): string => "{$record->noa}A / {$record->noc}C / {$record->noi}I")
                    ->alignCenter(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'Successful' => 'success',
                    'Failed', 'Cancelled' => 'danger',
                    default => 'warning',
                })->sortable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                WorkItemTable::filter(),
                SelectFilter::make('status')->options(self::STATUS_OPTIONS),
                TernaryFilter::make('provider')
                    ->label('Needs LoungePair booking')
                    ->queries(
                        true: fn ($query) => $query->where('provider', 'loungepair'),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('provider')->orWhere('provider', '!=', 'loungepair')),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                self::bookOnProviderAction(),
                FulfilmentActions::rowGroup(),
            ]);
    }

    public static function bookOnProviderAction(): Action
    {
        return Action::make('bookOnProvider')
            ->label('Book on LoungePair')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning')
            ->url(fn (LoungeBooking $record): string => (string) $record->provider_url)
            ->openUrlInNewTab()
            ->visible(fn (LoungeBooking $record): bool => $record->requiresManualProviderBooking());
    }
}
