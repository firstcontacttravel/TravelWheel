<?php

namespace App\Filament\Resources\LoungeBookings\Tables;

use App\Models\LoungeBooking;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            ->columns([
                TextColumn::make('trans_id')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('fullname')->label('Customer')->searchable()->description(fn (LoungeBooking $record): string => $record->email),
                TextColumn::make('phone_no')->copyable(),
                TextColumn::make('lounge_name')->badge()->description(fn (LoungeBooking $record): string => (string) $record->terminal),
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
            ->form(fn (LoungeBooking $record): array => [
                Select::make('status')
                    ->label('Status')
                    ->options(self::STATUS_OPTIONS)
                    ->default($record->status)
                    ->required(),
            ])
            ->action(function (LoungeBooking $record, array $data): void {
                $record->update(['status' => $data['status']]);

                Notification::make()->title('Status updated')->success()->send();
            });
    }
}
