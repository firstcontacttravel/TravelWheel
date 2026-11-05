<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\Lounge;
use App\Services\LoungeBookingService;
use App\Services\LoungePairCatalogueSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Throwable;

/**
 * Staff booking an airport lounge for a customer, step by step like the
 * website. Prices come from the same service.
 */
class BookLounge extends Page
{
    private const MAX_GUESTS_PER_TYPE = 50;

    protected string $view = 'filament.pages.book-lounge';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/lounge';

    protected static ?string $title = 'Book Lounge';

    /** 'nigeria' or 'worldwide' */
    #[Url]
    public string $tab = 'nigeria';

    public string $state = '';

    /** 1 is International and 2 is Local, as lounges store it. */
    public ?int $access = null;

    public string $service = '';

    public string $iata = '';

    public string $searchedIata = '';

    public ?string $searchError = null;

    public ?int $loungeId = null;

    public int $adults = 1;

    public int $children = 0;

    public int $infants = 0;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $serviceDate = '';

    public string $departureTime = '';

    public string $ticketNo = '';

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
        $this->tab = $this->tab === 'worldwide' ? 'worldwide' : 'nigeria';
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab === 'worldwide' ? 'worldwide' : 'nigeria';
        $this->loungeId = null;
        $this->resetErrorBag();
    }

    public function updatedState(): void
    {
        $this->loungeId = null;
    }

    public function setAccess(int $access): void
    {
        $this->access = $access === 1 ? 1 : 2;
        $this->loungeId = null;
    }

    public function setService(string $service): void
    {
        $this->service = $service === 'Arrival' ? 'Arrival' : 'Departure';
        $this->loungeId = null;
    }

    /** Asks LoungePair for the airport's lounges, so it runs when asked for rather than on every keystroke. */
    public function searchAirport(LoungePairCatalogueSyncService $sync): void
    {
        $iata = strtoupper(trim($this->iata));
        $this->loungeId = null;
        $this->searchedIata = '';
        $this->searchError = null;

        if (! preg_match('/^[A-Z]{3}$/', $iata)) {
            $this->searchError = 'Enter a three-letter airport code, e.g. LHR, DXB or LOS.';

            return;
        }

        try {
            $sync->sync($iata);
            $this->searchedIata = $iata;
        } catch (Throwable $e) {
            report($e);
            $this->searchError = 'We could not get lounges for that airport right now. Please try again shortly.';
        }
    }

    public function selectLounge(int $id): void
    {
        $this->loungeId = $this->lounges()->contains('id', $id) ? $id : null;
    }

    public function changeGuests(string $type, int $by): void
    {
        if (in_array($type, ['adults', 'children', 'infants'], true)) {
            $this->{$type} = max(0, min(self::MAX_GUESTS_PER_TYPE, $this->{$type} + $by));
        }
    }

    public function create(LoungeBookingService $bookings): void
    {
        $guests = ['integer', 'min:0', 'max:'.self::MAX_GUESTS_PER_TYPE];

        $this->validate([
            'loungeId' => ['required', Rule::exists('lounges', 'id')->where('is_active', true)],
            'adults' => ['required', ...$guests],
            'children' => ['required', ...$guests],
            'infants' => ['required', ...$guests],
            'firstName' => ['required', 'string', 'max:120'],
            'lastName' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'serviceDate' => ['required', 'date', 'after_or_equal:today'],
            'departureTime' => ['required', 'date_format:H:i'],
            'ticketNo' => ['nullable', 'string', 'max:255'],
        ], [], [
            'loungeId' => 'lounge',
            'firstName' => 'first name',
            'lastName' => 'last name',
            'serviceDate' => 'service date',
            'departureTime' => 'departure time',
            'ticketNo' => 'ticket number',
        ]);

        $lounge = Lounge::active()->findOrFail($this->loungeId);

        if ($bookings->quote($lounge, $this->adults, $this->children, $this->infants)['total'] <= 0) {
            Notification::make()->title('There is nothing to charge yet. Add an adult or child, or choose another lounge.')->danger()->send();

            return;
        }

        $booking = $bookings->createPending($lounge, [
            'fullname' => trim($this->lastName.' '.$this->firstName),
            'email' => $this->email,
            'phone' => $this->phone,
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'travel_date' => $this->serviceDate,
            'd_time' => $this->departureTime,
            'ticket_no' => $this->ticketNo,
        ], 'seerbit');

        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        $lounges = $this->lounges();
        $selected = $lounges->firstWhere('id', $this->loungeId);

        return [
            'states' => array_keys(Lounge::LOCAL_AIRPORTS),
            'lounges' => $lounges,
            'selected' => $selected,
            'quote' => $selected ? app(LoungeBookingService::class)->quote($selected, $this->adults, $this->children, $this->infants) : null,
            'vatPercent' => LoungeBookingService::VAT_PERCENT,
            'searchDone' => $this->tab === 'worldwide' ? filled($this->searchedIata) : filled($this->state) && $this->access && filled($this->service),
        ];
    }

    /**
     * What the customer would be offered for these choices.
     *
     * @return Collection<int, Lounge>
     */
    private function lounges(): Collection
    {
        if ($this->tab === 'worldwide') {
            return blank($this->searchedIata) ? collect() : Lounge::active()
                ->where('provider', 'loungepair')
                ->where('provider_airport_iata', $this->searchedIata)
                ->latest('provider_synced_at')
                ->get();
        }

        if (blank($this->state) || ! $this->access || blank($this->service)) {
            return collect();
        }

        return Lounge::active()
            ->where('location', $this->state)
            ->where('airport', $this->access)
            ->where(fn ($query) => $query->where('service', $this->service)->orWhereNull('service'))
            ->latest()
            ->get();
    }
}
