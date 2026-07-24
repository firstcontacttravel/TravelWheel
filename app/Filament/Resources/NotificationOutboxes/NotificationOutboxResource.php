<?php

namespace App\Filament\Resources\NotificationOutboxes;

use App\Filament\Resources\NotificationOutboxes\Pages\ListNotificationOutboxes;
use App\Models\NotificationOutbox;
use App\Services\DurableMailService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificationOutboxResource extends Resource
{
    protected static ?string $model = NotificationOutbox::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Email Delivery';

    protected static ?int $navigationSort = 45;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('kind')->label('Email type')->badge()->searchable(),
                TextColumn::make('recipient')->searchable()->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'processing' => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('attempts')->numeric(),
                TextColumn::make('last_error')->limit(80)->wrap(),
                TextColumn::make('available_at')->dateTime()->sortable(),
                TextColumn::make('sent_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'failed' => 'Failed',
                    'sent' => 'Sent',
                ]),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Retry now')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (NotificationOutbox $record): bool => ! $record->sent_at)
                    ->requiresConfirmation()
                    ->action(function (NotificationOutbox $record): void {
                        $sent = app(DurableMailService::class)->deliver($record);

                        Notification::make()
                            ->title($sent ? 'Email delivered' : 'Email retained for another retry')
                            ->{$sent ? 'success' : 'warning'}()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationOutboxes::route('/'),
        ];
    }
}
