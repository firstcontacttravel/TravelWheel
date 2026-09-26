<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightSuppliers\Pages\ListFlightSuppliers;
use App\Mail\FlightSupplierAlertMail;
use App\Models\FlightSupplierCall;
use App\Models\FlightSupplierEvent;
use App\Models\FlightSupplierSetting;
use App\Models\User;
use App\Services\Flights\FlightSupplierControl;
use App\Services\TravelnextFlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The automatic cut-off. Defaults (config/flights.php): pause when at least
 * 50% of at least 10 searches/price checks fail within 5 minutes; pause for
 * 10 minutes; 3 successes in a row resume.
 */
class FlightSupplierBreakerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->control()->enable('skylink', null);
    }

    // ── Pausing ───────────────────────────────────────────────────────────

    public function test_a_few_failures_are_not_enough_to_pause(): void
    {
        $this->calls('travelnext', ok: 0, failed: 9);

        $this->assertContains('travelnext', $this->control()->enabledKeys());
        $this->assertSame(0, FlightSupplierEvent::query()->where('action', 'auto_paused')->count());
    }

    public function test_mostly_failing_searches_pause_the_api_and_alert_support(): void
    {
        $this->calls('travelnext', ok: 4, failed: 6);

        $this->assertSame(['skylink'], $this->control()->enabledKeys());

        $setting = $this->setting('travelnext');
        $this->assertTrue($setting->isAutoPaused());
        $this->assertStringContainsString('6 of the last 10', $setting->breaker_reason);
        $this->assertStringContainsString('connection refused', $setting->breaker_reason);

        $this->assertSame(1, FlightSupplierEvent::query()->where('action', 'auto_paused')->count());
        Mail::assertSent(FlightSupplierAlertMail::class, 1);
    }

    public function test_more_failures_during_a_pause_neither_extend_it_nor_alert_again(): void
    {
        $this->calls('travelnext', ok: 0, failed: 10);
        $retryAt = $this->setting('travelnext')->breaker_retry_at;

        // Calls already under way when the pause began.
        $this->calls('travelnext', ok: 0, failed: 5);

        $this->assertEquals($retryAt, $this->setting('travelnext')->breaker_retry_at);
        Mail::assertSent(FlightSupplierAlertMail::class, 1);
    }

    public function test_bookings_and_ticketing_failures_are_not_counted(): void
    {
        $this->calls('travelnext', ok: 0, failed: 10, type: 'book');
        $this->calls('travelnext', ok: 0, failed: 10, type: 'ticket');

        $this->assertContains('travelnext', $this->control()->enabledKeys());
    }

    public function test_one_apis_failures_never_pause_another(): void
    {
        $this->calls('skylink', ok: 0, failed: 10);

        $this->assertSame(['travelnext'], $this->control()->enabledKeys());
        $this->assertFalse($this->setting('travelnext')->isAutoPaused());
    }

    // ── Trying again ──────────────────────────────────────────────────────

    public function test_after_the_pause_successes_in_a_row_resume_it(): void
    {
        $this->calls('travelnext', ok: 0, failed: 10);
        $this->travelTo(now()->addMinutes(11));

        // Let back in on trial as soon as the pause runs out.
        $this->assertContains('travelnext', $this->control()->enabledKeys());
        $this->assertTrue($this->setting('travelnext')->isOnTrial());

        $this->calls('travelnext', ok: 2, failed: 0);
        $this->assertTrue($this->setting('travelnext')->isOnTrial());

        $this->calls('travelnext', ok: 1, failed: 0);
        $this->assertSame(FlightSupplierSetting::BREAKER_CLOSED, $this->setting('travelnext')->breaker_state);
        $this->assertSame('auto_resumed', FlightSupplierEvent::query()->latest('id')->value('action'));
        Mail::assertSent(FlightSupplierAlertMail::class, 2);
    }

    public function test_a_failure_on_trial_pauses_it_again_without_another_email(): void
    {
        $this->calls('travelnext', ok: 0, failed: 10);
        $this->travelTo(now()->addMinutes(11));

        $this->calls('travelnext', ok: 1, failed: 1);

        $setting = $this->setting('travelnext');
        $this->assertTrue($setting->isAutoPaused());
        $this->assertSame(0, $setting->breaker_trial_successes);
        $this->assertSame('auto_paused_again', FlightSupplierEvent::query()->latest('id')->value('action'));
        Mail::assertSent(FlightSupplierAlertMail::class, 1);
    }

    // ── The admin stays in charge ─────────────────────────────────────────

    public function test_an_api_switched_off_by_hand_is_left_alone(): void
    {
        $this->control()->disable('travelnext', 'Funding', null);
        $this->calls('travelnext', ok: 0, failed: 10);

        $this->assertSame(FlightSupplierSetting::BREAKER_CLOSED, $this->setting('travelnext')->breaker_state);
    }

    public function test_switching_on_by_hand_clears_a_pause(): void
    {
        $this->calls('travelnext', ok: 0, failed: 10);
        $this->control()->disable('travelnext', 'Checking with the supplier', null);
        $this->control()->enable('travelnext', null);

        $this->assertContains('travelnext', $this->control()->enabledKeys());
        $this->assertSame(FlightSupplierSetting::BREAKER_CLOSED, $this->setting('travelnext')->breaker_state);
    }

    public function test_resume_now_ends_a_pause_and_is_recorded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->calls('travelnext', ok: 0, failed: 10);

        $this->control()->resumeNow('travelnext', $admin);

        $this->assertContains('travelnext', $this->control()->enabledKeys());
        $event = FlightSupplierEvent::query()->latest('id')->first();
        $this->assertSame('cutoff_cleared', $event->action);
        $this->assertSame($admin->id, $event->user_id);
    }

    public function test_the_cut_off_can_be_turned_off_for_an_api(): void
    {
        $this->control()->updateCutoff('travelnext', ['auto_cutoff' => false], null);
        $this->calls('travelnext', ok: 0, failed: 20);

        $this->assertContains('travelnext', $this->control()->enabledKeys());
    }

    public function test_an_apis_own_thresholds_replace_the_defaults(): void
    {
        $this->control()->updateCutoff('travelnext', [
            'auto_cutoff' => true,
            'min_calls' => 3,
            'failure_percent' => 100,
            'pause_minutes' => 30,
        ], null);

        // 2 of 3 is below this API's 100%.
        $this->calls('travelnext', ok: 1, failed: 2);
        $this->assertContains('travelnext', $this->control()->enabledKeys());

        // Past the window, so only the next three count: 3 of 3.
        $this->travelTo(now()->addMinutes(6));
        $this->calls('travelnext', ok: 0, failed: 3);
        $this->assertNotContains('travelnext', $this->control()->enabledKeys());
        $this->assertTrue($this->setting('travelnext')->breaker_retry_at->between(now()->addMinutes(29), now()->addMinutes(31)));
    }

    // ── What counts as a failure ──────────────────────────────────────────

    public function test_travelnext_errors_hidden_in_a_200_count_as_failures(): void
    {
        Http::fake(['travelnext.works/*' => Http::response(['AirSearchResponse' => ['AirSearchResult' => [
            'FareItineraries' => [],
            'Errors' => ['ErrorCode' => 'FLSEARCHVAL', 'ErrorMessage' => 'Invalid user_id/user_password'],
        ]]])]);

        $result = app(TravelnextFlightService::class)->search($this->criteria());

        $this->assertTrue($result['error']);
        $call = FlightSupplierCall::query()->sole();
        $this->assertFalse($call->success);
        $this->assertSame('FLSEARCHVAL Invalid user_id/user_password', $call->error_message);
    }

    public function test_travelnext_finding_no_flights_is_not_a_failure(): void
    {
        Http::fake(['travelnext.works/*' => Http::sequence()
            ->push(['AirSearchResponse' => ['AirSearchResult' => ['FareItineraries' => []]]])
            ->push(['AirSearchResponse' => ['AirSearchResult' => [
                'FareItineraries' => [],
                'Errors' => ['ErrorCode' => 'FLSEARCH', 'ErrorMessage' => 'No flights found for the given search criteria'],
            ]]])]);

        $service = app(TravelnextFlightService::class);
        $this->assertFalse($service->search($this->criteria())['error']);
        $this->assertFalse($service->search($this->criteria())['error']);

        $this->assertSame(0, FlightSupplierCall::query()->where('success', false)->count());
    }

    // ── The admin screen and the email ────────────────────────────────────

    public function test_the_screen_shows_a_pause_and_an_admin_can_end_it(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->calls('travelnext', ok: 0, failed: 10);
        $travelnext = $this->setting('travelnext');

        Livewire::test(ListFlightSuppliers::class)
            ->assertSee('Paused automatically')
            ->assertSee('10 of 10 failed')
            ->assertTableActionVisible('resumeNow', $travelnext)
            ->callTableAction('resumeNow', $travelnext);

        $this->assertSame(FlightSupplierSetting::BREAKER_CLOSED, $travelnext->fresh()->breaker_state);
    }

    public function test_an_admin_sets_an_apis_thresholds(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $travelnext = $this->setting('travelnext');

        Livewire::test(ListFlightSuppliers::class)
            ->callTableAction('cutoffSettings', $travelnext, data: [
                'auto_cutoff' => true,
                'failure_percent' => 80,
                'min_calls' => null,
                'window_minutes' => 15,
                'pause_minutes' => null,
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $travelnext->fresh();
        $this->assertSame(80, $fresh->cutoff_failure_percent);
        $this->assertNull($fresh->cutoff_min_calls);
        $this->assertSame(['min_calls' => 10, 'failure_percent' => 80, 'window_minutes' => 15, 'pause_minutes' => 10], $fresh->cutoffThresholds());
        $this->assertSame('cutoff_settings_changed', FlightSupplierEvent::query()->latest('id')->value('action'));
    }

    public function test_the_alert_email_says_what_happened(): void
    {
        $this->calls('travelnext', ok: 0, failed: 10);

        $event = FlightSupplierEvent::query()->where('action', 'auto_paused')->sole();
        $html = (new FlightSupplierAlertMail($event))->render();

        $this->assertStringContainsString('TravelNext has been paused automatically', $html);
        $this->assertStringContainsString('connection refused', $html);
        $this->assertSame('ALERT: TravelNext paused automatically — searches failing', (new FlightSupplierAlertMail($event))->envelope()->subject);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function calls(string $supplier, int $ok, int $failed, string $type = 'search'): void
    {
        foreach (array_merge(array_fill(0, $ok, true), array_fill(0, $failed, false)) as $success) {
            FlightSupplierCall::record([
                'supplier' => $supplier,
                'call_type' => $type,
                'success' => $success,
                'error_message' => $success ? null : 'cURL error 7: connection refused',
            ]);
        }
    }

    private function setting(string $key): FlightSupplierSetting
    {
        return FlightSupplierSetting::query()->where('key', $key)->sole();
    }

    private function control(): FlightSupplierControl
    {
        return app(FlightSupplierControl::class);
    }

    private function criteria(): array
    {
        return [
            'trip' => 'oneway',
            'from' => 'Lagos (LOS)',
            'to' => 'London (LHR)',
            'depart' => now()->addMonth()->format('d/m/Y'),
            'adults' => 1,
            'flight_type' => 'Y',
        ];
    }
}
