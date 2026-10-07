<?php

namespace App\Filament\Resources\AirCargoBookings\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\AirCargoModel;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AirCargoBookingsTable
{
    private const STATUS_OPTIONS = [
        'Pending' => 'Pending',
        'successful' => 'Successful',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('workItem.owner'))
            ->columns([
                WorkItemTable::ownerColumn(),
                TextColumn::make('shipping_id')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('fullname')->label('Customer')->searchable()->description(fn (AirCargoModel $record): string => $record->email),
                TextColumn::make('phone')->copyable(),
                TextColumn::make('shipment_type')->badge()->description(fn (AirCargoModel $record): string => (string) $record->shipping_to),
                TextColumn::make('total_price')->label('Total'),
                TextColumn::make('payment_status')->badge()->color(fn (string $state): string => match ($state) {
                    'successful' => 'success',
                    'failed', 'cancelled' => 'danger',
                    default => 'warning',
                })->sortable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                WorkItemTable::filter(),
                SelectFilter::make('payment_status')->options(self::STATUS_OPTIONS),
            ])
            ->recordActions([
                ViewAction::make(),
                FulfilmentActions::rowGroup(),
                self::downloadDocumentAction(),
            ]);
    }

    public static function downloadDocumentAction(): Action
    {
        return Action::make('downloadDocument')
            ->label('Download document')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (AirCargoModel $record): bool => filled($record->shipment_details))
            ->action(function (AirCargoModel $record): ?StreamedResponse {
                $path = 'public/shipments/' . $record->shipment_details;

                if (!Storage::exists($path)) {
                    Notification::make()->title('Document not found on server')->danger()->send();

                    return null;
                }

                return Storage::download($path, $record->shipment_details);
            });
    }
}
