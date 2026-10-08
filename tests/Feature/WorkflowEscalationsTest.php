<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightBookings\Pages\ViewFlightBooking;
use App\Filament\Resources\WorkItems\Pages\ListWorkItems;
use App\Filament\Resources\WorkItems\WorkItemResource;
use App\Filament\Widgets\OperationsTriage;
use App\Mail\WorkEscalationMail;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Services\DurableMailService;
use App\Workflow\EscalationService;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Workflow phase 2: escalations in both modes, the notification bell and
 * email, and the My Work page.
 */
class WorkflowEscalationsTest extends TestCase
{
    use RefreshDatabase;

    private EscalationService $escalations;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->escalations = app(EscalationService::class);
    }

    // ── Ask for help ─────────────────────────────────────────────────────

    public function test_asking_a_department_for_help_tells_everyone_in_it_and_the_owner_keeps_the_booking(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $chi = $this->staff('finance', 'Chi');
        $this->staff('finance', 'Gone', ['deactivated_at' => now()]);
        $item = $this->ownedBy($ada);

        $escalation = $this->escalations->raise($item, $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Customer says they paid, please check the account');

        $this->assertSame(Escalation::STATUS_OPEN, $escalation->status);
        $this->assertSame($ada->id, $item->fresh()->owner_id);
        $this->assertSame(1, $bola->notifications()->count());
        $this->assertSame(1, $chi->notifications()->count());
        $this->assertSame(0, $ada->notifications()->count());
        $this->assertEqualsCanonicalizing(
            [$bola->email, $chi->email],
            NotificationOutbox::query()->where('kind', DurableMailService::WORK_ESCALATION)->pluck('recipient')->all(),
            'Everyone active in the department is emailed, and nobody else.',
        );

        $event = $item->events()->where('type', WorkItemEvent::ESCALATED)->firstOrFail();
        $this->assertSame(['the Finance department', 'Customer says they paid, please check the account'], [$event->to, $event->body]);
    }

    public function test_help_is_accepted_then_resolved_and_goes_back_to_the_owner_with_a_note(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $item = $this->ownedBy($ada);
        $escalation = $this->escalations->raise($item, $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Check the transfer');

        $this->escalations->accept($escalation, $bola);
        $this->assertSame(Escalation::STATUS_ACCEPTED, $escalation->fresh()->status);

        $this->escalations->resolve($escalation->fresh(), $bola, 'Funds received at 10:40');

        $escalation->refresh();
        $this->assertSame(Escalation::STATUS_RESOLVED, $escalation->status);
        $this->assertSame('Funds received at 10:40', $escalation->response_note);
        $this->assertSame($ada->id, $item->fresh()->owner_id, 'Help never moves ownership.');
        $this->assertSame(2, $ada->notifications()->count(), 'Told when accepted and when resolved.');
        $this->assertContains('resolved', NotificationOutbox::query()->where('recipient', $ada->email)->get()->pluck('payload.event')->all());
    }

    // ── Hand-off ─────────────────────────────────────────────────────────

    public function test_a_hand_off_moves_the_booking_to_whoever_accepts_it(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $ngozi = $this->staff('operations', 'Ngozi');
        $item = $this->ownedBy($ada);

        $escalation = $this->escalations->raise($item, $ada, Escalation::MODE_HANDOFF, $this->department('operations'), null, 'Customer also needs a visa, please take it from here');
        $this->escalations->accept($escalation, $ngozi);

        $item->refresh();
        $this->assertSame($ngozi->id, $item->owner_id);
        $this->assertSame($this->department('operations')->id, $item->department_id);
        $this->assertSame(Escalation::STATUS_RESOLVED, $escalation->fresh()->status);
    }

    public function test_a_hand_off_cannot_be_resolved_without_being_accepted(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $escalation = $this->escalations->raise($this->ownedBy($ada), $ada, Escalation::MODE_HANDOFF, $this->department('operations'), null, 'Take it');

        $this->expectException(InvalidArgumentException::class);
        $this->escalations->resolve($escalation, $this->staff('operations', 'Ngozi'), 'Done');
    }

    // ── Who may answer ───────────────────────────────────────────────────

    public function test_only_the_named_person_or_the_ceo_can_answer_a_personal_escalation(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $colleague = $this->staff('finance', 'Colleague');
        $escalation = $this->escalations->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, null, $bola, 'Bola, you handled this one before');

        $this->assertSame($this->department('finance')->id, $escalation->to_department_id, 'A person carries their department.');
        $this->assertSame(0, $colleague->notifications()->count());
        $this->assertFalse($escalation->canBeRespondedToBy($colleague));
        $this->assertTrue($escalation->canBeRespondedToBy($bola));
        $this->assertTrue($escalation->canBeRespondedToBy(User::factory()->create(['is_admin' => true])));

        $this->expectException(InvalidArgumentException::class);
        $this->escalations->accept($escalation, $colleague);
    }

    public function test_declining_needs_a_reason_and_tells_whoever_raised_it(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $escalation = $this->escalations->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Please refund');

        try {
            $this->escalations->decline($escalation, $bola, '  ');
            $this->fail('Declined without a reason.');
        } catch (InvalidArgumentException) {
        }

        $this->escalations->decline($escalation->fresh(), $bola, 'No refund quote yet, get one first');

        $this->assertSame(Escalation::STATUS_DECLINED, $escalation->fresh()->status);
        $this->assertSame(1, $ada->notifications()->count());
        $this->assertSame(1, $escalation->workItem->events()->where('type', WorkItemEvent::ESCALATION_DECLINED)->count());
    }

    public function test_only_whoever_raised_it_can_withdraw_it(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $escalation = $this->escalations->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, $this->department('it'), null, 'Supplier API down?');

        try {
            $this->escalations->withdraw($escalation, $this->staff('it', 'Dev'));
            $this->fail('Someone else withdrew it.');
        } catch (InvalidArgumentException) {
        }

        $this->escalations->withdraw($escalation->fresh(), $ada);
        $this->assertSame(Escalation::STATUS_WITHDRAWN, $escalation->fresh()->status);
    }

    public function test_an_escalation_needs_somewhere_to_go_and_a_reason(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $item = $this->ownedBy($ada);

        foreach ([
            fn () => $this->escalations->raise($item, $ada, Escalation::MODE_HELP, null, null, 'Help'),
            fn () => $this->escalations->raise($item, $ada, Escalation::MODE_HELP, $this->department('finance'), null, ' '),
            fn () => $this->escalations->raise($item, $ada, Escalation::MODE_HELP, null, $ada, 'Me'),
            fn () => $this->escalations->raise($item, $ada, Escalation::MODE_HELP, null, $this->staff('finance', 'Left', ['deactivated_at' => now()]), 'Gone'),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('An invalid escalation was raised.');
            } catch (InvalidArgumentException) {
            }
        }

        $this->assertSame(0, Escalation::query()->count());
    }

    // ── The email ────────────────────────────────────────────────────────

    public function test_the_email_carries_the_reference_and_reason_but_no_passenger_details(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $item = $this->ownedBy($ada);
        $escalation = $this->escalations->raise($item, $ada, Escalation::MODE_HANDOFF, $this->department('finance'), null, 'Bank transfer reference does not match', 'urgent');

        $mail = new WorkEscalationMail($escalation, 'raised');
        $html = $mail->render();

        $this->assertStringContainsString('Hand-off (urgent): '.$item->subject->booking_ref, $mail->envelope()->subject);
        $this->assertStringContainsString($item->subject->booking_ref, $html);
        $this->assertStringContainsString('Bank transfer reference does not match', $html);
        $this->assertStringNotContainsString('Passenger', $html);
        $this->assertStringNotContainsString('traveller@example.test', $html);
    }

    public function test_the_outbox_delivers_escalation_emails(): void
    {
        Mail::fake();
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $this->escalations->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, null, $bola, 'Please look');

        app(DurableMailService::class)->processPending();

        Mail::assertSent(WorkEscalationMail::class, fn (WorkEscalationMail $mail) => $mail->hasTo($bola->email) && $mail->event === 'raised');
        $this->assertNotNull(NotificationOutbox::query()->where('recipient', $bola->email)->value('sent_at'));
    }

    // ── Screens ──────────────────────────────────────────────────────────

    public function test_escalating_and_answering_from_the_booking_page(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $booking = $this->ownedBy($ada)->subject;

        $this->actingAs($ada);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('workEscalate', data: [
                'mode' => Escalation::MODE_HELP,
                'department_id' => $this->department('finance')->id,
                'priority' => 'high',
                'reason' => 'Please confirm the transfer',
            ])
            ->assertHasNoActionErrors()
            ->assertActionVisible('workEscalationWithdraw')
            ->assertActionHidden('workEscalationAccept');

        $this->actingAs($bola);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workEscalationAccept')
            ->assertActionHidden('workEscalationWithdraw')
            ->callAction('workEscalationResolve', data: ['note' => 'Confirmed, marking paid'])
            ->assertHasNoActionErrors();

        $this->assertSame(Escalation::STATUS_RESOLVED, Escalation::query()->sole()->status);
    }

    public function test_escalating_with_no_department_or_person_is_refused_on_the_form(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $booking = $this->ownedBy($ada)->subject;

        $this->actingAs($ada);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('workEscalate', data: ['mode' => Escalation::MODE_HELP, 'priority' => 'normal', 'reason' => 'Help'])
            ->assertHasActionErrors(['department_id']);

        $this->assertSame(0, Escalation::query()->count());
    }

    public function test_my_work_shows_mine_and_whats_escalated_to_me(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $mine = $this->ownedBy($ada);
        $someoneElses = $this->ownedBy($this->staff('operations', 'Other'));
        $escalated = $this->ownedBy($ada);
        $this->escalations->raise($escalated, $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Check please');

        $this->actingAs($ada);
        Livewire::test(ListWorkItems::class)
            ->assertCanSeeTableRecords([$mine, $escalated])
            ->assertCanNotSeeTableRecords([$someoneElses])
            ->set('activeTab', 'escalated_by_me')
            ->assertCanSeeTableRecords([$escalated])
            ->assertCanNotSeeTableRecords([$mine]);

        $this->actingAs($bola);
        Livewire::test(ListWorkItems::class)
            ->set('activeTab', 'escalated_to_me')
            ->assertCanSeeTableRecords([$escalated])
            ->assertCanNotSeeTableRecords([$mine, $someoneElses]);

        $this->get(WorkItemResource::getUrl('index'))->assertOk()->assertSee('My Work');
    }

    public function test_my_work_finds_a_booking_by_its_reference(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $wanted = $this->ownedBy($ada);
        $other = $this->ownedBy($ada);

        $this->actingAs($ada);
        Livewire::test(ListWorkItems::class)
            ->searchTable($wanted->subject->booking_ref)
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_the_dashboard_counts_what_is_escalated_to_you(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $bola = $this->staff('finance', 'Bola');
        $this->escalations->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, null, $bola, 'Please look');

        $this->actingAs($bola);
        $waiting = collect(app(OperationsTriage::class)->getWaiting())->keyBy('label');

        $this->assertSame(1, $waiting['Escalated to you']['count']);
        $this->assertSame(0, $waiting['Your open work']['count']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function department(string $slug): Department
    {
        return Department::query()->where('slug', $slug)->firstOrFail();
    }

    private function staff(string $department, string $name, array $attributes = []): User
    {
        return User::factory()->create([
            'name' => $name,
            'department_id' => $this->department($department)->id,
            ...$attributes,
        ]);
    }

    private function ownedBy(User $owner): WorkItem
    {
        $booking = FlightBooking::create([
            'booking_ref' => 'TW-ES-'.strtoupper(Str::random(6)),
            'unique_id' => 'TR'.random_int(100000, 999999),
            'fare_source_code' => 'fsc_test',
            'fare_type' => 'Public',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
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

        return app(WorkItemService::class)->claim($booking->workItem, $owner);
    }
}
