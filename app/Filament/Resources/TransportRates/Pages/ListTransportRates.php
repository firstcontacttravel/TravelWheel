<?php

namespace App\Filament\Resources\TransportRates\Pages;

use App\Filament\Resources\TransportRates\TransportRateResource;
use App\Models\TransportRate;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListTransportRates extends ListRecords
{
    protected static string $resource = TransportRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // One pump price for every vehicle type — shown to customers as
            // "Fuel (₦…)" in the Car Hire price breakdown, and it drives each
            // type's fuel/min (recalculated by TransportRate on save).
            Action::make('fuelPumpPrice')
                ->label(fn (): string => 'Fuel pump price: ₦' . number_format((int) TransportRate::max('fuel_pump_price')))
                ->icon('heroicon-o-beaker')
                ->fillForm(fn (): array => ['fuel_pump_price' => (int) TransportRate::max('fuel_pump_price')])
                ->form([
                    TextInput::make('fuel_pump_price')
                        ->label('Current fuel pump price (per litre)')
                        ->numeric()->minValue(0)->required()->prefix('₦'),
                ])
                ->action(function (array $data): void {
                    TransportRate::all()->each->update(['fuel_pump_price' => (int) $data['fuel_pump_price']]);
                    Notification::make()->title('Fuel pump price updated')->body('Fuel/min recalculated for every vehicle type.')->success()->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'car_hire' => Tab::make('Car Hire'),
            'pickup_dropoff' => Tab::make('Pickup & Dropoff'),
        ];
    }
}
