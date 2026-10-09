<?php

namespace Tests\Feature;

use App\Filament\Pages\Workload;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\FlightBooking;
use App\Models\User;
use App\Support\Admin\WorkloadReport;
use App\Workflow\DeadlineMonitor;
use App\Workflow\EscalationService;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Workflow phase 6: the Workload report — time in each step, deadlines met,
 * and work by department and by person.
 */
class WorkflowWorkloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->freezeSecond();
    }

    public function test_time_in_each_step_is_measured_from_the_history(): void
    {
        $booking = $this->booking(['payment_status' => 'awaiting_bank_transfer']);
        $this->travel(90)->minutes();
        $booking->update(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        $this->travel(30)->minutes();
        $booking->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);

        $steps = collect($this->report()['steps'])->keyBy('stage');

        $this->assertSame(90.0, $steps['Confirm bank transfer']['median_minutes']);
        $this->assertSame(0, $steps['Confirm bank transfer']['over_allowance'], 'Within its 2 hours.');
        $this->assertSame(30.0, $steps['Ready to ticket']['median_minutes']);
        $this->assertSame(1, $steps['Ready to ticket']['count']);
        $this->assertArrayNotHasKey('Ticketed', $steps->all(), 'A finished step is not time anyone spent.');
    }

    public function test_a_step_that_ran_over_its_deadline_is_counted(): void
    {
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        $this->travel(3)->hours();
        $booking->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);

        $step = collect($this->report()['steps'])->firstWhere('stage', 'Ready to ticket');

        $this->assertSame(1, $step['over_allowance']);
        $this->assertSame(60, $step['allowance_minutes']);
    }

    public function test_the_summary_counts_completions_on_time_and_late(): void
    {
        $onTime = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        $late = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        $onTime->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);

        $this->travel(2)->hours();
        app(DeadlineMonitor::class)->run();
        $late->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);
        $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);

        $summary = $this->report()['summary'];

        $this->assertSame(2, $summary['completed']);
        $this->assertSame(50.0, $summary['on_time_rate']);
        $this->assertSame(1, $summary['missed']);
        $this->assertSame(1, $summary['open']);
        $this->assertSame(1, $summary['unclaimed']);
    }

    public function test_a_booking_that_was_already_finished_when_tracking_began_is_not_a_completion(): void
    {
        // Every old ticketed booking the backfill picks up looks like this.
        $this->booking(['payment_status' => 'paid', 'booking_status' => 'ticketed', 'ticket_ordered' => true]);

        $this->assertSame(0, $this->report()['summary']['completed']);
    }

    public function test_work_is_credited_to_the_people_who_did_it(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        app(WorkItemService::class)->claim($booking->workItem, $ada);
        $escalation = app(EscalationService::class)->raise($booking->workItem()->first(), $ada, Escalation::MODE_HELP, null, $bola, 'Check the payment');
        app(EscalationService::class)->resolve($escalation, $bola, 'Checked');
        $this->actingAs($ada);
        ActivityLog::record('test', 'Did something', $booking);
        $booking->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);

        $people = collect($this->report()['people'])->keyBy('name');

        $this->assertSame([1, 1, 1, 0], [$people['Ada']['completed'], $people['Ada']['claimed'], $people['Ada']['escalations_raised'], $people['Ada']['escalations_resolved']]);
        $this->assertSame(1, $people['Ada']['actions']);
        $this->assertSame([0, 1, 'Finance'], [$people['Bola']['completed'], $people['Bola']['escalations_resolved'], $people['Bola']['department']]);
    }

    public function test_departments_show_their_queue_and_escalation_response(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $this->booking(['payment_status' => 'awaiting_bank_transfer']);
        $owned = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        app(WorkItemService::class)->claim($owned->workItem, $ada);

        $escalation = app(EscalationService::class)->raise($owned->workItem()->first(), $ada, Escalation::MODE_HELP, Department::query()->where('slug', 'finance')->first(), null, 'Help');
        $this->travel(3)->hours();
        app(EscalationService::class)->accept($escalation, $bola);

        $departments = collect($this->report()['departments'])->keyBy('name');

        $this->assertSame([1, 1], [$departments['Finance']['open'], $departments['Finance']['unclaimed']]);
        $this->assertSame([1, 0, 1], [$departments['Operations']['open'], $departments['Operations']['unclaimed'], $departments['Operations']['overdue']]);
        $this->assertSame(1, $departments['Finance']['escalations_received']);
        $this->assertSame(3.0, $departments['Finance']['response_hours']);
    }

    public function test_the_service_filter_leaves_other_services_out(): void
    {
        $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);

        $this->assertSame(1, app(WorkloadReport::class)->build(now()->subDays(30), now(), 'flights')['summary']['open']);
        $this->assertSame(0, app(WorkloadReport::class)->build(now()->subDays(30), now(), 'car_hire')['summary']['open']);
    }

    public function test_only_the_ceo_sees_the_workload_page(): void
    {
        $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);

        $this->actingAs($this->staff('finance', 'Bola'))->get(Workload::getUrl())->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(Workload::getUrl())
            ->assertOk()
            ->assertSee('Time in each step')
            ->assertSee('By department')
            ->assertSee('By person');

        Livewire::test(Workload::class)->set('days', 7)->set('service', 'visas')->assertOk()->assertSee('Every service');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function report(): array
    {
        return app(WorkloadReport::class)->build(now()->subDays(30), now()->addMinute());
    }

    private function staff(string $department, string $name): User
    {
        // Heads, so they may escalate; permissions are tested in WorkflowPermissionsTest.
        return User::factory()->create(['name' => $name, 'department_id' => Department::query()->where('slug', $department)->value('id'), 'is_department_head' => true]);
    }

    private function booking(array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-WL-'.strtoupper(Str::random(6)),
            'unique_id' => 'TR'.random_int(100000, 999999),
            'fare_source_code' => 'fsc_test',
            'fare_type' => 'Public',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
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
        ], $overrides));
    }
}
