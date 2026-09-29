<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 of the console redesign: sign-in, Reports, System Health — and the
 * end of the migration.
 *
 * The admin panel was carrying five design systems at once: Filament's own,
 * the 1,790-line tw-* theme, an inline <style> block in System Health, another
 * in Reports, and the console. This phase leaves one.
 */
class ConsoleMigrationCompleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /*
     * ── One system ──────────────────────────────────────────────────────
     */

    /**
     * theme.css was 1,790 lines. It is a manifest now — Tailwind, Filament's
     * component CSS, and the console, in that order. If rules start
     * accumulating here again it is because something could not be reached by
     * the system, which is the problem this whole project was about.
     */
    public function test_the_panel_stylesheet_is_a_manifest_not_a_stylesheet(): void
    {
        $theme = (string) file_get_contents(base_path('resources/css/filament/admin/theme.css'));
        $rules = (string) preg_replace('#/\*.*?\*/#s', '', $theme);

        $this->assertStringNotContainsString(
            'tw-',
            $rules,
            'tw-* CSS is back in the panel theme.',
        );
        $this->assertStringContainsString('@import "../../admin/system.css";', $rules);
        $this->assertLessThan(
            60,
            substr_count($theme, "\n"),
            'The panel theme is growing rules again instead of importing the system.',
        );
    }

    /**
     * Both custom pages carried their own minified <style> with their own
     * palette — a design system in a Blade file that no token could reach.
     */
    public function test_no_admin_page_ships_its_own_inline_stylesheet(): void
    {
        foreach (glob(base_path('resources/views/filament/pages/*.blade.php')) as $page) {
            // Blade comments are stripped first: the comment recording that
            // this page's inline stylesheet was removed itself says "<style>".
            $this->assertStringNotContainsString(
                '<style>',
                (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($page)),
                basename($page).' declares its own inline stylesheet again.',
            );
        }
    }

    /** And no page should be hand-picking hexes for status either. */
    public function test_no_admin_page_hardcodes_a_colour(): void
    {
        foreach (array_merge(
            glob(base_path('resources/views/filament/pages/*.blade.php')),
            glob(base_path('resources/views/filament/pages/partials/*.blade.php')),
            glob(base_path('resources/views/filament/booking/*.blade.php')),
        ) as $view) {
            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{3,6}\b/',
                (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($view)),
                basename($view).' hardcodes a colour instead of using a token.',
            );
        }
    }

    /*
     * ── Sign-in ─────────────────────────────────────────────────────────
     */

    public function test_the_sign_in_page_renders_on_the_system(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('fi-simple-main', false);
    }

    /**
     * Full width belongs to the login form and nowhere else. The panel's
     * original stylesheet reached for a global `.fi-btn { width: 100% }` to
     * get it, which stretched table actions, header actions and the
     * pagination control across whatever space they sat in.
     */
    public function test_full_width_buttons_are_scoped_to_the_login_form(): void
    {
        $css = (string) preg_replace(
            '#/\*.*?\*/#s',
            '',
            (string) file_get_contents(base_path('resources/css/admin/pages.css')),
        );

        $this->assertStringContainsString('.fi-simple-main .fi-form .fi-btn', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/(^|\})\s*\.fi-btn\s*\{[^}]*width:\s*100%/s',
            $css,
        );
    }

    /*
     * ── The pages still work ────────────────────────────────────────────
     */

    public function test_system_health_renders(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/system-health')
            ->assertOk()
            ->assertSee('Live operational diagnostics')
            ->assertSee('Recent health runs')
            ->assertSee('tc-metric', false);
    }

    public function test_reports_renders(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/reports')
            ->assertOk()
            ->assertSee('tw-bi', false);
    }

    /*
     * ── Dead code stayed dead ───────────────────────────────────────────
     */

    /**
     * journeyColumn lost its only call site when the queue moved to routeCell
     * in Phase 3, and took three helpers with it. Dead renderers are how dead
     * CSS survives a cleanup.
     */
    public function test_the_orphaned_journey_renderers_are_gone(): void
    {
        $source = (string) file_get_contents(base_path(
            'app/Filament/Resources/FlightBookings/Tables/FlightBookingsTable.php',
        ));

        foreach (['journeyColumn', 'journeyGroups', 'journeySegmentDate', 'journeySegmentTime'] as $method) {
            $this->assertStringNotContainsString(
                "function {$method}(",
                $source,
                "{$method}() is back but nothing calls it.",
            );
        }
    }

    /**
     * Two tables built the action-modal context block identically and
     * separately. One partial now.
     */
    public function test_both_action_modals_share_one_context_partial(): void
    {
        foreach ([
            'app/Filament/Resources/FlightBookings/Tables/FlightBookingsTable.php',
            'app/Filament/Resources/TravelFlexApplications/Tables/TravelFlexApplicationsTable.php',
        ] as $file) {
            $this->assertStringContainsString(
                "view('filament.booking.action-context'",
                (string) file_get_contents(base_path($file)),
                basename($file).' builds its own action context again.',
            );
        }
    }

    /** No presenter should be writing class names in PHP any more. */
    public function test_no_presenter_writes_css_class_names(): void
    {
        foreach (glob(base_path('app/Support/Admin/*.php')) as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/["\']tw-[a-z0-9-]+/',
                (string) file_get_contents($file),
                basename($file).' is writing tw-* class names in PHP again.',
            );
        }
    }
}
