<?php

namespace Tests\Feature;

use App\Console\Commands\FlightReleaseCheck;
use App\Filament\Widgets\OperationsTriage;
use App\Models\ReportingSyncRun;
use App\Models\SystemHeartbeat;
use App\Models\User;
use App\Services\Reporting\ReportingSynchronizer;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Four places in the admin/ops code asked "is this stale?" with
 * now()->diffInMinutes($somethingInThePast). Carbon returns a *signed* value
 * from that call, so for any past timestamp it is negative — and every
 * threshold comparison built on it silently inverted:
 *
 *   -44640 <= 3   is true   → the dashboard called a month-dead scheduler healthy
 *   -44640 >  3   is false  → the release gate never warned about one
 *   -44640 >= 5   is false  → reporting facts were never re-synced after the first run
 *   -44640 >  30  is false  → system health never reported a stuck email outbox
 *
 * Each of those is a monitor that reports "fine" precisely when it should not,
 * which is the worst way for one to fail. These tests pin the direction.
 */
class AdminStalenessSignalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_raises_a_long_dead_scheduler(): void
    {
        SystemHeartbeat::query()->create([
            'name' => 'scheduler',
            'last_seen_at' => now()->subMonth(),
        ]);

        $this->assertContains(
            'Scheduler stale',
            $this->schedulerSignals(),
            'A scheduler last seen a month ago was not raised.',
        );
    }

    public function test_the_dashboard_stays_quiet_about_a_live_scheduler(): void
    {
        SystemHeartbeat::query()->create([
            'name' => 'scheduler',
            'last_seen_at' => now()->subMinute(),
        ]);

        $this->assertNotContains('Scheduler stale', $this->schedulerSignals());
    }

    /** No heartbeat row at all has to read as stale too, not as an absent problem. */
    public function test_a_scheduler_that_never_checked_in_is_raised(): void
    {
        $this->assertContains('Scheduler stale', $this->schedulerSignals());
    }

    public function test_the_release_check_warns_about_a_stale_scheduler(): void
    {
        SystemHeartbeat::query()->create([
            'name' => 'scheduler',
            'last_seen_at' => now()->subHours(6),
        ]);

        $this->artisan(FlightReleaseCheck::class)
            ->expectsOutputToContain('scheduler heartbeat');
    }

    /**
     * The admin Reports page reads reporting_facts. With the comparison
     * inverted, syncIfStale() stopped doing anything the moment one run had
     * completed, so those numbers froze at whatever the first sync produced.
     */
    public function test_reporting_facts_resync_once_the_freshness_window_has_passed(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('reporting_facts')) {
            $this->markTestSkipped('reporting_facts is not present in this schema.');
        }

        config(['reporting.fresh_for_minutes' => 5]);

        ReportingSyncRun::query()->create([
            'status' => 'completed',
            'started_at' => now()->subMinutes(40),
            'completed_at' => now()->subMinutes(40),
        ]);

        app(ReportingSynchronizer::class)->syncIfStale();

        $this->assertSame(
            2,
            ReportingSyncRun::query()->count(),
            'A 40-minute-old sync inside a 5-minute freshness window was treated as fresh.',
        );
    }

    /** And a run inside the window must still be left alone. */
    public function test_reporting_facts_are_left_alone_while_still_fresh(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('reporting_facts')) {
            $this->markTestSkipped('reporting_facts is not present in this schema.');
        }

        config(['reporting.fresh_for_minutes' => 30]);

        ReportingSyncRun::query()->create([
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        app(ReportingSynchronizer::class)->syncIfStale();

        $this->assertSame(1, ReportingSyncRun::query()->count());
    }

    /**
     * System Health printed "Oldest pending email: -44640 minute(s)" and, more
     * to the point, could never cross its own 30-minute threshold.
     */
    public function test_system_health_reports_a_stuck_email_outbox(): void
    {
        DB::table('notification_outboxes')->insert([
            'kind' => 'eticket',
            'recipient' => 'traveller@example.test',
            'payload' => json_encode([]),
            'status' => 'pending',
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $report = app(SystemHealthService::class)->run(includeConnectivity: false);

        $age = collect($report['checks'])
            ->pluck('details')
            ->filter(fn ($details): bool => is_array($details) && array_key_exists('Oldest pending email', $details))
            ->first()['Oldest pending email'] ?? null;

        $this->assertNotNull($age, 'The queue check no longer reports the oldest pending email.');
        $this->assertStringNotContainsString('-', (string) $age, "The oldest pending email was reported as a negative age: {$age}");
        $this->assertSame('180 minute(s)', $age);

        $failed = collect($report['checks'])
            ->filter(fn ($check): bool => ($check['status'] ?? null) === 'failed')
            ->flatMap(fn ($check) => (array) ($check['details']['Failures'] ?? []))
            ->implode(' ');

        $this->assertStringContainsString('Email delivery', $failed, 'A three-hour-old unsent email did not trip the 30-minute failure.');
    }

    /**
     * Phase 5 replaced the stats-overview tile with the triage widget's
     * "needs attention" band. The behaviour under test is unchanged — a dead
     * scheduler must be reported as dead — so the assertion moved with it
     * rather than being dropped.
     *
     * @return list<string>
     */
    private function schedulerSignals(): array
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        return array_column(app(OperationsTriage::class)->getBroken(), 'label');
    }
}
