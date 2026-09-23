<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 of the console redesign: the token foundation, the self-hosted type,
 * and the specimen and harness that exist to review them.
 *
 * These assert the properties that are expensive to notice by eye — a missing
 * font subset, a token that drifted off config/brand.php, a component rule
 * that reached past the roles into the palette — rather than re-testing what
 * a screenshot already shows.
 */
class ConsoleDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    private const CSS = 'resources/css/admin/';

    /*
     * ── Access ──────────────────────────────────────────────────────────
     * Both pages print live booking references and amounts.
     */

    public function test_a_guest_cannot_reach_the_specimen(): void
    {
        $this->get('/design-system')->assertRedirect();
        $this->get('/design-system/responsive')->assertRedirect();
    }

    public function test_a_signed_in_customer_cannot_reach_the_specimen(): void
    {
        // `auth` alone would have admitted this user, which is why the
        // controller checks the panel gate rather than trusting the middleware.
        $this->actingAs(User::factory()->create(['is_admin' => false, 'visa_role' => null]));

        $this->get('/design-system')->assertForbidden();
        $this->get('/design-system/responsive')->assertForbidden();
    }

    public function test_an_admin_sees_the_specimen(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/design-system')
            ->assertOk()
            ->assertSee('Design system')
            ->assertSee('Navigation rail');
    }

    /*
     * ── Harness ─────────────────────────────────────────────────────────
     * A dev tool that frames any URL handed to it is a dev tool that frames
     * someone else's login page.
     */

    /** @return array<string, array{0: string}> */
    public static function unsafePaths(): array
    {
        return [
            'protocol relative' => ['//evil.test/phish'],
            'absolute url' => ['https://evil.test'],
            'javascript scheme' => ['javascript:alert(1)'],
            'backslash' => ['\\\\evil.test'],
            'no leading slash' => ['design-system'],
        ];
    }

    /** @dataProvider unsafePaths */
    public function test_the_harness_refuses_anything_that_is_not_a_same_origin_path(string $path): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $response = $this->get('/design-system/responsive?path='.urlencode($path));

        $response->assertOk();
        $response->assertSee('Only same-origin paths');
        $response->assertDontSee('src="'.$path.'"', false);
    }

    /** A blank field is an unfilled input, not an attack; it falls back quietly. */
    public function test_the_harness_falls_back_without_warning_when_no_path_is_given(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach (['', '   '] as $blank) {
            $this->get('/design-system/responsive?path='.urlencode($blank))
                ->assertOk()
                ->assertDontSee('Only same-origin paths')
                ->assertSee('src="/design-system"', false);
        }
    }

    public function test_the_harness_previews_a_same_origin_path(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/design-system/responsive?path='.urlencode('/admin/flight-bookings'))
            ->assertOk()
            ->assertSee('src="/admin/flight-bookings"', false)
            ->assertDontSee('Only same-origin paths');
    }

    public function test_the_harness_renders_a_phone_width(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        // The whole reason this tool exists: the browser window on this
        // machine would not resize below 1531px, so phone breakpoints were
        // unverifiable. If the 390px frame ever disappears, that gap is back.
        $this->get('/design-system/responsive')
            ->assertOk()
            ->assertSee('width="390"', false);
    }

    /*
     * ── Tokens ──────────────────────────────────────────────────────────
     */

    public function test_the_brand_token_is_the_configured_brand_colour(): void
    {
        $tokens = $this->css('tokens.css');

        // #303191 is oklch(0.378 0.154 275.68), and the ramp is anchored so
        // that value IS shade 600 — the shade Filament fills buttons with.
        $this->assertStringContainsString('--tc-brand-600: oklch(0.378 0.154 275.68)', $tokens);
        $this->assertSame('#303191', config('brand.colors.brand'));
        $this->assertSame('#00a859', config('brand.colors.accent'));
    }

    /**
     * The one rule that makes this a system rather than a pile of variables:
     * components consume ROLE tokens, so dark mode is a remap rather than a
     * parallel set of rules. If a component reaches for a palette step
     * directly, dark mode silently stops covering it.
     */
    public function test_components_never_reference_palette_tokens_directly(): void
    {
        $components = $this->css('components.css');

        preg_match_all('/var\(--tc-(n|brand|accent|red|amber|green|blue)-\d+\)/', $components, $matches);

        $this->assertSame(
            [],
            array_values(array_unique(array_diff($matches[0], [
                // The two deliberate exceptions, both "text on a filled
                // status colour", where there is no role to point at.
                'var(--tc-n-0)',
                'var(--tc-accent-500)',
            ]))),
            'A component reached past the role tokens into the palette; dark mode will not cover it.',
        );
    }

    public function test_dark_mode_is_a_remap_and_not_a_second_set_of_rules(): void
    {
        foreach (['components.css', 'base.css'] as $file) {
            $this->assertStringNotContainsString(
                '.tc-dark',
                $this->css($file),
                "{$file} carries a dark-mode override; roles should have made that unnecessary.",
            );
        }

        $this->assertStringContainsString('.tc-dark', $this->css('tokens.css'));
    }

    /*
     * ── Type ────────────────────────────────────────────────────────────
     */

    /**
     * The naira sign ₦ is U+20A6, which lives in the latin-ext subset, not
     * latin. Ship latin alone and every price in the panel silently falls
     * back to a system font in the middle of the string.
     */
    public function test_both_faces_ship_the_subset_that_carries_the_naira_sign(): void
    {
        $typography = $this->css('typography.css');

        foreach (['inter-latin-ext-wght-normal', 'jetbrains-mono-latin-ext-wght-normal'] as $file) {
            $this->assertStringContainsString($file, $typography, "The {$file} subset is not loaded.");
        }

        // U+20A6 falls inside this declared range.
        $this->assertStringContainsString('U+20A0-20AB', $typography);
    }

    public function test_the_fonts_are_self_hosted_rather_than_fetched_from_a_third_party(): void
    {
        $typography = $this->css('typography.css');

        $this->assertStringNotContainsString('fonts.googleapis', $typography);
        $this->assertStringNotContainsString('fonts.gstatic', $typography);
        $this->assertStringContainsString('@fontsource-variable', $typography);

        // The property that actually matters: no src() reaches off-origin, so
        // the panel renders identically with no internet connection.
        preg_match_all('#src:\s*url\("([^"]+)"\)#', $typography, $sources);
        $this->assertNotEmpty($sources[1]);

        foreach ($sources[1] as $src) {
            $this->assertDoesNotMatchRegularExpression(
                '#^(https?:)?//#',
                $src,
                "Font source is not self-hosted: {$src}",
            );
        }
    }

    /** Every declared face must actually exist on disk, or it silently no-ops. */
    public function test_every_declared_font_file_is_present(): void
    {
        preg_match_all('#url\("([^"]+\.woff2)"\)#', $this->css('typography.css'), $matches);

        $this->assertNotEmpty($matches[1], 'No font files are declared.');

        foreach ($matches[1] as $relative) {
            $path = realpath(base_path('resources/css/admin/'.$relative));

            $this->assertNotFalse($path, "Declared font file is missing: {$relative}");
            $this->assertFileExists($path);
        }
    }

    private function css(string $file): string
    {
        return (string) file_get_contents(base_path(self::CSS.$file));
    }
}
