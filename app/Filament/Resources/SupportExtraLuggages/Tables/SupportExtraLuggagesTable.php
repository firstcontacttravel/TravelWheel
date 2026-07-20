<?php

namespace App\Filament\Resources\SupportExtraLuggages\Tables;

use App\Models\SupportExtraLuggage;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SupportExtraLuggagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('full_name')->label('Customer')->searchable()->description(fn (SupportExtraLuggage $record): string => $record->email),
                TextColumn::make('airline')->description(fn (SupportExtraLuggage $record): string => ucfirst($record->airline_category)),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid', 'confirmed', 'completed' => 'success',
                    'failed', 'cancelled' => 'danger',
                    default => 'warning',
                })->sortable(),
                TextColumn::make('data_page')->label('Passport page')->state('Download')
                    ->url(fn (SupportExtraLuggage $record): string => Storage::disk('public')->url($record->data_page))
                    ->openUrlInNewTab(),
                TextColumn::make('ticket')->label('Ticket')->state('Download')
                    ->url(fn (SupportExtraLuggage $record): string => Storage::disk('public')->url($record->ticket))
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
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
                self::changeStatusAction(),
            ]);
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->form(fn (SupportExtraLuggage $record): array => [
                Select::make('status')
                    ->label('Payment status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->default($record->payment_status)
                    ->required(),
            ])
            ->action(function (SupportExtraLuggage $record, array $data): void {
                $record->update(['payment_status' => $data['status']]);

                Notification::make()->title('Status updated')->success()->send();
            });
    }
}
