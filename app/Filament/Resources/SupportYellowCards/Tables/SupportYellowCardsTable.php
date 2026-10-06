<?php

namespace App\Filament\Resources\SupportYellowCards\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\SupportYellowCard;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class SupportYellowCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('workItem.owner'))
            ->columns([
                WorkItemTable::ownerColumn(),
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('full_name')->label('Customer')->searchable()->description(fn (SupportYellowCard $record): string => $record->email),
                TextColumn::make('service_type')->badge(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid', 'confirmed', 'completed' => 'success',
                    'failed', 'cancelled' => 'danger',
                    default => 'warning',
                })->sortable(),
                TextColumn::make('data_page')->label('Passport page')->state('Download')
                    ->url(fn (SupportYellowCard $record): string => Storage::disk('public')->url($record->data_page))
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                WorkItemTable::filter(),
                SelectFilter::make('payment_status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('service_type')->options([
                    'standard' => 'Standard',
                    'fasttrack' => 'Fast Track',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                FulfilmentActions::rowGroup(),
            ]);
    }
}
