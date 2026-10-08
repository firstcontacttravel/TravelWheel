<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Filament\Resources\FlightBookings\Pages\ListFlightBookings;
use App\Filament\Resources\FlightBookings\Pages\ViewFlightBooking;
use App\Filament\Resources\VisaApplications\VisaApplicationResource;
use App\Models\Country;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\PostTicketingRequest;
use App\Models\User;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Services\VisaOperationsService;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Workflow phase 1: every flight booking and visa application has a work
 * item that follows its status, an owner anyone can claim, and a history.
 */
class WorkflowWorkItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ── Following the booking ────────────────────────────────────────────

    public function test_a_new_booking_gets_a_work_item_with_its_first_history_line(): void
    {
        $booking = $this->booking();

        $item = $booking->workItem;
        $this->assertNotNull($item);
        $this->assertSame('flights', $item->service);
        $this->assertSame('pending_payment', $item->stage);
        $this->assertSame(WorkItem::STATE_WAITING, $item->state);
        $this->assertSame($this->department('operations'), $item->department_id);
        $this->assertSame([WorkItemEvent::CREATED], $item->events()->pluck('type')->all());
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function flightStages(): array
    {
        return [
            'unpaid' => [['payment_status' => 'pending', 'booking_status' => 'on_hold'], 'pending_payment'],
            'bank transfer' => [['payment_status' => 'awaiting_bank_transfer', 'booking_status' => 'on_hold'], 'awaiting_transfer'],
            'travelflex review' => [['payment_status' => 'pending', 'booking_status' => 'awaiting_approval'], 'travelflex_review'],
            'travelflex deposit' => [['payment_status' => 'pending', 'booking_status' => 'awaiting_deposit'], 'awaiting_deposit'],
            'hold expired' => [['payment_status' => 'pending', 'booking_status' => 'hold_expired_review'], 'hold_expired'],
            'paid, not ordered' => [['payment_status' => 'paid', 'booking_status' => 'confirmed', 'ticket_ordered' => false], 'ready_to_ticket'],
            'paid, ordered' => [['payment_status' => 'paid', 'booking_status' => 'ticketing_in_progress', 'ticket_ordered' => true], 'ticketing_in_progress'],
            'paid, failed' => [['payment_status' => 'paid', 'booking_status' => 'ticketing_failed', 'ticket_ordered' => true], 'ticketing_failed'],
            'ticketed' => [['payment_status' => 'paid', 'booking_status' => 'ticketed', 'ticket_ordered' => true], 'ticketed'],
            'cancelled' => [['payment_status' => 'paid', 'booking_status' => 'cancelled'], 'cancelled'],
        ];
    }

    #[DataProvider('flightStages')]
    public function test_the_stage_is_read_from_the_bookings_own_status(array $attributes, string $stage): void
    {
        $this->assertSame($stage, $this->booking($attributes)->workItem->stage);
    }

    public function test_an_unowned_booking_moves_to_the_queue_its_stage_belongs_in(): void
    {
        $booking = $this->booking(['payment_status' => 'awaiting_bank_transfer']);
        $this->assertSame($this->department('finance'), $booking->workItem->department_id);

        $booking->update(['payment_status' => 'paid', 'booking_status' => 'confirmed']);

        $item = $booking->workItem()->first();
        $this->assertSame('ready_to_ticket', $item->stage);
        $this->assertSame($this->department('operations'), $item->department_id);

        $change = $item->events()->where('type', WorkItemEvent::STAGE_CHANGED)->firstOrFail();
        $this->assertSame(['awaiting_transfer', 'ready_to_ticket'], [$change->from, $change->to]);
        $this->assertNull($change->user_id, 'A change with nobody signed in is the system.');
    }

    public function test_an_owned_booking_stays_with_its_owner_when_the_stage_moves(): void
    {
        $finance = $this->staff('finance');
        $booking = $this->booking(['payment_status' => 'awaiting_bank_transfer']);
        app(WorkItemService::class)->claim($booking->workItem, $finance);

        $this->actingAs($finance);
        $booking->update(['payment_status' => 'paid', 'booking_status' => 'confirmed']);

        $item = $booking->workItem()->first();
        $this->assertSame($finance->id, $item->owner_id);
        $this->assertSame($this->department('finance'), $item->department_id);
        $this->assertSame($finance->id, $item->events()->where('type', WorkItemEvent::STAGE_CHANGED)->value('user_id'));
    }

    public function test_an_open_refund_reopens_a_ticketed_booking_until_it_completes(): void
    {
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'ticketed', 'ticket_ordered' => true]);
        $this->assertNotNull($booking->workItem->closed_at);

        $request = PostTicketingRequest::create(['flight_booking_id' => $booking->id, 'operation_type' => 'refund', 'status' => 'submitted']);
        $item = $booking->workItem()->first();
        $this->assertSame('post_ticketing', $item->stage);
        $this->assertTrue($item->isActive());
        $this->assertNull($item->closed_at);

        $request->update(['status' => 'completed']);
        $item = $booking->workItem()->first();
        $this->assertSame('ticketed', $item->stage);
        $this->assertSame(WorkItem::STATE_DONE, $item->state);
    }

    public function test_an_unanswered_quote_does_not_hold_a_ticketed_booking_open(): void
    {
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'ticketed', 'ticket_ordered' => true]);

        PostTicketingRequest::create(['flight_booking_id' => $booking->id, 'operation_type' => 'refund_quote', 'status' => 'inprocess']);

        $this->assertSame('ticketed', $booking->workItem()->first()->stage);
    }

    public function test_the_airline_ticketing_deadline_is_the_due_time_while_work_is_open(): void
    {
        // Sooner than the step's own hour (phase 5), so the airline's limit wins.
        $deadline = now()->addMinutes(30)->startOfMinute();
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed', 'tkt_time_limit' => $deadline]);

        $this->assertTrue($booking->workItem->due_at->equalTo($deadline));

        $booking->update(['booking_status' => 'ticketed', 'ticket_ordered' => true]);
        $this->assertNull($booking->workItem()->first()->due_at);
    }

    public function test_a_failure_in_work_tracking_never_stops_a_booking_being_saved(): void
    {
        $this->app->bind(WorkItemService::class, fn () => throw new RuntimeException('workflow is broken'));

        $booking = $this->booking();

        $this->assertTrue($booking->exists);
        $this->assertNull($booking->workItem()->first());
    }

    // ── Ownership and history ────────────────────────────────────────────

    public function test_claim_release_reassign_note_and_priority_each_leave_history(): void
    {
        $items = app(WorkItemService::class);
        $ada = $this->staff('operations', ['name' => 'Ada']);
        $bola = $this->staff('finance', ['name' => 'Bola']);
        $item = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed'])->workItem;

        $items->claim($item, $ada);
        $items->assign($item->fresh(), $bola, null, $ada, 'Please confirm the transfer first');
        $items->addNote($item, $bola, 'Transfer confirmed with the bank');
        $items->setPriority($item->fresh(), 'urgent', $bola);
        $items->release($item->fresh(), $bola);

        $item->refresh();
        $this->assertNull($item->owner_id);
        $this->assertSame('urgent', $item->priority);
        // Bola carried it into Finance's queue; releasing leaves it there.
        $this->assertSame($this->department('finance'), $item->department_id);

        $this->assertSame(
            [WorkItemEvent::CREATED, WorkItemEvent::CLAIMED, WorkItemEvent::REASSIGNED, WorkItemEvent::NOTE, WorkItemEvent::PRIORITY_CHANGED, WorkItemEvent::RELEASED],
            $item->events()->reorder()->oldest('id')->pluck('type')->all(),
        );
        $reassigned = $item->events()->where('type', WorkItemEvent::REASSIGNED)->firstOrFail();
        $this->assertSame([$ada->id, 'Ada', 'Bola', 'Please confirm the transfer first'], [$reassigned->user_id, $reassigned->from, $reassigned->to, $reassigned->body]);
    }

    public function test_moving_to_another_department_queue_without_a_person(): void
    {
        $ada = $this->staff('operations');
        $item = $this->booking()->workItem;
        $it = Department::query()->where('slug', 'it')->firstOrFail();

        app(WorkItemService::class)->assign($item, null, $it, $ada, 'Supplier API keeps timing out');

        $item->refresh();
        $this->assertSame($it->id, $item->department_id);
        $moved = $item->events()->where('type', WorkItemEvent::MOVED)->firstOrFail();
        $this->assertSame(['Operations', 'IT', 'Supplier API keeps timing out'], [$moved->from, $moved->to, $moved->body]);
    }

    public function test_a_deactivated_person_cannot_be_given_work(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(WorkItemService::class)->assign($this->booking()->workItem, $this->staff('operations', ['deactivated_at' => now()]), null, $this->staff('operations'));
    }

    // ── Visas ────────────────────────────────────────────────────────────

    public function test_a_visa_application_follows_its_status(): void
    {
        $application = $this->visa('submitted');
        $this->assertSame('submitted', $application->workItem->stage);
        $this->assertSame($this->department('operations'), $application->workItem->department_id);

        $application->update(['status' => 'action_required']);
        $this->assertSame(WorkItem::STATE_WAITING, $application->workItem()->first()->state);
    }

    public function test_the_visa_officer_and_the_work_owner_are_always_the_same_person(): void
    {
        $officer = $this->staff('operations', ['name' => 'Officer Ngozi']);
        $other = $this->staff('operations', ['name' => 'Officer Tunde']);
        $application = $this->visa('submitted');

        // Assigned the visa way: the work item follows.
        app(VisaOperationsService::class)->assign($application, $officer, $officer);
        $this->assertSame($officer->id, $application->workItem()->first()->owner_id);

        // Claimed the work way: the visa follows, with its own audit entry.
        app(WorkItemService::class)->claim($application->workItem()->first(), $other);
        $this->assertSame($other->id, $application->fresh()->assigned_to);
        $this->assertTrue($application->auditEvents()->where('event_type', 'assignment')->where('summary', 'Assigned to Officer Tunde')->exists());
        $this->assertSame(1, $application->workItem()->first()->events()->where('type', WorkItemEvent::CLAIMED)->count(), 'One claim, not one per side.');
    }

    // ── Backfill ─────────────────────────────────────────────────────────

    public function test_the_backfill_creates_missing_work_items_and_is_safe_to_repeat(): void
    {
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        $application = $this->visa('processing');
        WorkItem::query()->delete();

        $this->artisan('workflow:backfill')->assertSuccessful();
        $this->artisan('workflow:backfill')->assertSuccessful();

        $this->assertSame(2, WorkItem::query()->count());
        $this->assertSame('ready_to_ticket', $booking->workItem()->first()->stage);
        $this->assertSame('processing', $application->workItem()->first()->stage);
        $this->assertSame(1, $booking->workItem()->first()->events()->count());
    }

    // ── Screens ──────────────────────────────────────────────────────────

    public function test_any_member_of_staff_can_claim_from_the_booking_page_and_it_shows_in_the_panel(): void
    {
        $booking = $this->booking(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
        $support = $this->staff('customer-support', ['name' => 'Chiamaka Support']);

        $this->actingAs($support);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workClaim')
            ->callAction('workClaim')
            ->assertHasNoActionErrors()
            ->assertActionHidden('workClaim')
            ->assertActionVisible('workRelease');

        $this->assertSame($support->id, $booking->workItem()->first()->owner_id);

        $this->get(FlightBookingResource::getUrl('view', ['record' => $booking]))
            ->assertOk()
            ->assertSee('Ready to ticket')
            ->assertSee('Chiamaka Support claimed it');
    }

    public function test_notes_and_reassignment_from_the_booking_page(): void
    {
        $booking = $this->booking();
        $finance = $this->staff('finance', ['name' => 'Bola Finance']);

        $this->actingAs($this->staff('operations'));
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('workNote', data: ['body' => 'Customer says the transfer was sent yesterday'])
            ->assertHasNoActionErrors()
            ->callAction('workReassign', data: ['department_id' => $finance->department_id, 'owner_id' => $finance->id, 'note' => 'Please check'])
            ->assertHasNoActionErrors();

        $item = $booking->workItem()->first();
        $this->assertSame($finance->id, $item->owner_id);
        $this->assertSame('Customer says the transfer was sent yesterday', $item->events()->where('type', WorkItemEvent::NOTE)->value('body'));
    }

    public function test_the_visa_page_shows_the_work_panel(): void
    {
        $application = $this->visa('under_review');

        $this->actingAs($this->staff('operations'))
            ->get(VisaApplicationResource::getUrl('view', ['record' => $application]))
            ->assertOk()
            ->assertSee('Under review')
            ->assertSee('Unclaimed');
    }

    public function test_the_flight_queue_filters_to_my_work(): void
    {
        $me = $this->staff('operations');
        $mine = $this->booking();
        $theirs = $this->booking();
        app(WorkItemService::class)->claim($mine->workItem, $me);

        $this->actingAs($me);
        Livewire::test(ListFlightBookings::class)
            ->filterTable('work', 'mine')
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function department(string $slug): int
    {
        return (int) Department::query()->where('slug', $slug)->value('id');
    }

    private function staff(string $department, array $attributes = []): User
    {
        return User::factory()->create(['department_id' => $this->department($department), ...$attributes]);
    }

    private function booking(array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-WF-'.strtoupper(Str::random(6)),
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

    private function visa(string $status): VisaApplication
    {
        $nationality = Country::query()->firstOrCreate(['alpha2' => 'GH'], ['name' => 'Ghana']);
        $destination = Country::query()->firstOrCreate(['alpha2' => 'GB'], ['name' => 'United Kingdom']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 1]);

        return VisaApplication::query()->create([
            'reference' => (string) Str::ulid(), 'resume_token_hash' => hash('sha256', Str::random()), 'visa_product_id' => $product->id, 'product_version' => 1, 'status' => $status,
            'current_step' => 8, 'completed_step' => 8, 'nationality_country_id' => $nationality->id, 'destination_country_id' => $destination->id,
            'arrival_date' => now()->addMonth(), 'departure_date' => now()->addMonths(2), 'adult_count' => 1, 'contact_email' => 'operations@example.com',
            'search_snapshot' => [], 'product_snapshot' => [], 'last_activity_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
    }
}
