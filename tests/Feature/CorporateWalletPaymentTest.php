<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\FlightBookings\Pages\ListFlightBookings;
use App\Mail\UnTicketedConfirmationAlert;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyWalletEntry;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\User;
use App\Services\Corporate\CompanyWallet;
use App\Services\Corporate\CorporateWalletPayments;
use App\Services\Flights\FlightSupplierControl;
use App\Services\Reporting\ReportingAdapterRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CorporateWalletPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SUPPLIER_FARE = 900000;

    private const PASSWORD = 'correct-horse-42';

    /** How SkyLink's reserve answers: confirmed, refused, timeout, server_error or no_pnr. */
    private string $reserveAnswer = 'confirmed';

    /** How TravelNext's booking call answers: confirmed, refused, timeout, server_error or no_reference. */
    private string $travelnextAnswer = 'confirmed';

    private string $ticketOrderAnswer = 'issued';

    private int $verifiedPrice = self::SUPPLIER_FARE;

    /** SeerBit's view of an earlier card attempt: failed, paid or down. */
    private string $seerbitAnswer = 'failed';

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
        \App\Support\FlightMarkup::forgetCachedConfiguration();
        \App\Models\ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);

        $control = app(FlightSupplierControl::class);
        $control->enable('skylink', null);
        $control->enable('travelnext', null);

        Mail::fake();

        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($url, '/payments/query/') => $this->seerbitQuery(),
                str_contains($url, '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]]),
                str_ends_with($url, '/api/login') => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
                str_ends_with($url, 'flights/pricing') => Http::response(['success' => true, 'data' => [
                    'booking_token' => 'btk_repriced', 'verified' => true,
                    'original_price' => self::SUPPLIER_FARE, 'verified_price' => $this->verifiedPrice, 'currency' => 'NGN',
                ]]),
                str_ends_with($url, 'flights/reserve') => $this->skylinkReserve(),
                str_ends_with($url, 'ticket_order') => $this->ticketOrder(),
                str_ends_with($url, 'trip_details') => Http::response([]),
                str_ends_with($url, 'booking') => $this->travelnextBooking(),
                default => Http::response([]),
            };
        });
    }

    private function seerbitQuery()
    {
        return match ($this->seerbitAnswer) {
            'paid' => Http::response(['data' => ['payments' => ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => 99999999, 'currency' => 'NGN']]]),
            'down' => Http::response([], 503),
            default => Http::response(['data' => ['payments' => ['gatewayCode' => 'S20', 'gatewayMessage' => 'Pending', 'amount' => 0, 'currency' => 'NGN']]]),
        };
    }

    private function skylinkReserve()
    {
        return match ($this->reserveAnswer) {
            'refused' => Http::response(['success' => false, 'message' => 'Seat no longer available'], 422),
            'timeout' => throw new ConnectionException('Timed out'),
            'server_error' => Http::response(['success' => false, 'message' => 'Upstream error'], 502),
            'no_pnr' => Http::response(['success' => true, 'data' => ['status' => 'confirmed']]),
            default => Http::response(['success' => true, 'data' => [
                'pnr' => 'SKY-PNR-9', 'status' => 'confirmed', 'ticket_deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ]]),
        };
    }

    private function travelnextBooking()
    {
        return match ($this->travelnextAnswer) {
            'refused' => Http::response(['BookFlightResponse' => ['BookFlightResult' => ['Success' => 'false', 'Errors' => [['Message' => 'Fare gone']]]]]),
            'timeout' => throw new ConnectionException('Timed out'),
            'server_error' => Http::response([], 500),
            'no_reference' => Http::response(['BookFlightResponse' => ['BookFlightResult' => ['Success' => 'true', 'UniqueID' => '']]]),
            default => Http::response(['BookFlightResponse' => ['BookFlightResult' => ['Success' => 'true', 'UniqueID' => 'TN-123', 'Status' => 'CONFIRMED']]]),
        };
    }

    private function ticketOrder()
    {
        return $this->ticketOrderAnswer === 'issued'
            ? Http::response(['AirOrderTicketRS' => ['TicketOrderResult' => ['Success' => 'true', 'UniqueID' => 'TN-HOLD']]])
            : Http::response(['AirOrderTicketRS' => ['TicketOrderResult' => ['Success' => 'false', 'Errors' => [['Message' => 'Queue error']]]]]);
    }

    // Fixtures

    private function company(float $balance = 2000000, string $cac = 'RC100'): Company
    {
        $company = Company::create([
            'name' => 'Acme '.$cac,
            'cac_number' => $cac,
            'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test',
            'status' => 'active',
        ]);

        if ($balance > 0) {
            app(CompanyWallet::class)->post($company, 'top_up', $balance, 'seed:'.$company->id);
        }

        return $company->fresh();
    }

    private function booker(Company $company): CompanyUser
    {
        return $company->users()->create([
            'name' => 'Booker', 'email' => 'booker'.uniqid().'@acme.test', 'password' => self::PASSWORD, 'role' => 'booker',
        ]);
    }

    private function signIn(CompanyUser $user): void
    {
        $this->post(route('business.login.store'), ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('business.dashboard'));
        // The first request after signing in clears any earlier checkout.
        $this->get(route('business.dashboard'))->assertOk();
    }

    private function flight(string $source, array $overrides = []): array
    {
        return array_merge([
            'source' => $source,
            'fareSourceCode' => 'fare_'.$source,
            'fareType' => $source === 'skylink' ? 'Public' : 'WebFare',
            'price' => 930000.0,
            'supplierPrice' => 900000.0,
            'markupAmount' => 30000.0,
            'currency' => 'NGN',
            'airline' => 'Royal Air Maroc',
            'cabin' => 'Economy',
            'isPassportMandatory' => false,
            'fareBreakdown' => [['qty' => 1, 'currency' => 'NGN', 'paxType' => 'ADT']],
            'segments' => [[
                'from' => 'LOS', 'to' => 'DXB', 'fromCountry' => 'Nigeria', 'toCountry' => 'UAE',
                'departDT' => now()->addDays(40)->toIso8601String(), 'arriveDT' => now()->addDays(41)->toIso8601String(),
                'flightNo' => 'AT576', 'airline' => 'Royal Air Maroc', 'cabin' => 'Economy',
            ]],
        ], $overrides);
    }

    /** At the pay page with a pay-first fare, as checkout leaves the session. */
    private function atCheckout(CompanyUser $user, string $source = 'skylink', array $flightOverrides = []): float
    {
        $this->signIn($user);
        $flight = $this->flight($source, $flightOverrides);

        session([
            'bookingFlight' => $flight,
            'bookingSessionId' => 'sess',
            'bookingContact' => ['email' => 'traveller@example.test', 'phone' => '8000000000', 'country_code' => '234', 'area_code' => '080'],
            'bookingPassengers' => [[
                'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger',
                'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG',
            ]],
            'selectedExtras' => [],
        ]);

        return (float) $flight['price'];
    }

    /** At the payment options page with a TravelNext hold. */
    private function atHeldPayment(CompanyUser $user, array $overrides = []): FlightBooking
    {
        $this->signIn($user);
        $flight = $this->flight('travelnext', ['fareType' => 'Public']);

        $booking = FlightBooking::create(array_merge([
            'company_id' => $user->company_id,
            'company_user_id' => $user->id,
            'booking_ref' => 'TW-HOLD1',
            'supplier' => 'travelnext',
            'unique_id' => 'TN-HOLD',
            'fare_source_code' => 'fare',
            'fare_type' => 'Public',
            'route' => 'LOS - DXB',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'currency' => 'NGN',
            'total_price' => $flight['price'],
            'tkt_time_limit' => now()->addDay(),
            'flight_snapshot' => $flight,
            'passengers_snapshot' => [],
            'adult_count' => 1,
            'contact_email' => 'traveller@example.test',
        ], $overrides));

        session([
            'bookingFlight' => ['flight' => $flight],
            'bookingUniqueId' => $booking->unique_id,
            'flightBookingDbId' => $booking->id,
            'bookingContact' => ['email' => 'traveller@example.test'],
            'bookingPassengers' => [['type' => 'ADT']],
        ]);

        return $booking;
    }

    private function assertBalance(Company $company, float $expected): void
    {
        $this->assertEquals($expected, (float) $company->fresh()->wallet_balance);
        $this->assertSame([], app(CompanyWallet::class)->mismatchedCompanyIds());
    }

    private function assertNoCardPaymentStarted(): void
    {
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));
    }

    private function assertAlerted(): void
    {
        Mail::assertSent(UnTicketedConfirmationAlert::class);
    }

    // Paying

    public function test_a_skylink_booking_is_paid_from_the_wallet_with_no_card(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $price = $this->atCheckout($booker);

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $booking = FlightBooking::sole();
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertSame('SKY-PNR-9', $booking->unique_id);
        $this->assertEquals($price, (float) $booking->payment_amount);
        $this->assertBalance($company, 2000000 - $price);
        $this->assertNoCardPaymentStarted();

        $debit = CompanyWalletEntry::where('type', 'booking_debit')->sole();
        $this->assertSame($booking->id, $debit->flight_booking_id);
        $this->assertSame($booker->id, $debit->company_user_id);
        $this->assertSame(1, ActivityLog::where('company_user_id', $booker->id)->where('action', 'company_wallet_payment')->count());
    }

    public function test_a_travelnext_webfare_is_paid_from_the_wallet(): void
    {
        $company = $this->company();
        $price = $this->atCheckout($this->booker($company), 'travelnext');

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $booking = FlightBooking::sole();
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertSame('TN-123', $booking->unique_id);
        $this->assertBalance($company, 2000000 - $price);
        $this->assertNoCardPaymentStarted();
    }

    public function test_a_travelnext_hold_is_paid_from_the_wallet_and_ticketed(): void
    {
        $company = $this->company();
        $booking = $this->atHeldPayment($this->booker($company));

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $booking->refresh();
        $this->assertSame('ticketed', $booking->booking_status);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertBalance($company, 2000000 - 930000);
    }

    public function test_extras_are_part_of_what_the_wallet_pays(): void
    {
        $company = $this->company();
        $price = $this->atCheckout($this->booker($company));
        session(['selectedExtras' => ['baggage' => [['line_total' => 25000]]]]);

        $this->post(route('flights.payment.wallet'));

        $this->assertBalance($company, 2000000 - $price - 25000);
    }

    public function test_the_whole_balance_can_pay_for_a_booking(): void
    {
        $company = $this->company(930000);
        $this->atCheckout($this->booker($company));

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $this->assertBalance($company, 0);
    }

    public function test_the_receipt_and_ticket_emails_go_out(): void
    {
        $this->atCheckout($this->booker($this->company()));

        $this->post(route('flights.payment.wallet'));

        $booking = FlightBooking::sole();
        $this->assertTrue($booking->confirmation_email_sent);
        $this->assertTrue($booking->payment_receipt_sent);
    }

    // Twice is once

    public function test_paying_again_does_not_debit_or_book_twice(): void
    {
        $company = $this->company();
        $price = $this->atCheckout($this->booker($company));
        $this->post(route('flights.payment.wallet'));
        $booking = FlightBooking::sole();

        // A double-click arriving after the first finished, with the booking still in the session.
        session(['flightBookingDbId' => $booking->id, 'bookingFlight' => $booking->flight_snapshot, 'bookingContact' => ['email' => 'a@b.test'], 'bookingPassengers' => [['type' => 'ADT']]]);
        $this->post(route('flights.payment.wallet'))->assertRedirect(route('business.bookings.index'));

        $this->assertBalance($company, 2000000 - $price);
        $this->assertSame(1, CompanyWalletEntry::where('type', 'booking_debit')->count());
        $this->assertCount(1, collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), 'flights/reserve')));
    }

    public function test_the_debit_itself_cannot_post_twice(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $booking = FlightBooking::create([
            'company_id' => $company->id, 'booking_ref' => 'TW-X', 'fare_source_code' => 'x', 'currency' => 'NGN',
            'total_price' => 1000, 'booking_status' => 'pending_payment', 'payment_status' => 'pending',
        ]);
        $payments = app(CorporateWalletPayments::class);

        $payments->debit($booking, $booker, 1000, 'skylink_reserve_full');
        $payments->debit($booking, $booker, 1000, 'skylink_reserve_full');

        $this->assertBalance($company, 1999000);
    }

    public function test_a_request_already_in_flight_blocks_a_second_one(): void
    {
        $company = $this->company();
        $booking = $this->atHeldPayment($this->booker($company));
        $lock = \Illuminate\Support\Facades\Cache::lock('flight-wallet-payment:'.$booking->id, 180);
        $lock->get();

        $this->post(route('flights.payment.wallet'))->assertSessionHasErrors('error');

        $lock->release();
        $this->assertBalance($company, 2000000);
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    // Not enough money

    public function test_too_little_in_the_wallet_charges_nothing_and_books_nothing(): void
    {
        $company = $this->company(100);
        $this->atCheckout($this->booker($company));

        $this->from(route('flights.payment.gateway'))->post(route('flights.payment.wallet'))
            ->assertRedirect(route('flights.payment.gateway'))
            ->assertSessionHasErrors('error');

        $this->assertBalance($company, 100);
        $this->assertSame('pending', FlightBooking::sole()->payment_status);
        $this->assertSame(0, CompanyWalletEntry::where('type', 'booking_debit')->count());
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve'));
    }

    public function test_a_booking_left_short_can_still_be_paid_by_card(): void
    {
        $this->atCheckout($this->booker($this->company(100)));
        $this->from(route('flights.payment.gateway'))->post(route('flights.payment.wallet'));

        $this->post(route('flights.payment.gateway.process'))->assertRedirect('https://checkout.example.test/pay');

        $this->assertSame('gateway', FlightBooking::sole()->payment_method);
    }

    public function test_the_page_tells_a_booker_and_an_admin_different_things_when_short(): void
    {
        $company = $this->company(100);
        $booker = $this->booker($company);
        $this->atCheckout($booker);

        $this->get(route('flights.payment.gateway'))->assertOk()
            ->assertSee('Ask your Company Admin')
            ->assertDontSee('Pay ₦930,000.00 from wallet')
            ->assertDontSee('₦100.00');
    }

    public function test_a_booker_sees_the_button_but_not_the_balance(): void
    {
        $this->atCheckout($this->booker($this->company()));

        $this->get(route('flights.payment.gateway'))->assertOk()
            ->assertSee('Pay ₦930,000.00 from wallet')
            ->assertDontSee('2,000,000.00');
    }

    public function test_the_public_never_see_the_wallet_and_cannot_use_it(): void
    {
        $this->company();
        session([
            'bookingFlight' => $this->flight('skylink'),
            'bookingContact' => ['email' => 'traveller@example.test', 'phone' => '8000000000', 'country_code' => '234'],
            'bookingPassengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'T', 'last_name' => 'P', 'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG']],
        ]);

        $this->get(route('flights.payment.gateway'))->assertOk()->assertDontSee('company wallet');

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('air.flight-s'));

        $this->assertSame(0, FlightBooking::count());
        $this->assertSame(0, CompanyWalletEntry::where('type', 'booking_debit')->count());
    }

    // A clear refusal: the money comes back

    public function test_a_clear_skylink_refusal_returns_the_money(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        $this->reserveAnswer = 'refused';

        $this->post(route('flights.payment.wallet'))
            ->assertRedirect(route('air.flight-s'))
            ->assertSessionHas('fareUnavailable');

        $booking = FlightBooking::sole();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('refunded', $booking->payment_status);
        $this->assertBalance($company, 2000000);
        $this->assertSame(1, CompanyWalletEntry::where('type', 'booking_return')->where('flight_booking_id', $booking->id)->count());
        Mail::assertNotSent(UnTicketedConfirmationAlert::class);
    }

    public function test_a_clear_travelnext_refusal_returns_the_money(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company), 'travelnext');
        $this->travelnextAnswer = 'refused';

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('air.flight-s'));

        $this->assertSame('refunded', FlightBooking::sole()->payment_status);
        $this->assertBalance($company, 2000000);
    }

    public function test_returning_twice_returns_once(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        $this->reserveAnswer = 'refused';
        $this->post(route('flights.payment.wallet'));

        app(CorporateWalletPayments::class)->returnAfterRefusal(FlightBooking::sole(), 'again');

        $this->assertBalance($company, 2000000);
    }

    public function test_a_returned_booking_cannot_be_paid_again(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        $this->reserveAnswer = 'refused';
        $this->post(route('flights.payment.wallet'));
        $booking = FlightBooking::sole();
        $this->reserveAnswer = 'confirmed';

        session(['flightBookingDbId' => $booking->id, 'bookingFlight' => $booking->flight_snapshot, 'bookingContact' => ['email' => 'a@b.test'], 'bookingPassengers' => [['type' => 'ADT']]]);

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('air.flight-s'));
        $this->post(route('flights.payment.gateway.process'))->assertRedirect(route('air.flight-s'));

        $this->assertSame('cancelled', $booking->fresh()->booking_status);
        $this->assertBalance($company, 2000000);
        $this->assertNoCardPaymentStarted();
    }

    public function test_after_a_refusal_the_booker_can_book_again_and_pays_once(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $price = $this->atCheckout($booker);
        $this->reserveAnswer = 'refused';
        $this->post(route('flights.payment.wallet'));

        $this->reserveAnswer = 'confirmed';
        session(['bookingFlight' => $this->flight('skylink'), 'bookingContact' => ['email' => 'a@b.test', 'phone' => '8', 'country_code' => '234'], 'bookingPassengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'T', 'last_name' => 'P', 'gender' => 'M', 'dob' => '1990-01-01', 'nationality' => 'NG']]]);
        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $this->assertSame(2, FlightBooking::count());
        $this->assertBalance($company, 2000000 - $price);
    }

    // An unclear answer: the money stays, and operations hears

    /** @dataProvider unclearSkylinkAnswers */
    public function test_an_unclear_skylink_answer_keeps_the_money_and_alerts(string $answer): void
    {
        $company = $this->company();
        $price = $this->atCheckout($this->booker($company));
        $this->reserveAnswer = $answer;

        $this->post(route('flights.payment.wallet'))
            ->assertRedirect(route('business.bookings.index'))
            ->assertSessionHasErrors('wallet');

        $booking = FlightBooking::sole();
        $this->assertSame('failed', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertBalance($company, 2000000 - $price);
        $this->assertSame(0, CompanyWalletEntry::where('type', 'booking_return')->count());
        $this->assertAlerted();
    }

    public static function unclearSkylinkAnswers(): array
    {
        return ['timeout' => ['timeout'], 'server error' => ['server_error'], 'no PNR' => ['no_pnr']];
    }

    /** @dataProvider unclearTravelnextAnswers */
    public function test_an_unclear_travelnext_answer_keeps_the_money_and_alerts(string $answer): void
    {
        $company = $this->company();
        $price = $this->atCheckout($this->booker($company), 'travelnext');
        $this->travelnextAnswer = $answer;

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('business.bookings.index'));

        $this->assertSame('failed', FlightBooking::sole()->booking_status);
        $this->assertBalance($company, 2000000 - $price);
        $this->assertAlerted();
    }

    public static function unclearTravelnextAnswers(): array
    {
        return ['timeout' => ['timeout'], 'server error' => ['server_error'], 'no reference' => ['no_reference']];
    }

    public function test_a_failed_ticket_order_on_a_hold_keeps_the_money_for_a_retry(): void
    {
        $company = $this->company();
        $booking = $this->atHeldPayment($this->booker($company));
        $this->ticketOrderAnswer = 'failed';

        $this->post(route('flights.payment.wallet'));

        $booking->refresh();
        $this->assertSame('ticketing_failed', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertBalance($company, 2000000 - 930000);
        $this->assertSame(0, CompanyWalletEntry::where('type', 'booking_return')->count());
        $this->assertAlerted();
    }

    public function test_the_unclear_message_names_no_supplier_and_shows_on_the_bookings_page(): void
    {
        $this->atCheckout($this->booker($this->company()));
        $this->reserveAnswer = 'timeout';

        $this->followingRedirects()->post(route('flights.payment.wallet'))->assertOk()
            ->assertSee('clear answer from the airline')
            ->assertDontSee('SkyLink');
    }

    // Before any money moves

    public function test_a_fare_rise_asks_first_and_charges_nothing(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        $this->verifiedPrice = self::SUPPLIER_FARE + 45000;

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.price-change'));

        $this->assertBalance($company, 2000000);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve'));
    }

    public function test_after_accepting_a_rise_the_wallet_pays_the_new_price(): void
    {
        $company = $this->company();
        $price = $this->atCheckout($this->booker($company));
        $this->verifiedPrice = self::SUPPLIER_FARE + 45000;
        $this->post(route('flights.payment.wallet'));

        $this->post(route('flights.price-change.accept'))->assertRedirect(route('flights.payment.gateway'));
        $newPrice = (float) FlightBooking::sole()->flight_snapshot['price'];
        $this->assertGreaterThan($price, $newPrice);

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $this->assertBalance($company, 2000000 - $newPrice);
    }

    public function test_a_suspended_company_cannot_pay(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        $company->update(['status' => 'suspended']);
        \Illuminate\Support\Facades\Auth::forgetGuards();

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('business.login'));

        $this->assertBalance($company, 2000000);
        $this->assertSame(0, FlightBooking::count());
    }

    public function test_a_switched_off_api_stops_payment_before_the_debit(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        app(FlightSupplierControl::class)->disable('skylink', 'Test', null);

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('air.flight-s'));

        $this->assertBalance($company, 2000000);
    }

    public function test_one_company_cannot_pay_for_anothers_booking(): void
    {
        $mine = $this->company();
        $theirs = $this->company(cac: 'RC200');
        $booking = $this->atHeldPayment($this->booker($mine), ['company_id' => $theirs->id]);

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('air.flight-s'));

        $this->assertSame('pending', $booking->fresh()->payment_status);
        $this->assertBalance($mine, 2000000);
        $this->assertBalance($theirs, 2000000);
    }

    public function test_a_booking_awaiting_a_bank_transfer_cannot_also_use_the_wallet(): void
    {
        $company = $this->company();
        $this->atHeldPayment($this->booker($company), ['payment_status' => 'awaiting_bank_transfer']);

        $this->post(route('flights.payment.wallet'))->assertSessionHasErrors('error');

        $this->assertBalance($company, 2000000);
    }

    // Card and wallet on the same booking

    private function atCheckoutAfterACardAttempt(Company $company): FlightBooking
    {
        $this->atCheckout($this->booker($company));
        $this->post(route('flights.payment.gateway.process'))->assertRedirect('https://checkout.example.test/pay');

        return FlightBooking::sole();
    }

    public function test_an_abandoned_card_attempt_does_not_stop_the_wallet(): void
    {
        $company = $this->company();
        $booking = $this->atCheckoutAfterACardAttempt($company);

        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $this->assertSame('wallet', $booking->fresh()->payment_method);
        $this->assertBalance($company, 2000000 - 930000);
    }

    public function test_a_card_that_already_went_through_stops_the_wallet(): void
    {
        $company = $this->company();
        $booking = $this->atCheckoutAfterACardAttempt($company);
        $this->seerbitAnswer = 'paid';

        $this->post(route('flights.payment.wallet'))
            ->assertRedirect(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]));

        $this->assertBalance($company, 2000000);
    }

    public function test_if_seerbit_cannot_say_the_wallet_waits(): void
    {
        $company = $this->company();
        $this->atCheckoutAfterACardAttempt($company);
        $this->seerbitAnswer = 'down';

        $this->post(route('flights.payment.wallet'))->assertSessionHasErrors('error');

        $this->assertBalance($company, 2000000);
    }

    public function test_a_late_card_charge_never_books_again_and_alerts_support(): void
    {
        $company = $this->company();
        $booking = $this->atCheckoutAfterACardAttempt($company);
        $this->post(route('flights.payment.wallet'));
        $booking->refresh();
        $this->seerbitAnswer = 'paid';

        $this->post(route('payments.seerbit.webhook'), ['paymentReference' => $booking->payment_reference])->assertOk();

        $this->assertSame('wallet', $booking->fresh()->payment_method);
        $this->assertBalance($company, 2000000 - 930000);
        $this->assertCount(1, collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), 'flights/reserve')));
        $this->assertAlerted();
    }

    public function test_card_and_bank_transfer_cannot_follow_a_wallet_payment(): void
    {
        $company = $this->company();
        $booking = $this->atHeldPayment($this->booker($company));
        $this->post(route('flights.payment.wallet'));
        session(['flightBookingDbId' => $booking->id, 'bookingUniqueId' => $booking->fresh()->unique_id, 'bookingFlight' => ['flight' => $this->flight('travelnext')], 'bookingContact' => ['email' => 'a@b.test'], 'bookingPassengers' => [['type' => 'ADT']]]);
        config(['travelwheel.travelflex_bank_accounts' => [['bank' => 'X', 'account_number' => '1', 'account_name' => 'Y']]]);

        $this->post(route('flights.payment.gateway-ticket'))->assertRedirect();
        $this->post(route('flights.payment.bank-transfer'), ['payment_reference' => 'ABC'])->assertRedirect(route('business.bookings.index'));

        $this->assertSame('wallet', $booking->fresh()->payment_method);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertNoCardPaymentStarted();
    }

    public function test_a_card_payment_keeps_working_as_before_for_a_company(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));

        $this->post(route('flights.payment.gateway.process'))->assertRedirect('https://checkout.example.test/pay');

        $this->assertSame('gateway', FlightBooking::sole()->payment_method);
        $this->assertBalance($company, 2000000);
    }

    // Finance: refunds and payouts

    private function paidBooking(Company $company): FlightBooking
    {
        $this->atCheckout($this->booker($company));
        $this->post(route('flights.payment.wallet'));

        return FlightBooking::sole();
    }

    public function test_finance_refunds_after_penalties_and_the_company_sees_why(): void
    {
        $company = $this->company();
        $booking = $this->paidBooking($company);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListFlightBookings::class)
            ->callTableAction('creditWallet', $booking, ['amount' => 500000, 'reason' => 'Refund less airline penalty']);

        $this->assertBalance($company, 2000000 - 930000 + 500000);
        $entry = CompanyWalletEntry::where('type', 'refund')->sole();
        $this->assertStringContainsString('Refund less airline penalty', $entry->reason);
        $this->assertSame($booking->id, $entry->flight_booking_id);
        $this->assertSame(auth()->id(), (int) $entry->user_id);
        $this->assertSame(1, ActivityLog::where('action', 'creditWallet')->where('subject_id', $booking->id)->count());
        $this->assertSame('paid', $booking->fresh()->payment_status);
    }

    public function test_refunds_cannot_add_up_to_more_than_was_paid(): void
    {
        $company = $this->company();
        $booking = $this->paidBooking($company);
        $payments = app(CorporateWalletPayments::class);
        $staff = User::factory()->create(['is_admin' => true]);

        $payments->creditRefund($booking, 900000, 'Most of it', $staff);

        try {
            $payments->creditRefund($booking->fresh(), 30000.01, 'Too much', $staff);
            $this->fail('Refunded more than was paid.');
        } catch (\DomainException) {
            $this->assertBalance($company, 2000000 - 30000);
        }

        $payments->creditRefund($booking->fresh(), 30000, 'The rest', $staff);
        $this->assertBalance($company, 2000000);
        $this->assertEquals(0, $payments->refundable($booking->fresh()));
    }

    public function test_crediting_everything_closes_a_booking_that_was_never_issued(): void
    {
        $company = $this->company();
        $this->atCheckout($this->booker($company));
        $this->reserveAnswer = 'timeout';
        $this->post(route('flights.payment.wallet'));
        $booking = FlightBooking::sole();

        app(CorporateWalletPayments::class)->creditRefund($booking, 930000, 'Seat never existed', User::factory()->create());

        $this->assertSame('cancelled', $booking->fresh()->booking_status);
        $this->assertSame('refunded', $booking->fresh()->payment_status);
        $this->assertBalance($company, 2000000);
    }

    public function test_crediting_everything_on_an_issued_booking_leaves_it_issued(): void
    {
        $company = $this->company();
        $booking = $this->paidBooking($company);

        app(CorporateWalletPayments::class)->creditRefund($booking, 930000, 'Voided', User::factory()->create());

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
        $this->assertSame('refunded', $booking->fresh()->payment_status);
    }

    public function test_a_card_paid_company_booking_refunds_to_the_wallet(): void
    {
        $company = $this->company(0);
        $booking = FlightBooking::create([
            'company_id' => $company->id, 'booking_ref' => 'TW-CARD', 'fare_source_code' => 'x', 'currency' => 'NGN',
            'total_price' => 500000, 'payment_amount' => 500000, 'payment_charged_amount' => 500000,
            'booking_status' => 'confirmed', 'payment_status' => 'paid', 'payment_method' => 'gateway',
        ]);

        app(CorporateWalletPayments::class)->creditRefund($booking, 500000, 'Cancelled', User::factory()->create());

        $this->assertBalance($company, 500000);
    }

    public function test_public_bookings_have_nothing_to_credit(): void
    {
        $booking = FlightBooking::create([
            'booking_ref' => 'TW-PUB', 'fare_source_code' => 'x', 'currency' => 'NGN', 'total_price' => 5000,
            'booking_status' => 'confirmed', 'payment_status' => 'paid',
        ]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->assertEquals(0, app(CorporateWalletPayments::class)->refundable($booking));
        Livewire::test(ListFlightBookings::class)->assertTableActionHidden('creditWallet', $booking);
    }

    public function test_other_staff_cannot_credit_a_wallet(): void
    {
        $company = $this->company();
        $booking = $this->paidBooking($company);
        $operations = User::factory()->create(['department_id' => Department::create(['name' => 'Ops', 'slug' => 'ops-test'])->id]);
        $this->actingAs($operations);

        Livewire::test(ListFlightBookings::class)->assertTableActionHidden('creditWallet', $booking);
        Livewire::test(ViewCompany::class, ['record' => $company->id])->assertActionHidden('payoutWallet');
    }

    public function test_finance_records_a_payout_which_takes_money_out(): void
    {
        $company = $this->company();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('payoutWallet', ['amount' => 500000, 'bank_reference' => 'FBN-77'])
            ->assertHasNoActionErrors();

        $this->assertBalance($company, 1500000);
        $this->assertStringContainsString('FBN-77', CompanyWalletEntry::where('type', 'payout')->sole()->reason);
    }

    public function test_a_payout_cannot_overdraw(): void
    {
        $company = $this->company(100);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('payoutWallet', ['amount' => 500, 'bank_reference' => 'X'])
            ->assertNotified('The balance is too low for that');

        $this->assertBalance($company, 100);
    }

    // Labels and reports

    public function test_the_confirmation_page_and_wallet_history_say_wallet(): void
    {
        $this->atCheckout($this->booker($this->company()));

        $this->followingRedirects()->post(route('flights.payment.wallet'))
            ->assertOk()
            ->assertSee('Paid from your company wallet');
    }

    public function test_the_admin_can_filter_by_wallet_and_card_actions_are_hidden(): void
    {
        $company = $this->company();
        $booking = $this->paidBooking($company);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListFlightBookings::class)
            ->filterTable('payment_method', 'wallet')
            ->assertSee($booking->booking_ref)
            ->assertTableActionHidden('verifySeerbitPayment', $booking)
            ->assertTableActionHidden('markBankTransferPaid', $booking);
    }

    public function test_the_workflow_treats_a_wallet_booking_as_paid_with_nothing_to_verify(): void
    {
        $booking = $this->paidBooking($this->company());

        $stage = app(\App\Workflow\Workflows\FlightWorkflow::class)->stageFor($booking);

        $this->assertNotContains($stage, ['awaiting_transfer', 'pending_payment']);
    }

    public function test_reports_count_a_wallet_booking_once_and_reflect_a_full_refund(): void
    {
        $company = $this->company();
        $booking = $this->paidBooking($company);

        $synchronizer = app(\App\Services\Reporting\ReportingSynchronizer::class);
        $synchronizer->sync();
        $fact = \App\Models\ReportingFact::query()->where('reference', $booking->booking_ref)->sole();
        $this->assertSame('wallet', $fact->payment_method);
        $this->assertEquals(930000, (float) $fact->verified_collections);

        // A wallet top-up is not revenue, so it makes no fact of its own.
        $this->assertSame(1, \App\Models\ReportingFact::query()->where('product', 'flights')->count());

        app(CorporateWalletPayments::class)->creditRefund($booking->fresh(), 930000, 'Voided', User::factory()->create());
        $synchronizer->sync();

        $this->assertEquals(0, (float) \App\Models\ReportingFact::query()->where('reference', $booking->booking_ref)->sole()->verified_collections);
    }
}
