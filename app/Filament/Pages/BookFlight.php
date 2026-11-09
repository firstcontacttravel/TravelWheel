<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\Country;
use App\Services\Flights\FlightPassengerRules;
use App\Services\Flights\FlightSearchStore;
use App\Services\Flights\FlightSupplierRegistry;
use App\Services\Flights\StaffFlightBooking;
use App\Support\FlightMarkup;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Throwable;

/**
 * Staff booking a flight for a customer without leaving the admin: a search of
 * the flight APIs that can hold a fare, the fare re-confirmed with the
 * airline, then the passengers. The fare is held, not paid; the customer pays
 * from an emailed link, or Finance records it and the flight team tickets.
 */
class BookFlight extends Page
{
    /** Changing any of these needs a new search. */
    private const SEARCH_FIELDS = ['trip', 'from', 'to', 'depart', 'returning', 'adults', 'children', 'infants', 'cabin'];

    private const CABINS = ['Y' => 'Economy', 'S' => 'Premium economy', 'C' => 'Business', 'F' => 'First'];

    private const TITLES = ['ADT' => ['Mr', 'Mrs', 'Ms', 'Dr'], 'CHD' => ['Master', 'Miss'], 'INF' => ['Master', 'Miss']];

    /** How long a re-confirmed fare is kept for staff to fill in the passengers. */
    private const FARE_MINUTES = 30;

    protected string $view = 'filament.pages.book-flight';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/flight';

    protected static ?string $title = 'Book Flight';

    public string $trip = 'oneway';

    /** "Lagos - Murtala Muhammed (LOS)": the website's form, ending in the airport code. */
    public string $from = '';

    public string $to = '';

    public string $depart = '';

    public string $returning = '';

    public int $adults = 1;

    public int $children = 0;

    public int $infants = 0;

    public string $cabin = 'Y';

    #[Locked]
    public ?string $searchId = null;

    public ?string $searchError = null;

    /** Where the chosen, re-confirmed fare is kept on the server: its price can't come from the browser. */
    #[Locked]
    public ?string $fareKey = null;

    public ?string $fareError = null;

    /** @var list<array<string, string>> */
    public array $passengers = [];

    public array $contact = ['email' => '', 'phone' => '', 'area_code' => '080', 'country_code' => '234'];

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

    public function updated(string $property): void
    {
        if (in_array($property, self::SEARCH_FIELDS, true)) {
            $this->searchId = null;
            $this->searchError = null;
            $this->clearFare();
        }
    }

    public function setTrip(string $trip): void
    {
        $this->trip = $trip === 'return' ? 'return' : 'oneway';
        $this->updated('trip');
    }

    public function changeCount(string $who, int $by): void
    {
        if (! in_array($who, ['adults', 'children', 'infants'], true)) {
            return;
        }

        $this->{$who} = max($who === 'adults' ? 1 : 0, min(9, $this->{$who} + $by));
        $this->infants = min($this->infants, $this->adults);
        $this->updated($who);
    }

    /** A live search of every switched-on API that can hold a fare. */
    public function search(StaffFlightBooking $staffFlight, FlightSearchStore $store): void
    {
        $this->validate([
            'trip' => ['required', Rule::in(['oneway', 'return'])],
            'from' => ['required', 'string', 'max:255', 'regex:/\([A-Za-z]{3}\)\s*$/'],
            'to' => ['required', 'string', 'max:255', 'regex:/\([A-Za-z]{3}\)\s*$/', 'different:from'],
            'depart' => ['required', 'date', 'after_or_equal:today'],
            'returning' => [Rule::requiredIf($this->trip === 'return'), 'nullable', 'date', 'after_or_equal:depart'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['required', 'integer', 'min:0', 'max:9'],
            'infants' => ['required', 'integer', 'min:0', 'max:9', 'lte:adults'],
            'cabin' => ['required', Rule::in(array_keys(self::CABINS))],
        ], [
            'from.regex' => 'Choose an airport from the list.',
            'to.regex' => 'Choose an airport from the list.',
            'to.different' => 'The destination must be a different airport.',
            'infants.lte' => 'Each infant must travel with an adult.',
        ], ['returning' => 'return date']);

        if ($this->adults + $this->children + $this->infants > 9) {
            throw ValidationException::withMessages(['adults' => 'A booking can have at most 9 passengers.']);
        }

        $this->searchId = null;
        $this->searchError = null;
        $this->clearFare();

        $suppliers = $staffFlight->holdingSuppliers();
        if ($suppliers === []) {
            $this->searchError = 'No flight API that can hold a fare is switched on. The flight team can switch one on under Flight APIs.';

            return;
        }

        $criteria = $this->criteria();
        $searchId = $store->start($criteria);
        set_time_limit(120);

        foreach ($suppliers as $key => $supplier) {
            try {
                $result = $supplier->search($criteria, ['search_id' => $searchId, 'started_at' => microtime(true), 'final_attempt' => true]);
            } catch (Throwable $e) {
                Log::warning('Staff flight search failed', ['supplier' => $key, 'error' => $e->getMessage()]);

                continue;
            }

            if (($result['error'] ?? true) || ($result['retry'] ?? false)) {
                continue;
            }

            $flights = array_values(array_map(fn (array $flight): array => FlightMarkup::apply($flight, null), (array) ($result['data']['flights'] ?? [])));
            $store->put($searchId, $key, $flights, (array) ($result['data']['meta'] ?? []));
        }

        $this->searchId = $searchId;
    }

    /** Re-confirms the fare with the airline, as the website does before its passenger form. */
    public function choose(string $supplierKey, string $fareSourceCode, StaffFlightBooking $staffFlight, FlightSearchStore $store, FlightSupplierRegistry $registry): void
    {
        $this->clearFare();
        $result = $this->searchId ? $store->result($this->searchId, $supplierKey) : null;
        $searched = collect($result['flights'] ?? [])->firstWhere('fareSourceCode', $fareSourceCode);

        if (! $searched || ! $staffFlight->holdable($searched)) {
            $this->fareError = "That fare can't be held. Search again or choose another.";

            return;
        }

        try {
            $selected = $registry->get($supplierKey)->select($fareSourceCode, $searched, $this->criteria(), ['session_id' => (string) ($result['meta']['session_id'] ?? '')]);
        } catch (Throwable $e) {
            report($e);
            $selected = ['error' => true];
        }

        if ($selected['error'] ?? true) {
            $this->fareError = ($selected['message'] ?? null) ?: 'The airline could not confirm this fare. Choose another or search again.';

            return;
        }

        $flight = FlightMarkup::apply($selected['data']['flight'], null);
        if (! $staffFlight->holdable($flight)) {
            $this->fareError = "This fare is paid by card when it is booked, so it can't be held. Choose another.";

            return;
        }

        $this->fareKey = 'staff-flight-fare:'.Str::uuid();
        Cache::put($this->fareKey, ['flight' => $flight, 'session_id' => (string) ($result['meta']['session_id'] ?? ''), 'criteria' => $this->criteria()], now()->addMinutes(self::FARE_MINUTES));

        $this->passengers = [];
        foreach (['ADT' => $this->adults, 'CHD' => $this->children, 'INF' => $this->infants] as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->passengers[] = ['type' => $type, 'title' => '', 'first_name' => '', 'last_name' => '', 'gender' => '', 'dob' => '', 'nationality' => 'NG',
                    'passport_no' => '', 'passport_issue_country' => '', 'passport_issue_date' => '', 'passport_exp' => ''];
            }
        }
    }

    public function changeFare(): void
    {
        if ($this->fareKey) {
            Cache::forget($this->fareKey);
        }

        $this->clearFare();
    }

    public function create(StaffFlightBooking $staffFlight, FlightPassengerRules $rules): void
    {
        $fare = $this->fare();
        if (! $fare) {
            $this->clearFare();
            $this->fareError = 'The confirmed fare has expired. Choose the flight again.';

            return;
        }

        $this->validate([
            'contact.email' => ['required', 'email', 'max:255'],
            'contact.phone' => ['required', 'string', 'min:7', 'max:20'],
            'contact.area_code' => ['required', 'string', 'max:6'],
            'contact.country_code' => ['required', 'string', 'max:6'],
            'passengers' => ['required', 'array', 'size:'.($this->adults + $this->children + $this->infants)],
            'passengers.*.type' => ['required', Rule::in(['ADT', 'CHD', 'INF'])],
            'passengers.*.title' => ['required', Rule::in(['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Master'])],
            'passengers.*.first_name' => ['required', 'string', 'max:100'],
            'passengers.*.last_name' => ['required', 'string', 'max:100'],
            'passengers.*.gender' => ['required', Rule::in(['M', 'F'])],
            'passengers.*.dob' => ['required', 'date', 'before:today'],
            'passengers.*.nationality' => ['required', 'string', 'size:2'],
            'passengers.*.passport_no' => ['nullable', 'string', 'max:20'],
            'passengers.*.passport_issue_country' => ['nullable', 'string', 'size:2'],
            'passengers.*.passport_issue_date' => ['nullable', 'date'],
            'passengers.*.passport_exp' => ['nullable', 'date', 'after:today'],
        ], [], $this->passengerNames());

        $passengers = array_map(fn (array $pax): array => array_map(fn ($value) => is_string($value) ? trim($value) : $value, $pax), $this->passengers);
        $counts = collect($passengers)->countBy('type');
        if ((int) ($counts['ADT'] ?? 0) !== $this->adults || (int) ($counts['CHD'] ?? 0) !== $this->children || (int) ($counts['INF'] ?? 0) !== $this->infants) {
            throw ValidationException::withMessages(['passengers' => 'The passengers no longer match the search. Choose the flight again.']);
        }
        $rules->validate($passengers, $fare['flight'], $rules->travelDate($fare['flight']));

        try {
            $booking = $staffFlight->hold($fare['flight'], $fare['criteria'], $fare['session_id'], $this->contact, $passengers);
        } catch (ValidationException $e) {
            $this->fareError = collect($e->errors())->flatten()->first();

            return;
        }

        Cache::forget($this->fareKey);
        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        $flights = collect();
        if ($this->searchId) {
            $store = app(FlightSearchStore::class);
            $staffFlight = app(StaffFlightBooking::class);
            foreach (array_keys($staffFlight->holdingSuppliers()) as $key) {
                $flights = $flights->concat(collect($store->result($this->searchId, $key)['flights'] ?? [])->filter(fn (array $flight): bool => $staffFlight->holdable($flight)));
            }
        }

        return [
            'cabins' => self::CABINS,
            'titles' => self::TITLES,
            'countries' => Country::query()->where('is_active', true)->whereNotNull('alpha2')->orderBy('name')->pluck('name', 'alpha2'),
            'flights' => $flights->sortBy('price')->take(40)->values(),
            'fare' => $this->fare()['flight'] ?? null,
        ];
    }

    /** The search as the website's form sends it, which every flight API reads. */
    private function criteria(): array
    {
        return [
            'trip' => $this->trip,
            'from' => $this->from,
            'to' => $this->to,
            'depart' => Carbon::parse($this->depart)->format('d/m/Y'),
            'returning' => $this->trip === 'return' && $this->returning ? Carbon::parse($this->returning)->format('d/m/Y') : null,
            'multi_legs' => [],
            'adults' => $this->adults,
            'childs' => $this->children,
            'kids' => $this->infants,
            'flight_type' => $this->cabin,
        ];
    }

    private function fare(): ?array
    {
        return $this->fareKey ? Cache::get($this->fareKey) : null;
    }

    private function clearFare(): void
    {
        $this->fareKey = null;
        $this->fareError = null;
        $this->passengers = [];
    }

    private function passengerNames(): array
    {
        $names = ['contact.email' => 'email', 'contact.phone' => 'phone'];
        foreach (['title' => 'title', 'first_name' => 'first name', 'last_name' => 'last name', 'gender' => 'gender', 'dob' => 'date of birth',
            'nationality' => 'nationality', 'passport_no' => 'passport number', 'passport_issue_country' => 'issuing country',
            'passport_issue_date' => 'issue date', 'passport_exp' => 'expiry date'] as $field => $label) {
            $names["passengers.*.$field"] = $label;
        }

        return $names;
    }
}
