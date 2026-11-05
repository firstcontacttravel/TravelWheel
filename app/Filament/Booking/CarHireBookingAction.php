<?php

namespace App\Filament\Booking;

use App\Services\CarFleetCatalogService;
use App\Services\GroundBookingService;
use App\Support\GroundStates;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class CarHireBookingAction
{
    public static function make(): Action
    {
        return Action::make('bookCarHire')
            ->label('Car Hire')
            ->icon('heroicon-o-truck')
            ->modalHeading('New Car Hire booking')
            ->modalSubmitActionLabel('Create booking')
            ->form([
                ...self::vehicleFields('car_hire', 'car_type'),
                TextInput::make('rental_hours')->label('Hours')->numeric()->minValue(1)->required()->live(onBlur: true),
                TextInput::make('pickup_location')->required()->maxLength(255)->placeholder(fn (Get $get): string => GroundStates::example($get('state'))),
                ...StaffBookingActions::customerFields(),
                Placeholder::make('price')
                    ->label('Price')
                    ->content(function (Get $get): string {
                        if (blank($get('car_type')) || blank($get('category')) || (float) $get('rental_hours') < 1) {
                            return 'Choose a vehicle and hours';
                        }

                        return '₦'.number_format(app(GroundBookingService::class)->quoteCarHire($get('car_type'), $get('category'), (float) $get('rental_hours'))['total']);
                    }),
                StaffBookingActions::sendLinkToggle(),
            ])
            ->modalWidth('3xl')
            ->action(function (array $data, Action $action): void {
                try {
                    $booking = app(GroundBookingService::class)->createCarHire($data, 'seerbit');
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                    $action->halt();

                    return;
                }

                StaffBookingActions::finish($booking, $data);
            });
    }

    /**
     * State, then only the vehicle types and categories with a car there.
     *
     * @return list<Select>
     */
    public static function vehicleFields(string $service, string $typeField): array
    {
        $catalog = fn (?string $state): array => blank($state) ? [] : ($service === 'car_hire'
            ? app(CarFleetCatalogService::class)->categories($state)
            : app(CarFleetCatalogService::class)->transferVehicles($state));

        return [
            Select::make('state')
                ->options(fn (): array => array_intersect_key(GroundStates::all(), array_flip(GroundStates::open($service))))
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) use ($typeField): void {
                    $set($typeField, null);
                    $set('category', null);
                }),
            Select::make($typeField)
                ->label('Vehicle type')
                ->options(function (Get $get) use ($catalog): array {
                    $types = array_filter(
                        CarFleetCatalogService::bookableVehicleTypes(),
                        fn (string $type): bool => ! empty($catalog($get('state'))[$type]['items']),
                    );

                    return collect($types)->mapWithKeys(fn (string $type): array => [$type => $type === 'van' ? 'Mini Van' : ucfirst($type)])->all();
                })
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('category', null)),
            Select::make('category')
                ->options(fn (Get $get): array => collect($catalog($get('state'))[$get($typeField)]['items'] ?? [])
                    ->mapWithKeys(fn (array $item): array => [$item['name'] => $item['name']])
                    ->all())
                ->required()
                ->live(),
        ];
    }
}
