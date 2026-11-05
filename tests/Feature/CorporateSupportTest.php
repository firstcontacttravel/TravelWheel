<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Mail\CompanyBookingProblemMail;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyInvoice;
use App\Models\CompanyUser;
use App\Models\CompanyWalletEntry;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Corporate\CompanyWallet;
use App\Services\Corporate\CorporateContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Feature\Concerns\CorporateFlightFixtures;
use Tests\TestCase;

class CorporateSupportTest extends TestCase
{
    use CorporateFlightFixtures;
    use RefreshDatabase;

    private const COMPANY_RATE = 5000.0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCorporateFlights();
    }

    private function ceo(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function department(string $slug = 'ops-test'): Department
    {
        return Department::firstOrCreate(['slug' => $slug], ['name' => $slug]);
    }

    private function staff(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['department_id' => $this->department()->id], $attributes));
    }

    private function fundedCompany(): Company
    {
        $company = $this->company();
        $this->setRate($company, self::COMPANY_RATE);
        app(CompanyWallet::class)->post($company, 'top_up', 2000000, 'seed');

        return $company->fresh();
    }

    /** Starts through the real admin action. */
    private function startBookingFor(Company $company, User $staff): void
    {
        $this->actingAs($staff);

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('bookForCompany')
            ->assertRedirect(route('air'));
    }

    private function bookAndPayFromWallet(): FlightBooking
    {
        $flight = $this->searchSequentially();
        $this->select($flight);
        $this->passengerDetails();
        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        return FlightBooking::sole();
    }

    // Who may book for a company

    public function test_the_ceo_a_department_head_and_the_account_manager_may_book_for_a_company(): void
    {
        $manager = $this->staff();
        $company = $this->company();
        $company->update(['account_manager_id' => $manager->id]);

        foreach ([$this->ceo(), $this->staff(['is_department_head' => true]), $manager] as $allowed) {
            $this->assertTrue($company->fresh()->canBeBookedForBy($allowed));
        }
    }

    public function test_other_staff_cannot_book_for_a_company(): void
    {
        $company = $this->company();
        $this->actingAs($this->staff());

        Livewire::test(ViewCompany::class, ['record' => $company->id])->assertActionHidden('bookForCompany');

        session()->put(CorporateContext::ACTING_KEY, $company->id);
        $this->get(route('air'));

        $this->assertNull(session(CorporateContext::ACTING_KEY));
        $this->assertNull(app(CorporateContext::class)->user());
    }

    public function test_another_companys_account_manager_cannot(): void
    {
        $manager = $this->staff();
        $theirs = $this->company('RC200');
        $theirs->update(['account_manager_id' => $manager->id]);

        $this->assertFalse($this->company()->canBeBookedForBy($manager));
    }

    public function test_a_deactivated_member_of_staff_cannot(): void
    {
        $this->assertFalse($this->company()->canBeBookedForBy($this->ceo()->fill(['deactivated_at' => now()])));
    }

    public function test_nobody_can_book_for_a_suspended_company(): void
    {
        $this->assertFalse($this->company('RC200', 'suspended')->canBeBookedForBy($this->ceo()));
    }

    public function test_a_guest_cannot_start_it_by_sending_a_session_value(): void
    {
        $company = $this->company();
        session()->put(CorporateContext::ACTING_KEY, $company->id);

        $this->get(route('air'))->assertOk();

        $this->assertNull(session(CorporateContext::ACTING_KEY));
    }

    // Booking

    public function test_staff_book_at_the_company_rate_and_pay_from_its_wallet(): void
    {
        $company = $this->fundedCompany();
        $staff = $this->ceo();
        $this->startBookingFor($company, $staff);

        $searched = $this->searchSequentially();
        $this->assertSame(self::COMPANY_RATE, $searched['markupAmount']);
        $this->select($searched);
        $this->passengerDetails();
        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        $booking = FlightBooking::sole();
        $this->assertSame($company->id, $booking->company_id);
        $this->assertSame($staff->id, $booking->booked_by_user_id);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertEquals(2000000 - $searched['price'], (float) $company->fresh()->wallet_balance);
    }

    public function test_the_staff_member_is_recorded_on_the_ledger_and_the_log(): void
    {
        $company = $this->fundedCompany();
        $staff = $this->staff(['is_department_head' => true]);
        $this->startBookingFor($company, $staff);

        $booking = $this->bookAndPayFromWallet();

        $this->assertSame($staff->id, (int) CompanyWalletEntry::where('type', 'booking_debit')->sole()->user_id);

        $log = ActivityLog::where('action', 'company_wallet_payment')->sole();
        $this->assertSame($staff->id, $log->user_id);
        $this->assertSame($staff->department_id, $log->department_id);
        $this->assertSame($booking->id, $log->subject_id);

        $this->assertSame(1, ActivityLog::where('action', 'bookForCompany')->where('user_id', $staff->id)->where('subject_id', $company->id)->count());
    }

    public function test_the_company_sees_it_as_booked_by_travelwheel(): void
    {
        $company = $this->fundedCompany();
        $this->startBookingFor($company, $this->ceo());
        $this->bookAndPayFromWallet();
        $staffName = auth()->user()->name;

        $this->post(route('business.acting.stop'));
        Auth::guard('web')->logout();
        $this->nextRequest();

        $admin = $company->users()->create(['name' => 'Admin', 'email' => 'admin@acme.test', 'password' => self::PASSWORD, 'role' => 'admin']);
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        Mail::assertSent(\App\Mail\CompanyLoginCodeMail::class, function ($mail) use ($admin) {
            if ($mail->hasTo($admin->email)) {
                $this->post(route('business.login.code.verify'), ['code' => $mail->code]);
            }

            return true;
        });

        $this->get(route('business.bookings.index'))->assertOk()
            ->assertSee('TravelWheel staff')
            ->assertDontSee($staffName);

        $this->assertSame('TravelWheel staff', CompanyInvoice::sole()->booking_details['booked_by']);
    }

    public function test_checkout_starts_from_the_companys_contact_not_the_stand_ins_address(): void
    {
        $company = $this->fundedCompany();
        $this->startBookingFor($company, $this->ceo());
        $this->select($this->searchSequentially());

        $this->get(route('flights.booking'))->assertOk()
            ->assertSee($company->contact_email)
            ->assertDontSee('system.invalid');
    }

    public function test_the_bar_says_staff_are_booking_and_stopping_ends_it(): void
    {
        $company = $this->fundedCompany();
        $this->startBookingFor($company, $this->ceo());
        $this->searchSequentially();

        $this->get(route('air'))->assertOk()->assertSee('TravelWheel staff booking for '.$company->name);

        $this->post(route('business.acting.stop'))->assertRedirect();

        $this->assertNull(session(CorporateContext::ACTING_KEY));
        $this->assertSame(1, ActivityLog::where('action', 'corporate.stopped_booking_for_company')->count());

        // Prices and search go back to public on the next page.
        $this->get(route('air'))->assertOk()->assertDontSee('TravelWheel staff booking for');
        $this->assertNull(session('flightResultsStore'));
        $this->assertNotEquals(self::COMPANY_RATE, $this->searchSequentially()['markupAmount']);
    }

    public function test_a_public_booking_by_staff_has_no_company_or_staff_recorded(): void
    {
        $this->actingAs($this->ceo());
        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'));

        $booking = FlightBooking::sole();
        $this->assertNull($booking->company_id);
        $this->assertNull($booking->booked_by_user_id);
    }

    public function test_losing_the_permission_ends_it_on_the_next_request(): void
    {
        $company = $this->fundedCompany();
        $manager = $this->staff();
        $company->update(['account_manager_id' => $manager->id]);
        $this->startBookingFor($company, $manager);
        $flight = $this->searchSequentially();

        $company->update(['account_manager_id' => null]);

        $this->post(route('flights.select'), ['fare_source_code' => $flight['fareSourceCode'], 'source' => 'skylink'])
            ->assertRedirect(filament()->getPanel('admin')->getUrl());

        $this->assertNull(session(CorporateContext::ACTING_KEY));
        $this->assertNull(session('flightResultsStore'));
    }

    public function test_suspending_the_company_ends_it(): void
    {
        $company = $this->fundedCompany();
        $this->startBookingFor($company, $this->ceo());
        $this->select($this->searchSequentially());
        $this->passengerDetails();

        $company->update(['status' => 'suspended']);

        $this->post(route('flights.payment.wallet'))->assertRedirect(filament()->getPanel('admin')->getUrl());

        $this->assertEquals(2000000, (float) $company->fresh()->wallet_balance);
    }

    public function test_an_unclear_supplier_answer_while_staff_book_goes_to_the_admin(): void
    {
        $company = $this->fundedCompany();
        $this->startBookingFor($company, $this->ceo());
        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->reserveFails = true;

        $this->post(route('flights.payment.wallet'))
            ->assertRedirect(\App\Filament\Resources\FlightBookings\FlightBookingResource::getUrl('index'));

        $this->assertSame('failed', FlightBooking::sole()->booking_status);
    }

    // A staff session never becomes a company login

    public function test_booking_for_a_company_gives_no_access_to_its_portal(): void
    {
        $this->startBookingFor($this->fundedCompany(), $this->ceo());

        foreach (['business.dashboard', 'business.team.index', 'business.wallet.index', 'business.bookings.index', 'business.invoices.index', 'business.travellers.index', 'business.statements.show', 'business.account'] as $route) {
            $this->get(route($route))->assertRedirect(route('business.login'));
        }

        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_the_stand_in_can_never_sign_in(): void
    {
        $company = $this->company();
        $standIn = $company->systemUser();

        $this->assertFalse($standIn->canSignIn());
        $this->assertTrue($standIn->is_system);

        // Even with the right password.
        $standIn->update(['password' => self::PASSWORD]);
        $this->post(route('business.login.store'), ['email' => $standIn->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_the_stand_in_gets_no_password_reset(): void
    {
        $standIn = $this->company()->systemUser();

        $this->post(route('business.password.email'), ['email' => $standIn->email]);

        Mail::assertNotSent(\App\Mail\CompanyPasswordResetMail::class);
    }

    public function test_the_stand_in_stays_off_the_team_and_out_of_the_counts(): void
    {
        $company = $this->company();
        $standIn = $company->systemUser();
        $this->assertTrue($standIn->is($company->systemUser()));

        $this->assertSame(0, $company->users()->count());

        $admin = $company->users()->create(['name' => 'Admin', 'email' => 'admin@acme.test', 'password' => self::PASSWORD, 'role' => 'booker']);
        $this->signIn($admin);
        $admin->update(['role' => 'admin']);
        $this->nextRequest();

        $this->get(route('business.team.index'))->assertOk()->assertDontSee('TravelWheel staff')->assertDontSee('system.invalid');
    }

    public function test_starting_ends_any_company_login_in_that_browser(): void
    {
        $company = $this->fundedCompany();
        $this->signIn($this->booker($company));
        $this->assertTrue(Auth::guard('corporate')->check());

        $this->startBookingFor($company, $this->ceo());

        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_one_company_login_is_not_affected_by_another_companys_stand_in(): void
    {
        $company = $this->fundedCompany();
        $other = $this->company('RC200');
        $other->systemUser();
        $this->signIn($this->booker($company));

        $this->assertSame($company->id, app(CorporateContext::class)->user()?->company_id);
    }

    // Account managers

    public function test_the_ceo_assigns_an_account_manager_and_other_staff_cannot(): void
    {
        $company = $this->company();
        $manager = $this->staff();
        $this->actingAs($this->ceo());

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('accountManager', ['account_manager_id' => $manager->id])
            ->assertHasNoActionErrors();

        $this->assertSame($manager->id, $company->fresh()->account_manager_id);

        $this->actingAs($this->staff());
        Livewire::test(ViewCompany::class, ['record' => $company->id])->assertActionHidden('accountManager');
    }

    private function bookingFor(Company $company, array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'company_id' => $company->id, 'booking_ref' => 'TW-AM'.uniqid(), 'supplier' => 'skylink', 'fare_source_code' => 'x',
            'route' => 'LOS - DXB', 'booking_status' => 'pending_payment', 'payment_status' => 'pending', 'currency' => 'NGN', 'total_price' => 1000,
            'flight_snapshot' => ['price' => 1000], 'passengers_snapshot' => [],
        ], $overrides));
    }

    public function test_the_manager_gets_a_bell_when_a_booking_is_paid(): void
    {
        $manager = $this->staff();
        $company = $this->company();
        $company->update(['account_manager_id' => $manager->id]);
        $booking = $this->bookingFor($company);
        $this->assertSame(0, $manager->notifications()->count());

        $booking->update(['payment_status' => 'paid']);

        $this->assertSame(1, $manager->notifications()->count());
        $this->assertStringContainsString($booking->booking_ref, $manager->notifications()->first()->data['title']);
        Mail::assertNotSent(CompanyBookingProblemMail::class);
    }

    public function test_the_manager_gets_a_bell_and_an_email_when_a_booking_has_a_problem(): void
    {
        $manager = $this->staff();
        $company = $this->company();
        $company->update(['account_manager_id' => $manager->id]);
        $booking = $this->bookingFor($company);

        $booking->update(['booking_status' => 'ticketing_failed']);

        $this->assertSame(1, $manager->notifications()->count());
        Mail::assertSent(CompanyBookingProblemMail::class, fn ($mail) => $mail->hasTo($manager->email));
        $this->assertStringContainsString($booking->booking_ref, (new CompanyBookingProblemMail($booking->fresh('company'), 'https://x.test'))->render());
    }

    public function test_no_manager_or_a_deactivated_one_means_no_alert_and_no_trouble(): void
    {
        $company = $this->company();
        $this->bookingFor($company)->update(['payment_status' => 'paid']);

        $gone = $this->staff(['deactivated_at' => now()]);
        $company->update(['account_manager_id' => $gone->id]);
        $this->bookingFor($company->fresh())->update(['booking_status' => 'failed']);

        $this->assertSame(0, $gone->notifications()->count());
        Mail::assertNotSent(CompanyBookingProblemMail::class);
    }

    public function test_a_public_booking_alerts_nobody(): void
    {
        $manager = $this->staff();
        $company = $this->company();
        $company->update(['account_manager_id' => $manager->id]);

        $this->bookingFor($company, ['company_id' => null])->update(['payment_status' => 'paid']);

        $this->assertSame(0, $manager->notifications()->count());
    }

    public function test_a_failing_alert_never_breaks_the_booking(): void
    {
        $this->mock(\App\Services\Corporate\AccountManagerAlerts::class)->shouldReceive('bookingSaved')->andThrow(new \RuntimeException('Oops'));

        $booking = $this->bookingFor($this->company());
        $booking->update(['payment_status' => 'paid']);

        $this->assertSame('paid', $booking->fresh()->payment_status);
    }

    // Workflow

    public function test_company_bookings_start_at_high_priority_and_public_ones_at_normal(): void
    {
        $company = $this->company();
        $corporate = $this->bookingFor($company);
        $public = $this->bookingFor($company, ['company_id' => null]);

        $this->assertSame('high', WorkItem::where('subject_id', $corporate->id)->sole()->priority);
        $this->assertSame('normal', WorkItem::where('subject_id', $public->id)->sole()->priority);
    }

    public function test_a_priority_someone_changed_is_kept_when_the_booking_moves_on(): void
    {
        $booking = $this->bookingFor($this->company());
        $item = WorkItem::where('subject_id', $booking->id)->sole();
        $item->update(['priority' => 'urgent']);

        $booking->update(['payment_status' => 'paid']);

        $this->assertSame('urgent', $item->fresh()->priority);
    }

    public function test_a_wallet_booking_needs_no_payment_verification(): void
    {
        $company = $this->fundedCompany();
        $this->signIn($this->booker($company));

        $stage = WorkItem::where('subject_id', $this->bookAndPayFromWallet()->id)->sole()->stage;

        $this->assertNotContains($stage, ['awaiting_transfer', 'pending_payment']);
    }

    // The dashboard

    private function dashboardBooking(Company $company, CompanyUser $user, string $ref, int $daysAway, array $overrides = []): FlightBooking
    {
        return $this->bookingFor($company, array_merge([
            'company_user_id' => $user->id,
            'booking_ref' => $ref,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'flight_snapshot' => ['price' => 1000, 'segments' => [['departDT' => now()->addDays($daysAway)->toIso8601String()]]],
        ], $overrides));
    }

    public function test_the_dashboard_shows_upcoming_trips_soonest_first_and_recent_bookings(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $this->dashboardBooking($company, $booker, 'TW-LATER', 30);
        $this->dashboardBooking($company, $booker, 'TW-SOON', 3);
        $this->dashboardBooking($company, $booker, 'TW-PAST', -5);
        $this->dashboardBooking($company, $booker, 'TW-UNPAID', 4, ['payment_status' => 'pending']);
        $this->signIn($booker);

        $upcoming = $this->get(route('business.dashboard'))->assertOk()->viewData('upcoming');

        $this->assertSame(['TW-SOON', 'TW-LATER'], $upcoming->pluck('booking.booking_ref')->all());

        $this->get(route('business.dashboard'))->assertSee('TW-PAST')->assertSee('TW-UNPAID');
    }

    public function test_a_booker_sees_their_own_trips_and_no_balance(): void
    {
        $company = $this->fundedCompany();
        $mine = $this->booker($company);
        $this->dashboardBooking($company, $mine, 'TW-MINE', 3);
        $this->dashboardBooking($company, $this->booker($company), 'TW-THEIRS', 3);
        $this->signIn($mine);

        $this->get(route('business.dashboard'))->assertOk()
            ->assertSee('TW-MINE')
            ->assertDontSee('TW-THEIRS')
            ->assertDontSee('2,000,000.00')
            ->assertDontSee('Wallet balance');
    }

    public function test_the_dashboard_names_the_account_manager_or_falls_back_to_support(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $this->signIn($booker);

        $this->get(route('business.dashboard'))->assertSee(config('brand.support_email'));

        $company->update(['account_manager_id' => $this->staff(['name' => 'Tola Manager', 'email' => 'tola@travelwheel.test'])->id]);
        $this->nextRequest();

        $this->get(route('business.dashboard'))->assertSee('Tola Manager')->assertSee('tola@travelwheel.test');
    }

    public function test_a_booking_with_an_unreadable_date_does_not_break_the_dashboard(): void
    {
        $company = $this->company();
        $booker = $this->booker($company);
        $this->dashboardBooking($company, $booker, 'TW-ODD', 3, ['flight_snapshot' => ['segments' => [['departDT' => 'not a date']]]]);
        $this->signIn($booker);

        $this->get(route('business.dashboard'))->assertOk();
    }

    // Launch switches

    public function test_public_links_stay_hidden_until_switched_on(): void
    {
        config(['corporate.public_links' => false]);
        $this->get(route('air'))->assertOk()->assertDontSee('Request a corporate account');

        config(['corporate.public_links' => true]);
        $this->get(route('air'))->assertOk()
            ->assertSee('Request a corporate account')
            ->assertSee(route('business.request.create'))
            ->assertSee(route('business.login'));
    }

    private function ticketEmailHtml(FlightBooking $booking): string
    {
        return (new \App\Mail\ETicketMail($booking, []))->render();
    }

    public function test_the_skylink_promise_shows_only_when_set_and_only_on_those_bookings(): void
    {
        $company = $this->company();
        $booking = $this->bookingFor($company, ['booking_status' => 'confirmed', 'payment_status' => 'paid', 'contact_email' => 'a@b.test']);
        $public = $this->bookingFor($company, ['company_id' => null, 'booking_status' => 'confirmed', 'payment_status' => 'paid']);

        $this->assertStringNotContainsString('Tickets and changes', $this->ticketEmailHtml($booking));

        config(['corporate.skylink_turnaround' => 'within 4 working hours']);

        $this->assertStringContainsString('within 4 working hours', $this->ticketEmailHtml($booking));
        $this->assertStringNotContainsString('within 4 working hours', $this->ticketEmailHtml($public));
    }
}
