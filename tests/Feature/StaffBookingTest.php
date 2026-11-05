<?php

namespace Tests\Feature;

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

    private function book(array $overrides = []): StaffBooking
    {
        Livewire::test(NewBooking::class)
            ->callAction('bookCarHire', data: $this->carHireForm($overrides))
            ->assertHasNoActionErrors();

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

        Livewire::test(NewBooking::class)
            ->callAction('bookCarHire', data: $this->carHireForm(['category' => 'Executive']));

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
        $this->mock(GroundBookingService::class, function ($mock) {
            $mock->shouldReceive('distance')->andReturn(['distance_km' => 25, 'distance_text' => '25 km', 'duration_text' => '40 mins', 'duration_mins' => 40, 'drive_time' => '40 mins']);
            $mock->shouldReceive('createTransfer')->andReturnUsing(fn (array $data, string $option) => (new GroundBookingService(app(\App\Services\CarFleetCatalogService::class), app(\App\Services\TransferPricingService::class)))->createTransfer($data, $option));
        });

        Livewire::test(NewBooking::class)->callAction('bookTransfer', data: [
            'state' => 'abuja', 'vehicle_type' => 'saloon', 'category' => 'Standard',
            'pickup_location' => 'Nnamdi Azikiwe Airport', 'dropoff_location' => 'Wuse 2',
            'full_name' => 'Ada Obi', 'email' => 'ada@example.com', 'phone_number' => '08030000000', 'passengers' => 2,
            'pickup_date' => now()->addDays(3)->toDateString(), 'pickup_time' => '10:00', 'send_link' => false,
        ])->assertHasNoActionErrors();

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
}
