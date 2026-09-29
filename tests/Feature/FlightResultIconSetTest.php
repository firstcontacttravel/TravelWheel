<?php

namespace Tests\Feature;

use Tests\TestCase;

class FlightResultIconSetTest extends TestCase
{
    private const VIEWS = [
        'resources/views/livewire/pages/flight/partials/flight-result.blade.php',
        'resources/views/livewire/pages/flight/partials/flight-timeline.blade.php',
    ];

    /**
     * The card's icons are CSS masks. A mask pointing at a missing file fails
     * silently — no console error, no broken-image glyph, just an invisible
     * icon — so a typo'd or deleted asset would ship unnoticed.
     */
    public function test_every_icon_the_flight_card_references_exists_on_disk(): void
    {
        $referenced = [];

        foreach (self::VIEWS as $view) {
            preg_match_all(
                "/asset\(\s*'(images\/[^']+\.svg)'\s*\)/",
                file_get_contents(base_path($view)),
                $matches
            );
            $referenced = [...$referenced, ...$matches[1]];
        }

        $referenced = array_unique($referenced);
        $this->assertNotEmpty($referenced, 'Expected the flight card to reference icon assets.');

        foreach ($referenced as $path) {
            $this->assertFileExists(public_path($path));
        }
    }

    /**
     * The icons are CSS masks driven by a --i custom property. When a
     * `sr-ic-*` class is used in markup but never defined in the stylesheet,
     * --i resolves to nothing, the mask is dropped, and `background:
     * currentColor` paints the element as a solid filled square — no console
     * error, no missing asset, just a black box where a glyph should be.
     * Caught exactly that on the filters, matrix and "Best overall" icons.
     */
    public function test_every_icon_class_used_in_markup_is_defined_in_the_stylesheet(): void
    {
        $sources = array_map(
            fn (string $view): string => file_get_contents(base_path($view)),
            self::VIEWS
        );
        $all = implode(PHP_EOL, $sources);

        preg_match_all('/\bsr-ic-([a-z0-9]+(?:-[a-z0-9]+)*)/', $all, $used);
        preg_match_all('/\.sr-ic-([a-z0-9-]+)\s*\{/', $all, $defined);

        // sm/lg are size modifiers on .sr-ic itself, not glyph selectors.
        $used = array_diff(array_unique($used[1]), ['sm', 'lg']);
        $defined = array_unique($defined[1]);

        $this->assertNotEmpty($used, 'Expected the flight results page to use icons.');

        $missing = array_diff($used, $defined);
        $this->assertSame(
            [],
            array_values($missing),
            'Icon classes used in markup with no --i rule (these render as solid squares): '
                .implode(', ', $missing)
        );
    }

    /**
     * Emoji were standing in for icons in the fare-rules panel and the results
     * header. They render differently on every platform, can't inherit colour,
     * and read as unfinished next to the line-icon set that replaced them.
     */
    public function test_the_flight_results_page_uses_icons_rather_than_emoji(): void
    {
        foreach (self::VIEWS as $view) {
            $source = file_get_contents(base_path($view));

            $this->assertSame(
                0,
                preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u', $source),
                "Emoji found in {$view} — use the .sr-ic icon set instead."
            );
        }
    }
}
