<?php

namespace Tests\Feature;

use App\Filament\Pages\BookInsurance;
use App\Filament\Pages\NewBooking;
use App\Mail\InsurancePurchaseNotificationMail;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\Department;
use App\Models\InsurancePurchase;
use App\Models\InsuranceQuote;
use App\Models\StaffBooking;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\SeerbitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class StaffInsuranceBookingTest extends TestCase
{
    use RefreshDatabase;

    /** Sanlam's quote; with 7.5% VAT the customer pays ₦43,000. */
    private const QUOTE = 40000;

    private bool $sanlamIssues = true;

    private bool $sanlamDown = false;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.sanlam.base_url' => 'https://sanlam.test', 'services.sanlam.username' => 'u', 'services.sanlam.password' => 'p']);

        Http::fake(function (Request $request) {
            return match (true) {
                $this->sanlamDown => Http::response('down', 500),
                str_ends_with($request->url(), '/Travel/token') => Http::response(['access_token' => 'token']),
                str_ends_with($request->url(), '/Travel/api/Quote') => Http::response([
                    'QuoteRequestId' => 'QR-'.uniqid(), 'ProductVariantId' => 'PV1', 'DateOfBirth' => '02-Feb-1990', 'Email' => $request['Email'],
                    'Telephone' => $request['Telephone'], 'CoverBegins' => $request['CoverBegins'], 'CoverEnds' => $request['CoverEnds'],
                    'CountryId' => $request['CountryId'], 'PurposeOfTravel' => $request['PurposeOfTravel'], 'TravelPlanId' => $request['TravelPlanId'],
                    'BookingTypeId' => $request['BookingTypeId'], 'NoOfPeople' => $request['NoOfPeople'], 'NoOfChildren' => $request['NoOfChildren'],
                    'Amount' => self::QUOTE, 'quoteId' => 'Q1',
                ]),
                str_contains($request->url(), '/Travel/api/') => $this->sanlamIssues ? Http::response('COVER-123') : Http::response('Server error', 500),
            };
        });
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function adult(string $first, array $overrides = []): array
    {
        return array_merge([
            'firstname' => $first, 'middlename' => '', 'gender' => '2', 'title' => '22', 'dob' => '1990-02-02', 'email' => strtolower($first).'@example.com',
            'phone' => '08030000000', 'passport_no' => 'A1234567', 'nationality' => '162', 'nin' => '', 'occupation' => 'Engineer',
            'marital_status' => '2', 'medical' => '',
        ], $overrides);
    }

    private function quoted(int $children = 0)
    {
        $page = Livewire::test(BookInsurance::class);

        if ($children) {
            $page->call('setBookingType', 2);
            for ($i = 0; $i < $children; $i++) {
                $page->call('changeChildren', 1);
            }
        }

        return $page->set('country', '2')->set('purpose', 'Leisure')->set('leadDob', '1990-02-02')
            ->set('email', 'ada@example.com')->set('phone', '08030000000')
            ->set('beginDate', now()->addDays(10)->toDateString())->set('endDate', now()->addDays(20)->toDateString())
            ->call('getQuote')->assertHasNoErrors();
    }

    private function filled(int $children = 0)
    {
        $page = $this->quoted($children)
            ->set('lead', $this->adult('Ada'))
            ->set('home', ['surname' => 'Obi', 'state' => '25', 'address' => '1 Allen Avenue', 'zipcode' => '100001'])
            ->set('nok', ['fullname' => 'Ngozi Obi', 'address' => '1 Allen Avenue', 'phone' => '08040000000', 'relationship' => 'Sister']);

        if ($children) {
            $page->set('partner', $this->adult('Chidi', ['gender' => '1', 'title' => '20', 'passport_no' => '', 'marital_status' => '']));
            for ($i = 0; $i < $children; $i++) {
                $page->set("kids.$i", ['firstname' => 'Kid'.$i, 'middlename' => '', 'gender' => '1', 'title' => '18', 'dob' => '2018-05-05', 'passport_no' => '', 'nationality' => '162', 'nin' => '']);
            }
        }

        return $page;
    }

    private function book(int $children = 0, bool $sendLink = true): StaffBooking
    {
        $this->filled($children)->set('sendLink', $sendLink)->call('create')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

        return StaffBooking::sole();
    }

    private function pay(InsurancePurchase $purchase): void
    {
        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]));
        $this->get(route('air.insurance.callback', ['reference' => $purchase->trans_id]))->assertRedirect(route('air.insurance.success'));
    }

    private function policyRequests(): array
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'Booking'))->all();
    }

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookInsurance::getUrl())->assertOk();

        $this->actingAs($this->staff('customer-support'))->get(BookInsurance::getUrl())->assertForbidden();
    }

    public function test_staff_get_a_live_quote_for_the_destinations_plan(): void
    {
        $this->actingAs($this->staff());

        $this->quoted()->assertSee('40,000.00')->assertSee('43,000.00')->assertSee('Schengen');

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/Travel/api/Quote')
            && $request['CountryId'] === '2' && $request['TravelPlanId'] === 1 && $request['BookingTypeId'] === 1 && $request['NoOfChildren'] === 0
            && $request['CoverBegins'] === now()->addDays(10)->format('d-M-Y'));
        $this->assertSame(1, InsuranceQuote::count());
    }

    public function test_changing_the_trip_needs_a_new_quote(): void
    {
        $this->actingAs($this->staff());

        $this->quoted()->set('endDate', now()->addDays(30)->toDateString())
            ->assertSet('quoteId', null)
            ->assertDontSee('Price breakdown')
            ->call('create')
            ->assertNoRedirect();

        $this->assertSame(0, InsurancePurchase::count());
    }

    public function test_a_quote_sanlam_cannot_give_is_explained(): void
    {
        $this->actingAs($this->staff());
        $this->sanlamDown = true;

        Livewire::test(BookInsurance::class)
            ->set('country', '162')->set('purpose', 'Business')->set('leadDob', '1990-02-02')->set('email', 'ada@example.com')->set('phone', '0803')
            ->set('beginDate', now()->addDays(10)->toDateString())->set('endDate', now()->addDays(20)->toDateString())
            ->call('getQuote')
            ->assertSet('quoteId', null)
            ->assertSee('Sanlam could not give a quote');
    }

    public function test_staff_book_an_individual_policy_and_nothing_is_issued_before_payment(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        $purchase = $this->book()->bookable;

        $this->assertInstanceOf(InsurancePurchase::class, $purchase);
        $this->assertSame('Pending', $purchase->status);
        $this->assertEquals(self::QUOTE, (float) $purchase->c_amount);
        $this->assertEquals(43000, (float) $purchase->t_amount);
        $this->assertSame('Obi Ada', $purchase->full_name);
        $this->assertSame('A1234567', $purchase->booking_form['form']['passport_no']);
        $this->assertSame('awaiting_payment', WorkItem::where('subject_id', $purchase->id)->where('subject_type', $purchase->getMorphClass())->sole()->stage);

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(InsurancePurchaseNotificationMail::class);
        $this->assertSame([], $this->policyRequests());
    }

    public function test_paying_through_the_link_issues_the_policy_once(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book();
        $purchase = $staffBooking->bookable;
        auth()->logout();

        $this->get($staffBooking->payUrl())->assertOk()->assertSee('Individual')->assertSee('43,000');

        $this->pay($purchase);
        $this->pay($purchase);

        $purchase->refresh();
        $this->assertSame('Successful', $purchase->status);
        $this->assertSame('COVER-123', $purchase->cover_id);
        $this->assertCount(1, $this->policyRequests());
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/Travel/api/IndividualBooking')
            && $request['QuoteId'] === $purchase->qoute_id && $request['Surname'] === 'Obi' && $request['FirstName'] === 'Ada'
            && $request['DateOfBirth'] === '02-Feb-1990' && $request['NextOfKin']['FullName'] === 'Ngozi Obi');
        Mail::assertSent(InsurancePurchaseNotificationMail::class, 1);
        $this->get($staffBooking->payUrl())->assertSee('This booking is paid');
    }

    public function test_a_family_policy_carries_both_adults_and_every_child(): void
    {
        $this->actingAs($this->staff());
        $purchase = $this->book(children: 2)->bookable;

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/Travel/api/Quote') && $request['BookingTypeId'] === 2 && $request['NoOfChildren'] === 2);
        $this->assertSame('2', (string) $purchase->noc);

        $this->pay($purchase);

        Http::assertSent(function (Request $request) use ($purchase) {
            if (! str_ends_with($request->url(), '/Travel/api/FamilyBooking')) {
                return false;
            }
            $travellers = $request->data();

            return count($travellers) === 4
                && $travellers[0]['FirstName'] === 'Ada' && $travellers[1]['FirstName'] === 'Chidi'
                && $travellers[1]['MaritalStatusId'] === '2'
                && $travellers[2]['FirstName'] === 'Kid0' && $travellers[3]['FirstName'] === 'Kid1'
                && $travellers[2]['StateID'] === '25' && $travellers[2]['Occupation'] === 'Student'
                && $travellers[3]['QuoteId'] === $purchase->qoute_id;
        });
        $this->assertSame('Successful', $purchase->fresh()->status);
    }

    public function test_a_family_needs_every_childs_details(): void
    {
        $this->actingAs($this->staff());

        $this->filled(1)->set('kids.0.firstname', '')->call('create')->assertHasErrors('kids.0.firstname');
        $this->assertSame(0, InsurancePurchase::count());
    }

    public function test_if_sanlam_fails_after_payment_customer_support_is_told(): void
    {
        $this->actingAs($this->staff());
        $purchase = $this->book()->bookable;
        $this->sanlamIssues = false;

        $this->pay($purchase);

        $purchase->refresh();
        $this->assertSame('Failed', $purchase->status);
        $this->assertNull($purchase->cover_id);
        $this->assertSame('issue_failed', WorkItem::where('subject_id', $purchase->id)->where('subject_type', $purchase->getMorphClass())->sole()->stage);
        Mail::assertSent(InsurancePurchaseNotificationMail::class, fn ($mail) => str_contains($mail->build()->subject, 'ACTION NEEDED'));
    }

    public function test_finance_records_a_payment_and_the_policy_is_issued(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book(sendLink: false);

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)
            ->assertSee('Obi Ada')
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'bank_transfer', 'reference' => 'TRF-1'])
            ->assertHasNoTableActionErrors();

        $purchase = $staffBooking->bookable->fresh();
        $this->assertSame('Successful', $purchase->status);
        $this->assertSame('bank_transfer', $purchase->payment_option);
        $this->assertCount(1, $this->policyRequests());
    }

    public function test_a_cancelled_booking_cannot_be_paid_and_leaves_the_queue(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book();

        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $staffBooking);

        $purchase = $staffBooking->bookable->fresh();
        $this->assertSame('Cancelled', $purchase->status);
        $this->assertSame('cancelled', WorkItem::where('subject_id', $purchase->id)->where('subject_type', $purchase->getMorphClass())->sole()->stage);

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect($staffBooking->payUrl());
    }

    // The website, now through the same service

    public function test_the_website_quote_and_policy_still_work(): void
    {
        $this->post(route('air.insurance.quote'), [
            'dob' => '1990-02-02', 'email' => 'web@example.com', 'phone_no' => '0803', 'begin_date' => '2027-01-10', 'end_date' => '2027-01-20',
            'country' => '162', 'purpose_of_travel' => 'Leisure', 'travel_plan' => 'Worldwide Area 1&2', 'booking_type' => '1', 'nop' => 1, 'noc' => 0,
        ])->assertRedirect();

        $quote = InsuranceQuote::sole();
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/Travel/api/Quote') && $request['TravelPlanId'] === 2);

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]));
        $this->withSession([
            'insurance_data_form' => [
                'qouteId' => $quote->quoteRequestId, 'bookingTypeId' => '1', 'amount' => self::QUOTE, 'surname' => 'Web', 'firstname' => 'Customer',
                'middlename' => '', 'gender' => '1', 'title' => '20', 'dob' => '1990-02-02', 'email' => 'web@example.com', 'phone_no' => '0803',
                'state' => '25', 'address' => 'Lekki', 'zipcode' => '1', 'nationalty' => '162', 'passport_no' => 'B1', 'ocupation' => 'Trader', 'marital_status' => '1',
            ],
            'insurance_data_form2' => ['reference' => 'INSWEB1', 'nok_fullname' => 'Kin', 'vat' => 3000, 'p_amount' => 43000],
        ])->get(route('air.insurance.callback', ['reference' => 'INSWEB1']))->assertRedirect(route('air.insurance.success'));

        $purchase = InsurancePurchase::sole();
        $this->assertSame('Successful', $purchase->status);
        $this->assertSame('COVER-123', $purchase->cover_id);
        $this->assertEquals(43000, (float) $purchase->t_amount);
    }
}
