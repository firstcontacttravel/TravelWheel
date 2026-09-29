<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightPage;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Services\Flights\FlightBookingGuard;
use App\Services\Flights\FlightSupplierControl;
use App\Support\FlightMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A switched-off API can't take a NEW booking. Nothing already at the
 * supplier — a TravelNext hold — is ever stopped.
 */
class FlightBookingGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->control()->enable('skylink', null);
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    // ── Choosing a fare ───────────────────────────────────────────────────

    public function test_choosing_a_switched_off_apis_fare_offers_the_same_flight_elsewhere(): void
    {
        $this->control()->disable('skylink', 'Supplier outage', null);
        Http::fake();

        $this->withSession([
            'searchParamsStore' => ['trip' => 'oneway', 'adults' => 1],
            'flightResultsStore' => FlightMatch::tag([$this->flight('travelnext', 'TN-SAME', 820000)]),
            'supplementResultsStore' => ['skylink' => FlightMatch::tag([$this->flight('skylink', 'btk_same', 790000)])],
        ])->post(route('flights.select'), [
            'fare_source_code' => 'btk_same',
            'source' => 'skylink',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable', fn (array $notice): bool => $notice['message'] === FlightBookingGuard::MESSAGE
                && $notice['alternate']['source'] === 'travelnext'
                && $notice['alternate']['fareSourceCode'] === 'TN-SAME'
                && $notice['alternate']['price'] === 820000.0);

        // Nothing sent to the switched-off API.
        Http::assertNothingSent();
    }

    public function test_with_no_other_offer_the_customer_is_sent_back_to_choose(): void
    {
        $this->control()->disable('skylink', 'Supplier outage', null);
        Http::fake();

        $this->withSession([
            'searchParamsStore' => ['trip' => 'oneway', 'adults' => 1],
            'supplementResultsStore' => ['skylink' => [$this->flight('skylink', 'btk_only', 790000)]],
        ])->post(route('flights.select'), [
            'fare_source_code' => 'btk_only',
            'source' => 'skylink',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable', fn (array $notice): bool => $notice['alternate'] === null);
    }

    public function test_an_alternate_from_another_switched_off_api_is_not_offered(): void
    {
        $this->control()->disable('skylink', 'Supplier outage', null);
        $this->control()->disable('travelnext', 'Maintenance', null);

        session([
            'flightResultsStore' => [$this->flight('travelnext', 'TN-SAME', 820000)],
        ]);

        $this->assertNull(app(FlightBookingGuard::class)->alternateFor($this->flight('skylink', 'btk_same', 790000), 'skylink'));
    }

    // ── Entering passengers ───────────────────────────────────────────────

    public function test_no_hold_is_placed_with_a_switched_off_api(): void
    {
        $this->control()->disable('travelnext', 'Funding', null);
        Http::fake();

        $this->withSession([
            'bookingFlight' => $this->flight('travelnext', 'TN-1', 820000) + ['fareType' => 'Public'],
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
            'bookingSessionId' => 'tn-session',
        ])->post(route('flights.book'), $this->bookPayload('TN-1'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable');

        Http::assertNothingSent();
        $this->assertDatabaseCount('flight_bookings', 0);
    }

    // ── Paying ────────────────────────────────────────────────────────────

    public function test_a_pay_first_payment_does_not_start_for_a_switched_off_api(): void
    {
        $this->control()->disable('skylink', 'Supplier outage', null);
        Http::fake();

        $this->withSession([
            'bookingFlight' => $this->flight('skylink', 'btk_1', 790000) + ['fareType' => 'Public'],
            'bookingContact' => ['email' => 'traveller@example.test', 'phone' => '08000000000'],
            'bookingPassengers' => [['type' => 'ADT', 'first_name' => 'Test', 'last_name' => 'Passenger']],
        ])->post(route('flights.payment.gateway.process'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable');

        // No payment set up, no booking row.
        Http::assertNothingSent();
        $this->assertDatabaseCount('flight_bookings', 0);
    }

    public function test_the_pay_page_is_not_shown_for_a_switched_off_api(): void
    {
        $this->control()->disable('skylink', 'Supplier outage', null);

        $this->withSession([
            'bookingFlight' => $this->flight('skylink', 'btk_1', 790000) + ['fareType' => 'Public'],
        ])->get(route('flights.payment.gateway'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable');
    }

    public function test_a_booking_already_held_at_the_supplier_always_goes_ahead(): void
    {
        $this->control()->disable('travelnext', 'Funding', null);
        $held = FlightBooking::query()->create([
            'booking_ref' => 'TW-HELD0001',
            'supplier' => 'travelnext',
            'unique_id' => 'TN-HOLD-1',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'fare_source_code' => 'TN-1',
            'fare_type' => 'Public',
            'currency' => 'NGN',
            'total_price' => 820000,
            'contact_email' => 'traveller@example.test',
        ]);

        $this->assertNull(app(FlightBookingGuard::class)->refusal($this->flight('travelnext', 'TN-1', 820000), $held));
    }

    // ── The results page ──────────────────────────────────────────────────

    public function test_a_switched_off_apis_fares_leave_the_page_and_the_others_fill_in(): void
    {
        $this->control()->disable('travelnext', 'Supplier outage', null);
        session([
            'searchParamsStore' => ['trip' => 'oneway', 'from' => 'Lagos (LOS)', 'to' => 'London (LHR)', 'depart' => now()->addMonth()->format('d/m/Y'), 'adults' => 1, 'flight_type' => 'Y'],
            'flightResultsStore' => [$this->flight('travelnext', 'TN-GONE', 820000)],
            // SkyLink was tried on the loading page, so it isn't listed…
            'searchSupplementSuppliers' => [],
        ]);

        config(['services.skylink.base_url' => 'https://247travels.test/api/', 'services.skylink.search_cache_ttl' => 0]);
        Http::fake([
            '*/api/login' => Http::response(['data' => ['access_token' => 't', 'expires_in' => 900]]),
            '*/api/flights/search' => Http::response(['success' => true, 'data' => ['flights' => []]]),
        ]);

        Livewire::test(FlightPage::class)
            ->assertDontSee('TN-GONE', false)
            ->call('loadSupplementalResults');

        // …but the page lost its flights, so it is searched again.
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'flights/search'));
    }

    public function test_the_results_page_shows_the_notice_and_error_bag_messages(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['error' => 'Fare rules fetch failed.']));

        $this->withSession([
            'searchParamsStore' => ['trip' => 'oneway', 'adults' => 1],
            'flightResultsStore' => [],
            'fareUnavailable' => ['message' => FlightBookingGuard::MESSAGE, 'alternate' => null],
            'errors' => $errors,
        ])->get(route('air.flight-s'))
            ->assertOk()
            ->assertSee('This fare is no longer available.', false)
            // Before, only a flashed 'error' reached the toast; failures put
            // in the error bag were silently dropped.
            ->assertSee('Fare rules fetch failed.', false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function control(): FlightSupplierControl
    {
        return app(FlightSupplierControl::class);
    }

    private function flight(string $source, string $fareSourceCode, float $price): array
    {
        return [
            'source' => $source,
            'fareSourceCode' => $fareSourceCode,
            'airline' => 'British Airways',
            'airlineCode' => 'BA',
            'cabin' => 'Economy',
            'cabinCode' => $source === 'skylink' ? 'O' : 'Y',
            'price' => $price,
            'currency' => 'NGN',
            'directionInd' => 'oneway',
            'segments' => [[
                'from' => 'LOS',
                'to' => 'LHR',
                'airlineCode' => 'BA',
                'flightNo' => 'BA75',
                'departDT' => $source === 'skylink' ? '2026-10-20T10:30:00+00:00' : '2026-10-20T10:30:00',
            ]],
            'returnSegments' => [],
            'multiLegs' => [],
            'fareBreakdown' => [],
        ];
    }

    private function bookPayload(string $fareSourceCode): array
    {
        return [
            'fare_source_code' => $fareSourceCode,
            'session_id' => 'tn-session',
            'contact' => ['email' => 'traveller@example.test', 'phone' => '08000000000', 'area_code' => '0', 'country_code' => 'NG'],
            'passengers' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
                'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG',
            ]],
        ];
    }
}
