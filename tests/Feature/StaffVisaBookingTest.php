<?php

namespace Tests\Feature;

use App\Filament\Pages\BookVisa;
use App\Filament\Pages\NewBooking;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\Country;
use App\Models\Department;
use App\Models\ExchangeRate;
use App\Models\StaffBooking;
use App\Models\User;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Services\VisaQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StaffVisaBookingTest extends TestCase
{
    use RefreshDatabase;

    private Country $ghana;

    private Country $canada;

    private VisaProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['visa.enabled' => true]);

        $this->ghana = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $this->canada = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $this->product = VisaProduct::query()->create(['destination_country_id' => $this->canada->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 1]);
        $this->product->processingOptions()->create(['name' => 'Standard', 'minimum_business_days' => 5, 'maximum_business_days' => 10]);
        // ₦100,000 a traveller plus ₦500 once: ₦200,500 for two adults
        $this->product->fees()->create(['name' => 'Adult visa fee', 'fee_type' => 'government', 'traveler_type' => 'adult', 'calculation_basis' => 'per_traveler', 'currency' => 'USD', 'amount' => 100, 'payee' => 'travelwheel', 'pay_online' => true]);
        $this->product->fees()->create(['name' => 'Service fee', 'fee_type' => 'service', 'traveler_type' => 'all', 'calculation_basis' => 'per_application', 'currency' => 'NGN', 'amount' => 500, 'payee' => 'travelwheel', 'pay_online' => true]);
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1000]);
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function searched(bool $sendLink = false)
    {
        return Livewire::test(BookVisa::class)
            ->set('nationality', (string) $this->ghana->id)
            ->set('destination', 'country:'.$this->canada->id)
            ->set('arrivalDate', now()->addMonth()->toDateString())
            ->set('departureDate', now()->addMonths(2)->toDateString())
            ->call('changeCount', 'adults', 1)
            ->set('email', 'customer@example.com')
            ->set('sendLink', $sendLink)
            ->call('search');
    }

    private function started(bool $sendLink = false): VisaApplication
    {
        $this->searched($sendLink)->call('start', $this->product->id);

        return VisaApplication::query()->latest('id')->firstOrFail();
    }

    /** What the customer or staff fill in on the website before the quote. */
    private function finish(VisaApplication $application): VisaApplication
    {
        $application->update(['completed_step' => 4, 'current_step' => 5, 'declaration_accepted' => true, 'declaration_accepted_at' => now()]);
        $application->travelers->each(fn ($traveler, $i) => $traveler->update(['first_name' => 'Ama'.($i ?: ''), 'last_name' => 'Mensah']));

        return $application->fresh();
    }

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookVisa::getUrl())->assertOk();
        $this->actingAs($this->staff('customer-support'))->get(BookVisa::getUrl())->assertForbidden();

        config(['visa.enabled' => false]);
        $this->actingAs($this->staff())->get(BookVisa::getUrl())->assertForbidden();
    }

    public function test_the_search_offers_the_visas_this_passport_can_get_with_their_price(): void
    {
        $this->actingAs($this->staff());

        $this->searched()
            ->assertHasNoErrors()
            ->assertSee('Visitor visa')
            ->assertSee('₦200,500.00');
    }

    public function test_staff_start_an_application_and_fill_it_in_on_the_website(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        $page = $this->searched()->call('start', $this->product->id);

        $application = VisaApplication::query()->sole();
        $page->assertRedirect(route('visa.application', $application));
        $this->assertSame('draft', $application->status);
        $this->assertSame('customer@example.com', $application->contact_email);
        $this->assertSame(2, $application->travelers()->count());
        $this->assertTrue(session("visa_application_access.{$application->reference}"));
        $this->assertDatabaseHas('visa_application_status_history', ['visa_application_id' => $application->id, 'actor_type' => 'user', 'actor_id' => $staff->id]);

        $staffBooking = StaffBooking::query()->sole();
        $this->assertTrue($staffBooking->bookable->is($application));
        $this->assertSame('pending', $staffBooking->paymentState());
        $this->assertNull($staffBooking->total(), 'No price until the application is finished.');
        Mail::assertNothingSent();

        $this->get(route('visa.application', $application))->assertOk();
    }

    public function test_only_a_visa_the_search_offers_can_be_started(): void
    {
        $this->actingAs($this->staff());
        $this->product->eligibilityRules()->create(['rule_type' => 'exclude_country', 'country_id' => $this->ghana->id, 'is_active' => true]);

        $this->searched()->assertDontSee('Visitor visa')->call('start', $this->product->id);

        $this->assertSame(0, VisaApplication::query()->count());
        $this->assertSame(0, StaffBooking::query()->count());
    }

    public function test_the_customer_gets_a_link_that_opens_their_application(): void
    {
        $this->actingAs($this->staff());
        $application = $this->started(sendLink: true);
        $staffBooking = StaffBooking::query()->sole();

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));
        $this->assertNotNull($staffBooking->fresh()->link_sent_at);

        // The customer, in their own browser
        auth()->logout();
        session()->flush();
        $this->get($staffBooking->payUrl())->assertRedirect(route('visa.application', $application));
        $this->get(route('visa.application', $application))->assertOk();
    }

    public function test_finance_records_a_payment_once_the_application_is_finished(): void
    {
        $this->actingAs($this->staff());
        $application = $this->started();
        $staffBooking = StaffBooking::query()->sole();

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)->assertTableActionHidden('recordPayment', $staffBooking);

        app(VisaQuotationService::class)->create($this->finish($application));
        $this->assertSame(200500.0, $staffBooking->fresh()->total());

        Livewire::test(NewBooking::class)
            ->assertTableActionVisible('recordPayment', $staffBooking)
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'bank_transfer', 'reference' => 'TRF-1'])
            ->assertHasNoTableActionErrors();

        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $payment = $application->payments()->sole();
        $this->assertSame(['manual', 'paid', '200500.00'], [$payment->provider, $payment->status, $payment->verified_amount]);
        $this->assertSame('TRF-1', $payment->verification_response['reference']);
        $this->assertSame('paid', $staffBooking->fresh()->paymentState());
        $this->assertDatabaseHas('visa_notification_events', ['visa_application_id' => $application->id, 'event_type' => 'payment_confirmed']);
    }

    public function test_a_payment_for_an_application_reopened_before_payment_is_not_recorded(): void
    {
        $this->actingAs($this->staff());
        $application = $this->started();
        app(VisaQuotationService::class)->create($this->finish($application));
        $application->update(['completed_step' => 1]);
        $staffBooking = StaffBooking::query()->sole();

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)->callTableAction('recordPayment', $staffBooking, data: ['method' => 'cash', 'reference' => 'R-1']);

        $this->assertNull($staffBooking->fresh()->recorded_at);
        $this->assertSame(0, $application->payments()->count());
        $this->assertSame('awaiting_payment', $application->fresh()->status);
    }

    public function test_a_cancelled_application_closes_and_its_link_stops_working(): void
    {
        $this->actingAs($this->staff());
        $application = $this->started();
        app(VisaQuotationService::class)->create($this->finish($application));
        $staffBooking = StaffBooking::query()->sole();

        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $staffBooking);

        $application->refresh();
        $this->assertSame('expired', $application->status);
        $this->assertSame(0, $application->quotes()->where('status', 'active')->count());
        $this->assertSame('cancelled', $staffBooking->fresh()->paymentState());

        $this->get($staffBooking->payUrl())->assertOk()->assertSee('no longer available');
        $this->get(route('visa.application', $application))->assertForbidden();
    }
}
