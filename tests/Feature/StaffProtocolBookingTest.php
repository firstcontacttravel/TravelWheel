<?php

namespace Tests\Feature;

use App\Filament\Pages\BookProtocol;
use App\Filament\Pages\NewBooking;
use App\Mail\ProtocolBookingMail;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\Department;
use App\Models\Protocol;
use App\Models\ProtocolBooking;
use App\Models\StaffBooking;
use App\Models\User;
use App\Services\SeerbitPaymentService;
use App\Support\ProtocolVehicles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffProtocolBookingTest extends TestCase
{
    use RefreshDatabase;

    private const VIP = 50000.0;

    private const REGULAR = 30000.0;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    /** VIP at ₦50,000 and Regular at ₦30,000 a passenger. */
    private function protocol(array $overrides = []): Protocol
    {
        // Fill whatever required columns these tests don't care about
        $required = collect(Schema::getColumns('protocols'))
            ->filter(fn ($c) => ! $c['nullable'] && $c['default'] === null && ! $c['auto_increment'])
            ->mapWithKeys(fn ($c) => [$c['name'] => preg_match('/int|decimal|double|float|numeric/', $c['type']) ? 0 : 'x'])
            ->except(['id', 'created_at', 'updated_at']);

        // protocols.id doesn't number itself
        return Protocol::forceCreate([
            ...$required->all(), 'id' => (int) Protocol::max('id') + 1, 'location' => 'Lagos', 'airport' => 'International', 'service' => 'Departure',
            'Given_Price1' => 40000, 'Given_Price2' => 20000, 'markup_price' => 10000, ...$overrides,
        ]);
    }

    private function page(array $overrides = [], int $passengers = 2)
    {
        $page = Livewire::test(BookProtocol::class)
            ->set('location', $overrides['location'] ?? 'Lagos')
            ->call('setAirport', $overrides['airport'] ?? 'International Airport')
            ->call('setService', $overrides['service'] ?? 'Departure')
            ->call('selectPlan', $overrides['plan'] ?? '1')
            ->call('changePassengers', $passengers - 1);

        for ($i = 0; $i < $passengers; $i++) {
            $page->set("passengers.$i", ['first' => 'Ada'.$i, 'last' => 'Obi', 'pnr' => 'PNR'.$i, 'ticket' => 'TKT'.$i, 'bags' => 2]);
        }

        $values = array_merge([
            'travelDate' => now()->addDays(3)->toDateString(), 'time' => '10:00', 'airline' => 'EMIRATES',
            'email' => 'ada@example.com', 'phone' => '08030000000', 'nextOfKinName' => 'Ngozi Obi', 'nextOfKinPhone' => '08040000000',
            'meansId' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'), 'sendLink' => true,
        ], array_diff_key($overrides, array_flip(['location', 'airport', 'service', 'plan'])));

        foreach ($values as $property => $value) {
            $page->set($property, $value);
        }

        return $page;
    }

    private function book(array $overrides = []): StaffBooking
    {
        $this->page($overrides)->call('create')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

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

    public function test_the_website_charges_the_protocols_price_whatever_the_form_says(): void
    {
        $this->protocol();
        $charged = null;
        $this->mock(SeerbitPaymentService::class, function ($mock) use (&$charged) {
            $mock->shouldReceive('initializePayment')->andReturnUsing(function (array $payment) use (&$charged) {
                $charged = $payment;

                return ['redirect_link' => 'https://pay.example.test/checkout'];
            });
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]);
        });

        $this->post(route('air.protocolmakePurchase'), [
            'state' => 'Lagos', 'airport' => 'International Airport', 'service' => 'Departure', 'package' => '1', 'plan' => '1',
            'no_of_passenger' => 2, 'firstname1' => 'Ada', 'lastname1' => 'Obi', 'firstname2' => 'Chi', 'lastname2' => 'Obi',
            'email' => 'ada@example.com', 'phone' => '8030000000', 'travel_date' => '2027-01-10', 'd_time' => '10:00', 'airline' => 'EMIRATES',
            'c_amount' => '1', 'vat' => '0', 'p_amount' => '1',
        ])->assertRedirect('https://pay.example.test/checkout');

        $this->assertEquals(2 * self::VIP * 1.075, (float) $charged['amount']);

        $this->get(route('air.protocol.callback', ['reference' => $charged['paymentReference']]))
            ->assertRedirect(route('air.protocol_payment', ['trans_id' => $charged['paymentReference']]));

        $booking = ProtocolBooking::sole();
        $this->assertEquals(2 * self::VIP, (float) $booking->amount);
        $this->assertEquals(2 * self::VIP * 0.075, (float) $booking->vat);
        Mail::assertSent(ProtocolBookingMail::class, fn ($mail) => $mail->hasTo('ada@example.com') && $mail->amount == 2 * self::VIP);
    }

    public function test_the_website_refuses_a_plan_not_offered_there(): void
    {
        // Regular is only offered for international departures
        $this->protocol(['service' => 'Arrival']);
        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));

        $this->post(route('air.protocolmakePurchase'), [
            'state' => 'Lagos', 'airport' => 'International Airport', 'service' => 'Arrival', 'package' => '2', 'no_of_passenger' => 1, 'p_amount' => '1',
        ])->assertRedirect(route('air.protocol'));

        $this->post(route('air.protocol_checkout'), [
            'location' => 'Lagos', 'airport' => 'International Airport', 'service' => 'Arrival', 'plan' => '1', 'nop' => 1, 'amount' => '1',
        ])->assertOk()->assertSee('53,750.00');
    }

    public function test_a_payment_for_another_checkout_cannot_claim_the_form_in_the_session(): void
    {
        $this->seerbitSays(true);

        $this->withSession(['protocol_checkout_form' => ['reference' => 'PROTEXPENSIVE', 'no_of_passenger' => 5, 'email' => 'ada@example.com']])
            ->get(route('air.protocol.callback', ['reference' => 'PROTCHEAP']))
            ->assertRedirect(route('air.protocol'));

        $this->assertSame(0, ProtocolBooking::count());
    }

    // Booking from the admin

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookProtocol::getUrl())->assertOk();

        $this->actingAs($this->staff('customer-support'))->get(BookProtocol::getUrl())->assertForbidden();
    }

    public function test_staff_create_a_booking_at_the_server_price_with_every_passenger(): void
    {
        $this->protocol();
        $staff = $this->staff();
        $this->actingAs($staff);

        $booking = $this->book()->bookable;

        $this->assertInstanceOf(ProtocolBooking::class, $booking);
        $this->assertSame('Pending', $booking->status);
        $this->assertEquals(2 * self::VIP, (float) $booking->amount);
        $this->assertEquals(2 * self::VIP * 0.075, (float) $booking->vat);
        $this->assertSame(['Obi Ada0', 'Obi Ada1'], $booking->fullname);
        $this->assertSame(['PNR0', 'PNR1'], $booking->reservationCode);
        $this->assertSame(2, (int) $booking->passenger);
        $this->assertSame('International Airport', $booking->airport);
        $this->assertSame('Departure', $booking->service_type);
        $this->assertSame('1', $booking->package);
        $this->assertSame($staff->id, StaffBooking::sole()->created_by);

        // The ID is kept off the public web
        Storage::disk('local')->assertExists($booking->means_id);
        $this->assertStringStartsWith('protocol/means-id/', $booking->means_id);

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(ProtocolBookingMail::class);
    }

    public function test_regular_is_only_offered_for_international_departures(): void
    {
        $this->actingAs($this->staff());
        $this->protocol();
        $this->protocol(['service' => 'Arrival']);
        $this->protocol(['airport' => 'Local']);

        Livewire::test(BookProtocol::class)
            ->set('location', 'Lagos')->call('setAirport', 'International Airport')->call('setService', 'Departure')
            ->assertSee('Regular')->assertSee('30,000')
            ->call('setService', 'Arrival')
            ->assertDontSee('Regular')
            ->call('selectPlan', '2')
            ->assertSet('plan', '')
            ->call('setAirport', 'Local Airport')->call('setService', 'Departure')
            ->assertDontSee('Regular');
    }

    public function test_a_plan_with_no_price_is_not_offered(): void
    {
        $this->actingAs($this->staff());
        $this->protocol(['Given_Price2' => 0]);

        Livewire::test(BookProtocol::class)
            ->set('location', 'Lagos')->call('setAirport', 'International Airport')->call('setService', 'Departure')
            ->assertDontSee('Regular')
            ->assertSee('VIP');
    }

    public function test_a_pickup_needs_a_vehicle_and_address_and_records_them(): void
    {
        $this->protocol();
        ProtocolVehicles::setPrice('suv', 40000);
        $this->actingAs($this->staff());

        $this->page(['optionalRequest' => 'Pick-up & Escort'])->call('create')->assertHasErrors(['vehicle', 'vehicleAddress']);

        $this->page(['optionalRequest' => 'Pick-up & Escort', 'vehicle' => 'suv', 'vehicleAddress' => '12 Allen Avenue, Ikeja'])
            ->call('create')->assertHasNoErrors();

        $booking = ProtocolBooking::sole();
        $this->assertSame('Pick-up & Escort', $booking->optional_request);
        $this->assertSame('SUV Business (Up to 3 Seaters) - ₦40,000, not included in protocol amount', $booking->optionalRequestOption);
        $this->assertSame('12 Allen Avenue, Ikeja', $booking->optionalRequestAddress);
        // The vehicle isn't added to the protocol amount
        $this->assertEquals(2 * self::VIP, (float) $booking->amount);
    }

    public function test_an_arrival_offers_drop_off_not_pick_up(): void
    {
        $this->protocol(['service' => 'Arrival']);
        $this->actingAs($this->staff());

        $this->page(['service' => 'Arrival', 'optionalRequest' => 'Pick-up'])->call('create')->assertHasErrors('optionalRequest');
    }

    public function test_an_airline_not_flying_there_or_an_unnamed_other_is_refused(): void
    {
        $this->protocol();
        $this->actingAs($this->staff());

        $this->page(['airline' => 'AIR PEACE LOCAL ONLY'])->call('create')->assertHasErrors('airline');
        $this->page(['airline' => 'OTHERS'])->call('create')->assertHasErrors('otherAirline');

        $this->page(['airline' => 'OTHERS', 'otherAirline' => 'Saudia'])->call('create')->assertHasNoErrors();
        $this->assertSame('Saudia', ProtocolBooking::sole()->airline);
    }

    public function test_every_passenger_needs_their_details_and_the_id_must_be_a_document(): void
    {
        $this->protocol();
        $this->actingAs($this->staff());

        $this->page()->set('passengers.1.pnr', '')->call('create')->assertHasErrors('passengers.1.pnr');
        $this->page(['meansId' => UploadedFile::fake()->create('virus.exe', 100)])->call('create')->assertHasErrors('meansId');

        $this->assertSame(0, ProtocolBooking::count());
    }

    // Paying

    public function test_the_customer_pays_the_total_with_vat_through_the_link_and_is_confirmed_once(): void
    {
        $this->protocol();
        $this->actingAs($this->staff());
        $staffBooking = $this->book();
        $booking = $staffBooking->bookable;
        auth()->logout();
        $total = 2 * self::VIP * 1.075;

        (new StaffBookingPaymentLinkMail($staffBooking))->assertSeeInHtml('Dear Obi Ada0')->assertSeeInHtml(number_format($total));

        $this->get($staffBooking->payUrl())->assertOk()
            ->assertSee('VIP, International Airport Departure, Lagos')
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
        $this->assertSame(route('air.protocol.callback'), $paid['callbackUrl']);
        $this->assertSame('Obi Ada0', $paid['fullName']);

        foreach ([1, 2] as $visit) {
            $this->get(route('air.protocol.callback', ['reference' => $booking->trans_id]))
                ->assertRedirect(route('air.protocol_payment', ['trans_id' => $booking->trans_id]));
        }

        $this->assertSame('Successful', $booking->fresh()->status);
        Mail::assertSent(ProtocolBookingMail::class, 1);
        $this->get(route('air.protocol_payment', ['trans_id' => $booking->trans_id]))->assertOk();
    }

    public function test_an_unpaid_booking_gets_no_protocol_pass(): void
    {
        $this->protocol();
        $this->actingAs($this->staff());
        $booking = $this->book()->bookable;

        $this->get(route('air.protocol_payment', ['trans_id' => $booking->trans_id]))->assertNotFound();
        $this->get(route('air.protocol_generate', ['trans_id' => $booking->trans_id]))->assertNotFound();
    }

    public function test_finance_records_a_payment_and_a_cancelled_one_cannot_be_paid(): void
    {
        $this->protocol();
        $this->actingAs($this->staff());
        $staffBooking = $this->book(['sendLink' => false]);

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)
            ->assertSee('Obi Ada0')
            ->assertSee('107,500')
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'pos', 'reference' => 'POS-1'])
            ->assertHasNoTableActionErrors();

        $booking = $staffBooking->bookable->fresh();
        $this->assertSame('Successful', $booking->status);
        $this->assertSame('pos', $booking->paymentoption);
        Mail::assertSent(ProtocolBookingMail::class, 1);

        StaffBooking::query()->delete();
        ProtocolBooking::query()->delete();
        $this->actingAs($this->staff());
        $second = $this->book();
        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $second);
        $this->assertSame('Cancelled', $second->bookable->fresh()->status);

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));
        $this->post(route('staff-bookings.pay.start', $second->pay_token))->assertRedirect($second->payUrl());
    }
}
