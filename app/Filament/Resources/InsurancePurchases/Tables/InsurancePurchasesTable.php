<?php

namespace App\Filament\Resources\InsurancePurchases\Tables;

use App\Models\InsurancePurchase;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            ->columns([
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
                SelectFilter::make('status')->options(self::STATUS_OPTIONS),
            ])
            ->recordActions([
                ViewAction::make(),
                self::changeStatusAction(),
            ]);
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->form(fn (InsurancePurchase $record): array => [
                Select::make('status')
                    ->label('Status')
                    ->options(self::STATUS_OPTIONS)
                    ->default($record->status)
                    ->required(),
            ])
            ->action(function (InsurancePurchase $record, array $data): void {
                $record->update(['status' => $data['status']]);

                Notification::make()->title('Status updated')->success()->send();
            });
    }
}
