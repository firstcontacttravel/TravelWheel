<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightBooking as FlightBookingPage;
use App\Mail\BookingPendingMail;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Services\Flights\FlightPlatformHold;
use App\Services\Flights\FlightSupplierControl;
use App\Services\SkylinkFlightService;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Hold and pay later" for SkyLink, which has no hold of its own and forbids
 * reserving before payment. The booking is saved on our side for a day,
 * nothing is sent to SkyLink until the customer pays, and a fare that has
 * risen by then is offered to accept or decline.
 */
class PayLaterHoldTest extends TestCase
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

    // ── Placing the hold ─────────────────────────────────────────────────

    public function test_the_booking_page_offers_to_hold_a_skylink_fare(): void
    {
        $this->atCheckout();

        // The choice sits beside "Continue to payment" on the review step.
        Livewire::test(FlightBookingPage::class)
            ->set('step', 3)
            ->assertSee('Hold and pay later')
            ->assertSee('Your seat and price are not guaranteed until you pay.');
    }

    public function test_it_is_not_offered_where_the_api_holds_with_the_airline(): void
    {
        $hold = app(FlightPlatformHold::class);

        $this->assertTrue($hold->offersPayLater(['source' => 'skylink']));
        $this->assertFalse($hold->offersPayLater(['source' => 'travelnext']));

        config(['flights.platform_hold.pay_later_enabled' => false]);
        $this->assertFalse($hold->offersPayLater(['source' => 'skylink']));
    }

    public function test_holding_saves_the_booking_for_a_day_and_sends_nothing_to_skylink(): void
    {
        $this->atCheckout();
        $this->fakeSkylink();

        $this->post(route('flights.book'), $this->bookPayload(['pay_later' => '1']))
            ->assertRedirect(route('flights.payment.gateway'))
            ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'not guaranteed until you pay'));

        $booking = FlightBooking::sole();
        $this->assertSame('on_hold', $booking->booking_status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame(FlightPlatformHold::PAY_LATER, $booking->hold_type);
        $this->assertSame('', (string) $booking->unique_id);
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $booking->tkt_time_limit->timestamp, 60);
        $this->assertSame('Ada', $booking->passengers_snapshot[0]['first_name']);

        Http::assertNothingSent();
        Mail::assertSent(BookingPendingMail::class, fn (BookingPendingMail $mail): bool => $mail->method === 'pay_later'
            && $mail->booking->is($booking));
    }

    public function test_the_email_promises_no_seat_and_links_back_to_the_pay_page(): void
    {
        $booking = $this->heldBooking();

        $html = (new BookingPendingMail($booking, 'pay_later'))->render();

        $this->assertStringContainsString('Your booking is saved', $html);
        $this->assertStringContainsString('not guaranteed until you pay', $html);
        $this->assertStringNotContainsString('reserved with the airline', $html);
        $this->assertStringContainsString('/flights/hold/'.$booking->booking_ref, $html);
    }

    public function test_without_the_choice_checkout_goes_straight_to_payment(): void
    {
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload())
            ->assertRedirect(route('flights.payment.gateway'));

        $this->assertSame(0, FlightBooking::count());
    }

    // ── Coming back to pay ───────────────────────────────────────────────

    public function test_the_emailed_link_reopens_the_pay_page_for_that_booking(): void
    {
        $booking = $this->heldBooking();

        $this->get($this->resumeUrl($booking))->assertRedirect(route('flights.payment.gateway'));

        $this->assertSame($booking->id, session('flightBookingDbId'));
        $this->assertSame('234', session('bookingContact.country_code'));

        $this->get(route('flights.payment.gateway'))->assertOk()
            ->assertSee('Saved until')
            ->assertSee('Your seat and price are not guaranteed until you pay.');
    }

    public function test_a_tampered_link_is_refused(): void
    {
        $booking = $this->heldBooking();

        $this->get($this->resumeUrl($booking).'x')->assertForbidden();
    }

    public function test_an_expired_hold_cannot_be_reopened(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute()]);
        $url = URL::signedRoute('flights.hold.resume', ['bookingRef' => $booking->booking_ref]);

        $this->get($url)
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHasErrors(['error' => FlightPlatformHold::PAY_LATER_EXPIRED]);
    }

    public function test_paying_a_held_booking_prices_it_again_before_the_card_is_charged(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink();
        $this->get($this->resumeUrl($booking));

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect('https://checkout.example.test/pay');

        $booking->refresh();
        $this->assertSame('btk_repriced', $booking->fare_source_code);
        $this->assertSame('skylink_reserve_full', $booking->payment_flow);
        $this->assertSame(1, FlightBooking::count());
    }

    public function test_paying_after_the_deadline_is_refused_before_any_call(): void
    {
        $booking = $this->heldBooking();
        $this->fakeSkylink();
        $this->get($this->resumeUrl($booking));
        $booking->update(['tkt_time_limit' => now()->subMinute()]);

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHasErrors(['error' => FlightPlatformHold::PAY_LATER_EXPIRED]);

        Http::assertNothingSent();
    }

    public function test_a_dearer_fare_is_offered_to_accept_or_decline(): void
    {
        $booking = $this->heldBooking();
        $oldTotal = (float) $booking->total_price;
        $this->fakeSkylink(verifiedPrice: self::PRICE_NGN + 45000);
        $this->get($this->resumeUrl($booking));

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect(route('flights.price-change'));

        $this->get(route('flights.price-change'))->assertOk()
            ->assertSee('The fare has changed')
            ->assertSee('Accept the new fare');

        $this->post(route('flights.price-change.accept'))
            ->assertRedirect(route('flights.payment.gateway'));

        $booking->refresh();
        $this->assertSame('btk_repriced', $booking->fare_source_code);
        $this->assertGreaterThan($oldTotal, (float) $booking->total_price);
        $this->assertSame('pending', $booking->payment_status);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));
    }

    public function test_another_visitor_cannot_see_someone_elses_fare_change(): void
    {
        $booking = $this->heldBooking();
        session(['corporatePriceChange' => ['booking_id' => $booking->id, 'new_fare' => 1]]);

        $this->get(route('flights.price-change'))->assertRedirect(route('air.flight-s'));
    }

    // ── Closing and reminding ────────────────────────────────────────────

    public function test_an_unpaid_hold_simply_closes_when_its_time_is_up(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute()]);

        $this->artisan('flights:reconcile')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertStringContainsString('Nothing was reserved with the supplier', $booking->reconciliation_note);
    }

    public function test_a_card_payment_started_near_the_deadline_is_given_time_to_return(): void
    {
        $booking = $this->heldBooking([
            'tkt_time_limit' => now()->subMinute(),
            'payment_reference' => 'PAY-LATE-1',
        ]);

        $this->artisan('flights:reconcile')->assertSuccessful();

        $this->assertSame('on_hold', $booking->fresh()->booking_status);
    }

    public function test_one_reminder_is_sent_before_the_hold_closes(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->addHours(2)]);
        $booking->forceFill(['created_at' => now()->subHours(22)])->save();
        $this->heldBooking(['booking_ref' => 'TW-LATER2', 'tkt_time_limit' => now()->addHours(20)]);

        $this->artisan('flights:remind-pay-later-holds')->assertSuccessful();
        $this->artisan('flights:remind-pay-later-holds')->assertSuccessful();

        Mail::assertSent(BookingPendingMail::class, 1);
        Mail::assertSent(BookingPendingMail::class, fn (BookingPendingMail $mail): bool => $mail->method === 'pay_later_reminder'
            && $mail->booking->is($booking));
        $this->assertNotNull($booking->fresh()->hold_reminder_sent_at);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function flight(): array
    {
        $criteria = ['trip' => 'oneway', 'adults' => 1, 'childs' => 0, 'kids' => 0];

        return FlightMarkup::apply(app(SkylinkFlightService::class)->mapSearchResult($this->rawFlight('btk_original'), $criteria), null);
    }

    private function atCheckout(): void
    {
        session([
            'bookingFlight' => $this->flight(),
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
            'searchParamsStore' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
            'bookingSessionId' => '',
        ]);
    }

    private function bookPayload(array $extra = []): array
    {
        return array_merge([
            'fare_source_code' => 'btk_original',
            'contact' => ['email' => 'ada@example.test', 'phone' => '8000000000', 'area_code' => '080', 'country_code' => '234'],
            'passengers' => [[
                'type' => 'ADT', 'title' => 'Ms', 'first_name' => 'Ada', 'last_name' => 'Obi',
                'gender' => 'F', 'dob' => '1990-01-01', 'nationality' => 'NG',
                'passport_no' => 'A12345678', 'passport_issue_country' => 'NG',
                'passport_issue_date' => '2020-01-01', 'passport_exp' => now()->addYears(5)->format('Y-m-d'),
            ]],
        ], $extra);
    }

    private function heldBooking(array $overrides = []): FlightBooking
    {
        $flight = $this->flight();

        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-LATER1',
            'supplier' => 'skylink',
            'unique_id' => '',
            'fare_source_code' => 'btk_original',
            'fare_type' => 'Public',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'payment_method' => 'gateway',
            'hold_type' => FlightPlatformHold::PAY_LATER,
            'tkt_time_limit' => now()->addHours(24),
            'currency' => 'NGN',
            'total_price' => $flight['price'],
            'flight_snapshot' => $flight,
            'passengers_snapshot' => [[
                'type' => 'ADT', 'title' => 'Ms', 'first_name' => 'Ada', 'last_name' => 'Obi',
                'gender' => 'F', 'dob' => '1990-01-01', 'nationality' => 'NG',
            ]],
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'contact_email' => 'ada@example.test',
            'contact_phone' => '8000000000',
            'contact_area_code' => '080',
            'contact_country_code' => '234',
        ], $overrides));
    }

    private function resumeUrl(FlightBooking $booking): string
    {
        return URL::temporarySignedRoute('flights.hold.resume', $booking->tkt_time_limit, ['bookingRef' => $booking->booking_ref]);
    }

    private function fakeSkylink(int $verifiedPrice = self::PRICE_NGN): void
    {
        Http::fake(function (Request $request) use ($verifiedPrice) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($url, '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]]),
                str_ends_with($url, '/api/login') => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
                str_ends_with($url, 'flights/pricing') => Http::response(['success' => true, 'data' => [
                    'booking_token' => 'btk_repriced', 'verified' => true,
                    'original_price' => self::PRICE_NGN, 'verified_price' => $verifiedPrice, 'currency' => 'NGN',
                ]]),
                default => Http::response(['success' => false, 'message' => 'not expected'], 500),
            };
        });
    }

    private function rawFlight(string $token): array
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
