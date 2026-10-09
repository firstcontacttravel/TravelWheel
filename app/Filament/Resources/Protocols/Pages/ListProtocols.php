<?php

namespace App\Filament\Resources\Protocols\Pages;

use App\Filament\Resources\Protocols\ProtocolResource;
use App\Support\ProtocolVehicles;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProtocols extends ListRecords
{
    protected static string $resource = ProtocolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Prices shown beside each pick-up / drop-off vehicle on the protocol form.
            // For information only: they are never added to the protocol amount.
            Action::make('vehiclePrices')
                ->label('Pick-up vehicle prices')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->modalDescription('Shown to customers who request a pick-up or drop-off. These prices are not added to the protocol amount; the customer pays for the vehicle separately. Leave a price empty to show "Price on request".')
                ->fillForm(fn () => collect(ProtocolVehicles::all())->map(fn ($vehicle) => $vehicle['price'])->all())
                ->form(collect(ProtocolVehicles::VEHICLES)->map(fn ($vehicle, $key) => TextInput::make($key)
                    ->label($vehicle['name'].' (up to '.$vehicle['seats'].' seats)')
                    ->numeric()->minValue(0)->prefix('₦'))->values()->all())
                ->action(function (array $data) {
                    foreach (array_keys(ProtocolVehicles::VEHICLES) as $key) {
                        ProtocolVehicles::setPrice($key, $data[$key] ?? null);
                    }
                    Notification::make()->title('Pick-up vehicle prices updated')->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}
