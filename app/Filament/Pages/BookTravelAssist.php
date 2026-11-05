<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Services\SupportBookingService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Staff making a Travel Assist request for a customer: the two services the
 * website offers. Prices come from the same service.
 */
class BookTravelAssist extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.book-travel-assist';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/travel-assist';

    protected static ?string $title = 'Book Travel Assist';

    /** 'flight_assist' or 'yellow_card' */
    #[Url]
    public string $tab = 'flight_assist';

    public string $requestType = '';

    public string $bookingSource = '';

    public string $nameOnTicket = '';

    public string $airlineReference = '';

    public string $airlineCategory = '';

    public string $airline = '';

    public string $tripType = '';

    public string $travelDate = '';

    public string $departureDate = '';

    public string $returnDate = '';

    public string $routeFrom = '';

    public string $routeTo = '';

    public string $preferredTime = '';

    public string $additionalInfo = '';

    public string $serviceType = '';

    public string $fullName = '';

    public string $homeAddress = '';

    public string $deliveryAddress = '';

    public ?TemporaryUploadedFile $dataPage = null;

    public string $email = '';

    public string $phone = '';

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
        $this->tab = $this->tab === 'yellow_card' ? 'yellow_card' : 'flight_assist';
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab === 'yellow_card' ? 'yellow_card' : 'flight_assist';
        $this->resetErrorBag();
    }

    public function updatedAirlineCategory(): void
    {
        $this->airline = '';
    }

    public function createFlightAssist(SupportBookingService $bookings): void
    {
        $data = $this->validate([
            'requestType' => ['required', 'in:date_change,rerouting'],
            'bookingSource' => ['required', 'in:airline'],
            'nameOnTicket' => ['required', 'string', 'max:255'],
            'airlineReference' => ['required', 'string', 'max:255'],
            'airlineCategory' => ['required', Rule::in(array_keys(SupportBookingService::AIRLINES))],
            'airline' => ['required', Rule::in(SupportBookingService::AIRLINES[$this->airlineCategory] ?? [])],
            'tripType' => ['required', 'in:one_way,round_trip'],
            'travelDate' => [Rule::requiredIf($this->tripType === 'one_way'), 'nullable', 'date'],
            'departureDate' => [Rule::requiredIf($this->tripType === 'round_trip'), 'nullable', 'date'],
            'returnDate' => [Rule::requiredIf($this->tripType === 'round_trip'), 'nullable', 'date', 'after_or_equal:departureDate'],
            'routeFrom' => ['nullable', 'string', 'max:255'],
            'routeTo' => ['nullable', 'string', 'max:255'],
            'preferredTime' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'additionalInfo' => ['nullable', 'string', 'max:2000'],
        ], [
            'bookingSource.in' => 'A ticket bought from a travel agent has to be changed by that agent.',
        ], [
            'requestType' => 'request type',
            'bookingSource' => 'where the ticket was booked',
            'nameOnTicket' => 'name on ticket',
            'airlineReference' => 'airline reference',
            'airlineCategory' => 'airline category',
            'tripType' => 'trip type',
            'travelDate' => 'travel date',
            'departureDate' => 'departure date',
            'returnDate' => 'return date',
        ]);

        $oneWay = $data['tripType'] === 'one_way';

        $booking = $bookings->createFlightAssist([
            'request_type' => $data['requestType'],
            'booking_source' => $data['bookingSource'],
            'name_on_ticket' => $data['nameOnTicket'],
            'airline_reference' => $data['airlineReference'],
            'airline_category' => $data['airlineCategory'],
            'airline' => $data['airline'],
            'trip_type' => $data['tripType'],
            // Only the dates that belong to the chosen trip type, whatever was typed before switching
            'travel_date_oneway' => $oneWay ? $data['travelDate'] : null,
            'departure_date' => $oneWay ? null : $data['departureDate'],
            'return_date' => $oneWay ? null : $data['returnDate'],
            'route_from' => $data['routeFrom'] ?: null,
            'route_to' => $data['routeTo'] ?: null,
            'preferred_time' => $data['preferredTime'] ?: null,
            'phone' => $data['phone'],
            'email' => $data['email'],
            'additional_info' => $data['additionalInfo'] ?: null,
        ]);

        StaffBookingActions::finish($booking, ['send_link' => false]);

        $this->redirect(NewBooking::getUrl());
    }

    public function createYellowCard(SupportBookingService $bookings): void
    {
        $data = $this->validate([
            'serviceType' => ['required', Rule::in(array_keys(SupportBookingService::YELLOW_CARD_SERVICES))],
            'fullName' => ['required', 'string', 'max:255'],
            'dataPage' => ['required', 'file', 'mimes:pdf,jpg,png', 'max:10240'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'homeAddress' => ['required', 'string', 'max:255'],
            'deliveryAddress' => ['required', 'string', 'max:255'],
        ], [], [
            'serviceType' => 'service type',
            'fullName' => 'full name',
            'dataPage' => 'passport data page',
            'homeAddress' => 'home address',
            'deliveryAddress' => 'delivery address',
        ]);

        $booking = $bookings->createYellowCard([
            'service_type' => $data['serviceType'],
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'phone_number' => $data['phone'],
            'home_address' => $data['homeAddress'],
            'delivery_address' => $data['deliveryAddress'],
        ], $this->dataPage->store('support/yellow-card', 'public'), 'seerbit');

        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        return [
            'flightAssistPrice' => app(SupportBookingService::class)->flightAssistPrice(),
            'yellowCardServices' => SupportBookingService::YELLOW_CARD_SERVICES,
            'airlines' => SupportBookingService::AIRLINES[$this->airlineCategory] ?? [],
        ];
    }
}
