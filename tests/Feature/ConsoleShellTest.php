<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 2 of the console redesign: the shell — a 56px icon rail with group
 * flyouts, a 48px topbar, and the page frame.
 *
 * Three of Filament's own layout views are FORKED into
 * resources/views/vendor/filament-panels/ rather than styled, because the
 * console changes their structure: the rail runs full height beside the topbar
 * instead of beneath it. Forking vendor views is the real cost of this
 * approach, and the drift guard at the bottom of this file is what keeps that
 * cost visible.
 */
class ConsoleShellTest extends TestCase
{
    use RefreshDatabase;

    private const FORKED = [
        'components/layout/index',
        'livewire/sidebar',
        'livewire/topbar',
    ];

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /*
     * ── Structure ───────────────────────────────────────────────────────
     */

    public function test_the_panel_renders_the_console_shell_rather_than_filaments_sidebar(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/flight-bookings')
            ->assertOk()
            ->assertSee('tc-rail', false)
            ->assertSee('tc-shell-main', false)
            ->assertSee('tc-topbar', false);
    }

    /**
     * The rail navigates by group, so a group without an icon is a blank square
     * in a column of blank squares. Every declared group must carry one.
     */
    public function test_every_navigation_group_has_an_icon(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin');

        $groups = Filament::getPanel('admin')->getNavigationGroups();

        $this->assertNotEmpty($groups);

        foreach ($groups as $group) {
            $this->assertNotNull(
                $group->getIcon(),
                "Navigation group [{$group->getLabel()}] has no icon; the rail would render it blank.",
            );
        }
    }

    /**
     * Discovered alphabetically, Flight Bookings — the busiest screen in the
     * panel — sat below Air Cargo and Car Hire.
     */
    public function test_operations_is_the_first_group_in_the_rail(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin');

        $labels = array_values(array_map(
            fn ($group) => $group->getLabel(),
            Filament::getPanel('admin')->getNavigationGroups(),
        ));

        $this->assertSame('Operations', $labels[0] ?? null);
    }

    /*
     * ── Palette ─────────────────────────────────────────────────────────
     */

    /**
     * Filament paints solid buttons with shade 600, and Color::hex() keeps only
     * the hue of what it is given while rebuilding lightness from a template —
     * #303191 came back at L 0.598 and every primary button read as disabled.
     * The panel ramp and the console's --tc-brand-* tokens must agree.
     */
    public function test_the_panel_ramp_matches_the_console_brand_tokens(): void
    {
        $ramp = (new ReflectionClass(AdminPanelProvider::class))->getConstant('BRAND_RAMP');

        $this->assertIsArray($ramp);
        $this->assertSame('oklch(0.378 0.154 275.68)', $ramp[600] ?? null);

        $tokens = $this->css('tokens.css');

        foreach ($ramp as $shade => $value) {
            $this->assertStringContainsString(
                "--tc-brand-{$shade}: {$value}",
                $tokens,
                "Panel primary {$shade} has drifted from --tc-brand-{$shade}.",
            );
        }
    }

    /*
     * ── Dark mode ───────────────────────────────────────────────────────
     */

    /**
     * Filament's dark-mode.js toggles `.dark` on <html>; the standalone design
     * specimen has no Filament and uses `.tc-dark`. Both must drive the same
     * token block, or the panel keeps its light surfaces in dark mode.
     */
    public function test_the_tokens_respond_to_filaments_dark_class(): void
    {
        $tokens = $this->css('tokens.css');

        $this->assertMatchesRegularExpression(
            '/\.tc-dark,\s*\n\.dark\s*\{/',
            $tokens,
            'The token file no longer responds to Filament\'s `.dark` class.',
        );
    }

    /** The shell must not need dark-mode rules of its own; roles handle it. */
    public function test_the_shell_carries_no_dark_mode_overrides(): void
    {
        $this->assertStringNotContainsString('.tc-dark', $this->rules('shell.css'));
        $this->assertStringNotContainsString('.dark ', $this->rules('shell.css'));
    }

    /*
     * ── Regressions this phase fixed ────────────────────────────────────
     */

    /**
     * Tailwind v4 writes the `translate` PROPERTY, not `transform`. Filament
     * hides .fi-sidebar with `-translate-x-full` and restores it only at `lg`,
     * so between 768px and 1023px the rail sat off screen with no toggle to
     * open it — and a `transform: translateX(0)` would have applied alongside
     * Filament's `translate` and changed nothing.
     */
    public function test_the_rail_neutralises_filaments_translate_rather_than_using_transform(): void
    {
        $shell = $this->rules('shell.css');

        $this->assertStringContainsString('translate: none;', $shell);
        $this->assertStringContainsString('translate: -100% 0;', $shell);
        $this->assertStringContainsString('translate: 0 0;', $shell);
        $this->assertStringNotContainsString('transform: translateX', $shell);
    }

    /**
     * Filament hides .fi-main-ctn with `opacity: 0` and its own layout reveals
     * it again with an inline x-bind:style. The console's layout carries no
     * such binding, so the reveal is declared in CSS — without it the whole
     * page renders and is invisible.
     */
    public function test_the_content_container_is_revealed(): void
    {
        $shell = $this->rules('shell.css');

        $this->assertMatchesRegularExpression('/\.tc-shell-main\s*\{[^}]*opacity:\s*1/s', $shell);
        $this->assertMatchesRegularExpression('/\.tc-shell-main\s*\{[^}]*width:\s*auto/s', $shell);
    }

    /**
     * A `.fi-btn { width: 100% }` written to fix the cramped sign-in button
     * stretched every button in the panel, including the pagination control.
     */
    public function test_the_global_full_width_button_rule_is_gone(): void
    {
        $theme = (string) file_get_contents(base_path('resources/css/filament/admin/theme.css'));
        $stripped = (string) preg_replace('#/\*.*?\*/#s', '', $theme);

        $this->assertStringNotContainsString('button.fi-btn', $stripped);
    }

    /** The old theme painted its own near-black over the shell's token. */
    public function test_the_old_theme_no_longer_overrides_shell_surfaces_in_dark_mode(): void
    {
        $theme = (string) file_get_contents(base_path('resources/css/filament/admin/theme.css'));
        $stripped = (string) preg_replace('#/\*.*?\*/#s', '', $theme);

        $this->assertStringNotContainsString('.dark .fi-body', $stripped);
        $this->assertStringNotContainsString('.dark .fi-topbar', $stripped);
    }

    /*
     * ── Drift guard ─────────────────────────────────────────────────────
     */

    /**
     * Forking vendor views means a `composer update` can change the original
     * underneath us and the fork silently keeps rendering the old structure —
     * losing a render hook, an accessibility attribute or a new feature with no
     * error anywhere.
     *
     * These hashes pin the vendor originals the forks were taken from. When
     * this fails, diff the vendor file against the fork, port what changed, and
     * update the hash. It failing is the point.
     */
    public function test_the_forked_vendor_views_have_not_changed_upstream(): void
    {
        $expected = [
            'components/layout/index' => '4023855894581467',
            'livewire/sidebar' => '3aa1ff2bd9de001d',
            'livewire/topbar' => 'd65df31237228385',
        ];

        foreach (self::FORKED as $view) {
            $path = base_path("vendor/filament/filament/resources/views/{$view}.blade.php");

            $this->assertFileExists($path, "Filament no longer ships {$view}; the fork may be orphaned.");

            $this->assertSame(
                $expected[$view],
                substr(hash_file('sha256', $path), 0, 16),
                "Filament's {$view} changed upstream. Diff it against the fork in ".
                "resources/views/vendor/filament-panels/{$view}.blade.php, port anything ".
                'that matters, then update this hash.',
            );
        }
    }

    /** Every fork must actually exist, or the override silently does nothing. */
    public function test_each_fork_is_present_and_overrides_the_vendor_view(): void
    {
        foreach (self::FORKED as $view) {
            $fork = base_path("resources/views/vendor/filament-panels/{$view}.blade.php");

            $this->assertFileExists($fork);
            $this->assertSame(
                realpath($fork),
                realpath(view("filament-panels::".str_replace('/', '.', $view))->getPath()),
                "filament-panels::{$view} does not resolve to the fork.",
            );
        }
    }

    /**
     * The forks must keep Filament's render hooks. Dropping one breaks plugins
     * and panel features quietly — nothing errors, the markup simply vanishes.
     */
    public function test_the_forks_preserve_filaments_render_hooks(): void
    {
        $required = [
            'components/layout/index' => ['LAYOUT_START', 'LAYOUT_END', 'CONTENT_START', 'CONTENT_END', 'CONTENT_BEFORE', 'CONTENT_AFTER', 'TOPBAR_BEFORE', 'TOPBAR_AFTER', 'FOOTER'],
            'livewire/sidebar' => ['SIDEBAR_START', 'SIDEBAR_NAV_START', 'SIDEBAR_NAV_END', 'SIDEBAR_FOOTER'],
            'livewire/topbar' => ['TOPBAR_START', 'TOPBAR_END', 'GLOBAL_SEARCH_BEFORE', 'GLOBAL_SEARCH_AFTER'],
        ];

        foreach ($required as $view => $hooks) {
            $source = (string) file_get_contents(
                base_path("resources/views/vendor/filament-panels/{$view}.blade.php"),
            );

            foreach ($hooks as $hook) {
                $this->assertStringContainsString(
                    $hook,
                    $source,
                    "The {$view} fork dropped the {$hook} render hook.",
                );
            }
        }
    }

    private function css(string $file): string
    {
        return (string) file_get_contents(base_path('resources/css/admin/'.$file));
    }

    /**
     * The stylesheet with its comments stripped. These files explain what they
     * are NOT doing — "there is no .tc-dark block here", "a transform:
     * translateX would change nothing" — and an assertion that a string is
     * absent would otherwise trip over the prose describing its absence.
     */
    private function rules(string $file): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $this->css($file));
    }
}
