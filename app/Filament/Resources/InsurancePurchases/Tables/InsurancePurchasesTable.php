<?php

namespace App\Filament\Resources\InsurancePurchases\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\InsurancePurchase;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InsurancePurchasesTable
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
                TextColumn::make('surname')
                    ->label('Customer')
                    ->formatStateUsing(fn (InsurancePurchase $record): string => trim("{$record->surname} {$record->firstname}"))
                    ->description(fn (InsurancePurchase $record): string => (string) $record->email)
                    ->searchable(),
                TextColumn::make('phone_no')->copyable(),
                TextColumn::make('bookingtype_id')->label('Type')->badge()->formatStateUsing(fn ($state): string => match ((int) $state) {
                    2 => 'Family',
                    default => 'Individual',
                }),
                TextColumn::make('t_amount')->label('Total')->money('NGN')->sortable(),
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
