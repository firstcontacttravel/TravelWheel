<?php

namespace App\Filament\Booking;

use App\Services\GroundBookingService;
use App\Services\TransferPricingService;
use App\Support\GroundStates;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TransferBookingAction
{
    public static function make(): Action
    {
        return Action::make('bookTransfer')
            ->label("Pick up 'n' Drop off")
            ->icon('heroicon-o-map-pin')
            ->modalHeading("New Pick up 'n' Drop off booking")
            ->modalSubmitActionLabel('Create booking')
            ->form([
                ...CarHireBookingAction::vehicleFields('transfer', 'vehicle_type'),
                TextInput::make('pickup_location')->required()->maxLength(255)->live(onBlur: true)->placeholder(fn (Get $get): string => GroundStates::airportExample($get('state'))),
                TextInput::make('dropoff_location')->required()->maxLength(255)->live(onBlur: true)->placeholder(fn (Get $get): string => GroundStates::example($get('state'))),
                ...StaffBookingActions::customerFields(),
                TextInput::make('flight_number')->label('Flight / vessel no. (optional)')->maxLength(50),
                TextInput::make('special_requests')->label('Special requests (optional)')->maxLength(500),
                Placeholder::make('price')
                    ->label('Price')
                    ->content(function (Get $get): string {
                        if (blank($get('vehicle_type')) || blank($get('category')) || blank($get('pickup_location')) || blank($get('dropoff_location'))) {
                            return 'Choose a vehicle and enter both locations';
                        }

                        try {
                            $route = self::route($get('pickup_location'), $get('dropoff_location'));
                        } catch (RuntimeException $e) {
                            return $e->getMessage();
                        }

                        $quote = app(TransferPricingService::class)->quote($get('vehicle_type'), $get('category'), $route['duration_mins']);

                        return '₦'.number_format($quote['total']).' ('.$route['distance_km'].' km, '.$route['drive_time'].')';
                    }),
                StaffBookingActions::sendLinkToggle(),
            ])
            ->modalWidth('3xl')
            ->action(function (array $data, Action $action): void {
                try {
                    $route = self::route($data['pickup_location'], $data['dropoff_location']);
                    $booking = app(GroundBookingService::class)->createTransfer($data + [
                        'distance_km' => $route['distance_km'],
                        'duration_mins' => $route['duration_mins'],
                    ], 'seerbit');
                } catch (DomainException|RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                    $action->halt();

                    return;
                }

                StaffBookingActions::finish($booking, $data);
            });
    }

    /** Held briefly: the form re-renders on every field, and each lookup is a paid Google call. */
    private static function route(string $from, string $to): array
    {
        return Cache::remember('staff-booking-route:'.sha1($from.'|'.$to), now()->addMinutes(10), fn (): array => app(GroundBookingService::class)->distance($from, $to));
    }
}
