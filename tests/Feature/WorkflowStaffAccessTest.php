<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\RelationManagers\StaffRelationManager;
use App\Filament\Resources\ExchangeRates\Pages\EditExchangeRate;
use App\Filament\Resources\FlightBookings\Pages\ViewFlightBooking;
use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\StaffResource;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Workflow phase 0: staff belong to departments, only the CEO manages them,
 * only Finance moves money, and everything anyone does leaves a receipt.
 */
class WorkflowStaffAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_four_departments_exist_and_only_it_lands_in_the_it_linear_team(): void
    {
        $this->assertEqualsCanonicalizing(
            ['operations', 'finance', 'customer-support', 'it'],
            Department::query()->pluck('slug')->all(),
        );
        $this->assertSame(['it'], Department::query()->where('linear_team', 'it')->pluck('slug')->all());
    }

    public function test_visas_and_ground_staff_queues_and_escalations_move_into_operations(): void
    {
        $migration = 'database/migrations/2026_10_12_000000_merge_flights_visas_and_ground_into_operations.php';
        $this->artisan('migrate:rollback', ['--path' => $migration])->assertSuccessful();

        $visas = Department::query()->where('slug', 'visas')->value('id');
        $ground = Department::query()->where('slug', 'ground-airport')->value('id');
        $officer = User::factory()->create(['department_id' => $visas]);
        $driverDesk = User::factory()->create(['department_id' => $ground]);
        $itemId = DB::table('work_items')->insertGetId([
            'subject_type' => 'test', 'subject_id' => 1, 'service' => 'visas', 'stage' => 'submitted',
            'state' => 'open', 'department_id' => $visas, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $escalationId = DB::table('escalations')->insertGetId([
            'work_item_id' => $itemId, 'mode' => 'help', 'status' => 'open', 'to_department_id' => $ground,
            'reason' => 'Help', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('migrate')->assertSuccessful();

        $operations = Department::query()->where('slug', 'operations')->firstOrFail();
        $this->assertSame('Operations', $operations->name);
        $this->assertSame([$operations->id, $operations->id], [$officer->fresh()->department_id, $driverDesk->fresh()->department_id]);
        $this->assertSame($operations->id, DB::table('work_items')->where('id', $itemId)->value('department_id'));
        $this->assertSame($operations->id, DB::table('escalations')->where('id', $escalationId)->value('to_department_id'));
        $this->assertFalse(Department::query()->whereIn('slug', ['visas', 'ground-airport', 'flights'])->exists());
        $this->assertTrue($officer->fresh()->canOperateVisas(), 'Operations works visas now.');
    }

    public function test_department_staff_can_sign_in_and_people_without_one_cannot(): void
    {
        $this->actingAs($this->staff('operations'))->get('/admin')->assertOk();

        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_a_deactivated_member_of_staff_is_locked_out(): void
    {
        $staff = $this->staff('operations', ['deactivated_at' => now()]);

        $this->actingAs($staff)->get('/admin')->assertForbidden();
    }

    public function test_only_the_admin_manages_staff_departments_and_the_activity_log(): void
    {
        $staff = $this->staff('finance');

        foreach ([StaffResource::getUrl('index'), DepartmentResource::getUrl('index'), ActivityLogResource::getUrl('index')] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
            $this->actingAs($this->admin())->get($url)->assertOk();
        }
    }

    public function test_a_department_page_lists_its_staff_and_lets_the_admin_add_more(): void
    {
        $finance = Department::query()->where('slug', 'finance')->firstOrFail();
        $member = $this->staff('finance', ['name' => 'Funmi Ledger']);

        $this->actingAs($this->admin());
        $this->get(DepartmentResource::getUrl('edit', ['record' => $finance]))->assertOk()->assertSee('Finance');

        Livewire::test(StaffRelationManager::class, ['ownerRecord' => $finance, 'pageClass' => EditDepartment::class])
            ->assertSee('Funmi Ledger')
            ->callTableAction('create', data: ['name' => 'New Cashier', 'email' => 'cashier@travelwheel.test', 'password' => 'a-long-password'])
            ->assertHasNoTableActionErrors();

        $this->assertSame($finance->id, User::query()->where('email', 'cashier@travelwheel.test')->value('department_id'));
        $this->assertTrue($member->fresh()->canHandleMoney());
    }

    public function test_the_admin_adds_staff_to_a_department_and_it_is_on_record(): void
    {
        $admin = $this->admin();
        $visas = Department::query()->where('slug', 'operations')->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(CreateStaff::class)
            ->fillForm([
                'name' => 'Ada Officer',
                'email' => 'ada@travelwheel.test',
                'department_id' => $visas->id,
                'password' => 'a-long-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ada = User::query()->where('email', 'ada@travelwheel.test')->firstOrFail();
        $this->assertSame($visas->id, $ada->department_id);
        $this->assertFalse($ada->isAdmin());
        $this->assertTrue($ada->canOperateVisas());

        $log = ActivityLog::query()->where('action', 'user.created')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertTrue($log->subject->is($ada));
        $this->assertSame($visas->id, $log->properties['values']['department_id']);
        $this->assertSame('[redacted]', $log->properties['values']['password']);
    }

    public function test_every_action_run_in_the_admin_leaves_a_receipt(): void
    {
        $admin = $this->admin();
        $leaver = $this->staff('operations');

        $this->actingAs($admin);
        Livewire::test(ListStaff::class)->callTableAction('deactivate', $leaver);

        $this->assertNotNull($leaver->fresh()->deactivated_at);
        $log = ActivityLog::query()->where('action', 'deactivate')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertTrue($log->subject->is($leaver));
        $this->assertStringContainsString($leaver->name, $log->description);
    }

    public function test_a_price_edited_on_an_edit_page_is_on_record_with_old_and_new_values(): void
    {
        $finance = $this->staff('finance');
        $rate = ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);

        $this->actingAs($finance);
        Livewire::test(EditExchangeRate::class, ['record' => $rate->getRouteKey()])
            ->fillForm(['rate' => 1650])
            ->call('save')
            ->assertHasNoFormErrors();

        $log = ActivityLog::query()->where('action', 'exchange_rate.updated')->firstOrFail();
        $this->assertSame($finance->id, $log->user_id);
        $this->assertEquals(1500, $log->properties['before']['rate']);
        $this->assertEquals(1650, $log->properties['after']['rate']);
        $this->assertStringContainsString('USD', $log->description);
    }

    public function test_the_receipt_names_the_department_the_person_was_in_at_the_time(): void
    {
        $finance = $this->staff('finance');
        $this->actingAs($finance);

        $log = ActivityLog::record('test', 'Something happened');
        $finance->update(['department_id' => Department::query()->where('slug', 'operations')->value('id')]);

        $this->assertSame(Department::query()->where('slug', 'finance')->value('id'), $log->fresh()->department_id);
    }

    public function test_passwords_never_reach_the_log(): void
    {
        $this->actingAs($this->admin());

        $log = ActivityLog::record('test', 'Typed a password', properties: ['input' => ['password' => 'hunter22', 'note' => 'ok']]);

        $this->assertSame('[redacted]', $log->properties['input']['password']);
        $this->assertSame('ok', $log->properties['input']['note']);
    }

    public function test_only_finance_can_mark_a_bank_transfer_paid(): void
    {
        $booking = $this->awaitingTransfer();

        $this->actingAs($this->staff('operations'));
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('markBankTransferPaid');

        // Finance staff see it once the booking is theirs.
        $finance = $this->staff('finance');
        $this->actingAs($finance);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('markBankTransferPaid');
        app(\App\Workflow\WorkItemService::class)->claim($booking->workItem()->first(), $finance);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('markBankTransferPaid');

        $this->actingAs($this->admin());
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('markBankTransferPaid');
    }

    public function test_legacy_finance_role_still_counts_as_finance_until_moved(): void
    {
        $this->assertTrue(User::factory()->create(['visa_role' => 'finance'])->canHandleMoney());
        $this->assertFalse(User::factory()->create(['visa_role' => 'support'])->canHandleMoney());
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function staff(string $department, array $attributes = []): User
    {
        return User::factory()->create([
            'department_id' => Department::query()->where('slug', $department)->value('id'),
            ...$attributes,
        ]);
    }

    private function awaitingTransfer(): FlightBooking
    {
        return FlightBooking::create([
            'booking_ref' => 'TW-BT-'.strtoupper(str()->random(6)),
            'unique_id' => 'TR123456',
            'fare_source_code' => 'fsc_test',
            'fare_type' => 'Public',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'awaiting_bank_transfer',
            'booking_status' => 'on_hold',
            'ticket_ordered' => false,
            'total_price' => 500000,
            'currency' => 'NGN',
            'contact_email' => 'traveller@example.test',
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'passengers_snapshot' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger']],
            'flight_snapshot' => ['currency' => 'NGN', 'segments' => [['from' => 'LOS', 'to' => 'ABV']]],
        ]);
    }
}
