<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Services\CarFleetCatalogService;
use App\Services\GroundBookingService;
use App\Services\TransferPricingService;
use App\Support\GroundStates;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use RuntimeException;
use Throwable;

/**
 * Staff booking Car Hire or Pick up 'n' Drop off for a customer, step by step
 * like the website's booking page. Prices come from the same services.
 */
class BookGroundTransport extends Page
{
    protected string $view = 'filament.pages.book-ground-transport';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/car-hire';

    protected static ?string $title = 'Book Car Hire';

    /** 'car' or 'transfer' */
    #[Url]
    public string $tab = 'car';

    public string $state = '';

    public string $vehicleType = '';

    public string $category = '';

    public ?float $hours = null;

    public bool $customHours = false;

    public string $pickup = '';

    public string $dropoff = '';

    /** The route Google gave for these two addresses. Cleared when either changes. */
    #[Locked]
    public ?int $routeKm = null;

    #[Locked]
    public ?int $routeMins = null;

    #[Locked]
    public ?string $routeDrive = null;

    public ?string $routeError = null;

    public string $fullName = '';

    public string $email = '';

    public string $phone = '';

    public ?int $passengers = 1;

    public string $pickupDate = '';

    public string $pickupTime = '';

    public string $flightNumber = '';

    public string $specialRequests = '';

    public bool $sendLink = true;

    public static function canAccess(): bool
    {
        return auth()->user()?->canBookForCustomers() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to bookings')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => NewBooking::getUrl()),
        ];
    }

    public function mount(): void
    {
        $this->tab = $this->tab === 'transfer' ? 'transfer' : 'car';
        $states = GroundStates::open($this->service());
        $this->state = count($states) === 1 ? $states[0] : '';
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab === 'transfer' ? 'transfer' : 'car';
        $this->resetVehicle();

        if (! in_array($this->state, GroundStates::open($this->service()), true)) {
            $this->state = '';
        }
    }

    public function updatedState(): void
    {
        $this->resetVehicle();
    }

    public function selectType(string $type): void
    {
        $this->vehicleType = $type;
        $this->category = '';
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
    }

    public function setHours(?int $hours): void
    {
        $this->customHours = $hours === null;
        $this->hours = $hours;
    }

    public function updatedPickup(): void
    {
        $this->clearRoute();
    }

    public function updatedDropoff(): void
    {
        $this->clearRoute();
    }

    /** A paid Google lookup, so it runs when asked for rather than on every keystroke. */
    public function getRoute(GroundBookingService $bookings): void
    {
        $this->clearRoute();

        if (blank($this->pickup) || blank($this->dropoff)) {
            $this->routeError = 'Enter both the pick-up and drop-off locations.';

            return;
        }

        try {
            $route = $bookings->distance($this->pickup, $this->dropoff);
        } catch (RuntimeException $e) {
            $this->routeError = $e->getMessage();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->routeError = 'Distance calculation failed. Please try again.';

            return;
        }

        $this->routeKm = $route['distance_km'];
        $this->routeMins = $route['duration_mins'];
        $this->routeDrive = $route['drive_time'];
    }

    public function create(GroundBookingService $bookings): void
    {
        $limit = CarFleetCatalogService::PASSENGER_LIMITS[$this->vehicleType] ?? 20;

        $this->validate([
            'state' => ['required', Rule::in(GroundStates::open($this->service()))],
            'vehicleType' => ['required', Rule::in(CarFleetCatalogService::bookableVehicleTypes())],
            'category' => ['required', 'in:Regular,Standard,Executive'],
            'hours' => $this->tab === 'car' ? ['required', 'numeric', 'min:1'] : ['nullable'],
            'pickup' => ['required', 'string', 'max:255'],
            'dropoff' => $this->tab === 'transfer' ? ['required', 'string', 'max:255'] : ['nullable'],
            'routeMins' => $this->tab === 'transfer' ? ['required'] : ['nullable'],
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'passengers' => ['required', 'integer', 'min:1', "max:{$limit}"],
            'pickupDate' => ['required', 'date', 'after_or_equal:today'],
            'pickupTime' => ['required'],
            'flightNumber' => ['nullable', 'string', 'max:50'],
            'specialRequests' => ['nullable', 'string', 'max:500'],
        ], [
            'routeMins.required' => 'Get the price for this route first.',
            'passengers.max' => "This vehicle carries up to {$limit} passengers.",
        ], [
            'state' => 'state',
            'vehicleType' => 'vehicle type',
            'pickup' => 'pick-up location',
            'dropoff' => 'drop-off location',
            'fullName' => 'customer name',
            'pickupDate' => 'pick-up date',
            'pickupTime' => 'pick-up time',
        ]);

        $shared = [
            'state' => $this->state,
            'category' => $this->category,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone_number' => $this->phone,
            'passengers' => $this->passengers,
            'pickup_location' => $this->pickup,
            'pickup_date' => $this->pickupDate,
            'pickup_time' => $this->pickupTime,
        ];

        try {
            $booking = $this->tab === 'car'
                ? $bookings->createCarHire($shared + ['car_type' => $this->vehicleType, 'rental_hours' => $this->hours], 'seerbit')
                : $bookings->createTransfer($shared + [
                    'vehicle_type' => $this->vehicleType,
                    'dropoff_location' => $this->dropoff,
                    'distance_km' => $this->routeKm,
                    'duration_mins' => $this->routeMins,
                    'flight_number' => $this->flightNumber ?: null,
                    'special_requests' => $this->specialRequests ?: null,
                ], 'seerbit');
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        $catalog = app(CarFleetCatalogService::class);
        $vehicles = blank($this->state) ? [] : ($this->tab === 'car'
            ? $catalog->categories($this->state)
            : $catalog->transferVehicles($this->state));

        $types = array_values(array_filter(
            CarFleetCatalogService::bookableVehicleTypes(),
            fn (string $type): bool => ! empty($vehicles[$type]['items']),
        ));

        $categories = $vehicles[$this->vehicleType]['items'] ?? [];
        $states = array_flip(GroundStates::open($this->service()));

        return [
            'stateGroups' => collect(GroundStates::grouped())
                ->map(fn (array $group): array => array_intersect_key($group, $states))
                ->filter()
                ->all(),
            'vehicles' => $vehicles,
            'types' => $types,
            'categories' => $categories,
            'selected' => collect($categories)->firstWhere('name', $this->category),
            'quote' => $this->quote(),
            'fuelPump' => $vehicles[$this->vehicleType]['fuel_pump_price'] ?? null,
            'passengerLimit' => CarFleetCatalogService::PASSENGER_LIMITS[$this->vehicleType] ?? null,
            'typeThumbs' => $catalog->typeThumbs($vehicles, []),
            'addressExample' => GroundStates::example($this->state),
            'airportExample' => GroundStates::airportExample($this->state),
            'mapsKey' => config('services.google_maps.key'),
        ];
    }

    private function quote(): ?array
    {
        if (blank($this->vehicleType) || blank($this->category)) {
            return null;
        }

        if ($this->tab === 'car') {
            return (float) $this->hours >= 1
                ? app(GroundBookingService::class)->quoteCarHire($this->vehicleType, $this->category, (float) $this->hours)
                : null;
        }

        return $this->routeMins
            ? app(TransferPricingService::class)->quote($this->vehicleType, $this->category, $this->routeMins)
            : null;
    }

    private function service(): string
    {
        return $this->tab === 'car' ? 'car_hire' : 'transfer';
    }

    private function resetVehicle(): void
    {
        $this->vehicleType = '';
        $this->category = '';
    }

    private function clearRoute(): void
    {
        $this->routeKm = null;
        $this->routeMins = null;
        $this->routeDrive = null;
        $this->routeError = null;
    }
}
