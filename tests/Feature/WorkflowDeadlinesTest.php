<?php

namespace Tests\Feature;

use App\Filament\Pages\WorkflowSettings;
use App\Filament\Widgets\OperationsTriage;
use App\Mail\WorkDeadlineMail;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Services\DurableMailService;
use App\Workflow\Deadlines;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Workflow phase 5: each step has a time allowance; work is warned before
 * its deadline, flagged when it misses it, and sent to the CEO when it stays
 * missed.
 */
class WorkflowDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->freezeSecond();
    }

    // ── Due times ────────────────────────────────────────────────────────

    public function test_a_step_is_due_its_allowance_after_the_booking_enters_it(): void
    {
        $item = $this->readyToTicket()->workItem;

        $this->assertTrue($item->due_at->equalTo(now()->addMinutes(Deadlines::DEFAULT_STEPS['flights']['ready_to_ticket'])));
        $this->assertTrue($item->stage_entered_at->equalTo(now()));
    }

    public function test_the_bookings_own_deadline_wins_when_it_is_sooner(): void
    {
        $item = $this->readyToTicket(['tkt_time_limit' => now()->addMinutes(20)])->workItem;

        $this->assertTrue($item->due_at->equalTo(now()->addMinutes(20)));
    }

    public function test_waiting_on_the_customer_is_not_on_the_clock(): void
    {
        $item = $this->booking(['payment_status' => 'pending'])->workItem;

        $this->assertSame(WorkItem::STATE_WAITING, $item->state);
        $this->assertNull($item->due_at);
    }

    public function test_moving_to_a_new_step_restarts_the_clock_and_clears_old_alerts(): void
    {
        $booking = $this->booking(['payment_status' => 'awaiting_bank_transfer']);
        $this->travel(3)->hours();
        app(\App\Workflow\DeadlineMonitor::class)->run();
        $this->assertNotNull($booking->workItem()->first()->breached_at);

        $booking->update(['payment_status' => 'paid', 'booking_status' => 'confirmed']);

        $item = $booking->workItem()->first();
        $this->assertSame('ready_to_ticket', $item->stage);
        $this->assertNull($item->breached_at);
        $this->assertTrue($item->due_at->equalTo(now()->addMinutes(60)));
    }

    // ── Alerts ───────────────────────────────────────────────────────────

    public function test_the_owner_is_warned_once_most_of_the_time_is_gone(): void
    {
        $owner = $this->staff('operations');
        $item = app(WorkItemService::class)->claim($this->readyToTicket()->workItem, $owner);

        $this->travel(30)->minutes();
        $this->artisan('workflow:check-deadlines')->assertSuccessful();
        $this->assertSame(0, $owner->notifications()->count(), 'Half the time is not a warning.');

        $this->travel(16)->minutes();
        $this->artisan('workflow:check-deadlines');
        $this->artisan('workflow:check-deadlines');

        $this->assertSame(1, $owner->notifications()->count(), 'Warned once, at 75%.');
        $this->assertSame(1, $item->events()->where('type', WorkItemEvent::DEADLINE_WARNING)->count());
        $this->assertSame(0, NotificationOutbox::query()->count(), 'A warning is the bell only.');
    }

    public function test_an_unowned_item_warns_its_whole_queue(): void
    {
        $a = $this->staff('operations');
        $b = $this->staff('operations');
        $finance = $this->staff('finance');
        $this->readyToTicket();

        $this->travel(50)->minutes();
        $this->artisan('workflow:check-deadlines');

        $this->assertSame([1, 1, 0], [$a->notifications()->count(), $b->notifications()->count(), $finance->notifications()->count()]);
    }

    public function test_a_missed_deadline_tells_the_owner_and_the_queue_by_bell_and_email(): void
    {
        $owner = $this->staff('operations');
        $colleague = $this->staff('operations');
        $item = app(WorkItemService::class)->claim($this->readyToTicket()->workItem, $owner);

        $this->travel(61)->minutes();
        $this->artisan('workflow:check-deadlines');

        $item->refresh();
        $this->assertNotNull($item->breached_at);
        $this->assertTrue($item->isOverdue());
        $this->assertSame(1, $item->events()->where('type', WorkItemEvent::DEADLINE_MISSED)->count());
        $this->assertGreaterThanOrEqual(1, $colleague->notifications()->count());
        $this->assertEqualsCanonicalizing(
            [$owner->email, $colleague->email],
            NotificationOutbox::query()->where('kind', DurableMailService::WORK_DEADLINE)->pluck('recipient')->all(),
        );
    }

    public function test_the_ceo_hears_only_when_it_stays_overdue(): void
    {
        $ceo = User::factory()->create(['is_admin' => true]);
        $item = $this->readyToTicket()->workItem;

        $this->travel(61)->minutes();
        $this->artisan('workflow:check-deadlines');
        $this->assertSame(0, $ceo->notifications()->count(), 'Not on the first miss.');

        $this->travel(Deadlines::DEFAULT_CEO_AFTER_MINUTES + 1)->minutes();
        $this->artisan('workflow:check-deadlines');
        $this->artisan('workflow:check-deadlines');

        $this->assertSame(1, $ceo->notifications()->count());
        $this->assertSame(1, $item->events()->where('type', WorkItemEvent::DEADLINE_CEO)->count());
        $this->assertTrue(NotificationOutbox::query()->where('recipient', $ceo->email)->exists());
    }

    public function test_finished_work_is_never_chased(): void
    {
        $booking = $this->readyToTicket();
        $booking->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);

        $this->travel(5)->hours();
        $this->artisan('workflow:check-deadlines');

        $this->assertSame(0, $booking->workItem()->first()->events()->where('type', 'like', 'deadline%')->count());
    }

    public function test_the_overdue_email_carries_the_reference_and_step_only(): void
    {
        Mail::fake();
        $this->staff('operations');
        $booking = $this->readyToTicket();

        $this->travel(61)->minutes();
        $this->artisan('workflow:check-deadlines');
        app(DurableMailService::class)->processPending();

        Mail::assertSent(WorkDeadlineMail::class, function (WorkDeadlineMail $mail) use ($booking): bool {
            $html = $mail->render();

            return str_contains($mail->envelope()->subject, $booking->booking_ref)
                && str_contains($html, 'Ready to ticket')
                && ! str_contains($html, 'traveller@example.test');
        });
    }

    // ── Settings ─────────────────────────────────────────────────────────

    public function test_the_ceo_changes_a_deadline_and_open_work_takes_it_at_once(): void
    {
        $item = $this->readyToTicket()->workItem;
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(WorkflowSettings::class)
            ->set('data.steps.flights.ready_to_ticket', 3)
            ->set('data.steps.flights.ticketing_failed', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(180, app(Deadlines::class)->allowance('flights', 'ready_to_ticket'));
        $this->assertNull(app(Deadlines::class)->allowance('flights', 'ticketing_failed'), 'Empty means no deadline.');
        $this->assertTrue($item->fresh()->due_at->equalTo(now()->addHours(3)));
    }

    public function test_only_the_ceo_can_open_deadline_settings(): void
    {
        $this->actingAs($this->staff('finance'))->get(WorkflowSettings::getUrl())->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(WorkflowSettings::getUrl())->assertOk()->assertSee('Ready to ticket');
    }

    // ── Showing it ───────────────────────────────────────────────────────

    public function test_the_dashboard_flags_overdue_work(): void
    {
        $this->readyToTicket();
        $this->travel(2)->hours();

        $this->actingAs($this->staff('operations'));
        $broken = collect(app(OperationsTriage::class)->getBroken())->keyBy('label');

        $this->assertSame(1, $broken['Overdue work']['count']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function staff(string $department): User
    {
        return User::factory()->create(['department_id' => Department::query()->where('slug', $department)->value('id')]);
    }

    private function readyToTicket(array $overrides = []): FlightBooking
    {
        return $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed', ...$overrides]);
    }

    private function booking(array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-DL-'.strtoupper(Str::random(6)),
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
