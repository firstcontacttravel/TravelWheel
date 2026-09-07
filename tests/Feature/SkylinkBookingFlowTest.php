<?php

namespace Tests\Feature;

use App\Mail\UnTicketedConfirmationAlert;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 3 — SkyLink fares are gateway-only: select() re-verifies price(),
 * book() skips the hold step entirely and goes straight to payment, and only
 * once SeerBit confirms payment does reserve() get called. These tests fake
 * every external call (SeerBit + SkyLink) — no live reserve() call happens
 * here or anywhere else without explicit authorization.
 */
class SkylinkBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_select_re_verifies_price_and_stores_a_bookable_skylink_flight(): void
    {
        $this->configureSkylink();

        session([
            'searchParamsStore' => ['trip' => 'oneway', 'adults' => 1, 'childs' => 0, 'kids' => 0],
            'skylinkResultsStore' => [$this->searchedSkylinkFlight()],
        ]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/pricing' => Http::response([
                'success' => true,
                'data' => [
                    'booking_token' => 'btk_refreshed',
                    'verified' => true,
                    'price_changed' => false,
                    'original_price' => 750000,
                    'verified_price' => 750000,
                    'currency' => 'NGN',
                ],
            ]),
        ]);

        $this->post(route('flights.select'), [
            'fare_source_code' => 'btk_original',
            'session_id' => 'sess-1',
            'source' => 'skylink',
        ])->assertRedirect(route('flights.booking'));

        $flight = session('bookingFlight');
        $this->assertSame('skylink', $flight['source']);
        $this->assertSame('btk_refreshed', $flight['fareSourceCode']);
        // 750,000 NGN / 1500 test rate == 500 USD, then markup applied on top.
        $this->assertGreaterThan(500.0, $flight['price']);
        $this->assertSame('NGN', $flight['currency']);
        $this->assertSame([], session('extraServices'));
        $this->assertSame([], session('fareRules'));
    }

    public function test_book_sends_skylink_fares_straight_to_the_gateway_without_a_hold(): void
    {
        $this->withSession($this->skylinkBookingSession())
            ->post(route('flights.book'), $this->bookPayload())
            ->assertRedirect(route('flights.payment.gateway'));

        // No TravelNext book API call, and no hold-based booking row created yet.
        Http::assertNothingSent();
        $this->assertDatabaseCount('flight_bookings', 0);
    }

    public function test_book_rejects_travelflex_intent_for_skylink_fares_with_a_clear_reason(): void
    {
        $session = $this->skylinkBookingSession();
        $session['bookingFlight']['isRefundable'] = true;

        $this->withSession($session)
            ->post(route('flights.book'), array_merge($this->bookPayload(), ['intent' => 'travelflex']))
            ->assertRedirect(route('flights.payment.gateway'))
            ->assertSessionHasErrors(['error' => 'TravelFlex is not available for this fare. Please choose another flight or pay by card/bank transfer.']);
    }

    public function test_successful_payment_reserves_and_confirms_a_skylink_booking(): void
    {
        Mail::fake();
        $this->configureSkylink();
        $this->configureSeerbit();

        $booking = $this->skylinkPendingBooking();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/encrypt/keys')) {
                return Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test-key']]]);
            }
            if (str_contains($request->url(), '/payments/query/')) {
                return Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00',
                    'gatewayMessage' => 'Successful',
                    'amount' => 750000,
                    'currency' => 'NGN',
                ]]]);
            }
            if (str_contains($request->url(), '/api/login')) {
                return Http::response($this->loginResponse());
            }
            if (str_contains($request->url(), '/flights/reserve')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'pnr' => 'SKY-PNR-1',
                        'booking_reference' => 'SKY-PNR-1',
                        'status' => 'confirmed',
                        'message' => 'PNR generated successfully',
                    ],
                ]);
            }

            return Http::response([]);
        });

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.confirmation'));

        $booking->refresh();
        $this->assertSame('SKY-PNR-1', $booking->unique_id);
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('skylink', $booking->supplier);
        Mail::assertNotSent(UnTicketedConfirmationAlert::class);
    }

    public function test_reserve_failure_after_payment_marks_the_booking_failed_and_alerts_ops(): void
    {
        Mail::fake();
        $this->configureSkylink();
        $this->configureSeerbit();

        $booking = $this->skylinkPendingBooking();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/encrypt/keys')) {
                return Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test-key']]]);
            }
            if (str_contains($request->url(), '/payments/query/')) {
                return Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00',
                    'gatewayMessage' => 'Successful',
                    'amount' => 750000,
                    'currency' => 'NGN',
                ]]]);
            }
            if (str_contains($request->url(), '/api/login')) {
                return Http::response($this->loginResponse());
            }
            if (str_contains($request->url(), '/flights/reserve')) {
                return Http::response([
                    'success' => false,
                    'blocked' => true,
                    'carrier' => 'AT',
                    'message' => 'AT reservations are not available at this time.',
                ], 403);
            }

            return Http::response([]);
        });

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.payment.gateway'))
            ->assertSessionHasErrors('error');

        $booking->refresh();
        // Payment WAS captured — this must never look like nothing happened.
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('failed', $booking->booking_status);
        Mail::assertSent(UnTicketedConfirmationAlert::class);
    }

    private function configureSkylink(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
        ]);

        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    private function configureSeerbit(): void
    {
        config([
            'services.seerbit.public_key' => 'test-public',
            'services.seerbit.secret_key' => 'test-secret',
        ]);
    }

    private function loginResponse(): array
    {
        return [
            'status' => 'success',
            'code' => 'LOGIN_SUCCESS',
            'data' => ['access_token' => 'test-access-token', 'expires_in' => 900],
        ];
    }

    private function searchedSkylinkFlight(): array
    {
        return [
            'fareSourceCode' => 'btk_original',
            'skylinkBookingToken' => 'btk_original',
            'source' => 'skylink',
            'detailLevel' => 'summary',
            'airline' => 'Royal Air Maroc',
            'airlineCode' => 'AT',
            'cabin' => 'Economy',
            'cabinCode' => 'Y',
            'price' => 500.0,
            'baseFare' => 500.0,
            'currency' => 'USD',
            'isRefundable' => false,
            'fareType' => 'Public',
            'directionInd' => 'oneway',
            'segments' => [['from' => 'LOS', 'to' => 'DXB', 'airlineCode' => 'AT', 'flightNo' => 'AT576', 'cabinCode' => 'Y']],
            'returnSegments' => [],
            'multiLegs' => [],
            'fareBreakdown' => [],
        ];
    }

    private function skylinkBookingSession(): array
    {
        return [
            'bookingFlight' => [
                'fareSourceCode' => 'btk_refreshed',
                'source' => 'skylink',
                'fareType' => 'Public',
                'currency' => 'NGN',
                'price' => 750000,
                'isRefundable' => false,
                'segments' => [['from' => 'LOS', 'to' => 'DXB']],
                'fareBreakdown' => [],
            ],
            'bookingSessionId' => 'sess-1',
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
        ];
    }

    private function bookPayload(): array
    {
        return [
            'fare_source_code' => 'btk_refreshed',
            'session_id' => 'sess-1',
            'contact' => ['email' => 'traveller@example.test', 'phone' => '08000000000', 'area_code' => '0', 'country_code' => 'NG'],
            'passengers' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
                'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG',
            ]],
        ];
    }

    private function skylinkPendingBooking(): FlightBooking
    {
        $booking = FlightBooking::create([
            'booking_ref' => 'TW-SKY-'.strtoupper(str()->random(6)),
            'fare_source_code' => 'btk_refreshed',
            'supplier' => 'skylink',
            'fare_type' => 'Public',
            'payment_reference' => 'PAY-'.strtoupper(str()->random(12)),
            'payment_gateway' => 'seerbit',
            'payment_flow' => 'skylink_reserve_full',
            'payment_amount' => 750000,
            'payment_currency' => 'NGN',
            'total_price' => 750000,
            'currency' => 'NGN',
            'payment_status' => 'pending',
            'booking_status' => 'pending_payment',
            'contact_email' => 'traveller@example.test',
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'passengers_snapshot' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
            ]],
            'flight_snapshot' => [
                'source' => 'skylink',
                'currency' => 'NGN',
                'price' => 750000,
                'segments' => [['from' => 'LOS', 'to' => 'DXB']],
            ],
        ]);

        session(['flightBookingDbId' => $booking->id]);

        return $booking;
    }
}
