<?php

namespace Tests\Feature;

use App\Filament\Pages\BookGroundTransport;
use App\Filament\Pages\NewBooking;
use App\Mail\CarHireSuccessMail;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Mail\TransferSuccessMail;
use App\Models\CarHire;
use App\Models\Department;
use App\Models\FleetCar;
use App\Models\StaffBooking;
use App\Models\Transfer;
use App\Models\TransportRate;
use App\Models\User;
use App\Services\GroundBookingService;
use App\Services\SeerbitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class StaffBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        foreach (['car_hire', 'transfer'] as $service) {
            FleetCar::create(['service_type' => $service, 'state' => 'abuja', 'vehicle_type' => 'saloon', 'year' => 2018, 'car_name' => 'Toyota Camry', 'is_active' => true]);
        }
    }

    private function staff(string $department): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function carHireForm(array $overrides = []): array
    {
        return array_merge([
            'state' => 'abuja', 'car_type' => 'saloon', 'category' => 'Standard', 'rental_hours' => 10,
            'pickup_location' => 'Wuse 2', 'full_name' => 'Ada Obi', 'email' => 'ada@example.com',
            'phone_number' => '08030000000', 'passengers' => 2,
            'pickup_date' => now()->addDays(3)->toDateString(), 'pickup_time' => '10:00', 'send_link' => true,
        ], $overrides);
    }

    /** Goes through the booking page's steps as staff would. */
    private function carHirePage(array $overrides = [])
    {
        $form = $this->carHireForm($overrides);

        return Livewire::withQueryParams(['tab' => 'car'])->test(BookGroundTransport::class)
            ->set('state', $form['state'])
            ->call('selectType', $form['car_type'])
            ->call('selectCategory', $form['category'])
            ->call('setHours', $form['rental_hours'])
            ->set('pickup', $form['pickup_location'])
            ->set('fullName', $form['full_name'])
            ->set('email', $form['email'])
            ->set('phone', $form['phone_number'])
            ->set('passengers', $form['passengers'])
            ->set('pickupDate', $form['pickup_date'])
            ->set('pickupTime', $form['pickup_time'])
            ->set('sendLink', $form['send_link']);
    }

    private function book(array $overrides = []): StaffBooking
    {
        $this->carHirePage($overrides)->call('create')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

        return StaffBooking::sole();
    }

    /** SeerBit's verification answering this way, for the callback. */
    private function seerbitSays(bool $paid): void
    {
        $this->mock(SeerbitPaymentService::class, function ($mock) use ($paid) {
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => $paid, 'query_succeeded' => true]);
            $mock->shouldReceive('initializePayment')->andReturn(['redirect_link' => 'https://pay.example.test/checkout']);
        });
    }

    public function test_only_the_ceo_finance_and_operations_can_open_new_booking(): void
    {
        foreach ([User::factory()->create(['is_admin' => true]), $this->staff('operations'), $this->staff('finance')] as $user) {
            $this->actingAs($user)->get(NewBooking::getUrl())->assertOk();
        }

        foreach ([$this->staff('customer-support'), $this->staff('it')] as $user) {
            $this->actingAs($user)->get(NewBooking::getUrl())->assertForbidden();
        }
    }

    public function test_staff_create_a_car_hire_at_the_server_price_and_the_customer_gets_a_link_not_a_confirmation(): void
    {
        $staff = $this->staff('operations');
        $this->actingAs($staff);

        $staffBooking = $this->book();
        $booking = $staffBooking->bookable;

        $expected = app(GroundBookingService::class)->quoteCarHire('saloon', 'Standard', 10)['total'];
        $this->assertEquals($expected, $booking->amount);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame('abuja', $booking->state);
        $this->assertSame($staff->id, $staffBooking->created_by);
        $this->assertNotNull($staffBooking->link_sent_at);

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(CarHireSuccessMail::class);
    }

    public function test_a_category_with_no_car_in_that_state_is_refused(): void
    {
        $this->actingAs($this->staff('operations'));

        $this->carHirePage(['category' => 'Executive'])->call('create')->assertNoRedirect();

        $this->assertSame(0, CarHire::count());
        $this->assertSame(0, StaffBooking::count());
    }

    public function test_the_customer_pays_through_the_link_and_is_then_confirmed(): void
    {
        $this->actingAs($this->staff('operations'));
        $staffBooking = $this->book();
        $booking = $staffBooking->bookable;
        auth()->logout();

        // Opening the link starts nothing, so a mail scanner can't
        $this->get($staffBooking->payUrl())->assertOk()->assertSee($booking->payment_reference)->assertSee('Pay');

        $this->seerbitSays(true);
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect('https://pay.example.test/checkout');

        $this->get(route('air.carhire.seerbit.callback', ['reference' => $booking->payment_reference, 'message' => 'Successful']));

        $this->assertSame('paid', $booking->fresh()->payment_status);
        Mail::assertSent(CarHireSuccessMail::class, 1);

        // Paying again isn't offered
        $this->get($staffBooking->payUrl())->assertSee('This booking is paid');
    }

    public function test_only_finance_or_the_ceo_can_record_a_payment(): void
    {
        $this->actingAs($this->staff('operations'));
        $staffBooking = $this->book();

        Livewire::test(NewBooking::class)
            ->assertTableActionHidden('recordPayment', $staffBooking)
            ->assertTableActionVisible('sendPaymentLink', $staffBooking);

        $finance = $this->staff('finance');
        $this->actingAs($finance);

        Livewire::test(NewBooking::class)
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'pos', 'reference' => 'POS-123'])
            ->assertHasNoTableActionErrors();

        $booking = $staffBooking->bookable->fresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('pos', $booking->payment_option);
        $this->assertSame($finance->id, $staffBooking->fresh()->recorded_by);
        $this->assertSame('POS-123', $staffBooking->fresh()->recorded_reference);
        Mail::assertSent(CarHireSuccessMail::class, 1);
    }

    public function test_a_cancelled_booking_can_no_longer_be_paid(): void
    {
        $this->actingAs($this->staff('operations'));
        $staffBooking = $this->book();

        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $staffBooking);
        $this->assertSame('cancelled', $staffBooking->bookable->fresh()->payment_status);

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect($staffBooking->payUrl());
        $this->get($staffBooking->payUrl())->assertSee('no longer available');
    }

    public function test_staff_create_a_transfer_priced_from_the_route(): void
    {
        $this->actingAs($this->staff('finance'));
        config(['services.google_maps.key' => 'test-key']);
        Http::fake(['maps.googleapis.com/*' => Http::response(['status' => 'OK', 'rows' => [['elements' => [[
            'status' => 'OK',
            'distance' => ['value' => 25000, 'text' => '25 km'],
            'duration' => ['value' => 2400, 'text' => '40 mins'],
        ]]]]])]);

        $page = Livewire::withQueryParams(['tab' => 'transfer'])->test(BookGroundTransport::class)
            ->set('state', 'abuja')
            ->call('selectType', 'saloon')
            ->call('selectCategory', 'Standard')
            ->set('pickup', 'Nnamdi Azikiwe Airport')
            ->set('dropoff', 'Wuse 2')
            ->set('fullName', 'Ada Obi')
            ->set('email', 'ada@example.com')
            ->set('phone', '08030000000')
            ->set('passengers', 2)
            ->set('pickupDate', now()->addDays(3)->toDateString())
            ->set('pickupTime', '10:00')
            ->set('sendLink', false);

        // A price is needed first, and changing an address asks for it again
        $page->call('create')->assertHasErrors('routeMins');
        $page->call('getRoute')->assertSet('routeKm', 25)->assertSee('25 km');
        $page->set('dropoff', 'Maitama')->assertSet('routeKm', null);
        $page->call('getRoute')->call('create')->assertHasNoErrors();

        $transfer = Transfer::sole();
        $this->assertEquals(25, $transfer->distance_km);
        $this->assertSame(40, (int) $transfer->duration_mins);
        $this->assertSame('pending', $transfer->payment_status);
        $this->assertSame($transfer->id, StaffBooking::sole()->bookable_id);
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);
        Mail::assertNotSent(TransferSuccessMail::class);
    }

    public function test_the_website_callback_no_longer_believes_the_address_bar(): void
    {
        $booking = app(GroundBookingService::class)->createCarHire($this->carHireForm(), 'seerbit');
        $this->seerbitSays(false);

        $this->get(route('air.carhire.seerbit.callback', ['reference' => $booking->payment_reference, 'message' => 'Successful']))
            ->assertRedirect(route('air.carhire'));

        $this->assertSame('pending', $booking->fresh()->payment_status);
        Mail::assertNotSent(CarHireSuccessMail::class);
    }

    public function test_a_travel_assist_callback_also_asks_seerbit(): void
    {
        $this->seerbitSays(false);

        $this->get(route('air.support.seerbit.callback', ['reference' => 'SUPPORTFAKE', 'message' => 'Successful']))
            ->assertRedirect(route('air.support'));
    }

    public function test_the_booking_page_only_offers_what_the_state_has_and_shows_the_price(): void
    {
        $this->actingAs($this->staff('operations'));
        FleetCar::create(['service_type' => 'car_hire', 'state' => 'lagos', 'vehicle_type' => 'suv', 'year' => 2021, 'car_name' => 'Toyota Prado', 'is_active' => true]);

        Livewire::withQueryParams(['tab' => 'car'])->test(BookGroundTransport::class)
            ->assertSee('Choose a state first')
            ->set('state', 'abuja')
            ->assertSee('Saloon')
            ->assertDontSee('SUV')
            ->call('selectType', 'saloon')
            ->assertSee('Standard')
            ->assertDontSee('Executive')
            ->call('selectCategory', 'Standard')
            ->assertDontSee('Price breakdown')
            ->call('setHours', 4)
            ->assertSee('Price breakdown')
            ->assertSee('Customer details')
            ->set('state', 'lagos')
            ->assertSet('vehicleType', '')
            ->assertSee('SUV');
    }

    public function test_too_many_passengers_for_the_vehicle_are_refused(): void
    {
        $this->actingAs($this->staff('operations'));

        $this->carHirePage(['passengers' => 4])->call('create')->assertHasErrors('passengers');
        $this->assertSame(0, CarHire::count());
    }
}
