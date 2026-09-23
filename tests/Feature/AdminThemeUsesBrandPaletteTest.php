<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin panel had drifted onto its own brand colours — #0d1883 for the blue
 * and #00a85a for the green — while the site, the 13 flight and visa emails and
 * both travel-document PDFs used #303191 and #00a859. Nobody notices a blue
 * that is 8% off in isolation; you notice it when a PDF is attached to an email
 * that was sent from a screen in the admin panel and all three disagree.
 *
 * config/brand.php is the one source. These tests fail if the panel wanders.
 */
class AdminThemeUsesBrandPaletteTest extends TestCase
{
    use RefreshDatabase;

    private const THEME = 'resources/css/filament/admin/theme.css';

    private const PANEL = 'app/Providers/Filament/AdminPanelProvider.php';

    public function test_the_theme_declares_the_brand_colours_from_config(): void
    {
        $css = $this->theme();

        $this->assertStringContainsString(
            '--tw-brand: '.config('brand.colors.brand').';',
            $css,
            'The admin theme does not use config(brand.colors.brand).',
        );
        $this->assertStringContainsString(
            '--tw-accent: '.config('brand.colors.accent').';',
            $css,
            'The admin theme does not use config(brand.colors.accent).',
        );
    }

    /**
     * --tw-brand-rgb feeds every rgb(var(--tw-brand-rgb) / a) tint in the sheet.
     * If it and --tw-brand disagree, the solid brand and its own tints come from
     * two different colours, which is close to impossible to spot by eye.
     */
    public function test_the_brand_channel_token_matches_the_brand_hex(): void
    {
        $css = $this->theme();
        $hex = ltrim((string) config('brand.colors.brand'), '#');

        $expected = sprintf(
            '%d %d %d',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );

        $this->assertStringContainsString("--tw-brand-rgb: {$expected};", $css);
    }

    /** The blues and greens this panel used to carry must not come back. */
    public function test_no_retired_brand_colour_is_used_in_a_rule(): void
    {
        foreach ([self::THEME, self::PANEL] as $file) {
            $source = (string) file_get_contents(base_path($file));

            // Strip comments first: the retired values are named in prose there
            // on purpose, explaining what went wrong and why.
            $stripped = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $source);

            $this->assertDoesNotMatchRegularExpression(
                '/#(0[Dd]1883|00[Aa]85[Aa]|39328[Ff]|009933|2[Ff]2[Cc]90)\b/',
                (string) $stripped,
                "{$file} still uses a retired brand colour.",
            );
        }
    }

    /**
     * Filament paints solid primary buttons with shade 600. Color::hex() keeps
     * only the hue of what it is given and rebuilds lightness from a template,
     * so handing it the brand navy produced a 600 that was far lighter than the
     * brand — every primary button in the panel looked disabled. The ramp is
     * spelled out so 600 really is the brand colour.
     */
    public function test_primary_shade_600_is_the_brand_navy(): void
    {
        // Read the declared ramp rather than FilamentColor, which only carries
        // the panel's colours once a panel has been booted for the request.
        $shades = (new \ReflectionClass(\App\Providers\Filament\AdminPanelProvider::class))
            ->getConstant('BRAND_RAMP');

        $this->assertIsArray($shades, 'AdminPanelProvider no longer declares an explicit primary ramp.');
        $this->assertArrayHasKey(600, $shades, 'The ramp defines no shade 600.');
        $this->assertStringContainsString(
            'self::BRAND_RAMP',
            (string) file_get_contents(base_path(self::PANEL)),
            'The panel no longer hands the explicit ramp to primary.',
        );

        [$l, $c, $h] = $this->oklch((string) config('brand.colors.brand'));
        preg_match_all('/[0-9.]+/', (string) $shades[600], $matches);
        [$rampL, $rampC, $rampH] = array_map('floatval', $matches[0]);

        // Tolerances are the rounding in the declared ramp, not slack: three
        // decimals on L and C, two on hue.
        $this->assertEqualsWithDelta($l, $rampL, 0.002, 'primary 600 lightness has drifted off the brand navy.');
        $this->assertEqualsWithDelta($c, $rampC, 0.002, 'primary 600 chroma has drifted off the brand navy.');
        $this->assertEqualsWithDelta($h, $rampH, 0.05, 'primary 600 hue has drifted off the brand navy.');
    }

    /**
     * sRGB hex to OKLCH, so the assertion above is anchored on config/brand.php
     * rather than on a second copy of the same numbers.
     *
     * @return array{float, float, float}
     */
    private function oklch(string $hex): array
    {
        $toLinear = static function (int $channel): float {
            $v = $channel / 255;

            return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        };

        $hex = ltrim($hex, '#');
        $r = $toLinear((int) hexdec(substr($hex, 0, 2)));
        $g = $toLinear((int) hexdec(substr($hex, 2, 2)));
        $b = $toLinear((int) hexdec(substr($hex, 4, 2)));

        $long = (0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b) ** (1 / 3);
        $medium = (0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b) ** (1 / 3);
        $short = (0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b) ** (1 / 3);

        $lightness = 0.2104542553 * $long + 0.7936177850 * $medium - 0.0040720468 * $short;
        $a = 1.9779984951 * $long - 2.4285922050 * $medium + 0.4505937099 * $short;
        $bAxis = 0.0259040371 * $long + 0.7827717662 * $medium - 0.8086757660 * $short;

        return [
            $lightness,
            sqrt($a ** 2 + $bAxis ** 2),
            fmod(rad2deg(atan2($bAxis, $a)) + 360, 360),
        ];
    }

    public function test_the_login_button_is_not_full_width_everywhere(): void
    {
        $css = $this->theme();

        // The old sheet fixed a cramped sign-in button with a global
        // `.fi-btn { width: 100% }`, which stretched table actions, header
        // actions and the pagination "Next" control across their containers.
        $this->assertStringNotContainsString(
            ".fi-btn,\nbutton.fi-btn",
            $css,
            'A global full-width button rule is back.',
        );
        $this->assertStringContainsString(
            '.fi-simple-main .fi-form .fi-btn',
            $css,
            'Full width is no longer scoped to the login form.',
        );
    }

    public function test_the_admin_still_renders_with_the_new_palette(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/flight-bookings')->assertOk();
    }

    private function theme(): string
    {
        return (string) file_get_contents(base_path(self::THEME));
    }
}
