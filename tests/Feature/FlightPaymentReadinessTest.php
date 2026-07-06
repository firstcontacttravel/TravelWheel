<?php

namespace Tests\Feature;

use App\Http\Controllers\FlightBookingController;
use App\Models\FlightBooking;
use App\Services\SeerbitPaymentService;
use App\Services\AdminTicketingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FlightPaymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_hold_cannot_start_gateway_ticket_payment(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute()]);

        $response = $this->withSession([
            'bookingUniqueId' => $booking->unique_id,
            'flightBookingDbId' => $booking->id,
        ])->post(route('flights.payment.gateway-ticket'));

        $response->assertSessionHasErrors('payment');
        $this->assertNull($booking->fresh()->payment_reference);
    }

    public function test_expired_hold_cannot_open_payment_options(): void
    {
        $booking = $this->heldBooking(['tkt_time_limit' => now()->subMinute()]);

        $this->withSession([
            'bookingUniqueId' => $booking->unique_id,
            'flightBookingDbId' => $booking->id,
        ])->get(route('flights.payment.options'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHasErrors('error');
    }

    public function test_booking_resume_requires_a_valid_signature(): void
    {
        $booking = $this->heldBooking();

        $this->get(route('flights.payment.options.resume', $booking->booking_ref))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute(
            'flights.payment.options.resume',
            now()->addHour(),
            ['bookingRef' => $booking->booking_ref],
        );

        $this->get($signedUrl)->assertRedirect(route('flights.payment.options'));
    }

    public function test_gateway_query_outage_is_distinct_from_a_declined_payment(): void
    {
        config()->set('services.seerbit.public_key', 'public-test');
        config()->set('services.seerbit.secret_key', 'secret-test');
        config()->set('services.seerbit.base_url', 'https://seerbit.test');

        Http::fake([
            'https://seerbit.test/api/v2/encrypt/keys' => Http::response([
                'data' => ['EncryptedSecKey' => ['encryptedKey' => 'encrypted-test']],
            ]),
            'https://seerbit.test/api/v3/payments/query/*' => Http::response([], 503),
        ]);

        $verification = app(SeerbitPaymentService::class)->verifyPayment('TW-TEST');

        $this->assertFalse($verification['ok']);
        $this->assertFalse($verification['query_succeeded']);
    }

    public function test_ancillary_price_is_taken_from_supplier_session_not_customer_input(): void
    {
        $this->withSession($this->ancillarySession('NGN'));
        $request = Request::create('/', 'POST', [
            'extra_baggage' => ['outbound' => ['BAG-1' => 2]],
        ]);

        $extras = $this->collectExtras($request);

        $this->assertSame(5000.0, $extras['total_amount']);
        $this->assertSame('NGN', $extras['currency']);
    }

    public function test_ancillary_currency_mismatch_is_rejected(): void
    {
        $this->withSession($this->ancillarySession('USD'));
        $request = Request::create('/', 'POST', [
            'extra_baggage' => ['outbound' => ['BAG-1' => 1]],
        ]);

        $this->expectException(ValidationException::class);

        $this->collectExtras($request);
    }

    public function test_duplicate_gateway_callback_is_serialized(): void
    {
        $booking = $this->heldBooking([
            'payment_reference' => 'TW-LOCKED-CALLBACK',
            'payment_flow' => 'held_ticket_full',
            'payment_amount' => 100000,
            'payment_currency' => 'NGN',
        ]);
        $lock = Cache::lock('flight-payment-callback:'.$booking->id, 30);
        $this->assertTrue($lock->get());

        try {
            $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
                ->assertRedirect(route('flights.payment.options'))
                ->assertSessionHasErrors('error');
        } finally {
            $lock->release();
        }
    }

    public function test_search_supplier_connection_failure_returns_customer_safe_error(): void
    {
        Http::fake(fn () => Http::failedConnection('supplier unavailable'));

        $this->withSession([
            'pendingFlightSearch' => [
                'trip' => 'oneway',
                'from' => 'Lagos (LOS)',
                'to' => 'Abuja (ABV)',
                'depart' => '20/08/2026',
                'adults' => 1,
                'childs' => 0,
                'kids' => 0,
                'flight_type' => 'Y',
            ],
        ])->get(route('flights.search.run'))
            ->assertRedirect(route('air'))
            ->assertSessionHasErrors('error');
    }

    public function test_admin_ticketing_supplier_connection_failure_is_controlled(): void
    {
        Http::fake(fn () => Http::failedConnection('supplier unavailable'));
        $booking = $this->heldBooking();

        $result = app(AdminTicketingService::class)->ticketOrder($booking);

        $this->assertFalse($result['ok']);
        $this->assertSame('Ticketing provider is temporarily unavailable.', $result['message']);
    }

    private function heldBooking(array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-READY-001',
            'unique_id' => 'HOLD-READY-001',
            'fare_source_code' => 'READY-FARE',
            'fare_type' => 'Public',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => now()->addHours(6),
            'contact_email' => 'traveller@example.com',
        ], $overrides));
    }

    private function ancillarySession(string $serviceCurrency): array
    {
        return [
            'bookingFlight' => ['flight' => ['currency' => 'NGN']],
            'extraServices' => [
                'ExtraServicesResponse' => [
                    'ExtraServicesResult' => [
                        'ExtraServicesData' => [
                            'DynamicBaggage' => [[
                                'Behavior' => 'OUTBOUND',
                                'Services' => [[[
                                    'ServiceId' => 'BAG-1',
                                    'Description' => 'Extra bag',
                                    'ServiceCost' => [
                                        'Amount' => 2500,
                                        'CurrencyCode' => $serviceCurrency,
                                    ],
                                ]]],
                            ]],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function collectExtras(Request $request): array
    {
        $method = new \ReflectionMethod(FlightBookingController::class, '_collectSelectedExtras');

        return $method->invoke(app(FlightBookingController::class), $request);
    }
}
