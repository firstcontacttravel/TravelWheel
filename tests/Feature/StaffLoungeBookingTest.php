<?php

namespace Tests\Feature;

use App\Filament\Pages\BookLounge;
use App\Filament\Pages\NewBooking;
use App\Mail\LoungeBookingMail;
use App\Mail\LoungeReservationNotificationMail;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\Department;
use App\Models\Lounge;
use App\Models\LoungeBooking;
use App\Models\StaffBooking;
use App\Models\User;
use App\Services\LoungePairCatalogueSyncService;
use App\Services\SeerbitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class StaffLoungeBookingTest extends TestCase
{
    use RefreshDatabase;

    private const ADULT = 60000.0;

    private const CHILD = 30000.0;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    /** Priced at ₦60,000 an adult, ₦30,000 a child and nothing for an infant. */
    private function lounge(array $overrides = []): Lounge
    {
        // The lounges table has many required columns; fill the ones these tests don't care about
        $required = collect(Schema::getColumns('lounges'))
            ->filter(fn ($column) => ! $column['nullable'] && $column['default'] === null && ! $column['auto_increment'])
            ->mapWithKeys(fn ($column) => [$column['name'] => str_contains($column['type'], 'int') || str_contains($column['type'], 'decimal') ? 0 : 'x'])
            ->except(['created_at', 'updated_at']);

        return Lounge::create([
            ...$required->only((new Lounge)->getFillable())->all(),
            'lounge_id' => 'LNGE'.uniqid(), 'brand_name' => 'Oasis Lounge', 'location' => 'Lagos', 'airport' => '1', 'terminal' => 'Old Terminal',
            'service' => 'Departure', 'given_PriceA' => 50000, 'given_PriceB' => 20000, 'given_PriceC' => 0, 'markup_price' => 10000, 'is_active' => true,
            ...$overrides,
        ]);
    }

    private function page(Lounge $lounge, array $overrides = [])
    {
        $values = array_merge([
            'firstName' => 'Ada', 'lastName' => 'Obi', 'email' => 'ada@example.com', 'phone' => '08030000000',
            'serviceDate' => now()->addDays(3)->toDateString(), 'departureTime' => '14:30', 'ticketNo' => 'ET123', 'sendLink' => true,
        ], $overrides);

        $page = Livewire::test(BookLounge::class)
            ->set('state', 'Lagos')
            ->call('setAccess', 1)
            ->call('setService', 'Departure')
            ->call('selectLounge', $lounge->id)
            ->call('changeGuests', 'adults', 1)
            ->call('changeGuests', 'children', 1);

        foreach ($values as $property => $value) {
            $page->set($property, $value);
        }

        return $page;
    }

    private function book(array $overrides = []): StaffBooking
    {
        $this->page($this->lounge(), $overrides)->call('create')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

        return StaffBooking::sole();
    }

    private function seerbitSays(bool $paid): void
    {
        $this->mock(SeerbitPaymentService::class, function ($mock) use ($paid) {
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => $paid, 'query_succeeded' => true]);
            $mock->shouldReceive('initializePayment')->andReturn(['redirect_link' => 'https://pay.example.test/checkout']);
        });
    }

    // The website's prices

    public function test_the_website_charges_the_lounges_price_whatever_the_form_says(): void
    {
        $lounge = $this->lounge();
        $charged = null;
        $this->mock(SeerbitPaymentService::class, function ($mock) use (&$charged) {
            $mock->shouldReceive('initializePayment')->andReturnUsing(function (array $payment) use (&$charged) {
                $charged = $payment;

                return ['redirect_link' => 'https://pay.example.test/checkout'];
            });
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]);
        });

        $this->post(route('air.lounge.purchase'), [
            'lounge_id' => $lounge->id, 'lounge' => 'Made up', 'firstname' => 'Ada', 'lastname' => 'Obi', 'email' => 'ada@example.com', 'phone' => '08030000000',
            'travel_date' => '2027-01-10', 'd_time' => '14:30', 'noa' => 2, 'noc' => 1, 'noi' => 0,
            'p_amount' => '1', 'c_amount' => '1', 'vat' => '0', 'adultAmount' => '1', 'childAmount' => '0',
        ])->assertRedirect('https://pay.example.test/checkout');

        $expected = (2 * self::ADULT + self::CHILD) * 1.075;
        $this->assertEquals($expected, (float) $charged['amount']);

        $this->get(route('air.lounge.callback', ['reference' => $charged['paymentReference']]))
            ->assertRedirect(route('air.lounge_payment', ['trans_id' => $charged['paymentReference']]));

        $booking = LoungeBooking::sole();
        $this->assertEquals(2 * self::ADULT + self::CHILD, (float) $booking->amount);
        $this->assertEquals(2 * self::ADULT, (float) $booking->amountA);
        $this->assertEquals($expected, $booking->totalAmount());
        $this->assertSame('Oasis Lounge', $booking->lounge_name);
    }

    public function test_the_website_checkout_shows_the_lounges_price_and_refuses_no_guests(): void
    {
        $lounge = $this->lounge();

        $this->post(route('air.loungecheckout'), ['lounge_id' => $lounge->id, 'nop' => 1, 'adultValue' => 1, 'amount' => '1', 'adultAmount' => '1'])
            ->assertOk()
            ->assertSee('64,500')
            ->assertDontSee('value="1.075"', false);

        $this->post(route('air.loungecheckout'), ['lounge_id' => $lounge->id, 'nop' => 0, 'adultValue' => 0])
            ->assertRedirect()
            ->assertSessionHasErrors('nop');

        $lounge->update(['is_active' => false]);
        $this->post(route('air.lounge.purchase'), ['lounge_id' => $lounge->id, 'noa' => 1])->assertRedirect(route('air.lounge'));
    }

    public function test_a_payment_for_another_checkout_cannot_claim_the_form_in_the_session(): void
    {
        $lounge = $this->lounge();
        $this->seerbitSays(true);

        $this->withSession(['lounge_checkout_form' => [
            'lounge_id' => $lounge->id, 'reference' => 'LNGEXPENSIVE', 'firstname' => 'Ada', 'lastname' => 'Obi', 'email' => 'ada@example.com',
            'noa' => 5, 'c_amount' => 300000, 'vat' => 22500, 'p_amount' => 322500,
        ]])->get(route('air.lounge.callback', ['reference' => 'LNGCHEAP']))->assertRedirect(route('air.lounge'));

        $this->assertSame(0, LoungeBooking::count());
    }

    // Booking from the admin

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookLounge::getUrl())->assertOk();

        $this->actingAs($this->staff('customer-support'))->get(BookLounge::getUrl())->assertForbidden();
    }

    public function test_staff_create_a_booking_at_the_server_price_and_the_customer_gets_a_link_not_a_confirmation(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        // Starts at one adult, so this is two adults and a child
        $booking = $this->book()->bookable;

        $this->assertInstanceOf(LoungeBooking::class, $booking);
        $this->assertSame('Pending', $booking->status);
        $this->assertEquals(2 * self::ADULT + self::CHILD, (float) $booking->amount);
        $this->assertEquals((2 * self::ADULT + self::CHILD) * 0.075, (float) $booking->vat);
        $this->assertSame('Obi Ada', $booking->fullname);
        $this->assertSame(3, (int) $booking->nop);
        $this->assertSame($staff->id, StaffBooking::sole()->created_by);

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(LoungeBookingMail::class);
        Mail::assertNotSent(LoungeReservationNotificationMail::class);
    }

    public function test_only_lounges_for_those_choices_are_offered(): void
    {
        $this->actingAs($this->staff());
        $this->lounge(['brand_name' => 'Right Lounge']);
        $this->lounge(['brand_name' => 'Arrivals Lounge', 'service' => 'Arrival']);
        $this->lounge(['brand_name' => 'Local Lounge', 'airport' => '2']);
        $this->lounge(['brand_name' => 'Abuja Lounge', 'location' => 'Abuja']);
        $this->lounge(['brand_name' => 'Disabled Lounge', 'is_active' => false]);

        Livewire::test(BookLounge::class)
            ->assertDontSee('Right Lounge')
            ->set('state', 'Lagos')->call('setAccess', 1)->call('setService', 'Departure')
            ->assertSee('Right Lounge')
            ->assertDontSee('Arrivals Lounge')
            ->assertDontSee('Local Lounge')
            ->assertDontSee('Abuja Lounge')
            ->assertDontSee('Disabled Lounge');
    }

    public function test_a_lounge_not_on_offer_cannot_be_selected_or_booked_once_disabled(): void
    {
        $this->actingAs($this->staff());
        $other = $this->lounge(['location' => 'Abuja']);

        Livewire::test(BookLounge::class)
            ->set('state', 'Lagos')->call('setAccess', 1)->call('setService', 'Departure')
            ->call('selectLounge', $other->id)
            ->assertSet('loungeId', null);

        $lounge = $this->lounge();
        $page = $this->page($lounge);
        $lounge->update(['is_active' => false]);

        $page->call('create')->assertHasErrors('loungeId');
        $this->assertSame(0, LoungeBooking::count());
    }

    public function test_a_booking_with_nothing_to_charge_is_refused(): void
    {
        $this->actingAs($this->staff());

        $this->page($this->lounge())
            ->call('changeGuests', 'adults', -5)
            ->call('changeGuests', 'children', -5)
            ->assertSet('adults', 0)
            ->call('create')
            ->assertNoRedirect();

        $this->assertSame(0, LoungeBooking::count());
    }

    public function test_the_worldwide_tab_searches_loungepair_and_the_booking_goes_to_staff_by_hand(): void
    {
        $this->actingAs($this->staff());
        $lounge = $this->lounge([
            'brand_name' => 'Heathrow Lounge', 'provider' => 'loungepair', 'provider_airport_iata' => 'LHR', 'provider_url' => 'https://loungepair.example/x',
            'provider_currency' => 'NGN', 'location' => 'London', 'airport' => '0', 'given_PriceA' => 50000,
        ]);
        $this->mock(LoungePairCatalogueSyncService::class, fn ($mock) => $mock->shouldReceive('sync')->once()->with('LHR')->andReturn([]));

        $page = Livewire::withQueryParams(['tab' => 'worldwide'])->test(BookLounge::class)
            ->set('iata', 'nonsense')->call('searchAirport')
            ->assertSee('three-letter airport code')
            ->set('iata', 'lhr')->call('searchAirport')
            ->assertSee('Heathrow Lounge')
            ->call('selectLounge', $lounge->id);

        foreach (['firstName' => 'Ada', 'lastName' => 'Obi', 'email' => 'ada@example.com', 'phone' => '0803', 'serviceDate' => now()->addDay()->toDateString(), 'departureTime' => '09:00'] as $property => $value) {
            $page->set($property, $value);
        }
        $page->call('create')->assertHasNoErrors();

        $booking = LoungeBooking::sole();
        $this->assertSame('loungepair', $booking->provider);
        $this->assertTrue($booking->requiresManualProviderBooking());
    }

    // Paying

    public function test_the_customer_pays_the_total_with_vat_through_the_link_and_is_confirmed_once(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book();
        $booking = $staffBooking->bookable;
        auth()->logout();
        $total = (2 * self::ADULT + self::CHILD) * 1.075;

        (new StaffBookingPaymentLinkMail($staffBooking))->assertSeeInHtml(number_format($total));

        $this->get($staffBooking->payUrl())->assertOk()
            ->assertSee('Oasis Lounge')
            ->assertSee(number_format($total))
            ->assertDontSee('Pick-up');

        $paid = null;
        $this->mock(SeerbitPaymentService::class, function ($mock) use (&$paid) {
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]);
            $mock->shouldReceive('initializePayment')->andReturnUsing(function (array $payment) use (&$paid) {
                $paid = $payment;

                return ['redirect_link' => 'https://pay.example.test/checkout'];
            });
        });
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect('https://pay.example.test/checkout');

        $this->assertEquals($total, (float) $paid['amount']);
        $this->assertSame(route('air.lounge.callback'), $paid['callbackUrl']);
        $this->assertSame($booking->trans_id, $paid['paymentReference']);

        foreach ([1, 2] as $visit) {
            $this->get(route('air.lounge.callback', ['reference' => $booking->trans_id]))
                ->assertRedirect(route('air.lounge_payment', ['trans_id' => $booking->trans_id]));
        }

        $this->assertSame('Successful', $booking->fresh()->status);
        Mail::assertSent(LoungeBookingMail::class, 1);
        Mail::assertSent(LoungeReservationNotificationMail::class, 1);

        $this->get($staffBooking->payUrl())->assertSee('This booking is paid');
        $this->get(route('air.lounge_payment', ['trans_id' => $booking->trans_id]))->assertOk();
    }

    public function test_a_failed_payment_leaves_the_booking_unpaid(): void
    {
        $this->actingAs($this->staff());
        $booking = $this->book()->bookable;
        $this->seerbitSays(false);

        $this->get(route('air.lounge.callback', ['reference' => $booking->trans_id]))->assertRedirect(route('air.lounge'));

        $this->assertSame('Pending', $booking->fresh()->status);
        Mail::assertNotSent(LoungeBookingMail::class);
    }

    public function test_an_unpaid_booking_gets_no_lounge_pass(): void
    {
        $this->actingAs($this->staff());
        $booking = $this->book()->bookable;

        $this->get(route('air.lounge_payment', ['trans_id' => $booking->trans_id]))->assertNotFound();
        $this->get(route('air.lounge_generate', ['trans_id' => $booking->trans_id]))->assertNotFound();
    }

    public function test_finance_records_a_payment_and_the_reservations_email_has_the_full_total(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book(['sendLink' => false]);
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)
            ->assertSee('Obi Ada')
            ->assertSee('Pending')
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'bank_transfer', 'reference' => 'TRF-9'])
            ->assertHasNoTableActionErrors();

        $booking = $staffBooking->bookable->fresh();
        $this->assertSame('Successful', $booking->status);
        $this->assertSame('bank_transfer', $booking->payment_option);

        Mail::assertSent(LoungeBookingMail::class, 1);
        Mail::assertSent(LoungeReservationNotificationMail::class, function ($mail) {
            $html = $mail->render();
            $this->assertStringContainsString('₦161,250.00', $html);
            $this->assertStringContainsString('Bank transfer', $html);

            return true;
        });
    }

    public function test_a_cancelled_booking_can_no_longer_be_paid(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book();

        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $staffBooking);
        $this->assertSame('Cancelled', $staffBooking->bookable->fresh()->status);

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect($staffBooking->payUrl());
        $this->get($staffBooking->payUrl())->assertSee('no longer available');
    }
}
