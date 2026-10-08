<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightBookings\Pages\ViewFlightBooking;
use App\Jobs\CloseLinearIssueForEscalation;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\FlightBooking;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Workflow\EscalationService;
use App\Workflow\WorkItemService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Workflow phase 3: escalations mirrored to Linear and back.
 *
 * Every Linear call is faked; nothing here can reach the real workspace
 * (phpunit.xml also blanks the key, and stray requests fail the test).
 */
class WorkflowLinearTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test';

    /** @var list<array{query: string, variables: array}> */
    private array $calls = [];

    private bool $linearDown = false;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['services.linear.api_key' => 'lin_api_test', 'services.linear.webhook_secret' => self::SECRET]);

        Http::preventStrayRequests();
        Http::fake(['api.linear.app/*' => fn (Request $request) => $this->linear($request)]);
    }

    // ── Admin → Linear ───────────────────────────────────────────────────

    public function test_an_escalation_to_it_always_opens_an_issue_in_the_it_team(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $item = $this->ownedBy($ada);

        $escalation = app(EscalationService::class)->raise($item, $ada, Escalation::MODE_HELP, $this->department('it'), null, 'Supplier API returns 500 on reserve', 'urgent');

        $create = $this->linearCall('issueCreate');
        $this->assertSame('team-it', $create['variables']['input']['teamId']);
        $this->assertSame(1, $create['variables']['input']['priority'], 'Urgent is Linear priority 1.');
        $this->assertSame('state-it-todo', $create['variables']['input']['stateId'], 'Into Todo, not the default Backlog.');
        $this->assertArrayNotHasKey('labelIds', $create['variables']['input'], 'IT has its own team, no label.');

        $description = $create['variables']['input']['description'];
        $this->assertStringContainsString('Supplier API returns 500 on reserve', $description);
        $this->assertStringContainsString($item->subject->booking_ref, $description);
        $this->assertStringContainsString('/admin/flight-bookings/'.$item->subject->id, $description);
        $this->assertStringNotContainsString('traveller@example.test', $description, 'No customer contact details leave the admin.');
        $this->assertStringNotContainsString('Passenger', $description);

        $escalation->refresh();
        $this->assertSame(['issue-1', 'IT-12', 'https://linear.app/travelwheel/issue/IT-12'], [$escalation->linear_issue_id, $escalation->linear_identifier, $escalation->linear_issue_url]);
        $this->assertSame('IT-12', $item->events()->where('type', WorkItemEvent::LINEAR_LINKED)->value('to'));
    }

    public function test_other_departments_go_to_linear_only_when_asked_with_their_label(): void
    {
        $ada = $this->staff('operations', 'Ada');

        app(EscalationService::class)->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Check transfer');
        $this->assertSame([], $this->calls, 'Not asked for: Linear is not touched.');

        app(EscalationService::class)->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, $this->department('finance'), null, 'Check transfer', toLinear: true);

        $this->assertSame('Finance', $this->linearCall('issueLabelCreate')['variables']['input']['name'], 'A missing department label is created.');
        $input = $this->linearCall('issueCreate')['variables']['input'];
        $this->assertSame('team-tra', $input['teamId']);
        $this->assertSame(['label-new'], $input['labelIds']);
    }

    public function test_a_named_person_with_a_linear_seat_is_assigned_the_issue(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $dev = $this->staff('it', 'Dev', ['email' => 'dev@travelwheel.test']);

        app(EscalationService::class)->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, null, $dev, 'Look at the logs');

        $this->assertSame('linear-user-dev', $this->linearCall('issueCreate')['variables']['input']['assigneeId']);
    }

    public function test_resolving_in_the_admin_completes_comments_on_and_archives_the_issue(): void
    {
        [$escalation, $dev] = $this->escalatedToIt();

        app(EscalationService::class)->resolve($escalation, $dev, 'Restarted the queue worker');

        $this->assertSame('state-it-done', $this->linearCall('issueUpdate')['variables']['stateId']);
        $comment = $this->linearCall('commentCreate')['variables']['input']['body'];
        $this->assertStringContainsString('Restarted the queue worker', $comment);
        $this->assertStringContainsString(CloseLinearIssueForEscalation::SIGNATURE, $comment);
        $this->assertSame('issue-1', $this->linearCall('issueArchive')['variables']['id']);
    }

    public function test_declining_in_the_admin_cancels_the_issue(): void
    {
        [$escalation, $dev] = $this->escalatedToIt();

        app(EscalationService::class)->decline($escalation, $dev, 'Not an IT problem, ask Finance');

        $this->assertSame('state-it-canceled', $this->linearCall('issueUpdate')['variables']['stateId']);
    }

    public function test_linear_being_down_never_blocks_the_escalation(): void
    {
        $this->linearDown = true;
        $ada = $this->staff('operations', 'Ada');
        $item = $this->ownedBy($ada);

        $escalation = app(EscalationService::class)->raise($item, $ada, Escalation::MODE_HELP, $this->department('it'), null, 'Help');

        $this->assertSame(Escalation::STATUS_OPEN, $escalation->fresh()->status);
        $this->assertNull($escalation->fresh()->linear_issue_id);
        $this->assertTrue($escalation->linear_requested);
        $this->assertSame(1, $item->events()->where('type', WorkItemEvent::LINEAR_FAILED)->count());
    }

    public function test_the_escalate_form_can_send_a_finance_escalation_to_linear(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $booking = $this->ownedBy($ada)->subject;

        $this->actingAs($ada);
        Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('workEscalate', data: [
                'mode' => Escalation::MODE_HELP,
                'department_id' => $this->department('finance')->id,
                'priority' => 'normal',
                'reason' => 'Confirm the transfer',
                'linear' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('TRA-3', Escalation::query()->sole()->linear_identifier);
    }

    // ── Linear → admin ───────────────────────────────────────────────────

    public function test_completing_the_issue_in_linear_resolves_the_escalation(): void
    {
        [$escalation] = $this->escalatedToIt();
        $this->calls = [];

        $this->webhook($this->issueUpdate('completed', ['name' => 'Dev Person', 'email' => 'dev@travelwheel.test']))
            ->assertOk()->assertJson(['status' => 'applied']);

        $escalation->refresh();
        $this->assertSame(Escalation::STATUS_RESOLVED, $escalation->status);
        $this->assertSame('Completed in Linear by Dev Person.', $escalation->response_note);
        $this->assertSame('Dev', $escalation->responder?->name, 'Matched to staff by email.');
        $this->assertSame('issue-1', $this->linearCall('issueArchive')['variables']['id']);
        $this->assertNull($this->linearCall('issueUpdate'), 'Already completed in Linear: not moved again.');
    }

    public function test_cancelling_the_issue_in_linear_declines_the_escalation(): void
    {
        [$escalation] = $this->escalatedToIt();

        $this->webhook($this->issueUpdate('canceled', ['name' => 'Someone Outside']))->assertOk();

        $this->assertSame(Escalation::STATUS_DECLINED, $escalation->fresh()->status);
        $this->assertNull($escalation->fresh()->responded_by);
    }

    public function test_completing_a_hand_off_in_linear_makes_that_person_the_owner(): void
    {
        $ada = $this->staff('operations', 'Ada');
        $dev = $this->staff('it', 'Dev', ['email' => 'dev@travelwheel.test']);
        $item = $this->ownedBy($ada);
        app(EscalationService::class)->raise($item, $ada, Escalation::MODE_HANDOFF, $this->department('it'), null, 'This is a system fault now');

        $this->webhook($this->issueUpdate('completed', ['name' => 'Dev', 'email' => 'dev@travelwheel.test']))->assertOk();

        $this->assertSame($dev->id, $item->fresh()->owner_id);
    }

    public function test_a_comment_in_linear_appears_in_the_booking_history_but_our_own_do_not(): void
    {
        [$escalation] = $this->escalatedToIt();

        $this->webhook($this->comment('Looking at it now, the supplier token expired', ['name' => 'Dev', 'email' => 'dev@travelwheel.test']))->assertOk();
        $this->webhook($this->comment("Resolved in the admin.\n\n_".CloseLinearIssueForEscalation::SIGNATURE.'_', ['name' => 'First Contact']))
            ->assertJson(['status' => 'ignored']);

        $comments = $escalation->workItem->events()->where('type', WorkItemEvent::LINEAR_COMMENT)->get();
        $this->assertCount(1, $comments);
        $this->assertSame(['Dev', 'IT-12', 'Looking at it now, the supplier token expired'], [$comments[0]->from, $comments[0]->to, $comments[0]->body]);
        $this->assertNotNull($comments[0]->user_id);
    }

    public function test_a_retried_delivery_is_applied_once(): void
    {
        [$escalation] = $this->escalatedToIt();
        $payload = $this->comment('Once only', ['name' => 'Dev']);

        $this->webhook($payload, delivery: 'delivery-1')->assertJson(['status' => 'applied']);
        $this->webhook($payload, delivery: 'delivery-1')->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, $escalation->workItem->events()->where('type', WorkItemEvent::LINEAR_COMMENT)->count());
    }

    public function test_unsigned_forged_or_stale_deliveries_are_refused(): void
    {
        [$escalation] = $this->escalatedToIt();
        $body = json_encode($this->issueUpdate('completed', ['name' => 'Mallory']));

        $this->call('POST', '/webhooks/linear', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertUnauthorized();
        $this->call('POST', '/webhooks/linear', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_LINEAR_SIGNATURE' => hash_hmac('sha256', $body, 'wrong-secret')], $body)->assertUnauthorized();
        $this->webhook($this->issueUpdate('completed', ['name' => 'Mallory'], sentAt: now()->subMinutes(10)))->assertUnauthorized();

        $this->assertTrue($escalation->fresh()->isActive());
    }

    public function test_without_a_signing_secret_the_endpoint_does_not_exist(): void
    {
        config(['services.linear.webhook_secret' => null]);

        $this->postJson('/webhooks/linear', ['type' => 'Issue'])->assertNotFound();
    }

    public function test_issues_that_are_not_escalations_are_ignored(): void
    {
        $payload = $this->issueUpdate('completed', ['name' => 'Dev']);
        $payload['data']['id'] = 'some-other-issue';

        $this->webhook($payload)->assertJson(['status' => 'ignored']);
    }

    // ── Free plan ────────────────────────────────────────────────────────

    public function test_the_ceo_is_warned_when_linear_nears_its_issue_cap(): void
    {
        $ceo = User::factory()->create(['is_admin' => true]);
        config(['services.linear.issue_warning_threshold' => 5]);

        $this->artisan('linear:status --warn')->assertSuccessful();

        $this->assertSame(1, $ceo->notifications()->count());
    }

    // ── Fakes and helpers ────────────────────────────────────────────────

    private function linear(Request $request)
    {
        if ($this->linearDown) {
            return Http::response('Service unavailable', 503);
        }

        $query = (string) $request['query'];
        $variables = (array) $request['variables'];
        $this->calls[] = ['query' => $query, 'variables' => $variables];

        $data = match (true) {
            str_contains($query, 'teams(filter') => ['teams' => ['nodes' => [[
                'id' => $variables['key'] === 'IT' ? 'team-it' : 'team-tra',
                'name' => $variables['key'] === 'IT' ? 'IT Department' : 'TravelWheel',
                'states' => ['nodes' => [
                    ['id' => 'state-'.strtolower($variables['key']).'-todo', 'type' => 'unstarted', 'position' => 1],
                    ['id' => 'state-'.strtolower($variables['key']).'-done', 'type' => 'completed', 'position' => 2],
                    ['id' => 'state-'.strtolower($variables['key']).'-canceled', 'type' => 'canceled', 'position' => 3],
                ]],
            ]]]],
            str_contains($query, 'issueLabelCreate') => ['issueLabelCreate' => ['success' => true, 'issueLabel' => ['id' => 'label-new']]],
            str_contains($query, 'issueLabels(') => ['issueLabels' => ['nodes' => []]],
            str_contains($query, 'users(filter') => ['users' => ['nodes' => $variables['email'] === 'dev@travelwheel.test' ? [['id' => 'linear-user-dev']] : []]],
            str_contains($query, 'issueCreate') => ['issueCreate' => ['success' => true, 'issue' => $variables['input']['teamId'] === 'team-it'
                ? ['id' => 'issue-1', 'identifier' => 'IT-12', 'url' => 'https://linear.app/travelwheel/issue/IT-12']
                : ['id' => 'issue-2', 'identifier' => 'TRA-3', 'url' => 'https://linear.app/travelwheel/issue/TRA-3']]],
            str_contains($query, 'issueUpdate') => ['issueUpdate' => ['success' => true]],
            str_contains($query, 'commentCreate') => ['commentCreate' => ['success' => true]],
            str_contains($query, 'issueArchive') => ['issueArchive' => ['success' => true]],
            str_contains($query, 'viewer') => ['viewer' => ['name' => 'Test'], 'organization' => ['name' => 'TravelWheel']],
            str_contains($query, 'issues(first') => ['issues' => ['nodes' => array_fill(0, 7, ['id' => 'x']), 'pageInfo' => ['hasNextPage' => false]]],
            default => throw new \RuntimeException('Unexpected Linear query: '.$query),
        };

        return Http::response(['data' => $data]);
    }

    /** The first recorded call whose query mentions $operation, or null. */
    private function linearCall(string $operation): ?array
    {
        return collect($this->calls)->first(fn (array $call) => str_contains($call['query'], $operation));
    }

    private function webhook(array $payload, string $delivery = '')
    {
        $body = json_encode($payload);

        return $this->call('POST', '/webhooks/linear', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_LINEAR_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
            'HTTP_LINEAR_DELIVERY' => $delivery ?: (string) Str::uuid(),
        ], $body);
    }

    private function issueUpdate(string $stateType, array $actor, $sentAt = null): array
    {
        return [
            'type' => 'Issue',
            'action' => 'update',
            'actor' => $actor,
            'data' => ['id' => 'issue-1', 'identifier' => 'IT-12', 'state' => ['type' => $stateType, 'name' => ucfirst($stateType)]],
            'webhookTimestamp' => ($sentAt ?? now())->getTimestampMs(),
        ];
    }

    private function comment(string $body, array $user): array
    {
        return [
            'type' => 'Comment',
            'action' => 'create',
            'actor' => $user,
            'data' => ['id' => (string) Str::uuid(), 'body' => $body, 'issueId' => 'issue-1', 'user' => $user],
            'webhookTimestamp' => now()->getTimestampMs(),
        ];
    }

    /** @return array{Escalation, User} */
    private function escalatedToIt(): array
    {
        $ada = $this->staff('operations', 'Ada');
        $dev = $this->staff('it', 'Dev', ['email' => 'dev@travelwheel.test']);
        $escalation = app(EscalationService::class)->raise($this->ownedBy($ada), $ada, Escalation::MODE_HELP, $this->department('it'), null, 'Supplier down?');

        return [$escalation->fresh(), $dev];
    }

    private function department(string $slug): Department
    {
        return Department::query()->where('slug', $slug)->firstOrFail();
    }

    private function staff(string $department, string $name, array $attributes = []): User
    {
        return User::factory()->create(['name' => $name, 'department_id' => $this->department($department)->id, ...$attributes]);
    }

    private function ownedBy(User $owner): WorkItem
    {
        $booking = FlightBooking::create([
            'booking_ref' => 'TW-LN-'.strtoupper(Str::random(6)),
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
