<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\User;
use App\Services\DurableMailService;
use App\Filament\Resources\FlightBookings\FlightBookingResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Concerns\CorporateFlightFixtures;
use Tests\TestCase;

class CorporateBookingTest extends TestCase
{
    use CorporateFlightFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCorporateFlights();
    }

    private function bookingFor(Company $company, ?CompanyUser $user, array $overrides = []): FlightBooking
    {
        static $sequence = 0;
        $sequence++;

        return FlightBooking::create(array_merge([
            'company_id' => $company->id,
            'company_user_id' => $user?->id,
            'booking_ref' => 'TW-CORP'.$sequence,
            'supplier' => 'skylink',
            'unique_id' => 'PNR'.$sequence,
            'fare_source_code' => 'btk',
            'fare_type' => 'Public',
            'route' => 'LOS - DXB',
            'airline' => 'Royal Air Maroc',
            'cabin' => 'Economy',
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'currency' => 'NGN',
            'total_price' => 900000,
            'flight_snapshot' => ['price' => 900000, 'segments' => []],
            'passengers_snapshot' => [],
            'contact_email' => 'traveller@example.test',
        ], $overrides));
    }

    private function adminOf(Company $company): CompanyUser
    {
        return $company->users()->create([
            'name' => 'Admin', 'email' => 'admin'.uniqid().'@acme.test', 'password' => self::PASSWORD, 'role' => 'admin', 'login_code_enabled' => false,
        ]);
    }

    /** An Admin must pass the email code, so go through it. */
    private function signInAdmin(CompanyUser $admin): void
    {
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CompanyLoginCodeMail::class, function ($mail) use ($admin) {
            if ($mail->hasTo($admin->email)) {
                $this->post(route('business.login.code.verify'), ['code' => $mail->code]);
            }

            return true;
        });
    }

    // Who booked

    public function test_a_booking_records_the_company_and_the_booker(): void
    {
        $booker = $this->booker();
        $this->signIn($booker);
        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'));

        $booking = FlightBooking::sole();
        $this->assertSame($booker->company_id, $booking->company_id);
        $this->assertSame($booker->id, $booking->company_user_id);
    }

    public function test_the_booking_form_starts_with_the_bookers_email(): void
    {
        $booker = $this->booker(attributes: ['email' => 'priya@acme.test']);
        $this->signIn($booker);
        $this->select($this->searchSequentially());

        $this->get(route('flights.booking'))->assertOk()->assertSee('priya@acme.test');
    }

    // Who sees what

    public function test_a_booker_sees_only_their_own_bookings(): void
    {
        $company = $this->company();
        $mine = $this->booker($company);
        $colleague = $this->booker($company);
        $this->bookingFor($company, $mine, ['booking_ref' => 'TW-MINE']);
        $this->bookingFor($company, $colleague, ['booking_ref' => 'TW-THEIRS']);

        $this->signIn($mine);

        $this->get(route('business.bookings.index'))->assertOk()->assertSee('TW-MINE')->assertDontSee('TW-THEIRS');
    }

    public function test_an_admin_sees_every_booking_in_their_company_only(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $other = $this->company('RC200');
        $this->bookingFor($company, $booker, ['booking_ref' => 'TW-OURS']);
        $this->bookingFor($other, $this->booker($other), ['booking_ref' => 'TW-ELSEWHERE']);
        $this->bookingFor($other, null, ['company_id' => null, 'booking_ref' => 'TW-PUBLIC']);

        $this->signInAdmin($this->adminOf($company));

        $this->get(route('business.bookings.index'))->assertOk()
            ->assertSee('TW-OURS')
            ->assertDontSee('TW-ELSEWHERE')
            ->assertDontSee('TW-PUBLIC');
    }

    public function test_the_page_never_names_a_supplier(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $this->bookingFor($company, $booker);
        $this->signIn($booker);

        $this->get(route('business.bookings.index'))->assertOk()
            ->assertDontSee('skylink', false)
            ->assertDontSee('SkyLink')
            ->assertDontSee('TravelNext');
    }

    public function test_bookings_can_be_searched_and_filtered(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $this->bookingFor($company, $booker, ['booking_ref' => 'TW-AAA', 'booking_status' => 'confirmed']);
        $this->bookingFor($company, $booker, ['booking_ref' => 'TW-BBB', 'booking_status' => 'cancelled']);
        $this->signIn($booker);

        $this->get(route('business.bookings.index', ['q' => 'AAA']))->assertSee('TW-AAA')->assertDontSee('TW-BBB');
        $this->get(route('business.bookings.index', ['status' => 'cancelled']))->assertSee('TW-BBB')->assertDontSee('TW-AAA');
    }

    public function test_a_ticket_downloads_for_its_owner_but_not_for_anyone_else(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $colleague = $this->booker($company);
        $other = $this->company('RC200');
        $outsider = $this->booker($other);
        $booking = $this->bookingFor($company, $booker);

        $this->signIn($booker);
        $this->get(route('business.bookings.ticket', $booking->booking_ref))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        foreach ([$colleague, $outsider] as $stranger) {
            $this->signOut();
            $this->signIn($stranger);
            $this->get(route('business.bookings.ticket', $booking->booking_ref))->assertNotFound();
        }
    }

    public function test_an_unpaid_booking_has_no_ticket(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $booking = $this->bookingFor($company, $booker, ['payment_status' => 'pending', 'booking_status' => 'on_hold']);
        $this->signIn($booker);

        $this->get(route('business.bookings.ticket', $booking->booking_ref))->assertNotFound();
    }

    public function test_a_guest_cannot_open_bookings(): void
    {
        $this->get(route('business.bookings.index'))->assertRedirect(route('business.login'));
    }

    // TravelFlex

    public function test_travelflex_pages_are_refused_for_companies_but_not_the_public(): void
    {
        $this->get(route('flights.travelflex'))->assertRedirect(route('air.flight-s'));

        $this->signIn($this->booker());

        $this->get(route('flights.travelflex'))->assertRedirect(route('air'))->assertSessionHasErrors('error');
        $this->get(route('flights.travelflex.application.get'))->assertRedirect(route('air'));
        $this->post(route('flights.travelflex.submit-application'), [])->assertRedirect(route('air'));
    }

    public function test_asking_for_travelflex_in_the_form_is_ignored_for_a_company(): void
    {
        $this->signIn($this->booker());
        $flight = $this->searchSequentially();

        $this->post(route('flights.select'), [
            'fare_source_code' => $flight['fareSourceCode'],
            'source' => 'skylink',
            'intent' => 'travelflex',
        ])->assertRedirect(route('flights.booking'));

        $this->assertSame('booking', session('bookingIntent'));
    }

    public function test_the_payment_page_offers_travelflex_to_the_public_but_not_to_a_company(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $booking = $this->bookingFor($company, $booker, ['booking_status' => 'on_hold', 'payment_status' => 'pending']);
        $session = [
            'bookingFlight' => ['flight' => ['price' => 900000]],
            'bookingUniqueId' => $booking->unique_id,
            'flightBookingDbId' => $booking->id,
        ];

        $this->withSession($session)->get(route('flights.payment.options'))->assertOk()->assertSee('TravelFlex instalments');

        // The first request after signing in clears anything from before it.
        $this->signIn($booker);
        $this->get(route('business.dashboard'))->assertOk();
        session($session);

        $this->get(route('flights.payment.options'))->assertOk()->assertDontSee('TravelFlex instalments');
    }

    public function test_the_results_page_offers_travelflex_to_the_public_but_not_to_a_company(): void
    {
        $this->searchSequentially();
        $this->get(route('air.flight-s'))->assertOk()
            ->assertSee('Continue with TravelFlex')
            ->assertSee('TravelFlex financing');

        $this->signIn($this->booker());
        $this->searchSequentially();
        $this->get(route('air.flight-s'))->assertOk()
            ->assertDontSee('Continue with TravelFlex')
            ->assertDontSee('TravelFlex financing')
            ->assertSee('Booking for');
    }

    // Suspension

    public function test_a_company_suspended_mid_checkout_is_stopped_at_select(): void
    {
        $booker = $this->booker();
        $this->signIn($booker);
        $flight = $this->searchSequentially();

        $booker->company->update(['status' => 'suspended']);
        $this->nextRequest();

        $this->post(route('flights.select'), ['fare_source_code' => $flight['fareSourceCode'], 'source' => 'skylink'])
            ->assertRedirect(route('business.login'))
            ->assertSessionHasErrors('email');

        $this->assertNull(session('flightResultsStore'));
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/pricing'));
    }

    public function test_a_company_suspended_mid_checkout_is_stopped_before_card_payment(): void
    {
        $booker = $this->booker();
        $this->signIn($booker);
        $this->select($this->searchSequentially());
        $this->passengerDetails();

        $booker->company->update(['status' => 'suspended']);
        $this->nextRequest();

        $this->post(route('flights.payment.gateway.process'))->assertRedirect(route('business.login'));

        $this->assertSame(0, FlightBooking::count());
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));
    }

    public function test_a_suspended_companys_booking_cannot_be_paid_from_an_emailed_link(): void
    {
        $company = $this->company();
        $booking = $this->bookingFor($company, null, [
            'supplier' => 'travelnext',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => now()->addDay(),
        ]);
        $company->update(['status' => 'suspended']);

        $this->get(URL::signedRoute('flights.payment.options.resume', ['bookingRef' => $booking->booking_ref]));

        $this->post(route('flights.payment.gateway-ticket'))->assertRedirect(route('business.login'));
        $this->assertSame('pending', $booking->fresh()->payment_status);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));
    }

    public function test_a_payment_already_made_still_completes_after_suspension(): void
    {
        $booker = $this->booker();
        $this->signIn($booker);
        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'));
        $booking = FlightBooking::sole();

        $booker->company->update(['status' => 'suspended']);
        $this->nextRequest();

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]));

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
    }

    // A fare that rises

    /** On the payment page, with the fare about to rise by 45,000. */
    private function checkoutWithRisingFare(CompanyUser $booker): FlightBooking
    {
        $this->signIn($booker);
        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->verifiedPrice = self::SUPPLIER_FARE + 45000;

        $this->post(route('flights.payment.gateway.process'))->assertRedirect(route('flights.price-change'));

        return FlightBooking::sole();
    }

    public function test_a_rise_shows_the_new_price_and_charges_nothing(): void
    {
        $booking = $this->checkoutWithRisingFare($this->booker());

        $this->get(route('flights.price-change'))->assertOk()
            ->assertSee('The fare has changed')
            ->assertSee(number_format($booking->flight_snapshot['price'], 2))
            ->assertSee(number_format($booking->flight_snapshot['price'] + 45000, 2));

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    public function test_accepting_moves_the_booking_to_the_new_price_which_is_what_gets_charged(): void
    {
        $booker = $this->booker();
        $booking = $this->checkoutWithRisingFare($booker);
        $oldPrice = (float) $booking->flight_snapshot['price'];
        $oldMarkup = (float) $booking->markup_amount;

        $this->post(route('flights.price-change.accept'))->assertRedirect(route('flights.payment.gateway'));

        $booking->refresh();
        $newPrice = (float) $booking->flight_snapshot['price'];
        $this->assertEqualsWithDelta($oldPrice + 45000, $newPrice, 50);
        $this->assertEqualsWithDelta($oldPrice + 45000, (float) $booking->total_price, 50);
        $this->assertEquals($oldMarkup, (float) $booking->markup_amount);
        $this->assertEquals($newPrice, session('bookingFlight')['price']);
        $this->assertSame('btk_repriced', $booking->fare_source_code);
        $this->assertSame(1, ActivityLog::where('company_user_id', $booker->id)->where('action', 'company_fare_change_accepted')->count());

        // Nothing has been charged yet, and paying charges the new price.
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments'));

        $this->post(route('flights.payment.gateway.process'))->assertRedirect('https://checkout.example.test/pay');
        $this->assertEquals($newPrice, (float) $booking->fresh()->payment_amount);
    }

    public function test_a_further_rise_while_deciding_asks_again(): void
    {
        $booking = $this->checkoutWithRisingFare($this->booker());
        $oldPrice = (float) $booking->flight_snapshot['price'];
        $this->verifiedPrice = self::SUPPLIER_FARE + 90000;

        $this->post(route('flights.price-change.accept'))->assertRedirect(route('flights.price-change'));

        $this->assertEquals($oldPrice, (float) $booking->fresh()->flight_snapshot['price']);
        $this->get(route('flights.price-change'))->assertOk()->assertSee('went up again');
    }

    public function test_extras_stay_in_the_total_when_the_fare_rises(): void
    {
        $booking = $this->checkoutWithRisingFare($this->booker());
        $oldPrice = (float) $booking->flight_snapshot['price'];
        $booking->update(['total_price' => $oldPrice + 10000]);

        $this->post(route('flights.price-change.accept'));

        $this->assertEqualsWithDelta($oldPrice + 45000 + 10000, (float) $booking->fresh()->total_price, 50);
    }

    public function test_another_company_cannot_accept_a_fare_change(): void
    {
        $booking = $this->checkoutWithRisingFare($this->booker());
        $price = (float) $booking->flight_snapshot['price'];

        $this->signOut();
        $this->signIn($this->booker($this->company('RC200')));
        session(['corporatePriceChange' => ['booking_id' => $booking->id, 'new_fare' => $price + 45000]]);

        $this->post(route('flights.price-change.accept'))->assertRedirect(route('air.flight-s'));

        $this->assertEquals($price, (float) $booking->fresh()->flight_snapshot['price']);
    }

    /** Since 2026-11-09 the public are offered the new fare too, like a company. */
    public function test_the_public_are_offered_the_new_fare_when_it_rises(): void
    {
        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->verifiedPrice = self::SUPPLIER_FARE + 45000;

        $this->post(route('flights.payment.gateway.process'))
            ->assertRedirect(route('flights.price-change'));

        $this->get(route('flights.price-change'))->assertOk()->assertSee('Accept the new fare');
    }

    // Emails

    public function test_company_admins_are_copied_on_booking_emails(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $admin = $this->adminOf($company);
        $gone = $this->adminOf($company);
        $gone->update(['deactivated_at' => now()]);
        $booking = $this->bookingFor($company, $booker);

        app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_RECEIPT, 'traveller@example.test', $booking);

        $this->assertSame([$admin->email], NotificationOutbox::sole()->cc);
    }

    public function test_an_admin_is_not_copied_on_their_own_email(): void
    {
        $company = $this->company();
        $admin = $this->adminOf($company);
        $booking = $this->bookingFor($company, $admin);

        app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_RECEIPT, strtoupper($admin->email), $booking);

        $this->assertSame([], NotificationOutbox::sole()->cc);
    }

    public function test_public_booking_emails_copy_nobody(): void
    {
        $company = $this->company();
        $this->adminOf($company);
        $booking = $this->bookingFor($company, null, ['company_id' => null]);

        app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_RECEIPT, 'traveller@example.test', $booking);

        $this->assertSame([], NotificationOutbox::sole()->cc);
    }

    // Rate limits

    public function test_each_booker_has_their_own_rate_limit_on_a_shared_address(): void
    {
        $company = $this->company();
        $first = $this->booker($company);
        $second = $this->booker($company);

        $this->signIn($first);
        foreach (range(1, 12) as $ignored) {
            $this->post(route('flights.search'), [])->assertRedirect();
        }
        $this->post(route('flights.search'), [])->assertStatus(429);

        $this->signOut();
        $this->signIn($second);

        $this->post(route('flights.search'), [])->assertRedirect();
    }

    public function test_the_public_limit_still_counts_by_address(): void
    {
        foreach (range(1, 12) as $ignored) {
            $this->post(route('flights.search'), [])->assertRedirect();
        }

        $this->post(route('flights.search'), [])->assertStatus(429);
    }

    // The admin

    public function test_staff_can_see_and_filter_bookings_by_company(): void
    {
        $company = $this->company();
        $this->bookingFor($company, null, ['booking_ref' => 'TW-CO']);
        $this->bookingFor($company, null, ['booking_ref' => 'TW-PUB', 'company_id' => null]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        \Livewire\Livewire::test(\App\Filament\Resources\FlightBookings\Pages\ListFlightBookings::class)
            ->assertOk()
            ->assertSee($company->name)
            ->filterTable('company_id', $company->id)
            ->assertSee('TW-CO')
            ->assertDontSee('TW-PUB');

        $this->get(FlightBookingResource::getUrl('index'))->assertOk();
    }
}
