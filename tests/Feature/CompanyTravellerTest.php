<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Livewire\Pages\FlightBooking as FlightBookingPage;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyTraveller;
use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\User;
use App\Services\Corporate\CorporateContext;
use App\Services\DurableMailService;
use App\Services\Flights\FlightSupplierControl;
use App\Services\ItineraryPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyTravellerTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-42';

    private const PASSPORT = 'A98765432';

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
        $control->disable('travelnext', 'Tests use SkyLink', null);

        Mail::fake();

        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($url, '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]]),
                str_contains($url, '/payments/query/') => Http::response(['data' => ['payments' => ['gatewayCode' => 'S20', 'gatewayMessage' => 'Pending']]]),
                str_ends_with($url, '/api/login') => Http::response(['data' => ['access_token' => 'test-token', 'expires_in' => 900]]),
                str_ends_with($url, 'flights/pricing') => Http::response(['success' => true, 'data' => [
                    'booking_token' => 'btk_repriced', 'verified' => true, 'original_price' => 900000, 'verified_price' => 900000, 'currency' => 'NGN',
                ]]),
                str_ends_with($url, 'flights/reserve') => Http::response(['success' => true, 'data' => [
                    'pnr' => 'SKY-PNR-9', 'status' => 'confirmed', 'ticket_deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
                ]]),
                default => Http::response([]),
            };
        });
    }

    // Fixtures

    private function company(string $cac = 'RC100'): Company
    {
        return Company::create([
            'name' => 'Acme '.$cac, 'cac_number' => $cac, 'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test', 'status' => 'active',
        ]);
    }

    private function member(Company $company, string $role = 'booker'): CompanyUser
    {
        return $company->users()->create([
            'name' => ucfirst($role), 'email' => $role.uniqid().'@acme.test', 'password' => self::PASSWORD, 'role' => $role,
        ]);
    }

    private function signIn(CompanyUser $user): void
    {
        $this->post(route('business.login.store'), ['email' => $user->email, 'password' => self::PASSWORD]);

        if ($user->needsLoginCode()) {
            Mail::assertSent(\App\Mail\CompanyLoginCodeMail::class, function ($mail) use ($user) {
                if ($mail->hasTo($user->email)) {
                    $this->post(route('business.login.code.verify'), ['code' => $mail->code]);
                }

                return true;
            });
        }

        // The first request after signing in clears any earlier checkout.
        $this->get(route('business.dashboard'))->assertOk();
    }

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Mrs',
            'first_name' => 'Ngozi',
            'last_name' => 'Eze',
            'gender' => 'F',
            'dob' => '1985-03-14',
            'nationality' => 'NG',
            'email' => 'ngozi@acme.test',
            'frequent_flyer_number' => 'FF12345',
            'passport_no' => self::PASSPORT,
            'passport_issue_country' => 'NG',
            'passport_issue_date' => now()->subYears(2)->toDateString(),
            'passport_exp' => now()->addYears(5)->toDateString(),
        ], $overrides);
    }

    private function traveller(Company $company, array $overrides = []): CompanyTraveller
    {
        $data = $this->formData($overrides);

        return app(\App\Services\Corporate\CompanyTravellers::class)->create($this->member($company), $data);
    }

    private function flight(): array
    {
        return [
            'source' => 'skylink', 'fareSourceCode' => 'btk_1', 'fareType' => 'Public', 'price' => 930000.0,
            'supplierPrice' => 900000.0, 'markupAmount' => 30000.0, 'currency' => 'NGN', 'airline' => 'Royal Air Maroc',
            'cabin' => 'Economy', 'isPassportMandatory' => true,
            'fareBreakdown' => [['qty' => 1, 'currency' => 'NGN', 'paxType' => 'ADT']],
            'segments' => [[
                'from' => 'LOS', 'to' => 'DXB', 'fromCountry' => 'Nigeria', 'toCountry' => 'UAE',
                'departDT' => now()->addDays(40)->toIso8601String(), 'arriveDT' => now()->addDays(41)->toIso8601String(),
                'flightNo' => 'AT576', 'airline' => 'Royal Air Maroc', 'cabin' => 'Economy',
            ]],
        ];
    }

    private function atCheckout(): void
    {
        session([
            'bookingFlight' => $this->flight(),
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
            'bookingSessionId' => '',
        ]);
    }

    private function bookPayload(array $passenger = [], array $extra = []): array
    {
        return array_merge([
            'fare_source_code' => 'btk_1',
            'contact' => ['email' => 'booker@acme.test', 'phone' => '8000000000', 'area_code' => '080', 'country_code' => '234'],
            'passengers' => [array_merge([
                'type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze',
                'gender' => 'F', 'dob' => '1985-03-14', 'nationality' => 'NG',
            ], $passenger)],
        ], $extra);
    }

    // The profile

    public function test_a_booker_saves_a_traveller_and_the_passport_is_encrypted(): void
    {
        $company = $this->company();
        $this->signIn($this->member($company));

        $this->post(route('business.travellers.store'), $this->formData())->assertRedirect(route('business.travellers.index'));

        $traveller = CompanyTraveller::sole();
        $this->assertSame($company->id, $traveller->company_id);
        $this->assertSame('5432', $traveller->passport_last4);
        $this->assertSame(self::PASSPORT, $traveller->passport['passport_no']);

        $row = (array) DB::table('company_travellers')->first();
        foreach (['passport', 'dob', 'frequent_flyer_number'] as $column) {
            $this->assertStringNotContainsString(self::PASSPORT, (string) $row[$column]);
            $this->assertStringNotContainsString('1985-03-14', (string) $row[$column]);
            $this->assertStringNotContainsString('FF12345', (string) $row[$column]);
        }
    }

    public function test_the_list_and_the_edit_form_only_ever_show_the_last_digits(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));

        $this->get(route('business.travellers.index'))->assertOk()
            ->assertSee('ending 5432')
            ->assertDontSee(self::PASSPORT);

        $this->get(route('business.travellers.edit', $traveller->id))->assertOk()
            ->assertSee('ending 5432')
            ->assertDontSee(self::PASSPORT);
    }

    public function test_the_model_keeps_the_passport_out_of_json(): void
    {
        $json = $this->traveller($this->company())->toJson();

        $this->assertStringNotContainsString(self::PASSPORT, $json);
        $this->assertStringNotContainsString('1985-03-14', $json);
    }

    public function test_a_blank_number_keeps_the_saved_one_and_a_new_one_replaces_it(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));

        $this->put(route('business.travellers.update', $traveller->id), $this->formData(['passport_no' => '', 'phone' => '0801']))
            ->assertRedirect(route('business.travellers.index'));
        $this->assertSame(self::PASSPORT, $traveller->fresh()->passport['passport_no']);

        $this->put(route('business.travellers.update', $traveller->id), $this->formData(['passport_no' => 'B11112222']));
        $this->assertSame('B11112222', $traveller->fresh()->passport['passport_no']);
        $this->assertSame('2222', $traveller->fresh()->passport_last4);
    }

    public function test_changes_are_logged_by_field_name_and_never_by_value(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $user = $this->member($company);
        $this->signIn($user);

        $this->put(route('business.travellers.update', $traveller->id), $this->formData(['passport_no' => 'B11112222', 'first_name' => 'Ngozika']));

        $log = ActivityLog::where('action', 'company_traveller_updated')->sole();
        $this->assertSame($user->id, $log->company_user_id);
        $this->assertContains('passport_no', $log->properties['fields_changed']);
        $this->assertContains('first_name', $log->properties['fields_changed']);
        $this->assertStringNotContainsString('B11112222', json_encode($log->properties).$log->description);
        $this->assertStringNotContainsString(self::PASSPORT, json_encode($log->properties).$log->description);
    }

    public function test_only_an_admin_can_delete_and_it_is_logged(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $booker = $this->member($company);
        $this->signIn($booker);

        $this->delete(route('business.travellers.destroy', $traveller->id))->assertForbidden();
        $this->get(route('business.travellers.index'))->assertDontSee('Delete this saved traveller');
        $this->assertNotNull($traveller->fresh());

        $this->post(route('business.logout'));
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $admin = $this->member($company, 'admin');
        $this->signIn($admin);

        $this->delete(route('business.travellers.destroy', $traveller->id))->assertRedirect();

        $this->assertNull($traveller->fresh());
        $this->assertSame(1, ActivityLog::where('company_user_id', $admin->id)->where('action', 'company_traveller_deleted')->count());
    }

    public function test_another_companys_traveller_cannot_be_seen_changed_or_deleted(): void
    {
        $theirs = $this->traveller($this->company('RC200'), ['first_name' => 'Stranger']);
        $mine = $this->company();
        $this->signIn($this->member($mine, 'admin'));

        $this->get(route('business.travellers.index'))->assertOk()->assertDontSee('Stranger');
        $this->get(route('business.travellers.edit', $theirs->id))->assertNotFound();
        $this->put(route('business.travellers.update', $theirs->id), $this->formData())->assertNotFound();
        $this->delete(route('business.travellers.destroy', $theirs->id))->assertNotFound();

        $this->assertSame('Stranger', $theirs->fresh()->first_name);
    }

    public function test_a_guest_goes_to_the_login(): void
    {
        $this->get(route('business.travellers.index'))->assertRedirect(route('business.login'));
    }

    public function test_bad_details_are_refused(): void
    {
        $this->signIn($this->member($this->company()));

        $this->post(route('business.travellers.store'), $this->formData(['passport_no' => 'A1<script>', 'dob' => 'tomorrow']))
            ->assertSessionHasErrors(['passport_no', 'dob']);

        $this->assertSame(0, CompanyTraveller::count());
    }

    public function test_the_ceo_can_delete_a_suspended_companys_travellers(): void
    {
        $company = $this->company();
        $this->traveller($company);
        $company->update(['status' => 'suspended']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ViewCompany::class, ['record' => $company->id])->callAction('deleteTravellers');

        $this->assertSame(0, CompanyTraveller::count());
        $this->assertSame(1, ActivityLog::where('action', 'corporate.travellers_deleted')->count());
    }

    public function test_an_active_companys_travellers_cannot_be_bulk_deleted(): void
    {
        $company = $this->company();
        $this->traveller($company);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ViewCompany::class, ['record' => $company->id])->assertActionHidden('deleteTravellers');
    }

    // Choosing one at checkout

    private function checkoutComponent(CompanyUser $user)
    {
        $this->signIn($user);
        $this->atCheckout();

        return $this->testComponent($user);
    }

    /**
     * Livewire's test helper builds its own request with no session, so it gets
     * the signed-in person directly. test_the_real_page_shows_the_picker covers
     * the real session.
     */
    private function testComponent(CompanyUser $user)
    {
        $this->app->instance(CorporateContext::class, new class($user) extends CorporateContext
        {
            public function __construct(private CompanyUser $signedIn) {}

            public function user(): ?CompanyUser
            {
                return $this->signedIn;
            }
        });

        return Livewire::test(FlightBookingPage::class);
    }

    public function test_choosing_a_traveller_fills_the_form_but_never_the_passport(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);

        $component = $this->checkoutComponent($this->member($company))
            ->call('useSavedTraveller', 0, $traveller->id)
            ->assertSet('passengers.0.first_name', 'Ngozi')
            ->assertSet('passengers.0.dob', '1985-03-14')
            ->assertSet('passengers.0.saved_traveller_id', $traveller->id)
            ->assertSet('passengers.0.passport_masked', 'ending 5432')
            ->assertSet('passengers.0.passport_no', '')
            ->assertSee('ending 5432');

        $this->assertStringNotContainsString(self::PASSPORT, $component->html());
        $this->assertStringNotContainsString(self::PASSPORT, json_encode($component->snapshot));
    }

    public function test_the_real_page_shows_the_picker_with_a_real_session(): void
    {
        $company = $this->company();
        $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->get(route('flights.booking'))->assertOk()
            ->assertSee('Saved traveller')
            ->assertSee('Ngozi Eze (passport ending 5432)')
            ->assertSee('Cost code')
            ->assertDontSee(self::PASSPORT);
    }

    public function test_the_picker_only_lists_this_company(): void
    {
        $company = $this->company();
        $this->traveller($company);
        $this->traveller($this->company('RC200'), ['first_name' => 'Stranger']);

        $this->checkoutComponent($this->member($company))->assertSee('Ngozi Eze')->assertDontSee('Stranger');
    }

    public function test_choosing_another_companys_traveller_does_nothing(): void
    {
        $theirs = $this->traveller($this->company('RC200'), ['first_name' => 'Stranger']);

        $this->checkoutComponent($this->member($this->company()))
            ->call('useSavedTraveller', 0, $theirs->id)
            ->assertSet('passengers.0.first_name', '')
            ->assertSet('passengers.0.saved_traveller_id', null);
    }

    public function test_the_public_form_has_no_picker_or_cost_code(): void
    {
        $this->traveller($this->company());
        $this->atCheckout();

        Livewire::test(FlightBookingPage::class)
            ->assertDontSee('Saved traveller')
            ->assertDontSee('Cost code')
            ->assertDontSee('Ngozi Eze');
    }

    public function test_an_expired_or_nearly_expired_passport_warns_at_checkout(): void
    {
        $company = $this->company();
        $soon = $this->traveller($company, ['first_name' => 'Soon', 'passport_exp' => now()->addDays(40 + 30)->toDateString()]);
        $expired = $this->traveller($company, ['first_name' => 'Gone', 'passport_exp' => now()->addDays(10)->toDateString()]);
        $fine = $this->traveller($company, ['first_name' => 'Fine']);

        $component = $this->checkoutComponent($this->member($company));

        $component->call('useSavedTraveller', 0, $soon->id)->assertSee('expires within 6 months');
        $component->call('useSavedTraveller', 0, $expired->id)->assertSee('will have expired by the travel date');
        $component->call('useSavedTraveller', 0, $fine->id)->assertDontSee('expires within 6 months')->assertDontSee('will have expired');
    }

    public function test_coming_back_to_the_form_does_not_bring_the_passport_with_it(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $user = $this->member($company);
        $this->signIn($user);
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id]))->assertRedirect();
        $this->assertSame(self::PASSPORT, session('bookingPassengers')[0]['passport_no']);

        $component = $this->testComponent($user)->assertSet('passengers.0.passport_no', '')->assertSee('ending 5432');

        $this->assertStringNotContainsString(self::PASSPORT, $component->html());
        $this->assertStringNotContainsString(self::PASSPORT, json_encode($component->snapshot));
    }

    public function test_a_saved_passport_shows_clearly_instead_of_empty_boxes(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);

        $component = $this->checkoutComponent($this->member($company))
            ->assertSeeHtml('placeholder="A12345678"')
            ->call('useSavedTraveller', 0, $traveller->id)
            ->assertSee('Saved passport will be used')
            ->assertSee('Ending 5432')
            ->assertSee('Nigerian')
            ->assertSee('Use a different passport for this trip')
            ->assertSeeHtml('placeholder="BA12345678"')
            ->assertDontSeeHtml('placeholder="A12345678"');

        $this->assertStringNotContainsString(self::PASSPORT, $component->html());
    }

    public function test_a_different_passport_can_be_entered_and_the_saved_one_chosen_again(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);

        $this->checkoutComponent($this->member($company))
            ->call('useSavedTraveller', 0, $traveller->id)
            ->call('useDifferentPassport', 0, true)
            ->assertSeeHtml('placeholder="A12345678"')
            ->assertSee('Use the saved passport (ending 5432)')
            ->assertSet('passengers.0.saved_traveller_id', $traveller->id)
            ->set('passengers.0.passport_no', 'C55556666')
            ->call('useDifferentPassport', 0, false)
            ->assertSet('passengers.0.passport_no', '')
            ->assertSee('Saved passport will be used');
    }

    public function test_a_traveller_with_no_saved_passport_gets_the_normal_boxes(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company, ['passport_no' => '']);

        $this->checkoutComponent($this->member($company))
            ->call('useSavedTraveller', 0, $traveller->id)
            ->assertSee('No passport is saved')
            ->assertDontSee('Saved passport will be used')
            ->assertSeeHtml('placeholder="A12345678"');
    }

    public function test_a_passport_typed_for_this_trip_survives_coming_back_to_the_form(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $user = $this->member($company);
        $this->signIn($user);
        $this->atCheckout();

        $typed = [
            'saved_traveller_id' => $traveller->id,
            'passport_no' => 'C55556666',
            'passport_issue_country' => 'GB',
            'passport_issue_date' => now()->subYear()->toDateString(),
            'passport_exp' => now()->addYears(4)->toDateString(),
        ];
        $this->post(route('flights.book'), $this->bookPayload($typed))->assertSessionHasNoErrors();

        $component = $this->testComponent($user)
            ->assertSet('passengers.0.passport_no', 'C55556666')
            ->assertSet('passengers.0.use_different_passport', true)
            ->assertDontSee('Saved passport will be used');

        // And the saved number still isn't sent to the browser.
        $this->assertStringNotContainsString(self::PASSPORT, $component->html());
    }

    public function test_a_passport_problem_shows_even_when_the_boxes_are_hidden(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company, ['passport_exp' => now()->addDays(10)->toDateString()]);

        $this->checkoutComponent($this->member($company))
            ->call('useSavedTraveller', 0, $traveller->id)
            ->assertSee('will have expired by the travel date')
            ->assertSee('Use a different passport for this trip');
    }

    public function test_the_form_strips_a_saved_passport_even_with_no_company_to_check_against(): void
    {
        $this->atCheckout();
        session(['bookingPassengers' => [[
            'type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'F',
            'dob' => '1985-03-14', 'nationality' => 'NG', 'passport_no' => self::PASSPORT, 'saved_traveller_id' => 999,
        ]]]);

        $component = Livewire::test(FlightBookingPage::class)
            ->assertSet('passengers.0.passport_no', '')
            ->assertSet('passengers.0.saved_traveller_id', null);

        $this->assertStringNotContainsString(self::PASSPORT, json_encode($component->snapshot));
    }

    // Joining the booking on the server

    public function test_the_server_adds_the_passport_and_the_supplier_receives_it(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id]))
            ->assertRedirect(route('flights.payment.gateway'));

        $passenger = session('bookingPassengers')[0];
        $this->assertSame(self::PASSPORT, $passenger['passport_no']);
        $this->assertSame('NG', $passenger['passport_issue_country']);
        $this->assertSame($traveller->id, (int) $passenger['saved_traveller_id']);

        // Pay from a funded wallet, so the reservation goes out.
        app(\App\Services\Corporate\CompanyWallet::class)->post($company, 'top_up', 2000000, 'seed');
        $this->post(route('flights.payment.wallet'))->assertRedirect(route('flights.confirmation'));

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'flights/reserve')
            && str_contains(json_encode($request->data()), self::PASSPORT));

        $this->assertSame(self::PASSPORT, FlightBooking::sole()->passengers_snapshot[0]['passport_no']);
    }

    public function test_the_saved_passport_meets_a_fare_that_requires_one(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id]))->assertSessionHasNoErrors();
        $this->post(route('flights.book'), $this->bookPayload())->assertSessionHasErrors('passengers.0.passport_no');
    }

    public function test_another_companys_traveller_id_is_refused_at_booking(): void
    {
        $theirs = $this->traveller($this->company('RC200'));
        $this->signIn($this->member($this->company()));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $theirs->id]))
            ->assertSessionHasErrors('passengers.0.saved_traveller_id');

        $this->assertNull(session('bookingPassengers'));
    }

    public function test_the_public_cannot_use_a_traveller_id_or_a_cost_code(): void
    {
        $traveller = $this->traveller($this->company());
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id], ['cost_code' => 'X']))
            ->assertSessionHasErrors('passengers.0.passport_no');

        $this->assertNull(session('bookingPassengers'));
        $this->assertNull(session('bookingCostCode'));
    }

    public function test_a_passport_typed_at_checkout_is_used_instead_and_the_profile_is_untouched(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload([
            'saved_traveller_id' => $traveller->id,
            'passport_no' => 'C55556666',
            'passport_issue_country' => 'GB',
            'passport_issue_date' => now()->subYear()->toDateString(),
            'passport_exp' => now()->addYears(4)->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertSame('C55556666', session('bookingPassengers')[0]['passport_no']);
        $this->assertSame('GB', session('bookingPassengers')[0]['passport_issue_country']);
        $this->assertSame(self::PASSPORT, $traveller->fresh()->passport['passport_no']);
    }

    public function test_a_passport_expired_by_the_travel_date_is_refused_at_booking(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company, ['passport_exp' => now()->addDays(10)->toDateString()]);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id]))
            ->assertSessionHasErrors('passengers.0.passport_exp');
    }

    // Save for next time

    private function typedPassenger(array $overrides = []): array
    {
        return array_merge([
            'passport_no' => self::PASSPORT, 'passport_issue_country' => 'NG',
            'passport_issue_date' => now()->subYear()->toDateString(), 'passport_exp' => now()->addYears(4)->toDateString(),
        ], $overrides);
    }

    public function test_ticking_save_creates_a_profile_and_links_it(): void
    {
        $company = $this->company();
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload($this->typedPassenger(['save_traveller' => '1'])))->assertSessionHasNoErrors();

        $traveller = CompanyTraveller::sole();
        $this->assertSame(self::PASSPORT, $traveller->passport['passport_no']);
        $this->assertSame($traveller->id, session('bookingPassengers')[0]['saved_traveller_id']);
        $this->assertArrayNotHasKey('save_traveller', session('bookingPassengers')[0]);
    }

    public function test_a_guest_is_not_saved(): void
    {
        $this->signIn($this->member($this->company()));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload($this->typedPassenger(['save_traveller' => ''])))->assertSessionHasNoErrors();

        $this->assertSame(0, CompanyTraveller::count());
    }

    public function test_saving_the_same_person_twice_links_to_the_first(): void
    {
        $company = $this->company();
        $existing = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload($this->typedPassenger(['save_traveller' => '1', 'passport_no' => 'C55556666'])))->assertSessionHasNoErrors();

        $this->assertSame(1, CompanyTraveller::count());
        $this->assertSame($existing->id, session('bookingPassengers')[0]['saved_traveller_id']);
        $this->assertSame(self::PASSPORT, $existing->fresh()->passport['passport_no']);
        $this->assertSame('C55556666', session('bookingPassengers')[0]['passport_no']);
    }

    public function test_a_refused_booking_saves_nobody(): void
    {
        $this->signIn($this->member($this->company()));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['save_traveller' => '1']))
            ->assertSessionHasErrors('passengers.0.passport_no');

        $this->assertSame(0, CompanyTraveller::count());
    }

    public function test_the_public_cannot_save_a_traveller(): void
    {
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload($this->typedPassenger(['save_traveller' => '1'])))->assertSessionHasNoErrors();

        $this->assertSame(0, CompanyTraveller::count());
        $this->assertArrayNotHasKey('save_traveller', session('bookingPassengers')[0]);
    }

    // Every other place a passport could show

    private function paidBookingWithSavedTraveller(Company $company, array $travellerOverrides = []): FlightBooking
    {
        $traveller = $this->traveller($company, $travellerOverrides);

        return FlightBooking::create([
            'company_id' => $company->id, 'booking_ref' => 'TW-T1', 'supplier' => 'skylink', 'unique_id' => 'PNR1',
            'fare_source_code' => 'x', 'route' => 'LOS - DXB', 'booking_status' => 'confirmed', 'payment_status' => 'paid',
            'currency' => 'NGN', 'total_price' => 930000, 'contact_email' => 'booker@acme.test',
            'flight_snapshot' => $this->flight(),
            'passengers_snapshot' => [[
                'type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'F',
                'dob' => '1985-03-14', 'nationality' => 'NG', 'passport_no' => self::PASSPORT, 'saved_traveller_id' => $traveller->id,
            ]],
        ]);
    }

    public function test_the_confirmation_page_masks_a_saved_passport(): void
    {
        $company = $this->company();
        $booking = $this->paidBookingWithSavedTraveller($company);
        $this->signIn($this->member($company));
        session(['flightBookingDbId' => $booking->id, 'bookingRef' => $booking->booking_ref, 'bookingUniqueId' => 'PNR1', 'bookingPassengers' => $booking->passengers_snapshot, 'bookingFlight' => ['flight' => $booking->flight_snapshot], 'paymentMethod' => 'gateway']);

        $this->get(route('flights.confirmation'))->assertOk()
            ->assertSee('ending 5432')
            ->assertDontSee(self::PASSPORT);
    }

    public function test_the_ticket_document_never_prints_a_passport_number(): void
    {
        $service = app(ItineraryPdfService::class);
        $html = view('pdf.itinerary', $service->buildViewData($this->paidBookingWithSavedTraveller($this->company())))->render();

        $this->assertStringContainsString('NGOZI', strtoupper($html));
        $this->assertStringNotContainsString(self::PASSPORT, $html);
    }

    public function test_the_traveller_gets_a_copy_of_their_e_ticket_but_not_other_emails(): void
    {
        $company = $this->company();
        $booking = $this->paidBookingWithSavedTraveller($company);

        app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_ETICKET, 'booker@acme.test', $booking);
        $this->assertContains('ngozi@acme.test', NotificationOutbox::where('kind', DurableMailService::FLIGHT_ETICKET)->sole()->cc);

        app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_RECEIPT, 'booker@acme.test', $booking);
        $this->assertNotContains('ngozi@acme.test', NotificationOutbox::where('kind', DurableMailService::FLIGHT_RECEIPT)->sole()->cc);
    }

    public function test_no_copy_goes_out_for_a_traveller_of_another_company(): void
    {
        $company = $this->company();
        $booking = $this->paidBookingWithSavedTraveller($company);
        $stranger = $this->traveller($this->company('RC200'), ['email' => 'stranger@other.test']);
        $booking->update(['passengers_snapshot' => [['type' => 'ADT', 'saved_traveller_id' => $stranger->id]]]);

        app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_ETICKET, 'booker@acme.test', $booking);

        $this->assertNotContains('stranger@other.test', NotificationOutbox::sole()->cc);
    }

    // Cost codes

    public function test_a_cost_code_goes_from_checkout_to_the_booking_and_can_be_searched(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id], ['cost_code' => '  PROJ-77  ']));
        app(\App\Services\Corporate\CompanyWallet::class)->post($company, 'top_up', 2000000, 'seed');
        $this->post(route('flights.payment.wallet'));

        $booking = FlightBooking::sole();
        $this->assertSame('PROJ-77', $booking->cost_code);

        $this->get(route('business.bookings.index', ['q' => 'PROJ-77']))->assertOk()->assertSee($booking->booking_ref)->assertSee('PROJ-77');
        $this->get(route('business.bookings.index', ['q' => 'NOPE']))->assertDontSee($booking->booking_ref);
    }

    public function test_the_cost_code_is_optional(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id], ['cost_code' => '']))->assertSessionHasNoErrors();

        $this->assertNull(session('bookingCostCode'));
    }

    public function test_a_new_search_clears_the_cost_code(): void
    {
        $company = $this->company();
        $traveller = $this->traveller($company);
        $this->signIn($this->member($company));
        $this->atCheckout();
        $this->post(route('flights.book'), $this->bookPayload(['saved_traveller_id' => $traveller->id], ['cost_code' => 'PROJ-77']));

        $this->post(route('flights.search'), []);

        $this->assertNull(session('bookingCostCode'));
    }
}
