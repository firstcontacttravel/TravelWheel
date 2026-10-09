<?php

namespace Tests\Feature;

use App\Mail\UnTicketedConfirmationAlert;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Services\Flights\FlightSupplierControl;
use App\Services\Flights\SkylinkFareRefresh;
use App\Services\SkylinkFlightService;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * SkyLink wants pricing within five minutes of reserve, with the token that
 * pricing returns. A paid booking that reserved with the token from flight
 * selection was refused with "Please re-price and try again" (staging
 * booking 126), so the fare is refreshed when the customer clicks Pay and
 * again right before reserve.
 */
class SkylinkFareRefreshTest extends TestCase
{
    use RefreshDatabase;

    private const PRICE_NGN = 900000;

    protected function setUp(): void
    {
        parent::setUp();

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
        app(FlightSupplierControl::class)->enable('skylink', null);
        Mail::fake();
    }

    // ── After payment, before reserve ────────────────────────────────────

    public function test_reserve_uses_the_token_from_a_fresh_pricing_call(): void
    {
        $booking = $this->paidPendingBooking();
        $this->fakeSkylink();

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.confirmation'));

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve')
            && $request['booking_token'] === 'btk_repriced');
        // Re-pricing the token we hold was enough; no search was needed.
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/search'));
    }

    public function test_an_expired_token_is_replaced_by_finding_the_flight_again(): void
    {
        $booking = $this->paidPendingBooking();
        $this->fakeSkylink(expiredTokens: ['btk_original']);

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.confirmation'));

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/search'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve')
            && $request['booking_token'] === 'btk_repriced');
    }

    public function test_a_fare_that_cannot_be_refreshed_after_payment_goes_to_support_without_reserving(): void
    {
        $booking = $this->paidPendingBooking();
        $this->fakeSkylink(expiredTokens: ['btk_original'], searchFlightNo: 'AT999');

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.payment.gateway'))
            ->assertSessionHasErrors('error');

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('failed', $booking->booking_status);
        Mail::assertSent(UnTicketedConfirmationAlert::class);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve'));
    }

    // ── When the customer clicks Pay ─────────────────────────────────────

    public function test_paying_refreshes_the_token_before_the_card_is_charged(): void
    {
        $booking = $this->checkoutBooking();
        $this->fakeSkylink();

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect('https://checkout.example.test/pay');

        $this->assertSame('btk_repriced', $booking->fresh()->fare_source_code);
        // The customer pays what they were shown.
        $this->assertSame($booking->flight_snapshot['price'], $booking->fresh()->flight_snapshot['price']);
        $this->assertEquals($booking->total_price, $booking->fresh()->payment_amount);
    }

    public function test_a_dearer_fare_is_refused_before_any_payment_starts(): void
    {
        $booking = $this->checkoutBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN + 45000);

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable', fn (array $notice): bool => $notice['message'] === SkylinkFareRefresh::PRICE_INCREASED);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));
        $this->assertSame('btk_original', $booking->fresh()->fare_source_code);
    }

    public function test_a_supplier_outage_at_pay_keeps_the_customer_on_the_payment_page(): void
    {
        $this->checkoutBooking();
        Http::fake([
            '*/api/login' => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
            '*/api/flights/*' => Http::response(['success' => false, 'message' => 'Supplier unreachable'], 502),
        ]);

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect(route('flights.payment.gateway'))
            ->assertSessionHasErrors(['error' => SkylinkFareRefresh::UNCONFIRMED]);
    }

    /**
     * Pricing never sent the cabin, so SkyLink priced every fare at its
     * default, economy: a business fare showed one price on the results card
     * and another once selected.
     */
    public function test_selecting_a_fare_prices_it_in_the_cabin_that_was_searched(): void
    {
        $this->fakeSkylink();
        $criteria = ['trip' => 'oneway', 'adults' => 1, 'childs' => 0, 'kids' => 0, 'flight_type' => 'C'];
        $searched = FlightMarkup::apply(app(SkylinkFlightService::class)->mapSearchResult($this->rawFlight('btk_original', 'AT576'), $criteria));

        $this->assertFalse(app(SkylinkFlightService::class)->select('btk_original', $searched, $criteria)['error']);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/pricing')
            && $request['class'] === 'business');
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function booking(array $overrides = []): FlightBooking
    {
        $criteria = ['trip' => 'oneway', 'adults' => 1, 'childs' => 0, 'kids' => 0];
        $flight = FlightMarkup::apply(app(SkylinkFlightService::class)->mapSearchResult($this->rawFlight('btk_original', 'AT576'), $criteria));

        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-REFRESH1',
            'supplier' => 'skylink',
            'unique_id' => '',
            'fare_source_code' => 'btk_original',
            'fare_type' => 'Public',
            'booking_status' => 'pending_payment',
            'payment_status' => 'pending',
            'currency' => 'NGN',
            'total_price' => $flight['price'],
            'flight_snapshot' => $flight,
            'passengers_snapshot' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
                'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG',
            ]],
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'contact_email' => 'traveller@example.test',
            'contact_phone' => '8000000000',
            'contact_country_code' => '234',
        ], $overrides));
    }

    /** Back from SeerBit with the card charged; reserve comes next. */
    private function paidPendingBooking(): FlightBooking
    {
        $booking = $this->booking();
        $booking->update([
            'payment_reference' => 'PAY-REFRESH-1',
            'payment_gateway' => 'seerbit',
            'payment_flow' => 'skylink_reserve_full',
            'payment_amount' => $booking->total_price,
            'payment_currency' => 'NGN',
        ]);
        session(['flightBookingDbId' => $booking->id]);

        return $booking->fresh();
    }

    /** On the payment page, about to click Pay. */
    private function checkoutBooking(): FlightBooking
    {
        $booking = $this->booking();
        session([
            'flightBookingDbId' => $booking->id,
            'bookingFlight' => ['flight' => $booking->flight_snapshot],
            'bookingContact' => ['email' => 'traveller@example.test', 'phone' => '8000000000', 'country_code' => '234'],
            'bookingPassengers' => $booking->passengers_snapshot,
        ]);

        return $booking;
    }

    /**
     * Pricing answers per token: an expired token fails, any other returns
     * btk_repriced at $verifiedPrice. Seerbit and reserve always succeed.
     */
    private function fakeSkylink(int $verifiedPrice = self::PRICE_NGN, array $expiredTokens = [], string $searchFlightNo = 'AT576'): void
    {
        Http::fake(function (Request $request) use ($verifiedPrice, $expiredTokens, $searchFlightNo) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($url, '/payments/query/') => Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => 5000000, 'currency' => 'NGN',
                ]]]),
                str_contains($url, '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]]),
                str_ends_with($url, '/api/login') => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
                str_ends_with($url, 'flights/search') => Http::response(['success' => true, 'data' => ['flights' => [
                    $this->rawFlight('btk_search', $searchFlightNo),
                ]]]),
                str_ends_with($url, 'flights/pricing') => in_array($request['booking_token'], $expiredTokens, true)
                    ? Http::response(['success' => false, 'message' => 'Offer expired. Please search again.'], 400)
                    : Http::response(['success' => true, 'data' => [
                        'booking_token' => 'btk_repriced', 'verified' => true,
                        'original_price' => self::PRICE_NGN, 'verified_price' => $verifiedPrice, 'currency' => 'NGN',
                    ]]),
                str_ends_with($url, 'flights/reserve') => Http::response(['success' => true, 'data' => [
                    'pnr' => 'SKY-PNR-9', 'status' => 'confirmed', 'ticket_deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
                ]]),
                default => Http::response([]),
            };
        });
    }

    private function rawFlight(string $token, string $flightNo): array
    {
        $depart = now()->addDays(40);

        return [
            'price' => self::PRICE_NGN,
            'actual_adult_base' => self::PRICE_NGN,
            'currency' => 'NGN',
            'booking_token' => $token,
            'seats_left' => 4,
            'segments' => [[[
                'img' => 'AT',
                'flight_no' => $flightNo,
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
