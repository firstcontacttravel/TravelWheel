<?php

namespace Tests\Feature;

use App\Filament\Resources\CarHires\Pages\ListCarHires;
use App\Filament\Resources\CarHires\Pages\ViewCarHire;
use App\Filament\Resources\InsurancePurchases\Pages\ViewInsurancePurchase;
use App\Models\AirCargoModel;
use App\Models\CarHire;
use App\Models\Department;
use App\Models\InsurancePurchase;
use App\Models\LoungeBooking;
use App\Models\ProtocolBooking;
use App\Models\SupportExtraLuggage;
use App\Models\SupportFlightAssist;
use App\Models\SupportVisaConfirmation;
use App\Models\SupportYellowCard;
use App\Models\Transfer;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Workflow\FulfilmentService;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Workflow phase 4: every other paid service gets the same owner, steps and
 * history as flights and visas, with payment and fulfilment kept apart.
 */
class WorkflowServiceBookingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** @return array<string, array{string, string, string}> */
    public static function services(): array
    {
        return [
            'car hire' => ['carHire', 'car_hire', 'operations'],
            'transfer' => ['transfer', 'transfers', 'operations'],
            'lounge' => ['lounge', 'lounge', 'operations'],
            'protocol' => ['protocol', 'protocol', 'operations'],
            'air cargo' => ['cargo', 'air_cargo', 'operations'],
            'yellow card' => ['yellowCard', 'yellow_card', 'customer-support'],
            'extra luggage' => ['extraLuggage', 'extra_luggage', 'customer-support'],
            'flight assist' => ['flightAssist', 'flight_assist', 'customer-support'],
            'visa confirmation' => ['visaConfirmation', 'visa_confirmation', 'customer-support'],
        ];
    }

    #[DataProvider('services')]
    public function test_a_paid_booking_lands_in_its_departments_queue_at_the_first_step(string $factory, string $service, string $department): void
    {
        $booking = $this->{$factory}(paid: true);
        $item = $booking->workItem;

        $this->assertNotNull($item, "{$service} booking has no work item.");
        $this->assertSame($service, $item->service);
        $this->assertSame('new', $item->stage);
        $this->assertSame(WorkItem::STATE_OPEN, $item->state);
        $this->assertSame(Department::query()->where('slug', $department)->value('id'), $item->department_id);
    }

    #[DataProvider('services')]
    public function test_an_unpaid_booking_waits_on_the_customer(string $factory): void
    {
        $item = $this->{$factory}(paid: false)->workItem;

        $this->assertSame('awaiting_payment', $item->stage);
        $this->assertSame(WorkItem::STATE_WAITING, $item->state);
    }

    // ── Moving through the steps ─────────────────────────────────────────

    public function test_a_car_hire_moves_from_driver_to_trip_without_touching_its_payment(): void
    {
        $staff = $this->staff('operations');
        $booking = $this->carHire(paid: true);

        $booking->update(['driver_assigned' => true]);
        $this->assertSame('driver_assigned', $booking->workItem()->first()->stage, 'Assigning a driver moves it on by itself.');

        $this->actingAs($staff);
        app(FulfilmentService::class)->advance($booking->fresh(), 'completed', $staff, 'Customer dropped at the airport');

        $booking->refresh();
        $this->assertSame('completed', $booking->fulfilment_status);
        $this->assertSame('paid', $booking->payment_status, 'Payment and fulfilment are separate now.');
        $item = $booking->workItem()->first();
        $this->assertSame(WorkItem::STATE_DONE, $item->state);
        $this->assertSame($staff->id, $item->events()->where('type', WorkItemEvent::STAGE_CHANGED)->latest('id')->value('user_id'));
        $this->assertSame('Customer dropped at the airport', $item->events()->where('type', WorkItemEvent::NOTE)->value('body'));
    }

    public function test_a_step_that_is_not_next_is_refused(): void
    {
        $booking = $this->cargo(paid: true);

        $this->expectException(InvalidArgumentException::class);
        app(FulfilmentService::class)->advance($booking, 'delivered', $this->staff('operations'));
    }

    public function test_an_unpaid_booking_cannot_be_moved_on(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(FulfilmentService::class)->advance($this->yellowCard(paid: false), 'completed', $this->staff('customer-support'));
    }

    public function test_cancelling_needs_a_reason_and_records_it(): void
    {
        $staff = $this->staff('customer-support');
        $booking = $this->extraLuggage(paid: true);

        try {
            app(FulfilmentService::class)->cancel($booking, $staff, ' ');
            $this->fail('Cancelled without a reason.');
        } catch (InvalidArgumentException) {
        }

        app(FulfilmentService::class)->cancel($booking->fresh(), $staff, 'Customer changed airline');

        $item = $booking->workItem()->first();
        $this->assertSame('cancelled', $item->stage);
        $this->assertSame('Cancelled: Customer changed airline', $item->events()->where('type', WorkItemEvent::NOTE)->value('body'));
    }

    // ── Payment ──────────────────────────────────────────────────────────

    public function test_only_finance_can_mark_a_payment_received(): void
    {
        $booking = $this->transfer(paid: false);

        try {
            app(FulfilmentService::class)->markPaid($booking, $this->staff('operations'), 'Transfer ref 123');
            $this->fail('Someone outside Finance marked it paid.');
        } catch (InvalidArgumentException) {
        }

        // Finance works it like anyone else: claim it, then record the payment.
        $finance = $this->staff('finance');
        app(WorkItemService::class)->claim($booking->workItem()->first(), $finance);
        app(FulfilmentService::class)->markPaid($booking->fresh(), $finance, 'Transfer ref 123, NGN 45,000');

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('new', $booking->workItem()->first()->stage);
        $this->assertStringContainsString('Transfer ref 123', $booking->workItem()->first()->events()->where('type', WorkItemEvent::NOTE)->value('body'));
    }

    public function test_flight_assist_billed_with_a_flight_counts_as_paid(): void
    {
        $booking = $this->flightAssist(paid: false);
        $booking->update(['payment_status' => 'billed_with_main_fee']);

        $this->assertSame('new', $booking->workItem()->first()->stage);
    }

    // ── Insurance ────────────────────────────────────────────────────────

    public function test_a_paid_insurance_with_no_policy_is_work_for_customer_support(): void
    {
        $failed = $this->insurance('Failed');
        $issued = $this->insurance('Successful');

        $this->assertSame('issue_failed', $failed->workItem->stage);
        $this->assertSame(WorkItem::STATE_OPEN, $failed->workItem->state);
        $this->assertSame(WorkItem::STATE_DONE, $issued->workItem->state);

        $staff = $this->staff('customer-support');
        app(FulfilmentService::class)->advance($failed, 'issued', $staff, 'Issued by phone with Leadway, policy LW-889');

        $this->assertSame('issued', $failed->workItem()->first()->stage);
        $this->assertSame('Failed', $failed->fresh()->status, 'What the insurer said is kept.');
    }

    // ── Due dates ────────────────────────────────────────────────────────

    public function test_the_service_date_is_the_due_time_while_work_is_open(): void
    {
        // With no step allowance (phase 5), the service date alone decides.
        $deadlines = app(\App\Workflow\Deadlines::class);
        $settings = $deadlines->settings();
        $settings['steps']['lounge']['new'] = null;
        $deadlines->save($settings);

        $lounge = $this->lounge(paid: true, travelDate: now()->addDays(3)->format('Y-m-d'), time: '14:30');

        $this->assertSame(now()->addDays(3)->format('Y-m-d').' 14:30', $lounge->workItem->due_at->timezone('Africa/Lagos')->format('Y-m-d H:i'));
    }

    public function test_an_unreadable_date_means_no_due_time_not_an_error(): void
    {
        // Older rows hold whatever the form sent; the model's date cast would
        // refuse to even read this one.
        $id = DB::table('lounge_service')->insertGetId($this->loungeRow(['travel_date' => 'sometime next week']));

        $item = app(WorkItemService::class)->sync(LoungeBooking::query()->findOrFail($id));

        $this->assertSame('new', $item->stage);
        // No service date to go by: the step's own allowance applies alone.
        $this->assertSame(
            $item->stage_entered_at->copy()->addMinutes(\App\Workflow\Deadlines::DEFAULT_STEPS['lounge']['new'])->toIso8601String(),
            $item->due_at->toIso8601String(),
        );
    }

    // ── Existing data ────────────────────────────────────────────────────

    public function test_fulfilment_words_in_the_payment_column_move_to_fulfilment(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_10_10_000000_add_fulfilment_status_to_service_bookings.php'])->assertSuccessful();

        $completed = DB::table('car_hires')->insertGetId($this->carHireRow(['payment_status' => 'completed']));
        $confirmed = DB::table('car_hires')->insertGetId($this->carHireRow(['payment_status' => 'confirmed', 'driver_assigned' => true]));
        $cancelled = DB::table('car_hires')->insertGetId($this->carHireRow(['payment_status' => 'cancelled']));
        $lounge = DB::table('lounge_service')->insertGetId($this->loungeRow(['status' => 'Cancelled']));

        $this->artisan('migrate')->assertSuccessful();

        $this->assertSame(['completed', 'paid'], [DB::table('car_hires')->where('id', $completed)->value('fulfilment_status'), DB::table('car_hires')->where('id', $completed)->value('payment_status')]);
        $this->assertSame(['driver_assigned', 'paid'], [DB::table('car_hires')->where('id', $confirmed)->value('fulfilment_status'), DB::table('car_hires')->where('id', $confirmed)->value('payment_status')]);
        $this->assertSame('cancelled', DB::table('car_hires')->where('id', $cancelled)->value('fulfilment_status'));
        $this->assertSame('cancelled', DB::table('lounge_service')->where('id', $lounge)->value('fulfilment_status'));
    }

    // ── Screens ──────────────────────────────────────────────────────────

    public function test_the_booking_page_shows_the_work_panel_and_moves_the_booking_on(): void
    {
        $booking = $this->carHire(paid: true);
        $staff = $this->staff('operations');

        $this->actingAs($staff);
        $this->get(\App\Filament\Resources\CarHires\CarHireResource::getUrl('view', ['record' => $booking]))
            ->assertOk()
            ->assertSee('Assign a driver')
            ->assertSee('Unclaimed');

        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workAdvance')
            ->assertActionHidden('workMarkPaid')
            ->callAction('workAdvance', data: ['step' => 'completed'])
            ->assertHasNoActionErrors();

        $this->assertSame('completed', $booking->fresh()->fulfilment_status);
    }

    public function test_mark_paid_is_hidden_from_everyone_but_finance(): void
    {
        $booking = $this->carHire(paid: false);

        $this->actingAs($this->staff('operations'));
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])->assertActionHidden('workMarkPaid');

        $finance = $this->staff('finance');
        app(WorkItemService::class)->claim($booking->workItem()->first(), $finance);
        $this->actingAs($finance);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])->assertActionVisible('workMarkPaid');
    }

    public function test_the_insurance_page_offers_to_record_the_policy(): void
    {
        $booking = $this->insurance('Failed');

        $this->actingAs($this->staff('customer-support'));
        Livewire::test(ViewInsurancePurchase::class, ['record' => $booking->getRouteKey()])
            ->callAction('workAdvance', data: ['step' => 'issued', 'note' => 'Policy issued manually'])
            ->assertHasNoActionErrors();

        $this->assertSame('issued', $booking->fresh()->fulfilment_status);
    }

    public function test_the_service_list_filters_to_my_work(): void
    {
        $me = $this->staff('operations');
        $mine = $this->carHire(paid: true);
        $other = $this->carHire(paid: true);
        app(WorkItemService::class)->claim($mine->workItem, $me);

        $this->actingAs($me);
        Livewire::test(ListCarHires::class)
            ->filterTable('work', 'mine')
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_the_backfill_covers_every_service(): void
    {
        foreach (array_column(self::services(), 0) as $factory) {
            $this->{$factory}(paid: true);
        }
        $this->insurance('Failed');
        WorkItem::query()->delete();

        $this->artisan('workflow:backfill')->assertSuccessful();

        $this->assertSame(10, WorkItem::query()->count());
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function staff(string $department): User
    {
        return User::factory()->create(['department_id' => Department::query()->where('slug', $department)->value('id'), 'is_department_head' => true]);
    }

    private function carHire(bool $paid): CarHire
    {
        return CarHire::create($this->carHireRow(['payment_status' => $paid ? 'paid' : 'pending']));
    }

    private function carHireRow(array $overrides = []): array
    {
        return array_merge([
            'car_type' => 'Sedan', 'category' => 'standard', 'car_model' => 'Corolla', 'full_name' => 'Test Customer',
            'email' => 'customer@example.test', 'phone_number' => '08000000000', 'passengers' => 1,
            'pickup_location' => 'Ikeja', 'dropoff_location' => 'Lekki', 'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '09:00', 'amount' => 45000, 'payment_option' => 'card', 'payment_reference' => 'CAR-'.Str::random(8),
            'payment_status' => 'pending', 'driver_assigned' => false, 'created_at' => now(), 'updated_at' => now(),
        ], $overrides);
    }

    private function transfer(bool $paid): Transfer
    {
        return Transfer::create([
            'vehicle_type' => 'Sedan', 'vehicle_name' => 'Camry', 'pickup_location' => 'Airport', 'dropoff_location' => 'Hotel', 'distance_km' => 25,
            'pickup_date' => now()->addDay()->format('Y-m-d'), 'pickup_time' => '10:00', 'full_name' => 'Test Customer',
            'email' => 'customer@example.test', 'phone_number' => '08000000000', 'passengers' => 1, 'amount' => 30000,
            'payment_option' => 'card', 'payment_reference' => 'TRF-'.Str::random(8), 'payment_status' => $paid ? 'paid' : 'pending',
            'driver_assigned' => false,
        ]);
    }

    private function lounge(bool $paid, ?string $travelDate = null, ?string $time = null): LoungeBooking
    {
        return LoungeBooking::create($this->loungeRow([
            'status' => $paid ? 'Successful' : 'Pending',
            'travel_date' => $travelDate ?? now()->addDays(5)->format('Y-m-d'),
            'd_time' => $time ?? '08:00',
        ]));
    }

    private function loungeRow(array $overrides = []): array
    {
        return array_merge([
            'lounge_name' => 'Test Lounge', 'payment_option' => 'card', 'fullname' => 'Test Customer', 'service' => 'Departure',
            'email' => 'customer@example.test', 'phone_no' => '08000000000', 'terminal' => 'T1', 'nop' => 1, 'noa' => 1, 'noc' => 0,
            'noi' => 0, 'travel_date' => now()->addDays(5)->format('Y-m-d'), 'ticket_no' => 'TK1', 'd_time' => '08:00', 'amount' => 50000,
            'status' => 'Successful', 'trans_id' => 'T-'.Str::random(6), 'ref_id' => 'LNG-'.Str::random(6),
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides);
    }

    private function protocol(bool $paid): ProtocolBooking
    {
        return ProtocolBooking::create([
            'paymentoption' => 'card', 'fullname' => 'Test Customer', 'package' => 'Standard', 'service' => 'Departure',
            'passenger' => 1, 'email' => 'customer@example.test', 'phone' => '08000000000',
            'travel_date' => now()->addDays(4)->format('Y-m-d'), 'state' => 'Lagos', 'airline' => 'Air Peace', 'airport' => 'LOS',
            'd_time' => '07:00', 'service_type' => 'Standard', 'status' => $paid ? 'Successful' : 'Pending', 'amount' => 60000,
            'trans_id' => 'T-'.Str::random(6), 'ref_id' => 'PRT-'.Str::random(6),
        ]);
    }

    private function cargo(bool $paid): AirCargoModel
    {
        return AirCargoModel::create([
            'shipping_id' => 'SHP-'.Str::random(6), 'fullname' => 'Test Customer', 'email' => 'customer@example.test',
            'phone' => '08000000000', 'shipping_to' => 'United Kingdom', 'shipment_type' => 'document', 'price' => 20000,
            'total_price' => 20000, 'payment_status' => $paid ? 'successful' : 'Pending', 'transaction_ref' => 'T-'.Str::random(6),
        ]);
    }

    private function yellowCard(bool $paid): SupportYellowCard
    {
        return SupportYellowCard::create($this->supportRow($paid, ['service_type' => 'new', 'full_name' => 'Test Customer', 'phone_number' => '08000000000', 'data_page' => 'uploads/data-page.jpg', 'home_address' => '1 Test Street', 'delivery_address' => '1 Test Street']));
    }

    private function extraLuggage(bool $paid): SupportExtraLuggage
    {
        return SupportExtraLuggage::create($this->supportRow($paid, ['full_name' => 'Test Customer', 'airline' => 'Air Peace', 'airline_category' => 'local', 'data_page' => 'uploads/data-page.jpg', 'ticket' => 'uploads/ticket.pdf', 'contact_number' => '08000000000']));
    }

    private function flightAssist(bool $paid): SupportFlightAssist
    {
        return SupportFlightAssist::create($this->supportRow($paid, [
            'request_type' => 'change', 'booking_source' => 'travelwheel', 'name_on_ticket' => 'Test Customer', 'phone' => '08000000000',
            'travel_date_oneway' => now()->addDays(6)->format('Y-m-d'),
        ]));
    }

    private function visaConfirmation(bool $paid): SupportVisaConfirmation
    {
        return SupportVisaConfirmation::create($this->supportRow($paid, ['full_name' => 'Test Customer', 'phone_number' => '08000000000', 'visa_file' => 'uploads/visa.pdf']));
    }

    private function supportRow(bool $paid, array $fields): array
    {
        return array_merge([
            'email' => 'customer@example.test', 'payment_option' => 'card', 'payment_reference' => 'SUP-'.Str::random(8),
            'payment_status' => $paid ? 'paid' : 'pending', 'amount' => 15000,
        ], $fields);
    }

    private function insurance(string $status): InsurancePurchase
    {
        return InsurancePurchase::create([
            'trans_id' => 'T-'.Str::random(6), 'ref_id' => 'INS-'.Str::random(6), 'surname' => 'Customer', 'firstname' => 'Test',
            'email' => 'customer@example.test', 'phone_no' => '08000000000', 'status' => $status, 't_amount' => 25000,
        ]);
    }
}
