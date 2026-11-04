<?php

namespace Tests\Feature;

use App\Filament\Pages\DefaultCorporateRates;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyServiceCharge;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\FlightSearch;
use App\Models\User;
use App\Services\Flights\FlightSearchStore;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CorporateFlightFixtures;
use Tests\TestCase;

class CorporatePricingTest extends TestCase
{
    use CorporateFlightFixtures;
    use RefreshDatabase;

    private const COMPANY_RATE = 5000.0;

    private const DEFAULT_RATE = 7000.0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCorporateFlights();
    }

    private function companyWithRate(): Company
    {
        $company = $this->company();
        $this->setRate($company, self::COMPANY_RATE);

        return $company;
    }

    // The rate a flight gets

    public function test_the_fallback_runs_company_then_default_then_public(): void
    {
        $company = $this->company();
        $flight = ['price' => 100, 'cabin' => 'Economy', 'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'France']]];
        $public = FlightMarkup::apply($flight, null)['markupAmount'];

        // Nothing set: the public rate.
        $this->assertSame($public, FlightMarkup::apply($flight, $company)['markupAmount']);

        // A default rate.
        $this->setRate(null, self::DEFAULT_RATE);
        $this->assertSame(self::DEFAULT_RATE, FlightMarkup::apply($flight, $company)['markupAmount']);

        // The company's own rate wins.
        $this->setRate($company, self::COMPANY_RATE);
        $this->assertSame(self::COMPANY_RATE, FlightMarkup::apply($flight, $company)['markupAmount']);
    }

    public function test_one_rate_falls_back_on_its_own(): void
    {
        $company = $this->company();
        $this->setRate(null, self::DEFAULT_RATE);
        CompanyServiceCharge::create(['company_id' => $company->id, 'route_category' => 'from_nigeria', 'cabin' => 'economy', 'amount' => self::COMPANY_RATE]);
        FlightMarkup::forgetCachedConfiguration();

        $economy = ['price' => 100, 'cabin' => 'Economy', 'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'France']]];
        $business = ['price' => 100, 'cabin' => 'Business', 'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'France']]];

        $this->assertSame(self::COMPANY_RATE, FlightMarkup::apply($economy, $company)['markupAmount']);
        $this->assertSame(self::DEFAULT_RATE, FlightMarkup::apply($business, $company)['markupAmount']);
    }

    public function test_one_companys_rate_never_reaches_another(): void
    {
        $this->companyWithRate();
        $other = $this->company('RC200');
        $flight = ['price' => 100, 'cabin' => 'Economy', 'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'France']]];

        $this->assertSame(FlightMarkup::apply($flight, null)['markupAmount'], FlightMarkup::apply($flight, $other)['markupAmount']);
    }

    public function test_corporate_rates_leave_public_prices_alone(): void
    {
        $flight = ['price' => 100, 'cabin' => 'Economy', 'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'France']]];
        $before = FlightMarkup::apply($flight, null);
        $this->assertSame(30000.0, $before['markupAmount']);

        $this->setRate(null, self::DEFAULT_RATE);
        $this->setRate($this->company(), self::COMPANY_RATE);

        $this->assertSame($before, FlightMarkup::apply($flight, null));
    }

    public function test_leaving_out_the_company_fails_rather_than_charging_the_public_rate(): void
    {
        $this->expectException(\ArgumentCountError::class);

        FlightMarkup::apply(['price' => 100]);
    }

    // The whole journey: the price must not move

    public function test_sequential_search_keeps_the_company_price_from_search_through_payment(): void
    {
        $company = $this->companyWithRate();
        $this->signIn($this->booker($company));

        $searched = $this->searchSequentially();
        $this->assertSame(self::COMPANY_RATE, $searched['markupAmount']);

        $selected = $this->select($searched);
        $this->assertSame($searched['price'], $selected['price']);

        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'))->assertRedirect('https://checkout.example.test/pay');

        $booking = FlightBooking::sole();
        $this->assertSame($company->id, $booking->company_id);
        $this->assertSame((float) $searched['price'], (float) $booking->payment_amount);
        $this->assertSame((float) $searched['price'], (float) $booking->total_price);
        $this->assertSame(self::COMPANY_RATE, (float) $booking->markup_amount);
    }

    public function test_parallel_search_prices_at_the_company_rate_with_no_session(): void
    {
        config(['flights.parallel_search' => true]);
        $company = $this->companyWithRate();
        $this->signIn($this->booker($company));

        $this->post(route('flights.search'), $this->searchForm())->assertRedirect(route('air.flight-s'));

        $searchId = session(FlightSearchStore::SESSION_ID);
        $this->assertSame($company->id, FlightSearch::find($searchId)->company_id);

        // Sign out first: the search itself must know whose rate applies.
        $this->signOut();

        $flights = $this->getJson(route('flights.search.supplier', ['search' => $searchId, 'supplier' => 'skylink']))
            ->assertOk()->json('flights');

        $this->assertSame(self::COMPANY_RATE, (float) $flights[0]['markupAmount']);
    }

    public function test_parallel_search_keeps_the_company_price_through_select_and_payment(): void
    {
        config(['flights.parallel_search' => true]);
        $company = $this->companyWithRate();
        $this->signIn($this->booker($company));

        $this->post(route('flights.search'), $this->searchForm());
        $searched = $this->getJson(route('flights.search.supplier', ['search' => session(FlightSearchStore::SESSION_ID), 'supplier' => 'skylink']))
            ->json('flights')[0];

        $selected = $this->select($searched);
        $this->assertEquals($searched['price'], $selected['price']);

        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'))->assertRedirect('https://checkout.example.test/pay');

        $this->assertEquals($searched['price'], FlightBooking::sole()->payment_amount);
    }

    public function test_a_public_parallel_search_is_priced_at_the_public_rate(): void
    {
        config(['flights.parallel_search' => true]);
        $this->companyWithRate();
        $this->setRate(null, self::DEFAULT_RATE);

        $this->post(route('flights.search'), $this->searchForm());

        $searchId = session(FlightSearchStore::SESSION_ID);
        $this->assertNull(FlightSearch::find($searchId)->company_id);

        $flights = $this->getJson(route('flights.search.supplier', ['search' => $searchId, 'supplier' => 'skylink']))->json('flights');

        $this->assertNotEquals(self::COMPANY_RATE, $flights[0]['markupAmount']);
        $this->assertNotEquals(self::DEFAULT_RATE, $flights[0]['markupAmount']);
    }

    public function test_a_suspended_companys_parallel_search_stops_rather_than_falling_to_public_prices(): void
    {
        config(['flights.parallel_search' => true]);
        $company = $this->companyWithRate();
        $this->signIn($this->booker($company));
        $this->post(route('flights.search'), $this->searchForm());
        $searchId = session(FlightSearchStore::SESSION_ID);

        $company->update(['status' => 'suspended']);

        $this->getJson(route('flights.search.supplier', ['search' => $searchId, 'supplier' => 'skylink']))
            ->assertNotFound()->assertJson(['status' => 'expired']);
    }

    public function test_the_company_rate_survives_the_seerbit_return_trip(): void
    {
        $company = $this->companyWithRate();
        $this->signIn($this->booker($company));

        $searched = $this->searchSequentially();
        $this->select($searched);
        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'));

        $booking = FlightBooking::sole();
        $reference = $booking->payment_reference;

        // Re-pricing before reserve must read the booking's company. On the
        // public rate this fare would look dearer and the reservation would fail.
        $this->signOut();
        $this->get(route('payments.seerbit.callback', ['paymentReference' => $reference]))->assertRedirect();

        $booking->refresh();
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertEquals($searched['price'], $booking->total_price);
    }

    // Public use after a corporate session, in the same browser

    public function test_signing_out_throws_away_corporate_results_and_checkout(): void
    {
        $this->signIn($this->booker($this->companyWithRate()));
        $searched = $this->searchSequentially();
        $this->select($searched);
        $this->assertNotNull(session('flightResultsStore'));
        $this->assertNotNull(session('bookingFlight'));

        $this->signOut();
        $this->get(route('air'));

        $this->assertNull(session('flightResultsStore'));
        $this->assertNull(session('bookingFlight'));
    }

    public function test_after_signing_out_a_search_in_the_same_browser_is_public(): void
    {
        $this->signIn($this->booker($this->companyWithRate()));
        $this->assertSame(self::COMPANY_RATE, $this->searchSequentially()['markupAmount']);

        $this->signOut();

        $this->assertNotEquals(self::COMPANY_RATE, $this->searchSequentially()['markupAmount']);
    }

    public function test_after_signing_out_a_booking_in_the_same_browser_has_no_company(): void
    {
        $this->signIn($this->booker($this->companyWithRate()));
        $this->searchSequentially();
        $this->signOut();

        $this->select($this->searchSequentially());
        $this->passengerDetails();
        $this->post(route('flights.payment.gateway.process'));

        $booking = FlightBooking::sole();
        $this->assertNull($booking->company_id);
        $this->assertNull($booking->company_user_id);
        $this->assertNotEquals(self::COMPANY_RATE, (float) $booking->markup_amount);
    }

    public function test_signing_in_throws_away_public_results_and_checkout(): void
    {
        $public = $this->searchSequentially();
        $this->select($public);
        $this->assertNotEquals(self::COMPANY_RATE, session('bookingFlight')['markupAmount']);

        $this->signIn($this->booker($this->companyWithRate()));
        $this->get(route('air'));

        $this->assertNull(session('flightResultsStore'));
        $this->assertNull(session('bookingFlight'));
    }

    public function test_a_company_cannot_choose_its_rate_from_the_browser(): void
    {
        $this->companyWithRate();
        $other = $this->company('RC200');
        $this->signIn($this->booker($other));

        $this->post(route('flights.search'), $this->searchForm() + ['company_id' => Company::first()->id]);
        $this->get(route('flights.search.run'));

        $this->assertNotEquals(self::COMPANY_RATE, session('flightResultsStore')[0]['markupAmount']);
    }

    // The admin screen

    /** The form's data: every rate blank except economy from Nigeria. */
    private function rates(?float $amount): array
    {
        $rates = collect(FlightMarkup::publicCharges())
            ->map(fn (array $cabins): array => array_fill_keys(array_keys($cabins), null))
            ->all();
        $rates['from_nigeria']['economy'] = $amount;

        return $rates;
    }

    public function test_the_ceo_sets_a_companys_rates_and_blank_falls_back(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = $this->company();

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('rates', ['rates' => $this->rates(12000)])
            ->assertHasNoActionErrors()
            ->assertNotified('Rates saved');

        $this->assertSame(1, CompanyServiceCharge::where('company_id', $company->id)->count());
        $this->assertSame(12000.0, CompanyServiceCharge::keyedFor($company->id)['from_nigeria.economy']);
        $this->assertSame(1, ActivityLog::where('action', 'rates')->where('subject_id', $company->id)->count());

        // Blanking it removes the row, so it falls back again.
        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('rates', ['rates' => $this->rates(null)]);

        $this->assertSame(0, CompanyServiceCharge::where('company_id', $company->id)->count());
    }

    public function test_a_rate_above_the_public_one_saves_with_a_warning(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = $this->company();

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('rates', ['rates' => $this->rates(99999)])
            ->assertHasNoActionErrors()
            ->assertNotified('Rates saved, but 1 is above the public rate');

        $this->assertSame(99999.0, CompanyServiceCharge::keyedFor($company->id)['from_nigeria.economy']);
    }

    public function test_a_negative_rate_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = $this->company();

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('rates', ['rates' => $this->rates(-1)])
            ->assertHasActionErrors();

        $this->assertSame(0, CompanyServiceCharge::count());
    }

    public function test_the_ceo_sets_default_rates_and_it_is_logged(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(DefaultCorporateRates::class)
            ->fillForm(['rates' => $this->rates(15000)])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(15000.0, CompanyServiceCharge::keyedFor(null)['from_nigeria.economy']);

        $log = ActivityLog::where('action', 'corporate.default_rates_updated')->sole();
        $this->assertSame(15000, (int) $log->properties['after']['from_nigeria']['economy']);
    }

    public function test_other_staff_cannot_set_rates(): void
    {
        $staff = User::factory()->create(['department_id' => Department::create(['name' => 'Fin', 'slug' => 'finance-test'])->id]);
        $this->actingAs($staff);
        $company = $this->company();

        Livewire::test(ViewCompany::class, ['record' => $company->id])->assertActionHidden('rates');

        $this->get(DefaultCorporateRates::getUrl())->assertForbidden();
    }
}
