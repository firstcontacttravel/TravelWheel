<?php

namespace Tests\Feature;

use App\Filament\Resources\CarHires\Pages\ViewCarHire;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\VisaApplications\Pages\ViewVisaApplication;
use App\Filament\Resources\WorkItems\Pages\ListWorkItems;
use App\Models\CarHire;
use App\Models\Country;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\User;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Workflow\EscalationService;
use App\Workflow\FulfilmentService;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Who may do what: staff claim and work what they own; department heads
 * escalate, reassign, take over and answer their department's escalations;
 * the CEO does anything.
 */
class WorkflowPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ── Staff ────────────────────────────────────────────────────────────

    public function test_staff_can_claim_an_unowned_booking_but_not_take_one_over(): void
    {
        $ada = $this->staff('operations');
        $bola = $this->staff('operations');
        $booking = $this->carHire();

        app(WorkItemService::class)->claim($booking->workItem, $ada);
        $this->assertSame($ada->id, $booking->workItem()->first()->owner_id);

        $this->actingAs($bola);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])->assertActionHidden('workClaim');

        $this->expectException(InvalidArgumentException::class);
        app(WorkItemService::class)->claim($booking->workItem()->first(), $bola);
    }

    public function test_staff_cannot_work_a_booking_until_they_own_it(): void
    {
        $ada = $this->staff('operations');
        $booking = $this->carHire();

        $this->actingAs($ada);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workClaim')
            ->assertActionHidden('workAdvance')
            ->assertActionHidden('workNote')
            ->assertActionHidden('assignDriver');

        try {
            app(FulfilmentService::class)->advance($booking, 'completed', $ada);
            $this->fail('Worked a booking they do not own.');
        } catch (InvalidArgumentException) {
        }

        app(WorkItemService::class)->claim($booking->workItem()->first(), $ada);

        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workAdvance')
            ->assertActionVisible('workNote')
            ->assertActionVisible('workRelease')
            ->callAction('workAdvance', data: ['step' => 'completed'])
            ->assertHasNoActionErrors();

        $this->assertSame('completed', $booking->fresh()->fulfilment_status);
    }

    public function test_an_owner_who_is_not_a_head_cannot_escalate_reassign_or_set_priority(): void
    {
        $ada = $this->staff('operations');
        $booking = $this->carHire();
        app(WorkItemService::class)->claim($booking->workItem, $ada);

        $this->actingAs($ada);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('workEscalate')
            ->assertActionHidden('workReassign')
            ->assertActionHidden('workPriority');

        $item = $booking->workItem()->first();
        foreach ([
            fn () => app(EscalationService::class)->raise($item, $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Help'),
            fn () => app(WorkItemService::class)->assign($item, $this->staff('operations'), null, $ada),
            fn () => app(WorkItemService::class)->setPriority($item, 'urgent', $ada),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('A non-head managed a booking.');
            } catch (InvalidArgumentException) {
            }
        }
    }

    public function test_a_hidden_action_called_anyway_does_nothing(): void
    {
        $booking = $this->carHire();

        $this->actingAs($this->staff('operations'));
        try {
            Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
                ->callAction('workAdvance', data: ['step' => 'completed']);
        } catch (\Throwable) {
            // The test helper may refuse outright; either way nothing may change.
        }

        $this->assertSame('new', $booking->fresh()->fulfilment_status);
    }

    // ── Heads ────────────────────────────────────────────────────────────

    public function test_a_head_manages_their_departments_bookings(): void
    {
        $head = $this->staff('operations', head: true);
        $ada = $this->staff('operations');
        $booking = $this->carHire();
        app(WorkItemService::class)->claim($booking->workItem, $ada);

        $this->actingAs($head);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionVisible('workClaim')
            ->assertActionVisible('workEscalate')
            ->assertActionVisible('workReassign')
            ->assertActionVisible('workPriority')
            ->assertActionVisible('workAdvance')
            ->callAction('workEscalate', data: ['mode' => Escalation::MODE_HELP, 'department_id' => $this->department('finance')->id, 'priority' => 'high', 'reason' => 'Check the payment'])
            ->assertHasNoActionErrors();

        $this->assertSame(1, Escalation::query()->count());
    }

    public function test_a_head_of_another_department_cannot_manage(): void
    {
        $financeHead = $this->staff('finance', head: true);
        $booking = $this->carHire();
        app(WorkItemService::class)->claim($booking->workItem, $this->staff('operations'));

        $this->actingAs($financeHead);
        Livewire::test(ViewCarHire::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('workEscalate')
            ->assertActionHidden('workReassign')
            ->assertActionHidden('workAdvance');
    }

    public function test_handing_a_visa_to_another_officer_is_for_heads(): void
    {
        $officer = $this->staff('operations');
        $application = $this->visa();
        app(WorkItemService::class)->claim($application->workItem, $officer);

        $this->actingAs($officer);
        Livewire::test(ViewVisaApplication::class, ['record' => $application->getRouteKey()])->assertActionHidden('workReassign')->assertActionDoesNotExist('assign');

        $this->actingAs($this->staff('operations', head: true));
        Livewire::test(ViewVisaApplication::class, ['record' => $application->getRouteKey()])->assertActionVisible('workReassign');
    }

    // ── Escalations ──────────────────────────────────────────────────────

    public function test_an_escalation_to_a_department_goes_to_its_heads(): void
    {
        $opsHead = $this->staff('operations', head: true);
        $financeHead = $this->staff('finance', head: true);
        $financeStaff = $this->staff('finance');
        $booking = $this->carHire();

        $escalation = app(EscalationService::class)->raise($booking->workItem, $opsHead, Escalation::MODE_HELP, $this->department('finance'), null, 'Payment query');

        $this->assertSame([1, 0], [$financeHead->notifications()->count(), $financeStaff->notifications()->count()]);
        $this->assertTrue($escalation->canBeRespondedToBy($financeHead));
        $this->assertFalse($escalation->canBeRespondedToBy($financeStaff));

        $this->actingAs($financeHead);
        Livewire::test(ListWorkItems::class)->set('activeTab', 'escalated_to_me')->assertCanSeeTableRecords([$booking->workItem]);
        $this->actingAs($financeStaff);
        Livewire::test(ListWorkItems::class)->set('activeTab', 'escalated_to_me')->assertCanNotSeeTableRecords([$booking->workItem]);
    }

    public function test_a_department_with_no_head_yet_still_gets_its_escalations_answered(): void
    {
        $opsHead = $this->staff('operations', head: true);
        $support = $this->staff('customer-support');

        $escalation = app(EscalationService::class)->raise($this->carHire()->workItem, $opsHead, Escalation::MODE_HELP, $this->department('customer-support'), null, 'Customer called');

        $this->assertSame(1, $support->notifications()->count());
        $this->assertTrue($escalation->canBeRespondedToBy($support));
    }

    // ── Naming heads ─────────────────────────────────────────────────────

    public function test_the_ceo_names_department_heads(): void
    {
        $ada = $this->staff('operations');

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditStaff::class, ['record' => $ada->getRouteKey()])
            ->fillForm(['is_department_head' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($ada->fresh()->isDepartmentHead());
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function department(string $slug): Department
    {
        return Department::query()->where('slug', $slug)->firstOrFail();
    }

    private function staff(string $department, bool $head = false): User
    {
        return User::factory()->create(['department_id' => $this->department($department)->id, 'is_department_head' => $head]);
    }

    private function carHire(): CarHire
    {
        return CarHire::create([
            'car_type' => 'Sedan', 'category' => 'standard', 'car_model' => 'Corolla', 'full_name' => 'Test Customer',
            'email' => 'customer@example.test', 'phone_number' => '08000000000', 'passengers' => 1,
            'pickup_location' => 'Ikeja', 'dropoff_location' => 'Lekki', 'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '09:00', 'amount' => 45000, 'payment_option' => 'card', 'payment_reference' => 'CAR-'.Str::random(8),
            'payment_status' => 'paid', 'driver_assigned' => false,
        ]);
    }

    private function visa(): VisaApplication
    {
        $nationality = Country::query()->firstOrCreate(['alpha2' => 'GH'], ['name' => 'Ghana']);
        $destination = Country::query()->firstOrCreate(['alpha2' => 'GB'], ['name' => 'United Kingdom']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 1]);

        return VisaApplication::query()->create([
            'reference' => (string) Str::ulid(), 'resume_token_hash' => hash('sha256', Str::random()), 'visa_product_id' => $product->id, 'product_version' => 1, 'status' => 'submitted',
            'current_step' => 8, 'completed_step' => 8, 'nationality_country_id' => $nationality->id, 'destination_country_id' => $destination->id,
            'arrival_date' => now()->addMonth(), 'departure_date' => now()->addMonths(2), 'adult_count' => 1, 'contact_email' => 'operations@example.com',
            'search_snapshot' => [], 'product_snapshot' => [], 'last_activity_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
    }
}
