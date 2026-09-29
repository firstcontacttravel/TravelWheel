<?php

namespace Tests\Feature;

use App\Http\Controllers\FlightBookingController;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use App\Services\Flights\FlightPlatformHold;
use App\Services\Flights\FlightSupplierControl;
use App\Services\SkylinkFlightService;
use App\Services\TravelFlexFlowService;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * TravelFlex with an API that can't hold seats (SkyLink): the booking is
 * held on our side, the fare is found again and re-priced before any money
 * is taken, and the supplier booking is only made after payment.
 */
class PlatformHoldTravelFlexTest extends TestCase
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
        ]);
        FlightMarkup::forgetCachedConfiguration();
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
        app(FlightSupplierControl::class)->enable('skylink', null);
        Mail::fake();
    }

    // ── Finding and re-pricing the held fare ──────────────────────────────

    public function test_the_same_flight_at_the_same_price_is_reconfirmed_with_a_live_token(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN);

        $hold = app(FlightPlatformHold::class);
        $result = $hold->reconfirm($booking);

        $this->assertTrue($result['ok']);
        $hold->adopt($booking, $result['flight']);

        $this->assertSame('btk_fresh', $booking->fresh()->fare_source_code);
        $this->assertSame('btk_fresh', $booking->fresh()->flight_snapshot['skylinkBookingToken']);
        // The customer's price is untouched.
        $this->assertSame($booking->flight_snapshot['price'], $booking->fresh()->flight_snapshot['price']);
    }

    public function test_the_search_is_rebuilt_from_the_held_flight(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN);

        app(FlightPlatformHold::class)->reconfirm($booking);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/search')
            && $request['from'] === 'LOS'
            && $request['to'] === 'DXB'
            && $request['flights_departure_date'] === now()->addDays(40)->format('Y-m-d')
            && $request['adults'] === 1
            && $request['class'] === 'economy');
    }

    public function test_a_flight_that_is_gone_is_not_reconfirmed(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN, flightNo: 'AT999');

        $result = app(FlightPlatformHold::class)->reconfirm($booking);

        $this->assertFalse($result['ok']);
        $this->assertSame('unavailable', $result['reason']);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/pricing'));
    }

    public function test_conversion_rounding_is_not_a_fare_rise(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN + 30);

        $this->assertTrue(app(FlightPlatformHold::class)->reconfirm($booking)['ok']);
    }

    public function test_a_dearer_fare_is_refused(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN + 45000);

        $result = app(FlightPlatformHold::class)->reconfirm($booking);

        $this->assertFalse($result['ok']);
        $this->assertSame('price_increased', $result['reason']);
        $this->assertGreaterThan($result['approved_price'], $result['current_price']);
    }

    public function test_a_cheaper_fare_goes_ahead_at_the_approved_price(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN - 50000);

        $result = app(FlightPlatformHold::class)->reconfirm($booking);

        $this->assertTrue($result['ok']);
        $this->assertLessThan($result['approved_price'], $result['current_price']);
    }

    // ── Before any payment ────────────────────────────────────────────────

    public function test_a_price_rise_stops_the_deposit_before_any_money_is_taken(): void
    {
        [, $application] = $this->approvedApplication();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN + 45000);

        try {
            app(TravelFlexFlowService::class)->revalidateHold($application);
            $this->fail('A dearer fare was accepted for payment.');
        } catch (ValidationException $exception) {
            $this->assertSame(FlightPlatformHold::PRICE_INCREASED, $exception->errors()['travelflex'][0]);
        }

        $this->assertNull($application->fresh()->pricing_revalidated_at);
    }

    public function test_the_approval_page_offers_card_only(): void
    {
        config()->set('travelwheel.travelflex_bank_accounts', [['bank' => 'Test Bank', 'account_number' => '1234567890', 'account_name' => 'TravelWheel']]);
        [, $application] = $this->approvedApplication();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN);

        $this->get(app(TravelFlexFlowService::class)->approvalUrl($application))
            ->assertOk()
            ->assertSee('Pay by card')
            ->assertSee('book your seat as soon as both payments clear')
            ->assertDontSee('value="bank_transfer"', false);
    }

    public function test_bank_transfer_is_refused_for_a_fare_held_on_our_side(): void
    {
        config()->set('travelwheel.travelflex_bank_accounts', [['bank' => 'Test Bank', 'account_number' => '1234567890', 'account_name' => 'TravelWheel']]);
        [, $application] = $this->approvedApplication();
        Http::fake();

        $this->withSession(['travelFlexApplicationId' => $application->id])
            ->from('/flights/travelflex/approved')
            ->post(route('flights.travelflex.approved.payment'), ['pay_method' => 'bank_transfer'])
            ->assertSessionHasErrors(['travelflex' => 'This booking can only be paid by card. Please choose card payment to continue.']);

        Http::assertNothingSent();
        $this->assertSame('pending', $application->fresh()->deposit_status);
    }

    // ── After both payments ───────────────────────────────────────────────

    public function test_after_payment_the_fare_is_reconfirmed_and_reserved(): void
    {
        [$booking] = $this->approvedApplication();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN, reserve: [
            'success' => true,
            'data' => ['pnr' => 'SKY123', 'status' => 'CONFIRMED', 'ticket_deadline' => now()->addDays(2)->format('Y-m-d H:i:s')],
        ]);

        $message = $this->reserve($booking);

        $this->assertNull($message);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve')
            && $request['booking_token'] === 'btk_fresh');

        $booking->refresh();
        $this->assertSame('SKY123', $booking->unique_id);
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('flex_gateway', $booking->payment_method);
    }

    public function test_a_fare_gone_after_payment_goes_to_manual_review(): void
    {
        [$booking] = $this->approvedApplication();
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN, flightNo: 'AT999');

        $message = $this->reserve($booking);

        $this->assertStringContainsString('Both payments were received', $message);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve'));
        $this->assertSame('failed', $booking->fresh()->booking_status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
    }

    public function test_an_expired_hold_is_noted_as_ours_not_the_airlines(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute(), 'booking_status' => 'on_hold']);

        $this->artisan('flights:reconcile')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('hold_expired_review', $booking->booking_status);
        $this->assertStringContainsString('nothing was reserved with the supplier', $booking->reconciliation_note);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function reserve(FlightBooking $booking): ?string
    {
        $method = new \ReflectionMethod(FlightBookingController::class, '_reservePlatformHeldBooking');

        return $method->invoke(app(FlightBookingController::class), $booking);
    }

    private function approvedApplication(): array
    {
        $booking = $this->heldBooking(['booking_status' => 'awaiting_deposit']);

        $application = TravelFlexApplication::create([
            'flight_booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'pending',
            'payment_status' => 'not_due',
            'fees_status' => 'not_due',
            'approval_expires_at' => now()->addDay(),
            'repayment_plan' => ['down_percent' => 30, 'repayment_plan' => '72 hours', 'down_payment' => 300000],
        ]);

        return [$booking, $application];
    }

    private function heldBooking(array $overrides = []): FlightBooking
    {
        $criteria = ['trip' => 'oneway', 'adults' => 1, 'childs' => 0, 'kids' => 0];
        $flight = FlightMarkup::apply(app(SkylinkFlightService::class)->mapSearchResult($this->rawFlight('btk_original', 'AT576', self::PRICE_NGN), $criteria));

        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-PLATFORM1',
            'supplier' => 'skylink',
            'unique_id' => '',
            'fare_source_code' => 'btk_original',
            'fare_type' => 'Public',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => now()->addHours(72),
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

    private function fakeSkylink(int $verifiedPrice, string $flightNo = 'AT576', array $reserve = []): void
    {
        Http::fake([
            '*/api/login' => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
            '*/api/flights/search' => Http::response(['success' => true, 'data' => ['flights' => [
                $this->rawFlight('btk_search', $flightNo, self::PRICE_NGN),
            ]]]),
            '*/api/flights/pricing' => Http::response(['success' => true, 'data' => [
                'booking_token' => 'btk_fresh',
                'verified' => true,
                'original_price' => self::PRICE_NGN,
                'verified_price' => $verifiedPrice,
                'currency' => 'NGN',
            ]]),
            '*/api/flights/reserve' => Http::response($reserve ?: ['success' => false, 'message' => 'not expected'], $reserve ? 200 : 500),
        ]);
    }

    private function rawFlight(string $token, string $flightNo, int $priceNgn): array
    {
        $depart = now()->addDays(40);

        return [
            'price' => $priceNgn,
            'actual_adult_base' => $priceNgn,
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
