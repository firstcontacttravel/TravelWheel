<?php

namespace App\Filament\Resources\ProtocolBookings\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\ProtocolBooking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProtocolBookingsTable
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
                TextColumn::make('fullname')
                    ->label('Customer')
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            $state = json_decode($state, true) ?? [$state];
                        }

                        return implode(', ', (array) ($state ?? []));
                    })
                    ->description(fn (ProtocolBooking $record): string => $record->email)
                    ->searchable(),
                TextColumn::make('phone')->copyable(),
                TextColumn::make('service_type')->badge()->description(fn (ProtocolBooking $record): string => $record->package),
                TextColumn::make('airport')->label('Route'),
                TextColumn::make('travel_date')->label('Travel Date')->date()->sortable(),
                TextColumn::make('passenger')->label('Pax')->alignCenter(),
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
            ])
            ->recordActions([
                ViewAction::make(),
                FulfilmentActions::rowGroup(),
            ]);
    }
}
