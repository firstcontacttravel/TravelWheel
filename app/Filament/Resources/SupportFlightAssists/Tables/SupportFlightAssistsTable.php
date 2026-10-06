<?php

namespace App\Filament\Resources\SupportFlightAssists\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\SupportFlightAssist;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportFlightAssistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('workItem.owner'))
            ->columns([
                WorkItemTable::ownerColumn(),
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('name_on_ticket')->label('Customer')->searchable()->description(fn (SupportFlightAssist $record): string => $record->email),
                TextColumn::make('request_type')->badge(),
                TextColumn::make('booking_source')->badge(),
                TextColumn::make('airline')->placeholder('-'),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'billed_with_main_fee' => 'Billed with main fee',
                    default => ucfirst($state),
                })->color(fn (string $state): string => match ($state) {
                    'paid', 'confirmed', 'completed' => 'success',
                    'failed', 'cancelled' => 'danger',
                    'billed_with_main_fee' => 'info',
                    default => 'warning',
                })->sortable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                WorkItemTable::filter(),
                SelectFilter::make('payment_status')->options([
                    'pending' => 'Pending',
                    'billed_with_main_fee' => 'Billed with main fee',
                    'paid' => 'Paid',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('request_type')->options([
                    'date_change' => 'Date Change',
                    'rerouting' => 'Rerouting',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                FulfilmentActions::rowGroup(),
            ]);
    }
}
