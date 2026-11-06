<?php

namespace Tests\Feature;

use App\Filament\Pages\BookAirCargo;
use App\Filament\Pages\NewBooking;
use App\Mail\ShipmentMail;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\AirCargoModel;
use App\Models\CargoDocumentPrice;
use App\Models\CargoPackagePrice;
use App\Models\Department;
use App\Models\ShippingZone;
use App\Models\StaffBooking;
use App\Models\User;
use App\Services\AirCargoBookingService;
use App\Services\SeerbitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAirCargoBookingTest extends TestCase
{
    use RefreshDatabase;

    /** United Kingdom is in Zone 1: a 1kg document costs ₦66,000 there, a 1kg package ₦72,000. */
    private const UK_DOCUMENT_1KG = 66000;

    private const UK_PACKAGE_1KG = 72000;

    /** Ikeja is in Mainland 2, where a pick-up costs ₦4,000. */
    private const IKEJA_PICKUP = 4000;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake(config('filesystems.default'));

        $zone = ShippingZone::create(['zone_name' => 'Zone 1']);
        $weights = array_combine(array_values(AirCargoBookingService::WEIGHTS), [66000, 66000, 67000, 68000, 93000, 110000, 127000, 144000, 161000, 179000]);
        CargoDocumentPrice::create(['zone_id' => $zone->id] + $weights);
        CargoPackagePrice::create(['zone_id' => $zone->id] + array_merge($weights, ['weight_1_0' => self::UK_PACKAGE_1KG]));
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function websiteForm(array $overrides = []): array
    {
        return array_merge([
            'fullname' => 'Ada Obi', 'phone_no' => '08030000000', 'email' => 'ada@example.com', 'address1' => '1 Allen Avenue, Ikeja',
            'postalcode1' => '100001', 'country' => 'NG', 'sCountry' => 'Nigeria',
            'fullname2' => 'John Smith', 'contact2' => '+447700900000', 'email2' => 'john@example.com', 'address2' => '10 Downing St',
            'postalcode2' => 'SW1A 2AA', 'country1' => 'GB', 'rCountry' => 'United Kingdom',
            'shipment_type' => 'Document', 'typeOfDoc' => 'Certificate', 'doc_weight' => '1', 'dropoff_date' => '2027-01-10',
            'price' => '1', 'totalPrice' => '1',
        ], $overrides);
    }

    private function page(array $overrides = [])
    {
        $values = array_merge([
            'senderName' => 'Ada Obi', 'senderPhone' => '08030000000', 'senderEmail' => 'ada@example.com', 'senderAddress' => '1 Allen Avenue, Ikeja',
            'senderPostalCode' => '100001', 'senderCountry' => 'NG',
            'receiverName' => 'John Smith', 'receiverPhone' => '+447700900000', 'receiverEmail' => 'john@example.com', 'receiverAddress' => '10 Downing St',
            'receiverPostalCode' => 'SW1A 2AA', 'receiverCountry' => 'GB',
            'description' => 'Certificate', 'sendLink' => true,
        ], $overrides);

        $page = Livewire::test(BookAirCargo::class)
            ->call('setType', $overrides['type'] ?? 'Document')
            ->call('setWeight', $overrides['kg'] ?? '1')
            ->call('setPickup', $overrides['pickup'] ?? false);

        foreach (array_diff_key($values, array_flip(['type', 'kg', 'pickup'])) as $property => $value) {
            $page->set($property, $value);
        }

        return $page;
    }

    private function book(array $overrides = []): StaffBooking
    {
        $this->page($overrides + ['dropoffDate' => now()->addDays(2)->toDateString()])
            ->call('create')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

        return StaffBooking::sole();
    }

    private function seerbitCharges(?array &$charged): void
    {
        $this->mock(SeerbitPaymentService::class, function ($mock) use (&$charged) {
            $mock->shouldReceive('initializePayment')->andReturnUsing(function (array $payment) use (&$charged) {
                $charged = $payment;

                return ['redirect_link' => 'https://pay.example.test/checkout'];
            });
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]);
        });
    }

    // The website's prices

    public function test_the_website_charges_the_price_tables_whatever_the_form_says(): void
    {
        $charged = null;
        $this->seerbitCharges($charged);

        $this->post(route('air.cargo.post'), $this->websiteForm())->assertRedirect('https://pay.example.test/checkout');

        $this->assertEquals(self::UK_DOCUMENT_1KG, (float) $charged['amount']);
        $shipment = AirCargoModel::sole();
        $this->assertEquals(self::UK_DOCUMENT_1KG, (float) $shipment->total_price);
        $this->assertSame('United Kingdom', $shipment->shipping_to);
        $this->assertSame($charged['paymentReference'], $shipment->transaction_ref);

        $this->get(route('air.cargo.callback', ['reference' => $charged['paymentReference']]))->assertRedirect(route('air.cargo.success'));

        $this->assertSame('successful', $shipment->fresh()->payment_status);
        Mail::assertSent(ShipmentMail::class, 1);
    }

    public function test_the_website_adds_the_pickup_price_for_the_area(): void
    {
        $charged = null;
        $this->seerbitCharges($charged);

        $this->post(route('air.cargo.post'), $this->websiteForm([
            'shipment_type' => 'Package', 'package_weight' => '1', 'pDescription' => 'Shoes', 'nBus_stop' => 'ikeja',
            'pick_upAddress' => '1 Allen Avenue', 'pick_upDate' => '2027-01-09', 'pick-Upshipment' => '1', 'totalPrice2' => '1', 'price2' => '1',
        ]))->assertRedirect('https://pay.example.test/checkout');

        $this->assertEquals(self::UK_PACKAGE_1KG + self::IKEJA_PICKUP, (float) $charged['amount']);
    }

    public function test_the_website_refuses_a_destination_or_weight_with_no_price(): void
    {
        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));

        // France is Zone 4, which has no prices here
        $this->post(route('air.cargo.post'), $this->websiteForm(['country1' => 'FR']))->assertRedirect()->assertSessionHas('error');
        $this->post(route('air.cargo.post'), $this->websiteForm(['doc_weight' => '7']))->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, AirCargoModel::count());
    }

    public function test_a_payment_for_something_else_does_not_mark_the_shipment_paid(): void
    {
        $charged = null;
        $this->seerbitCharges($charged);
        $this->post(route('air.cargo.post'), $this->websiteForm());

        // Any other verified payment, with this shipment still in the session
        $this->get(route('air.cargo.callback', ['reference' => 'ACGSOMETHINGELSE']))->assertRedirect(route('air.cargo'));

        $this->assertSame('Pending', AirCargoModel::sole()->payment_status);
        Mail::assertNotSent(ShipmentMail::class);
    }

    public function test_a_shipment_started_before_the_change_still_completes(): void
    {
        $this->seerbitCharges($charged);
        $shipment = AirCargoModel::create([
            'shipping_id' => 'TWAC07OLD', 'fullname' => 'Ada Obi', 'email' => 'ada@example.com', 'phone' => '0803', 'shipping_to' => 'United Kingdom',
            'shipment_type' => 'Document', 'price' => '66000', 'total_price' => '66000', 'payment_status' => 'Pending',
        ]);

        $this->withSession(['shipData' => ['shipping_id' => 'TWAC07OLD']])
            ->get(route('air.cargo.callback', ['reference' => 'ACGOLDPAYMENT']))
            ->assertRedirect(route('air.cargo.success'));

        $this->assertSame('successful', $shipment->fresh()->payment_status);
        $this->assertSame('ACGOLDPAYMENT', $shipment->fresh()->transaction_ref);
    }

    public function test_the_website_form_still_lists_each_country_in_its_zone(): void
    {
        $this->get(route('air.cargo.international'))->assertOk()
            ->assertSee('<option value="GB" data-category="zone_1" data-name="United Kingdom">United Kingdom</option>', false)
            ->assertSee('<option value="NG" data-category="zone_2" data-name="Nigeria">Nigeria</option>', false)
            ->assertSee('"Mainland 2":{"price":4000', false);
    }

    // Booking from the admin

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookAirCargo::getUrl())->assertOk();

        $this->actingAs($this->staff('customer-support'))->get(BookAirCargo::getUrl())->assertForbidden();
    }

    public function test_staff_create_a_shipment_at_the_server_price_with_pickup(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        $this->page(['pickup' => true, 'pickupArea' => 'Ikeja', 'pickupAddress' => '1 Allen Avenue', 'pickupDate' => now()->addDay()->toDateString()])
            ->assertSee('Price breakdown')
            ->call('create')->assertHasNoErrors();

        $shipment = StaffBooking::sole()->bookable;
        $this->assertInstanceOf(AirCargoModel::class, $shipment);
        $this->assertSame('Pending', $shipment->payment_status);
        $this->assertEquals(self::UK_DOCUMENT_1KG, (float) $shipment->price);
        $this->assertEquals(self::UK_DOCUMENT_1KG + self::IKEJA_PICKUP, (float) $shipment->total_price);
        $this->assertSame('United Kingdom', $shipment->shipping_to);
        $this->assertStringStartsWith('ACG', $shipment->transaction_ref);
        Storage::assertExists('public/shipments/'.$shipment->shipment_details);
        $this->assertSame($staff->id, StaffBooking::sole()->created_by);

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(ShipmentMail::class);
    }

    public function test_a_pickup_needs_an_area_on_the_list_and_a_drop_off_needs_a_date(): void
    {
        $this->actingAs($this->staff());

        $this->page(['pickup' => true, 'pickupArea' => 'Somewhere else', 'pickupAddress' => 'x', 'pickupDate' => now()->addDay()->toDateString()])
            ->call('create')->assertHasErrors('pickupArea');
        $this->page()->call('create')->assertHasErrors('dropoffDate');
        $this->page(['receiverCountry' => 'XX', 'dropoffDate' => now()->addDay()->toDateString()])->call('create')->assertHasErrors('receiverCountry');

        $this->assertSame(0, AirCargoModel::count());
    }

    public function test_a_destination_with_no_price_is_refused(): void
    {
        $this->actingAs($this->staff());

        $this->page(['receiverCountry' => 'FR', 'dropoffDate' => now()->addDay()->toDateString()])
            ->assertSee('no price for this destination')
            ->call('create')
            ->assertNoRedirect();

        $this->assertSame(0, AirCargoModel::count());
    }

    // Paying

    public function test_the_sender_pays_through_the_link_and_is_confirmed_once(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book();
        $shipment = $staffBooking->bookable;
        auth()->logout();

        (new StaffBookingPaymentLinkMail($staffBooking))->assertSeeInHtml('Document to United Kingdom')->assertSeeInHtml('66,000');

        $this->get($staffBooking->payUrl())->assertOk()
            ->assertSee('Document to United Kingdom')
            ->assertSee($shipment->shipping_id)
            ->assertSee('66,000');

        $charged = null;
        $this->seerbitCharges($charged);
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect('https://pay.example.test/checkout');

        $this->assertEquals(self::UK_DOCUMENT_1KG, (float) $charged['amount']);
        $this->assertSame(route('air.cargo.callback'), $charged['callbackUrl']);
        $this->assertSame($shipment->transaction_ref, $charged['paymentReference']);

        foreach ([1, 2] as $visit) {
            $this->get(route('air.cargo.callback', ['reference' => $shipment->transaction_ref]))->assertRedirect(route('air.cargo.success'));
        }

        $this->assertSame('successful', $shipment->fresh()->payment_status);
        Mail::assertSent(ShipmentMail::class, 1);
        $this->get($staffBooking->payUrl())->assertSee('This booking is paid');
    }

    public function test_finance_records_a_payment_and_a_cancelled_shipment_cannot_be_paid(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->book(['sendLink' => false]);
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)
            ->assertSee('Ada Obi')
            ->assertSee('Pending')
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'cash', 'reference' => 'RCPT-1'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('successful', $staffBooking->bookable->fresh()->payment_status);
        Mail::assertSent(ShipmentMail::class, 1);

        StaffBooking::query()->delete();
        AirCargoModel::query()->delete();
        $this->actingAs($this->staff());
        $second = $this->book();
        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $second);
        $this->assertSame('cancelled', strtolower($second->bookable->fresh()->payment_status));

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));
        $this->post(route('staff-bookings.pay.start', $second->pay_token))->assertRedirect($second->payUrl());
        $this->get($second->payUrl())->assertSee('no longer available');
    }
}
