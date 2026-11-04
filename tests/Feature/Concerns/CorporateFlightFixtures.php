<?php

namespace Tests\Feature\Concerns;

use App\Models\Company;
use App\Models\CompanyServiceCharge;
use App\Models\CompanyUser;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Services\Flights\FlightSupplierControl;
use App\Support\FlightMarkup;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/** A company, its people, and a faked SkyLink and SeerBit, for walking real flight routes. */
trait CorporateFlightFixtures
{
    protected const SUPPLIER_FARE = 900000;

    protected const PASSWORD = 'correct-horse-42';

    /** What SkyLink's pricing call answers. A test raises this to make the fare rise. */
    protected int $verifiedPrice = self::SUPPLIER_FARE;

    protected function setUpCorporateFlights(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            'services.skylink.search_cache_ttl' => 0,
            'services.seerbit.public_key' => 'test-public',
            'services.seerbit.secret_key' => 'test-secret',
        ]);
        FlightMarkup::forgetCachedConfiguration();
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);

        $control = app(FlightSupplierControl::class);
        $control->enable('skylink', null);
        $control->disable('travelnext', 'Tests use SkyLink', null);

        Mail::fake();
        $this->fakeSkylink();
    }

    protected function company(string $cac = 'RC100', string $status = 'active'): Company
    {
        return Company::create([
            'name' => 'Acme '.$cac,
            'cac_number' => $cac,
            'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test',
            'status' => $status,
        ]);
    }

    protected function booker(?Company $company = null, array $attributes = []): CompanyUser
    {
        $company ??= $this->company();

        return $company->users()->create(array_merge([
            'name' => 'Booker',
            'email' => 'booker'.uniqid().'@acme.test',
            'password' => self::PASSWORD,
            'role' => 'booker',
        ], $attributes));
    }

    protected function signIn(CompanyUser $user): void
    {
        $this->post(route('business.login.store'), ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('business.dashboard'));
    }

    protected function signOut(): void
    {
        $this->post(route('business.logout'));
        $this->nextRequest();
    }

    /** A test keeps one app between requests; a real request reads the session and database afresh. */
    protected function nextRequest(): void
    {
        Auth::forgetGuards();
    }

    /** The same charge on every route and cabin, so a test doesn't depend on which one a flight lands in. */
    protected function setRate(?Company $company, float $amount): void
    {
        foreach (FlightMarkup::publicCharges() as $category => $cabins) {
            foreach (array_keys($cabins) as $cabin) {
                CompanyServiceCharge::create([
                    'company_id' => $company?->id,
                    'route_category' => $category,
                    'cabin' => $cabin,
                    'amount' => $amount,
                ]);
            }
        }

        FlightMarkup::forgetCachedConfiguration();
    }

    protected function searchForm(): array
    {
        return [
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'Dubai (DXB)',
            'depart' => now()->addDays(40)->format('d/m/Y'),
            'adults' => 1,
            'childs' => 0,
            'kids' => 0,
            'flight_type' => 'Y',
        ];
    }

    /** Runs a search through the loading page and returns the first flight the page would show. */
    protected function searchSequentially(): array
    {
        $this->post(route('flights.search'), $this->searchForm())->assertRedirect(route('flights.search.loading'));
        $this->get(route('flights.search.run'))->assertRedirect(route('air.flight-s'));

        return session('flightResultsStore')[0];
    }

    /** Selects a fare and returns the price checkout holds. */
    protected function select(array $flight): array
    {
        $this->post(route('flights.select'), [
            'fare_source_code' => $flight['fareSourceCode'],
            'source' => 'skylink',
            'session_id' => '',
        ])->assertRedirect(route('flights.booking'));

        return session('bookingFlight');
    }

    protected function passengerDetails(): void
    {
        session([
            'bookingContact' => ['email' => 'traveller@example.test', 'phone' => '8000000000', 'country_code' => '234'],
            'bookingPassengers' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
                'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG',
            ]],
        ]);
    }

    protected function fakeSkylink(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($url, '/payments/query/') => Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00', 'gatewayMessage' => 'Successful',
                    'amount' => (float) FlightBooking::query()->latest('id')->value('payment_amount'), 'currency' => 'NGN',
                ]]]),
                str_contains($url, '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]]),
                str_ends_with($url, '/api/login') => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
                str_ends_with($url, 'flights/search') => Http::response(['success' => true, 'data' => ['flights' => [$this->rawFlight()]]]),
                str_ends_with($url, 'flights/pricing') => Http::response(['success' => true, 'data' => [
                    'booking_token' => 'btk_repriced', 'verified' => true,
                    'original_price' => self::SUPPLIER_FARE, 'verified_price' => $this->verifiedPrice, 'currency' => 'NGN',
                ]]),
                str_ends_with($url, 'flights/reserve') => Http::response(['success' => true, 'data' => [
                    'pnr' => 'SKY-PNR-9', 'status' => 'confirmed', 'ticket_deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
                ]]),
                default => Http::response([]),
            };
        });
    }

    protected function rawFlight(): array
    {
        $depart = now()->addDays(40);

        return [
            'price' => self::SUPPLIER_FARE,
            'actual_adult_base' => self::SUPPLIER_FARE,
            'currency' => 'NGN',
            'booking_token' => 'btk_search',
            'seats_left' => 4,
            'segments' => [[[
                'img' => 'AT',
                'flight_no' => 'AT576',
                'airline' => 'Royal Air Maroc',
                'class' => 'economy',
                'class_letter' => 'O',
                'departure_code' => 'LOS',
                'departure_time' => '07:15 pm',
                'departure_date' => $depart->format('d-m-Y'),
                'arrival_code' => 'DXB',
                'arrival_time' => '02:00 am',
                'arrival_date' => $depart->copy()->addDay()->format('d-m-Y'),
                'duration_time' => '6h 45m',
                'seg_duration' => '6h 45m',
                'refundable' => 1,
            ]]],
        ];
    }
}
