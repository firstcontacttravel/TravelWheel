<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phase 8 of the console redesign: the cross-cutting accessibility audit.
 *
 * Every phase before this built responsive and dark as it went, so this is the
 * sweep, not the first attempt. What it found was worth the sweep:
 *
 *  - --tc-text-tertiary was --tc-n-500, which measures 3.69:1 on the sunken
 *    surface. Secondary and tertiary both moved one step darker.
 *  - Placeholder text was borrowing the DISABLED role. A disabled control is
 *    exempt from AA; an empty field's prompt and a "no value" marker are not.
 *  - An empty queue's count was muted to the same disabled role at 2.51:1.
 *  - Filament pairs its warning button with white in dark mode at 4.15:1.
 *  - base.css was scoped to .tc-root, which only the design-system specimen
 *    sets, so the focus ring, the selection colour, the scrollbars and the
 *    reduced-motion block had never applied inside the actual panel.
 *  - The rail's nav was labelled "Expand sidebar": an action, not a region.
 *
 * The contrast maths below is the real thing, oklch through oklab to linear
 * sRGB and then WCAG relative luminance, so changing a palette value fails
 * this suite rather than quietly dropping a role under 4.5:1.
 */
class ConsoleAccessibilityTest extends TestCase
{
    private const AA_NORMAL = 4.5;

    private static ?string $tokens = null;

    private function tokens(): string
    {
        return self::$tokens ??= (string) file_get_contents(
            base_path('resources/css/admin/tokens.css'),
        );
    }

    private function css(string $file): string
    {
        // Comments are stripped so an assertion can never be satisfied, or
        // broken, by prose describing the thing it asserts about.
        return (string) preg_replace(
            '#/\*.*?\*/#s',
            '',
            (string) file_get_contents(base_path("resources/css/admin/{$file}")),
        );
    }

    /** Resolve a token name to its literal oklch(...) declaration. */
    private function resolve(string $name, string $scope = 'light'): string
    {
        $css = $this->tokens();

        // The dark remap lives in a `.tc-dark, .dark` block; everything before
        // it is the light scope. Only ROLES are remapped there — the palette
        // is declared once, so a lookup that misses the scope falls back to
        // the whole file rather than failing.
        $split = strpos($css, '.tc-dark');
        $scoped = $scope === 'dark'
            ? substr($css, $split === false ? 0 : $split)
            : substr($css, 0, $split === false ? strlen($css) : $split);

        for ($i = 0; $i < 10; $i++) {
            $pattern = '/--'.preg_quote($name, '/').':\s*([^;]+);/';

            if (! preg_match($pattern, $scoped, $m) && ! preg_match($pattern, $css, $m)) {
                $this->fail("Token --{$name} is not defined in the {$scope} scope.");
            }

            $value = trim($m[1]);

            if (! preg_match('/^var\(--([a-z0-9-]+)\)$/i', $value, $ref)) {
                return $value;
            }

            $name = $ref[1];
        }

        $this->fail("Token --{$name} never resolves to a literal.");
    }

    /** oklch(L C H) to gamma-encoded sRGB in 0..1. */
    private function srgb(string $oklch): array
    {
        if (! preg_match('/oklch\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)/i', $oklch, $m)) {
            $this->fail("Not an oklch colour: {$oklch}");
        }

        [$lightness, $chroma, $hue] = [(float) $m[1], (float) $m[2], (float) $m[3]];

        $a = $chroma * cos(deg2rad($hue));
        $b = $chroma * sin(deg2rad($hue));

        $l = ($lightness + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m_ = ($lightness - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($lightness - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        $linear = [
            4.0767416621 * $l - 3.3077115913 * $m_ + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m_ - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m_ + 1.7076147010 * $s,
        ];

        // Gamma-encode and clamp exactly as the browser does before painting.
        return array_map(function (float $c): float {
            $v = $c <= 0.0031308 ? 12.92 * $c : 1.055 * $c ** (1 / 2.4) - 0.055;

            return max(0.0, min(1.0, $v));
        }, $linear);
    }

    private function luminance(string $oklch): float
    {
        $linear = array_map(
            fn (float $v): float => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
            $this->srgb($oklch),
        );

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    private function contrast(string $foreground, string $background): float
    {
        $a = $this->luminance($foreground);
        $b = $this->luminance($background);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /*
     * ── The maths itself ────────────────────────────────────────────────
     */

    /**
     * If the converter is wrong then every assertion below it is worthless, so
     * it is checked against ratios known independently of this codebase.
     */
    public function test_the_contrast_calculation_agrees_with_known_values(): void
    {
        $this->assertEqualsWithDelta(
            21.0,
            $this->contrast('oklch(1 0 0)', 'oklch(0 0 0)'),
            0.01,
            'White on black is 21:1 by definition.',
        );

        // Cross-checked against Chrome, which resolves the same tokens through
        // an entirely separate code path (relative colour syntax into sRGB).
        // These four were read off the live panel during the Phase 8 audit.
        $measuredInChrome = [
            ['tc-n-500', 'tc-n-100', 3.69],
            ['tc-n-600', 'tc-n-0', 6.46],
            ['tc-n-600', 'tc-n-100', 5.72],
            ['tc-n-900', 'tc-n-0', 17.85],
        ];

        foreach ($measuredInChrome as [$foreground, $background, $expected]) {
            $this->assertEqualsWithDelta(
                $expected,
                $this->contrast($this->resolve($foreground), $this->resolve($background)),
                0.01,
                "--{$foreground} on --{$background} disagrees with the browser.",
            );
        }
    }

    /*
     * ── Text roles ──────────────────────────────────────────────────────
     */

    public static function textRoleProvider(): array
    {
        return [
            'primary on surface (light)' => ['tc-text-primary', 'tc-bg-surface', 'light'],
            'primary on sunken (light)' => ['tc-text-primary', 'tc-bg-sunken', 'light'],
            'secondary on surface (light)' => ['tc-text-secondary', 'tc-bg-surface', 'light'],
            'secondary on sunken (light)' => ['tc-text-secondary', 'tc-bg-sunken', 'light'],
            'tertiary on surface (light)' => ['tc-text-tertiary', 'tc-bg-surface', 'light'],
            'tertiary on sunken (light)' => ['tc-text-tertiary', 'tc-bg-sunken', 'light'],
            'placeholder on surface (light)' => ['tc-text-placeholder', 'tc-bg-surface', 'light'],
            'primary on surface (dark)' => ['tc-text-primary', 'tc-bg-surface', 'dark'],
            'secondary on surface (dark)' => ['tc-text-secondary', 'tc-bg-surface', 'dark'],
            'tertiary on surface (dark)' => ['tc-text-tertiary', 'tc-bg-surface', 'dark'],
            'tertiary on raised (dark)' => ['tc-text-tertiary', 'tc-bg-raised', 'dark'],
            'placeholder on surface (dark)' => ['tc-text-placeholder', 'tc-bg-surface', 'dark'],
        ];
    }

    /**
     * @dataProvider textRoleProvider
     */
    public function test_every_text_role_meets_aa_on_the_surfaces_it_sits_on(
        string $role,
        string $surface,
        string $scope,
    ): void {
        $ratio = $this->contrast($this->resolve($role, $scope), $this->resolve($surface, $scope));

        $this->assertGreaterThanOrEqual(
            self::AA_NORMAL,
            round($ratio, 2),
            sprintf(
                '--%s on --%s (%s) is %.2f:1, under the %.1f:1 AA threshold.',
                $role,
                $surface,
                $scope,
                $ratio,
                self::AA_NORMAL,
            ),
        );
    }

    /**
     * The specific regression that started this phase: tertiary sat on n-500,
     * which is 3.69:1 on the sunken surface.
     */
    public function test_the_neutral_below_tertiary_is_the_one_that_fails(): void
    {
        $this->assertLessThan(
            self::AA_NORMAL,
            $this->contrast($this->resolve('tc-n-500'), $this->resolve('tc-bg-sunken')),
            'n-500 now passes on sunken, so the ramp moved: re-derive which step tertiary should use.',
        );
    }

    /*
     * ── Placeholder is not disabled ─────────────────────────────────────
     */

    /**
     * WCAG 1.4.3 exempts inactive controls. It does not exempt an empty
     * field's prompt or a "no value" marker, which are content.
     */
    public function test_placeholder_text_does_not_borrow_the_disabled_role(): void
    {
        foreach (['components.css', 'form.css', 'table.css', 'dashboard.css'] as $file) {
            preg_match_all(
                '/([^{}]*placeholder[^{}]*)\{([^}]*)\}/i',
                $this->css($file),
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $this->assertStringNotContainsString(
                    'var(--tc-text-disabled)',
                    $match[2],
                    trim($match[1])." in {$file} uses the disabled role for placeholder text.",
                );
            }
        }
    }

    /** The disabled role should only ever dress something actually disabled. */
    public function test_the_disabled_role_is_only_used_on_disabled_controls(): void
    {
        $files = ['components.css', 'form.css', 'table.css', 'dashboard.css', 'workspace.css', 'shell.css'];

        foreach ($files as $file) {
            preg_match_all(
                '/([^{}]*)\{([^}]*var\(--tc-text-disabled\)[^}]*)\}/',
                $this->css($file),
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $this->assertMatchesRegularExpression(
                    '/\[disabled\]|:disabled|aria-disabled|fi-disabled|is-disabled/',
                    $match[1],
                    trim($match[1])." in {$file} uses --tc-text-disabled but is not a disabled state.",
                );
            }
        }
    }

    /** An empty queue still has to be readable. */
    public function test_a_zero_queue_count_stays_legible(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.tc-queue-count\.is-zero\s*\{[^}]*color:\s*var\(--tc-text-tertiary\)/',
            $this->css('dashboard.css'),
        );
    }

    /*
     * ── The base layer actually reaches the panel ───────────────────────
     */

    /**
     * base.css was written entirely against .tc-root, which only the design
     * system specimen puts on its body. Filament's panel body is .fi-body, so
     * the focus ring, the selection colour, the scrollbars and the
     * reduced-motion block applied to the specimen page and nowhere else.
     */
    public function test_the_base_layer_is_written_against_both_roots(): void
    {
        preg_match_all('/([^{}]+)\{/', $this->css('base.css'), $matches);

        foreach ($matches[1] as $selector) {
            $selector = trim($selector);

            if (! str_contains($selector, '.tc-root')) {
                continue;
            }

            $this->assertStringContainsString(
                '.fi-body',
                $selector,
                "`{$selector}` is scoped to the specimen root only, so it never reaches the panel.",
            );
        }
    }

    public function test_the_focus_ring_reaches_the_panel(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.fi-body :focus-visible[^{]*\{[^}]*box-shadow:\s*var\(--tc-focus-ring\)/',
            $this->css('base.css'),
        );
    }

    public function test_reduced_motion_reaches_the_panel(): void
    {
        $this->assertMatchesRegularExpression(
            '/prefers-reduced-motion.*?\.fi-body \*/s',
            $this->css('base.css'),
        );
    }

    /*
     * ── Filament's own colours that fail in this panel ──────────────────
     */

    /**
     * Filament pairs amber-600 with white in dark mode (4.15:1) and flips to
     * dark ink on pale amber in light, so the same control reads as two
     * different buttons. Neither ink passes on amber-600 in both rest AND
     * hover, so the background moves instead: one treatment, both themes,
     * which is why this must not become a theme-scoped rule.
     */
    public function test_the_warning_button_carries_white_in_both_themes(): void
    {
        $css = $this->css('form.css');

        $this->assertMatchesRegularExpression(
            '/\.fi-btn\.fi-color-warning[^{]*\{[^}]*background-color:\s*var\(--tc-amber-700\)/',
            $css,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\.(dark|tc-dark)[^{]*\.fi-color-warning/',
            $css,
            'The warning button grew a theme-scoped exception instead of one treatment.',
        );

        foreach (['tc-amber-700' => 'rest', 'tc-amber-800' => 'hover'] as $token => $state) {
            $ratio = $this->contrast($this->resolve('tc-n-0'), $this->resolve($token));

            $this->assertGreaterThanOrEqual(
                self::AA_NORMAL,
                round($ratio, 2),
                sprintf('White on --%s (%s) is %.2f:1.', $token, $state, $ratio),
            );
        }
    }

    /*
     * ── Landmarks and keyboard ──────────────────────────────────────────
     */

    /** A landmark's label names the region, not an action you can take. */
    public function test_the_rail_landmark_is_named_as_a_region(): void
    {
        $sidebar = $this->sidebar();

        $this->assertStringContainsString('aria-label="Main navigation"', $sidebar);
        $this->assertStringNotContainsString(
            'actions.sidebar.expand.label\') }}"',
            $sidebar,
            'The navigation landmark is labelled with an action again.',
        );
    }

    /** The group trigger has to be a real button for the keyboard to reach it. */
    public function test_rail_groups_open_from_the_keyboard(): void
    {
        $sidebar = $this->sidebar();

        $this->assertMatchesRegularExpression('/<button\s+type="button"/', $sidebar);
        $this->assertStringContainsString('aria-haspopup="true"', $sidebar);
        $this->assertStringContainsString('x-bind:aria-expanded', $sidebar);
        $this->assertStringContainsString('x-on:keydown.escape.window="hide()"', $sidebar);
    }

    private function sidebar(): string
    {
        return (string) file_get_contents(base_path(
            'resources/views/vendor/filament-panels/livewire/sidebar.blade.php',
        ));
    }
}
