<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 of the console redesign: controls, forms and detail.
 *
 * This layer reaches every create, edit and view page in the panel — 19 Edit,
 * 16 Create and 14 View pages across 36 resources — without any of them being
 * touched individually.
 */
class ConsoleFormTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /*
     * ── The trap this layer is built around ─────────────────────────────
     */

    /**
     * Filament draws control edges with Tailwind's `ring-1`, which is a
     * BOX-SHADOW and not a border. Any rule that takes over an edge with
     * `border` and forgets to clear the ring renders two edges, one inside the
     * other — and it looks like a slightly-too-thick border rather than like a
     * mistake, which is why it survives review.
     *
     * Every surface below takes over its own edge, so every one of them has to
     * clear it.
     */
    public function test_every_surface_that_takes_over_an_edge_also_clears_filaments_ring(): void
    {
        $css = $this->rules('form.css');

        foreach ([
            '.fi-btn',
            '.fi-icon-btn',
            '.fi-badge',
            '.fi-input-wrp',
            '.fi-fieldset',
            '.fi-modal-window',
            '.fi-dropdown-panel',
            '.fi-no-notification',
            '.fi-callout',
        ] as $selector) {
            $block = $this->block($css, $selector);

            $this->assertNotNull($block, "No rule found for {$selector}.");
            $this->assertStringContainsString(
                '--tw-ring-shadow: 0 0 #0000;',
                $block,
                "{$selector} takes over its edge but leaves Filament's ring on; it will render a double border.",
            );
        }
    }

    /*
     * ── Read and write are different jobs ───────────────────────────────
     */

    /**
     * On a form the label leads, because the value does not exist yet. On a
     * view page the value leads, because the record is read far more often
     * than it is edited. Same data, opposite emphasis — so the infolist label
     * steps back to a micro tracked cap and the value carries body weight.
     */
    public function test_a_view_page_gives_weight_to_the_value_and_a_form_to_the_label(): void
    {
        $css = $this->rules('form.css');

        $entryLabel = $this->block($css, '.fi-in-entry-label');
        $this->assertNotNull($entryLabel);
        $this->assertStringContainsString('--tc-text-micro', $entryLabel);
        $this->assertStringContainsString('text-transform: uppercase', $entryLabel);
        $this->assertStringContainsString('--tc-text-tertiary', $entryLabel);

        $fieldLabel = $this->block($css, '.fi-fo-field-label-content');
        $this->assertNotNull($fieldLabel);
        $this->assertStringContainsString('--tc-text-body', $fieldLabel);
        $this->assertStringContainsString('--tc-text-primary', $fieldLabel);
    }

    /*
     * ── Contrast ────────────────────────────────────────────────────────
     */

    /**
     * Filament fills a primary button with shade 600 and sets white on it.
     * Shade 600 is the brand navy, so this is the one contrast pair the whole
     * panel depends on and it is worth pinning rather than eyeballing — a
     * screenshot at JPEG compression is not evidence about contrast.
     */
    public function test_white_on_the_primary_button_passes_wcag_aa(): void
    {
        $ratio = $this->contrast('#303191', '#ffffff');

        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "White on the brand navy is {$ratio}:1, below the 4.5:1 AA threshold for body text.",
        );
    }

    /** The console's own status inks, used as text on their tinted fills. */
    public function test_status_inks_pass_wcag_aa_on_their_own_tints(): void
    {
        foreach ([
            'critical' => ['#b42318', '#fef3f2'],
            'warning' => ['#b54708', '#fffaeb'],
            'positive' => ['#067647', '#ecfdf3'],
            'info' => ['#175cd3', '#eff8ff'],
        ] as $name => [$ink, $tint]) {
            $ratio = $this->contrast($tint, $ink);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "Status [{$name}] is {$ratio}:1 on its own tint.",
            );
        }
    }

    /*
     * ── System discipline ───────────────────────────────────────────────
     */

    public function test_the_form_layer_carries_no_dark_mode_overrides(): void
    {
        $css = $this->rules('form.css');

        $this->assertStringNotContainsString('.tc-dark', $css);
        $this->assertStringNotContainsString('.dark ', $css);
    }

    /** Sections are defined once, not in both the table and the form layer. */
    public function test_section_rules_are_not_duplicated_across_layers(): void
    {
        $this->assertStringNotContainsString(
            '.fi-section-header-heading',
            $this->rules('table.css'),
            'Section rules live in form.css; a second copy in table.css will drift.',
        );
    }

    /**
     * 30px is what lets an action sit inside a 40px table row. If the control
     * height token moves, Phase 3's row height goes with it.
     */
    public function test_controls_are_sized_from_the_shared_token(): void
    {
        $css = $this->rules('form.css');

        $this->assertStringContainsString('min-height: var(--tc-control-height);', $css);
        $this->assertStringContainsString('--tc-control-height: 30px;', $this->rules('tokens.css'));
    }

    /*
     * ── It still works ──────────────────────────────────────────────────
     */

    public function test_create_and_edit_pages_render(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/exchange-rates/create')
            ->assertOk()
            ->assertSee('Source currency');
    }

    public function test_a_view_page_renders(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/visa-applications')->assertOk();
    }

    /*
     * ── Helpers ─────────────────────────────────────────────────────────
     */

    /** The body of the first rule whose selector list contains $selector. */
    private function block(string $css, string $selector): ?string
    {
        $pattern = '/(?:^|\})[^{}]*'.preg_quote($selector, '/').'\s*(?:,[^{}]*)?\{([^}]*)\}/m';

        return preg_match($pattern, $css, $matches) ? $matches[1] : null;
    }

    /** WCAG 2.1 relative-luminance contrast ratio between two sRGB hexes. */
    private function contrast(string $a, string $b): float
    {
        $luminance = static function (string $hex): float {
            $hex = ltrim($hex, '#');

            $channel = static function (int $value): float {
                $v = $value / 255;

                return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            };

            return 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
                + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
                + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));
        };

        $l1 = $luminance($a);
        $l2 = $luminance($b);

        return round((max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05), 2);
    }

    /** Stylesheet with comments stripped; they describe what is absent. */
    private function rules(string $file): string
    {
        $css = (string) file_get_contents(base_path('resources/css/admin/'.$file));

        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }
}
