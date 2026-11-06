<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\Protocol;
use App\Services\ProtocolBookingService;
use App\Support\ProtocolAirlines;
use App\Support\ProtocolVehicles;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Staff booking airport protocol for a customer, step by step like the
 * website. Prices come from the same service.
 */
class BookProtocol extends Page
{
    use WithFileUploads;

    private const OTHER_AIRLINE = 'OTHERS';

    protected string $view = 'filament.pages.book-protocol';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/protocol';

    protected static ?string $title = 'Book Protocol';

    public string $location = '';

    /** 'International Airport' or 'Local Airport', as the website names them. */
    public string $airport = '';

    public string $service = '';

    public string $plan = '';

    public string $travelDate = '';

    public string $time = '';

    public string $airline = '';

    public string $otherAirline = '';

    /** @var list<array{first: string, last: string, pnr: string, ticket: string, bags: int|string}> */
    public array $passengers = [];

    public string $optionalRequest = '';

    public string $vehicle = '';

    public string $vehicleAddress = '';

    public string $email = '';

    public string $phone = '';

    public string $nextOfKinName = '';

    public string $nextOfKinPhone = '';

    public ?TemporaryUploadedFile $meansId = null;

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
        $this->passengers = [self::blankPassenger()];
    }

    public function updatedLocation(): void
    {
        $this->resetChoice();
    }

    public function setAirport(string $airport): void
    {
        $this->airport = isset(ProtocolBookingService::AIRPORTS[$airport]) ? $airport : '';
        $this->resetChoice();
    }

    public function setService(string $service): void
    {
        $this->service = $service === 'Arrival' ? 'Arrival' : 'Departure';
        $this->optionalRequest = '';
        $this->resetChoice();
    }

    public function selectPlan(string $plan): void
    {
        $protocol = $this->protocol();
        $this->plan = $protocol && isset(app(ProtocolBookingService::class)->plans($protocol)[$plan]) ? $plan : '';
    }

    public function changePassengers(int $by): void
    {
        $count = max(1, min(ProtocolBookingService::MAX_PASSENGERS, count($this->passengers) + $by));
        $this->passengers = array_slice(array_pad($this->passengers, $count, self::blankPassenger()), 0, $count);
    }

    public function updatedOptionalRequest(): void
    {
        if (! ProtocolVehicles::needsVehicle($this->optionalRequest)) {
            $this->vehicle = '';
            $this->vehicleAddress = '';
        }
    }

    public function create(ProtocolBookingService $bookings): void
    {
        $protocol = $this->protocol();
        $needsVehicle = ProtocolVehicles::needsVehicle($this->optionalRequest);
        $airlines = ProtocolAirlines::for($this->location, $this->isInternational());

        $this->validate([
            'plan' => ['required', Rule::in(array_keys($protocol ? $bookings->plans($protocol) : []))],
            'travelDate' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'airline' => ['required', Rule::in([...$airlines, self::OTHER_AIRLINE])],
            'otherAirline' => [Rule::requiredIf($this->airline === self::OTHER_AIRLINE), 'nullable', 'string', 'max:255'],
            'passengers' => ['required', 'array', 'min:1', 'max:'.ProtocolBookingService::MAX_PASSENGERS],
            'passengers.*.first' => ['required', 'string', 'max:120'],
            'passengers.*.last' => ['required', 'string', 'max:120'],
            'passengers.*.pnr' => ['required', 'string', 'max:50'],
            'passengers.*.ticket' => ['required', 'string', 'max:50'],
            'passengers.*.bags' => ['required', 'integer', 'min:0', 'max:50'],
            'optionalRequest' => ['nullable', Rule::in($this->optionalRequests())],
            'vehicle' => [Rule::requiredIf($needsVehicle), 'nullable', Rule::in(array_keys(ProtocolVehicles::VEHICLES))],
            'vehicleAddress' => [Rule::requiredIf($needsVehicle), 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'nextOfKinName' => ['required', 'string', 'max:255'],
            'nextOfKinPhone' => ['required', 'string', 'max:20'],
            'meansId' => ['required', 'file', 'mimes:pdf,jpg,png', 'max:10240'],
        ], [
            'plan.required' => 'Choose a plan.',
            'plan.in' => 'That plan is not offered here.',
        ], [
            'travelDate' => 'travel date',
            'otherAirline' => 'airline name',
            'passengers.*.first' => 'first name',
            'passengers.*.last' => 'last name',
            'passengers.*.pnr' => 'PNR',
            'passengers.*.ticket' => 'e-ticket number',
            'passengers.*.bags' => 'number of bags',
            'vehicleAddress' => strtolower($this->vehicleDirection() ?? 'pick-up').' location',
            'nextOfKinName' => 'next of kin name',
            'nextOfKinPhone' => 'next of kin phone',
            'meansId' => 'means of ID',
        ]);

        if (! $protocol || $bookings->quote($protocol, $this->plan, count($this->passengers))['total'] <= 0) {
            Notification::make()->title('That protocol service is no longer available here.')->danger()->send();

            return;
        }

        $vehicle = $needsVehicle ? ProtocolVehicles::find($this->vehicle) : null;

        $booking = $bookings->createPending($protocol, [
            'plan' => $this->plan,
            'passengers' => array_map(fn (array $p) => ['first' => $p['first'], 'last' => $p['last'], 'pnr' => $p['pnr'], 'ticket' => $p['ticket'], 'bags' => (int) $p['bags']], $this->passengers),
            'email' => $this->email,
            'phone' => $this->phone,
            'travel_date' => $this->travelDate,
            'd_time' => $this->time,
            'airline' => $this->airline === self::OTHER_AIRLINE ? $this->otherAirline : $this->airline,
            'optional_request' => $this->optionalRequest ?: 'None',
            'optional_request_option' => $vehicle ? ProtocolVehicles::summary($vehicle['name'], 'Up to '.$vehicle['seats'].' Seaters', $vehicle['price']) : null,
            'optional_request_address' => $vehicle ? $this->vehicleAddress : null,
            'next_of_kin_name' => $this->nextOfKinName,
            'next_of_kin_phone' => $this->nextOfKinPhone,
            // Kept off the public web, unlike the website's uploads
            'means_id' => $this->meansId->store('protocol/means-id', 'local'),
        ], 'seerbit');

        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        $bookings = app(ProtocolBookingService::class);
        $protocol = $this->protocol();
        $plans = $protocol ? $bookings->plans($protocol) : [];

        return [
            'locations' => Protocol::query()->distinct()->orderBy('location')->pluck('location'),
            'protocol' => $protocol,
            'plans' => $plans,
            'quote' => $protocol && isset($plans[$this->plan]) ? $bookings->quote($protocol, $this->plan, count($this->passengers)) : null,
            'airlines' => ProtocolAirlines::for($this->location, $this->isInternational()),
            'requests' => $this->optionalRequests(),
            'vehicles' => ProtocolVehicles::all(),
            'vehicleDirection' => $this->vehicleDirection(),
            'vatPercent' => ProtocolBookingService::VAT_PERCENT,
            'chosen' => filled($this->location) && filled($this->airport) && filled($this->service),
        ];
    }

    private function protocol(): ?Protocol
    {
        return app(ProtocolBookingService::class)->find($this->location, $this->airport, $this->service);
    }

    private function isInternational(): bool
    {
        return $this->airport === 'International Airport';
    }

    /** @return list<string> */
    private function optionalRequests(): array
    {
        return $this->service === 'Arrival'
            ? ['Drop-off', 'Police Escort', 'Drop-off & Escort']
            : ['Pick-up', 'Police Escort', 'Pick-up & Escort'];
    }

    private function vehicleDirection(): ?string
    {
        if (! ProtocolVehicles::needsVehicle($this->optionalRequest)) {
            return null;
        }

        return str_contains($this->optionalRequest, 'Pick-up') ? 'Pick-up' : 'Drop-off';
    }

    private function resetChoice(): void
    {
        $this->plan = '';
        $this->airline = '';
    }

    /** @return array{first: string, last: string, pnr: string, ticket: string, bags: string} */
    private static function blankPassenger(): array
    {
        return ['first' => '', 'last' => '', 'pnr' => '', 'ticket' => '', 'bags' => ''];
    }
}
