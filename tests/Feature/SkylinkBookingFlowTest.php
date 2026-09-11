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

    public function test_select_works_when_travelnext_left_session_id_empty(): void
    {
        // searchSessionId defaults to '' whenever TravelNext's own search
        // returned nothing (data_get(..., 'AirSearchResponse.session_id', ''))
        // — which happens whenever TravelNext has no results for a route
        // SkyLink does. session_id has no meaning for SkyLink at all, but the
        // form still submits it verbatim from session('searchSessionId', '').
        // This used to be a hard `required` rule, which threw a validation
        // exception before _selectSkylinkFare() was ever reached — found via
        // live testing (every SkyLink "Book Now" silently bounced back to
        // the results page whenever TravelNext had zero results).
        $this->configureSkylink();

        session([
            'searchParamsStore' => ['trip' => 'oneway', 'adults' => 1, 'childs' => 0, 'kids' => 0],
            'skylinkResultsStore' => [$this->searchedSkylinkFlight()],
        ]);

        Http::fake([
            '*/api/login' => Http::response($this->loginResponse()),
            '*/api/flights/pricing' => Http::response([
                'success' => true,
                'data' => ['booking_token' => 'btk_refreshed', 'verified' => true, 'verified_price' => 750000, 'original_price' => 750000, 'currency' => 'NGN'],
            ]),
        ]);

        $this->post(route('flights.select'), [
            'fare_source_code' => 'btk_original',
            'session_id' => '',
            'source' => 'skylink',
        ])->assertRedirect(route('flights.booking'))
            ->assertSessionHasNoErrors();

        $this->assertSame('skylink', session('bookingFlight')['source']);
    }

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

    public function test_booking_page_shows_a_blended_total_not_a_zero_tax_per_type_breakdown(): void
    {
        // Once fareBreakdown was populated (for the Fare Rules tab — see
        // SkylinkFlightServiceTest), it stopped being empty, which silently
        // switched the booking page's fare summary from its intended
        // "blended total" branch (guarded by empty($breakdown)) to a
        // per-passenger-type branch built for TravelNext's much richer data
        // (real per-type tax/surcharge fields). SkyLink's breakdown has none
        // of that, so every "Taxes & Fees" row rendered ₦0.00 per passenger
        // while Trip Total (computed separately, unaffected) legitimately
        // included real taxes and markup — the numbers visibly didn't add
        // up on screen. Found via live testing with a 2-adult-1-child search.
        $this->configureSkylink();

        session([
            'bookingFlight' => [
                'source' => 'skylink',
                'currency' => 'NGN',
                'price' => 3353911.0,
                'baseFare' => 3263911.0,
                'totalTax' => 90000.0,
                'airline' => 'Kenya Airways', 'airlineCode' => 'KQ', 'cabinCode' => 'Y',
                'stops' => 0, 'isRefundable' => false, 'fareType' => 'Public',
                'segments' => [[
                    'from' => 'LOS', 'to' => 'NBO', 'airlineCode' => 'KQ', 'flightNo' => 'KQ1234', 'cabinCode' => 'Y',
                    'departTime' => '12:25 pm', 'arriveTime' => '07:45 pm',
                    'departDT' => '2026-09-17T12:25:00+00:00', 'arriveDT' => '2026-09-17T19:45:00+00:00',
                    'duration' => 320, 'seatsLeft' => 9,
                ]],
                'returnSegments' => [], 'multiLegs' => [],
                'fareBreakdown' => [
                    ['passengerType' => 'ADT', 'qty' => 2, 'baseFare' => 1676928.34, 'totalFare' => 1676928.34, 'baggage' => ['2PC'], 'cabinBaggage' => ['7kg'], 'refundAllowed' => false, 'changeAllowed' => null],
                    ['passengerType' => 'CHD', 'qty' => 1, 'baseFare' => 629515.07, 'totalFare' => 629515.07, 'baggage' => ['2PC'], 'cabinBaggage' => ['7kg'], 'refundAllowed' => false, 'changeAllowed' => null],
                ],
            ],
            'bookingSearchParams' => ['adults' => 2, 'childs' => 1, 'kids' => 0],
            'bookingSessionId' => 'sess-1',
        ]);

        $response = $this->get(route('flights.booking'));

        $response->assertOk();

        // The summary has to add up on screen: base fare plus everything else
        // charged must equal the total, with no unexplained gap between them.
        $response->assertSeeText('Base fare');
        $response->assertSeeText('Taxes, fees and charges');
        $response->assertSeeText('₦3,263,911.00');   // base
        $response->assertSeeText('₦90,000.00');      // 3,353,911 - 3,263,911
        $response->assertSeeText('₦3,353,911.00');   // total to pay

        // The old broken branch would render these per-type labels instead.
        $response->assertDontSeeText('Adult x 2');
        $response->assertDontSeeText('Child x 1');
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

    public function test_reserve_sends_a_correctly_shaped_multi_passenger_travellers_object(): void
    {
        // The flat passengers_snapshot list was previously forwarded to
        // reserve() completely unchanged as the "travellers" payload field —
        // SkyLink's API expects a nested { primary_guest, travelers: {
        // adult_0, adult_1, child_0, ... } } object instead (see their docs
        // §7.1), a shape that was simply never built. Every real SkyLink
        // reservation would have submitted malformed traveller data. This
        // test inspects the actual outgoing request body, unlike the other
        // reserve tests here which only fake a canned response and would
        // pass regardless of what was sent.
        Mail::fake();
        $this->configureSkylink();
        $this->configureSeerbit();

        $booking = $this->skylinkPendingBooking([
            'contact_email' => 'lead@example.test',
            'contact_phone' => '8012345678',
            'contact_country_code' => '234',
            'adult_count' => 2,
            'child_count' => 1,
            'infant_count' => 0,
            'passengers_snapshot' => [
                ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Lead', 'last_name' => 'Adult', 'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG', 'passport_no' => 'A1111111', 'passport_exp' => '2030-01-01', 'passport_issue_date' => '2020-01-01'],
                ['type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Second', 'last_name' => 'Adult', 'gender' => 'F', 'dob' => '1992-02-02', 'nationality' => 'NG', 'passport_no' => 'A2222222', 'passport_exp' => '2031-01-01', 'passport_issue_date' => '2021-01-01'],
                ['type' => 'CHD', 'title' => 'Miss', 'first_name' => 'One', 'last_name' => 'Child', 'gender' => 'F', 'dob' => '2018-03-03', 'nationality' => 'NG', 'passport_no' => 'A3333333', 'passport_exp' => '2032-01-01', 'passport_issue_date' => '2022-01-01'],
            ],
        ]);

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/encrypt/keys')) {
                return Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test-key']]]);
            }
            if (str_contains($request->url(), '/payments/query/')) {
                return Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => 750000, 'currency' => 'NGN',
                ]]]);
            }
            if (str_contains($request->url(), '/api/login')) {
                return Http::response($this->loginResponse());
            }
            if (str_contains($request->url(), '/flights/reserve')) {
                return Http::response([
                    'success' => true,
                    'data' => ['pnr' => 'SKY-PNR-MULTI', 'status' => 'confirmed', 'message' => 'PNR generated successfully'],
                ]);
            }

            return Http::response([]);
        });

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.confirmation'));

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/flights/reserve')) {
                return true;
            }

            $body = $request->data();
            $travellers = $body['travellers'];

            $this->assertArrayHasKey('primary_guest', $travellers);
            $this->assertArrayHasKey('travelers', $travellers);
            // Every passenger present, correctly keyed by type + index —
            // not the old flat list.
            $this->assertArrayHasKey('adult_0', $travellers['travelers']);
            $this->assertArrayHasKey('adult_1', $travellers['travelers']);
            $this->assertArrayHasKey('child_0', $travellers['travelers']);
            $this->assertSame('Lead', $travellers['primary_guest']['first_name']);
            $this->assertSame('Second', $travellers['travelers']['adult_1']['first_name']);
            $this->assertSame('One', $travellers['travelers']['child_0']['first_name']);
            // Contact info only lives on primary_guest — SkyLink has nowhere
            // else to put it, and our form only collects it once anyway.
            $this->assertSame('lead@example.test', $travellers['primary_guest']['email']);
            $this->assertSame('8012345678', $travellers['primary_guest']['phone']);
            $this->assertSame('234', $travellers['primary_guest']['country_code']);
            // M/F -> male/female, since that's what SkyLink's API expects.
            $this->assertSame('male', $travellers['primary_guest']['gender']);
            $this->assertSame('female', $travellers['travelers']['adult_1']['gender']);
            // passport_no/passport_exp renamed to SkyLink's field names.
            $this->assertSame('A1111111', $travellers['primary_guest']['passport_number']);
            $this->assertSame('2030-01-01', $travellers['primary_guest']['passport_expiry']);

            return true;
        });
    }

    public function test_payment_gateway_page_does_not_mislabel_skylink_as_an_lcc_ticket(): void
    {
        // Found via user review: this page is reached unconditionally for
        // every SkyLink fare (SkyLink has no hold concept, so it always pays
        // first) but the copy called every fare here a "Low Cost Carrier
        // (LCC)" ticket — TravelNext's own fare-type jargon, meaningless (and
        // confusing) for a SkyLink booking that has nothing to do with LCC
        // fare classification. Also checks the stale "simulate a successful
        // payment" copy is gone — this button genuinely redirects to a real,
        // live SeerBit checkout (redirect()->away() in _startSeerbitPayment()),
        // so telling the customer their payment is "simulated" was simply
        // wrong, not a placeholder-only page.
        $response = $this->withSession($this->skylinkBookingSession())
            ->get(route('flights.payment.gateway'));

        $response->assertOk();
        $response->assertDontSeeText('Low Cost Carrier');
        $response->assertDontSeeText('LCC');
        $response->assertDontSeeText('simulate', false);
    }

    public function test_payment_gateway_page_still_shows_travelnext_fare_breakdown(): void
    {
        // The per-type fare breakdown section on this page is skipped for
        // SkyLink (see the test above) but should still render normally for
        // TravelNext, whose fareBreakdown carries a real per-type total fare.
        $response = $this->withSession([
            'bookingFlight' => [
                'fareSourceCode' => 'fs-1',
                'source' => 'travelnext',
                'fareType' => 'WebFare',
                'currency' => 'NGN',
                'price' => 190000,
                'airline' => 'Kenya Airways',
                'segments' => [['from' => 'LOS', 'to' => 'ACC']],
                'fareBreakdown' => [
                    ['passengerType' => 'ADT', 'qty' => 1, 'totalFare' => 190000],
                ],
            ],
        ])->get(route('flights.payment.gateway'));

        $response->assertOk();
        $response->assertSeeText('Fare breakdown');
        $response->assertSeeText('Adult × 1');
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

    private function skylinkPendingBooking(array $overrides = []): FlightBooking
    {
        // Plain array_merge (not recursive) — a passed 'passengers_snapshot'
        // or similar list-valued override replaces the default wholesale
        // rather than merging item-by-item, which is what every caller wants.
        $booking = FlightBooking::create(array_merge([
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
        ], $overrides));

        session(['flightBookingDbId' => $booking->id]);

        return $booking;
    }
}
