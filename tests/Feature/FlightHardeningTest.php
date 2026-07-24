<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\SystemHeartbeat;
use App\Models\TravelFlexApplication;
use App\Services\AdminTicketingService;
use App\Services\DurableMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FlightHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_searches_are_rejected_before_the_supplier_is_called(): void
    {
        Http::fake();

        $this->from(route('air.flight-s'))->post(route('flights.search'), [
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'Lagos (LOS)',
            'depart' => now()->subDay()->format('d/m/Y'),
            'adults' => 1,
            'childs' => 0,
            'kids' => 2,
            'flight_type' => 'Y',
        ])->assertRedirect(route('air.flight-s'))
            ->assertSessionHasErrors(['to', 'depart', 'kids']);

        Http::assertNothingSent();
    }

    public function test_malformed_successful_supplier_response_is_contained(): void
    {
        Http::fake([
            'travelnext.works/*' => Http::response([
                'AirSearchResponse' => [
                    'AirSearchResult' => [
                        'FareItineraries' => [[
                            'FareItinerary' => ['unexpected' => true],
                        ]],
                    ],
                ],
            ]),
        ]);

        $search = [
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'London (LHR)',
            'depart' => now()->addMonth()->format('d/m/Y'),
            'adults' => 1,
            'childs' => 0,
            'kids' => 0,
            'flight_type' => 'Y',
        ];

        $this->post(route('flights.search'), $search)
            ->assertRedirect(route('flights.search.loading'));

        $this->get(route('flights.search.run'))
            ->assertRedirect(route('air.flight-s'));

        $this->assertSame([], session('flightResultsStore'));
    }

    public function test_repeated_checkout_reuses_one_gateway_reference_and_checkout(): void
    {
        config([
            'services.seerbit.public_key' => 'test-public',
            'services.seerbit.secret_key' => 'test-secret',
        ]);

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/encrypt/keys')) {
                return Http::response([
                    'data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test-key']],
                ]);
            }

            return Http::response([
                'data' => [
                    'payments' => [
                        'redirectLink' => 'https://checkout.example.test/'.$request['paymentReference'],
                    ],
                ],
            ]);
        });

        $session = $this->webFareSession();

        $this->withSession($session)->post(route('flights.payment.gateway.process'))->assertRedirect();
        $firstReference = FlightBooking::firstOrFail()->payment_reference;
        $this->post(route('flights.payment.gateway.process'))->assertRedirect();

        $paymentRequests = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn ($request) => str_contains($request->url(), '/api/v2/payments'));

        $this->assertCount(1, $paymentRequests);
        $this->assertSame($firstReference, FlightBooking::firstOrFail()->payment_reference);
    }

    public function test_successful_webhook_validates_and_completes_held_ticket_payment(): void
    {
        Mail::fake();
        $this->configureSeerbit();
        $booking = $this->heldBooking();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/encrypt/keys')) {
                return Http::response([
                    'data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test-key']],
                ]);
            }
            if (str_contains($request->url(), '/payments/query/')) {
                return Http::response([
                    'data' => ['payments' => [
                        'gatewayCode' => '00',
                        'gatewayMessage' => 'Successful',
                        'amount' => 250000,
                        'currency' => 'NGN',
                    ]],
                ]);
            }
            if (str_contains($request->url(), '/ticket_order')) {
                return Http::response([
                    'AirOrderTicketRS' => ['TicketOrderResult' => [
                        'Success' => true,
                        'UniqueID' => 'SUPPLIER-HOLD-1',
                    ]],
                ]);
            }

            return Http::response([]);
        });

        $this->post(route('payments.seerbit.webhook'), [
            'paymentReference' => $booking->payment_reference,
        ])->assertOk()->assertJson(['status' => 'processed']);

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('ticketed', $booking->booking_status);
        $this->assertTrue($booking->ticket_ordered);
        $this->assertNotNull($booking->payment_verified_at);
    }

    public function test_webhook_rejects_an_underpayment_before_ticketing(): void
    {
        Mail::fake();
        $this->configureSeerbit();
        $booking = $this->heldBooking();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/encrypt/keys')) {
                return Http::response([
                    'data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test-key']],
                ]);
            }

            return Http::response([
                'data' => ['payments' => [
                    'gatewayCode' => '00',
                    'gatewayMessage' => 'Successful',
                    'amount' => 1000,
                    'currency' => 'NGN',
                ]],
            ]);
        });

        $this->post(route('payments.seerbit.webhook'), [
            'paymentReference' => $booking->payment_reference,
        ])->assertOk();

        $booking->refresh();
        $this->assertSame('failed', $booking->payment_status);
        $this->assertFalse($booking->ticket_ordered);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/ticket_order'));
    }

    public function test_expired_hold_is_blocked_inside_the_shared_ticketing_service(): void
    {
        Http::fake();
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute()]);

        $result = app(AdminTicketingService::class)->ticketOrder($booking);

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['hold_expired']);
        $this->assertSame('hold_expired_review', $booking->fresh()->booking_status);
        Http::assertNothingSent();
    }

    public function test_failed_mail_is_preserved_in_the_durable_outbox(): void
    {
        $booking = $this->heldBooking(['contact_email' => 'customer@example.test']);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP unavailable'));

        $sent = app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::FLIGHT_RECEIPT,
            'customer@example.test',
            $booking,
            [],
            'test-receipt:'.$booking->id,
        );

        $this->assertFalse($sent);
        $this->assertDatabaseHas('notification_outboxes', [
            'unique_key' => 'test-receipt:'.$booking->id,
            'status' => 'failed',
            'attempts' => 1,
        ]);
    }

    public function test_stale_processing_mail_is_recovered_and_retried(): void
    {
        Mail::fake();
        $booking = $this->heldBooking(['contact_email' => 'customer@example.test']);
        $message = NotificationOutbox::create([
            'kind' => DurableMailService::FLIGHT_RECEIPT,
            'recipient' => 'customer@example.test',
            'related_type' => $booking->getMorphClass(),
            'related_id' => $booking->id,
            'status' => 'processing',
            'attempts' => 1,
            'last_attempted_at' => now()->subMinutes(11),
        ]);

        $result = app(DurableMailService::class)->processPending(10);

        $this->assertSame(['sent' => 1, 'failed' => 0], $result);
        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame(2, $message->fresh()->attempts);
    }

    public function test_sensitive_json_is_encrypted_at_rest_and_remains_readable(): void
    {
        $booking = $this->heldBooking([
            'passengers_snapshot' => [['first_name' => 'Private', 'passport_no' => 'A1234567']],
        ]);
        $rawPassengers = DB::table('flight_bookings')->where('id', $booking->id)->value('passengers_snapshot');

        $this->assertStringContainsString('__travelwheel_encrypted', $rawPassengers);
        $this->assertStringNotContainsString('A1234567', $rawPassengers);
        $this->assertSame('A1234567', $booking->fresh()->passengers_snapshot[0]['passport_no']);

        $application = TravelFlexApplication::create([
            'booking_ref' => 'TW-PRIVATE',
            'applicant_details' => ['full_name' => 'Private Applicant'],
        ]);
        $rawApplicant = DB::table('travel_flex_applications')->where('id', $application->id)->value('applicant_details');

        $this->assertStringContainsString('__travelwheel_encrypted', $rawApplicant);
        $this->assertStringNotContainsString('Private Applicant', $rawApplicant);
        $this->assertSame('Private Applicant', $application->fresh()->applicant_details['full_name']);
    }

    public function test_reconciliation_expires_stale_holds_and_heartbeat_is_recorded(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute()]);

        $this->artisan('flights:reconcile')->assertSuccessful();
        $this->artisan('operations:heartbeat scheduler')->assertSuccessful();

        $this->assertSame('hold_expired_review', $booking->fresh()->booking_status);
        $this->assertNotNull(SystemHeartbeat::where('name', 'scheduler')->first()?->last_seen_at);
    }

    public function test_mandatory_passport_fields_are_enforced_by_the_booking_endpoint(): void
    {
        Http::fake();

        $session = $this->webFareSession();
        $session['bookingFlight']['flight']['isPassportMandatory'] = true;
        $session['bookingSearchParams'] = ['adults' => 1, 'childs' => 0, 'kids' => 0];

        $this->withSession($session)->post(route('flights.book'), [
            'fare_source_code' => 'FARE-SOURCE',
            'session_id' => 'SESSION-ID',
            'contact' => [
                'email' => 'customer@example.test',
                'phone' => '08000000000',
                'area_code' => '1',
                'country_code' => 'NG',
            ],
            'passengers' => [[
                'type' => 'ADT',
                'title' => 'Mr',
                'first_name' => 'Test',
                'last_name' => 'Passenger',
                'gender' => 'M',
                'dob' => '1990-01-01',
                'nationality' => 'NG',
            ]],
        ])->assertSessionHasErrors([
            'passengers.0.passport_no',
            'passengers.0.passport_issue_country',
            'passengers.0.passport_issue_date',
            'passengers.0.passport_exp',
        ]);

        Http::assertNothingSent();
    }

    private function configureSeerbit(): void
    {
        config([
            'services.seerbit.public_key' => 'test-public',
            'services.seerbit.secret_key' => 'test-secret',
        ]);
    }

    private function heldBooking(array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-HARDENING-'.strtoupper(str()->random(6)),
            'fare_source_code' => 'FARE-SOURCE',
            'unique_id' => 'SUPPLIER-HOLD-1',
            'payment_reference' => 'PAY-'.strtoupper(str()->random(12)),
            'payment_gateway' => 'seerbit',
            'payment_flow' => 'held_ticket_full',
            'payment_amount' => 250000,
            'payment_currency' => 'NGN',
            'total_price' => 250000,
            'payment_status' => 'pending',
            'booking_status' => 'on_hold',
            'tkt_time_limit' => now()->addHour(),
            'contact_email' => 'customer@example.test',
            'passengers_snapshot' => [['type' => 'ADT', 'first_name' => 'Test']],
            'flight_snapshot' => [
                'currency' => 'NGN',
                'price' => 250000,
                'segments' => [['from' => 'LOS', 'to' => 'LHR']],
            ],
        ], $overrides));
    }

    private function webFareSession(): array
    {
        return [
            'bookingFlight' => [
                'flight' => [
                    'fareSourceCode' => 'FARE-SOURCE',
                    'fareType' => 'WebFare',
                    'currency' => 'NGN',
                    'price' => 250000,
                    'segments' => [['from' => 'LOS', 'to' => 'LHR']],
                ],
            ],
            'bookingContact' => [
                'email' => 'customer@example.test',
                'phone' => '08000000000',
            ],
            'bookingPassengers' => [[
                'type' => 'ADT',
                'first_name' => 'Test',
                'last_name' => 'Passenger',
            ]],
        ];
    }
}
