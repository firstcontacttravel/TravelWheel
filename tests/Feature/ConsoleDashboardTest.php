<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\OperationsTriage;
use App\Models\FlightBooking;
use App\Models\SystemHeartbeat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 5 of the console redesign: the operations dashboard.
 *
 * Replaces two stats-overview widgets — seventeen tiles of identical weight, in
 * which "Failed payment 5" and "Paid revenue NGN 370,009.40" were
 * indistinguishable apart from a 12px icon at the end of a caption.
 *
 * The order is the design: broken, then waiting, then money.
 */
class ConsoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function booking(array $attributes = []): FlightBooking
    {
        return FlightBooking::query()->create(array_merge([
            'fare_source_code' => 'test-fare-source',
            'booking_ref' => 'TW-'.fake()->bothify('??####'),
            'route' => 'LOS → DXB',
            'currency' => 'NGN',
            'total_price' => 100000,
            'booking_status' => 'ticketed',
            'payment_status' => 'paid',
            'ticket_ordered' => true,
        ], $attributes));
    }

    /** A live scheduler, so it stops being a permanent "broken" signal. */
    private function healthyScheduler(): void
    {
        SystemHeartbeat::query()->updateOrCreate(
            ['name' => 'scheduler'],
            ['last_seen_at' => now()->subSeconds(30)],
        );
    }

    private function triage(): OperationsTriage
    {
        $this->actingAs($this->admin());

        return app(OperationsTriage::class);
    }

    /*
     * ── The all-clear ───────────────────────────────────────────────────
     */

    /**
     * The most important behaviour on this screen. A dashboard that is usually
     * quiet is one people keep looking at; a dashboard that is permanently
     * amber is one they stop seeing. With nothing wrong, the whole band
     * collapses to a single calm line.
     */
    public function test_nothing_is_broken_when_nothing_is_broken(): void
    {
        $this->healthyScheduler();
        $this->booking();

        $this->assertSame([], $this->triage()->getBroken());
    }

    public function test_the_all_clear_line_is_shown_rather_than_an_empty_band(): void
    {
        $this->healthyScheduler();
        $this->actingAs($this->admin());

        // Filament widgets are lazy — the page itself only ships placeholders
        // and the content arrives on a second request — so the widget has to
        // be rendered directly rather than asserted against the page HTML.
        Livewire::test(OperationsTriage::class)
            ->assertSee('Nothing needs attention');
    }

    /*
     * ── Broken ──────────────────────────────────────────────────────────
     */

    public function test_a_paid_booking_with_failed_ticketing_is_raised(): void
    {
        $this->healthyScheduler();
        $this->booking(['payment_status' => 'paid', 'booking_status' => 'ticketing_failed']);

        $broken = $this->triage()->getBroken();

        $this->assertCount(1, $broken);
        $this->assertSame('Ticketing failed', $broken[0]['label']);
        $this->assertSame(1, $broken[0]['count']);
        // Every alert has to say WHY, or it is a number nobody can act on.
        $this->assertNotEmpty($broken[0]['detail']);
        $this->assertNotNull($broken[0]['url'], 'A raised signal should link to its queue.');
    }

    public function test_a_failed_payment_is_raised(): void
    {
        $this->healthyScheduler();
        $this->booking(['payment_status' => 'failed', 'booking_status' => 'pending']);

        $labels = array_column($this->triage()->getBroken(), 'label');

        $this->assertContains('Failed payment', $labels);
    }

    /**
     * Carbon's diffInMinutes is signed, so `now()->diffInMinutes($past) <= 3`
     * was true for any past timestamp and the old dashboard called a
     * month-dead scheduler "Healthy".
     */
    public function test_a_stale_scheduler_is_raised_and_a_live_one_is_not(): void
    {
        SystemHeartbeat::query()->create(['name' => 'scheduler', 'last_seen_at' => now()->subMonth()]);

        $this->assertContains('Scheduler stale', array_column($this->triage()->getBroken(), 'label'));

        $this->healthyScheduler();

        $this->assertNotContains('Scheduler stale', array_column($this->triage()->getBroken(), 'label'));
    }

    /** A state, not a quantity — a stale scheduler is not "3 of" anything. */
    public function test_a_state_signal_carries_no_count(): void
    {
        $scheduler = collect($this->triage()->getBroken())->firstWhere('label', 'Scheduler stale');

        $this->assertNotNull($scheduler);
        $this->assertNull($scheduler['count']);
    }

    /*
     * ── Waiting ─────────────────────────────────────────────────────────
     */

    public function test_normal_queues_are_counted_without_being_raised(): void
    {
        $this->healthyScheduler();
        $this->booking(['payment_status' => 'awaiting_bank_transfer', 'booking_status' => 'pending', 'ticket_ordered' => false]);

        $triage = $this->triage();

        $this->assertSame([], $triage->getBroken(), 'A booking awaiting transfer is a queue, not a fault.');

        $waiting = collect($triage->getWaiting())->firstWhere('label', 'Awaiting transfer');
        $this->assertSame(1, $waiting['count']);
    }

    /** Visa queues belong only to people who can act on them. */
    public function test_visa_queues_are_hidden_from_users_who_cannot_see_visa_operations(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'visa_role' => null]));
        $labels = array_column(app(OperationsTriage::class)->getWaiting(), 'label');

        $visaOnly = array_filter($labels, fn (string $l): bool => str_starts_with($l, 'Visa'));

        $this->assertSame(
            auth()->user()->canViewVisaOperations() ? $visaOnly : [],
            $visaOnly,
        );
    }

    /*
     * ── Order ───────────────────────────────────────────────────────────
     */

    /**
     * The order IS the design. Money used to be the loudest thing on the
     * screen; it is context, not an action.
     */
    public function test_the_page_puts_broken_before_waiting_before_money(): void
    {
        $this->healthyScheduler();
        $this->actingAs($this->admin());

        $html = Livewire::test(OperationsTriage::class)->html();

        $attention = strpos($html, 'Needs attention');
        $waiting = strpos($html, '>Waiting<');
        $money = strpos($html, '>Money<');

        $this->assertNotFalse($attention);
        $this->assertNotFalse($waiting);
        $this->assertNotFalse($money);
        $this->assertLessThan($waiting, $attention, 'Waiting is shown before what is broken.');
        $this->assertLessThan($money, $waiting, 'Money is shown before the queues.');
    }

    /*
     * ── What it replaced ────────────────────────────────────────────────
     */

    public function test_the_generic_stat_grids_are_gone(): void
    {
        foreach (['PaymentOperationsOverview', 'VisaOperationsOverview'] as $widget) {
            $this->assertFileDoesNotExist(
                base_path("app/Filament/Widgets/{$widget}.php"),
                "{$widget} still exists; the dashboard would render two competing designs.",
            );
        }

        $widgets = (new Dashboard)->getWidgets();

        $this->assertSame(OperationsTriage::class, $widgets[0] ?? null, 'Triage must be first on the page.');
    }

    /**
     * Filament's widget default is every 5 seconds: a full panel boot and a
     * dozen aggregate queries every five seconds, per open dashboard, forever.
     * The queries are cheap — around 50ms for all of them — so the frequency
     * was the cost, not the work.
     */
    public function test_no_widget_polls_at_filaments_five_second_default(): void
    {
        foreach (glob(base_path('app/Filament/Widgets/*.php')) as $file) {
            $class = 'App\\Filament\\Widgets\\'.basename($file, '.php');
            $property = (new ReflectionClass($class))->getDefaultProperties()['pollingInterval'] ?? null;

            $this->assertNotSame(
                '5s',
                $property,
                basename($file).' polls at the 5-second default.',
            );
            $this->assertNotNull(
                $property,
                basename($file).' does not set a polling interval, so it inherits the 5-second default.',
            );
        }
    }
}
