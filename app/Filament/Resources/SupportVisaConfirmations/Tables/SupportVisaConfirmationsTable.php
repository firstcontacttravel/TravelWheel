<?php

namespace App\Filament\Resources\SupportVisaConfirmations\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\SupportVisaConfirmation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class SupportVisaConfirmationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('workItem.owner'))
            ->columns([
                WorkItemTable::ownerColumn(),
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('full_name')->label('Customer')->searchable()->description(fn (SupportVisaConfirmation $record): string => $record->email),
                TextColumn::make('phone_number')->copyable(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid', 'confirmed', 'completed' => 'success',
                    'failed', 'cancelled' => 'danger',
                    default => 'warning',
                })->sortable(),
                TextColumn::make('visa_file')->label('Visa document')->state('Download')
                    ->url(fn (SupportVisaConfirmation $record): string => Storage::disk('public')->url($record->visa_file))
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
            ])
            ->recordActions([
                ViewAction::make(),
                FulfilmentActions::rowGroup(),
            ]);
    }
}
