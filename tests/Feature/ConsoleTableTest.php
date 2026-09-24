<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightBookings\Pages\ListFlightBookings;
use App\Models\FlightBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 3 of the console redesign: the data table.
 *
 * A booking row was 126px tall and a 1517px screen showed four of them. The
 * queue is now single-line at 40px and shows twelve. Nothing was deleted to
 * get there — what left the default view became a column of its own, one click
 * away in the column manager, which is a different thing from being gone.
 */
class ConsoleTableTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function booking(array $attributes = []): FlightBooking
    {
        return FlightBooking::query()->create(array_merge([
            // The only NOT NULL column without a default on this table.
            'fare_source_code' => 'test-fare-source',
            'booking_ref' => 'TW-TEST001',
            'route' => 'LOS → DXB',
            'airline' => 'Egyptair',
            'currency' => 'NGN',
            'total_price' => 925023.49,
            'booking_status' => 'ticketed',
            'payment_status' => 'paid',
            'ticket_ordered' => true,
        ], $attributes));
    }

    /*
     * ── Status as a dot ─────────────────────────────────────────────────
     */

    /**
     * Eleven coloured pills in a column is a fruit salad; eleven dots in a
     * fixed position is a stripe the eye scans without reading. Shape carries
     * the meaning too, so the language never depends on colour alone.
     */
    public function test_queue_and_payment_render_as_status_dots(): void
    {
        $this->booking();
        $this->actingAs($this->admin());

        $html = Livewire::test(ListFlightBookings::class)->html();

        $this->assertStringContainsString('tc-status tc-status-positive', $html);
        $this->assertStringContainsString('tc-status', $html);
    }

    public function test_an_unstarted_state_is_an_open_ring_not_just_a_colour(): void
    {
        $this->booking([
            'booking_ref' => 'TW-PEND001',
            'booking_status' => 'pending',
            'payment_status' => 'pending',
            'ticket_ordered' => false,
        ]);
        $this->actingAs($this->admin());

        $html = Livewire::test(ListFlightBookings::class)->html();

        $this->assertStringContainsString('tc-status-pending', $html);
    }

    /*
     * ── The route cell ──────────────────────────────────────────────────
     */

    /**
     * The regression this phase had to fix. The old cell derived its legs from
     * flight_snapshot.segments, which a multi-city booking leaves EMPTY — those
     * keep their legs in .multiLegs — so every multi-city trip rendered with no
     * route at all. It now reads the stored `route` column.
     */
    public function test_a_multi_city_booking_renders_its_full_route(): void
    {
        $this->booking([
            'booking_ref' => 'TW-MULTI01',
            'route' => 'LOS → ABV → LOS',
            // Exactly the shape that broke it: segments empty, legs elsewhere.
            'flight_snapshot' => ['segments' => [], 'multiLegs' => [['segments' => []], ['segments' => []]]],
        ]);
        $this->actingAs($this->admin());

        $html = Livewire::test(ListFlightBookings::class)->html();

        $this->assertStringContainsString('>LOS<', $html);
        $this->assertStringContainsString('>ABV<', $html);
        $this->assertStringContainsString('tc-route-line', $html);
    }

    /** Intermediate stops step back so the endpoints stay dominant. */
    public function test_intermediate_stops_are_marked_as_via(): void
    {
        $this->booking(['booking_ref' => 'TW-VIA0001', 'route' => 'LOS → IST → DXB']);
        $this->actingAs($this->admin());

        $html = Livewire::test(ListFlightBookings::class)->html();

        $this->assertStringContainsString('tc-route-via', $html);
    }

    /**
     * U+2192 is in none of Inter's subsets, so a typed arrow falls back to a
     * system font mid-route. The connector is drawn.
     */
    public function test_the_route_connector_is_drawn_not_typed(): void
    {
        $source = (string) file_get_contents(base_path(
            'app/Filament/Resources/FlightBookings/Tables/FlightBookingsTable.php',
        ));

        $routeCell = substr($source, (int) strpos($source, 'private static function routeCell'));
        $routeCell = substr($routeCell, 0, (int) strpos($routeCell, "\n    }"));

        $this->assertStringNotContainsString('→', $routeCell);
        $this->assertStringContainsString('tc-route-line', $routeCell);
    }

    /*
     * ── Chrome ──────────────────────────────────────────────────────────
     */

    /** The resource name was printed three times on one screen. */
    public function test_the_table_does_not_repeat_the_page_title(): void
    {
        $source = (string) file_get_contents(base_path(
            'app/Filament/Resources/FlightBookings/Tables/FlightBookingsTable.php',
        ));
        $stripped = (string) preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $source);

        $this->assertStringNotContainsString("->heading('Flight Bookings')", $stripped);
        $this->assertStringNotContainsString('->description(\'Operational queue', $stripped);
    }

    public function test_the_context_moved_to_the_page_subheading(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/flight-bookings')
            ->assertOk()
            ->assertSee('Operational queue for payment verification');
    }

    /*
     * ── Density ─────────────────────────────────────────────────────────
     */

    /**
     * Filament pads its actions, checkbox and reorder cells with py-4 of their
     * own. One cell sets a table row's height for every cell in it, so the row
     * stayed at 53px however tight the text cells were.
     */
    public function test_the_cells_that_set_the_row_height_are_neutralised(): void
    {
        $css = $this->rules('table.css');

        $this->assertMatchesRegularExpression(
            '/\.fi-ta-cell:has\(\.fi-ta-actions\)[^{]*\{[^}]*padding-block:\s*0/s',
            $css,
            'The actions cell padding is back; rows will not reach 40px.',
        );
        $this->assertStringContainsString('padding: 0.625rem var(--tc-space-4);', $css);
    }

    /** Scrolling 100 bookings without column names is scrolling a spreadsheet
     *  with the header row deleted. */
    public function test_column_headers_are_sticky(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.fi-ta-header-cell\s*\{[^}]*position:\s*sticky/s',
            $this->rules('table.css'),
        );
    }

    /** A table cut off with no sign it continues is a table whose right-hand
     *  columns nobody knows exist. */
    public function test_the_scroll_container_shows_where_there_is_more_to_see(): void
    {
        $css = $this->rules('table.css');

        $this->assertMatchesRegularExpression(
            '/\.fi-ta-content-ctn\s*\{[^}]*overflow-x:\s*auto/s',
            $css,
        );
        // attachment:local travels with the content, attachment:scroll stays
        // pinned — together they reveal the shadow only where it is warranted.
        $this->assertStringContainsString('no-repeat local', $css);
        $this->assertStringContainsString('no-repeat scroll', $css);
    }

    /**
     * Filament v5 marks the current tab with `fi-active`. The panel's previous
     * stylesheet targeted `.fi-tabs-item-active`, a v3 name that no longer
     * ships, so the active tab had no treatment at all.
     */
    public function test_the_active_tab_uses_the_class_filament_actually_emits(): void
    {
        $css = $this->rules('table.css');

        $this->assertStringContainsString('.fi-tabs-item.fi-active', $css);
        $this->assertStringNotContainsString('fi-tabs-item-active', $css);
    }

    /*
     * ── Nothing was deleted ─────────────────────────────────────────────
     */

    /**
     * What left the default view became a column of its own. This is the line
     * between "denser" and "we threw your data away", and it is worth a test:
     * every value that used to sit in a row description must still be
     * reachable from the column manager.
     */
    public function test_everything_removed_from_a_row_is_still_an_available_column(): void
    {
        $source = (string) file_get_contents(base_path(
            'app/Filament/Resources/FlightBookings/Tables/FlightBookingsTable.php',
        ));

        foreach ([
            'unique_id',        // was the Booking description
            'fare_type',        // was the Booking description
            'passengers',       // was the Total description
            'markup_amount',    // was the Total description
            'supplier_price',   // was the Total description
            'contact_phone',    // was the Customer description
            'payment_method',
            'booking_status',
        ] as $column) {
            $this->assertStringContainsString(
                "TextColumn::make('{$column}')",
                $source,
                "Column [{$column}] carried data that used to be on every row and is now unreachable.",
            );
        }
    }

    /** And the table still renders with all of it. */
    public function test_the_queue_renders(): void
    {
        $this->booking();
        $this->actingAs($this->admin());

        $this->get('/admin/flight-bookings')
            ->assertOk()
            ->assertSee('TW-TEST001');
    }

    /**
     * The stylesheet explains what it is NOT doing, so an assertion that a
     * string is absent would otherwise trip over the prose describing it.
     */
    private function rules(string $file): string
    {
        $css = (string) file_get_contents(base_path('resources/css/admin/'.$file));

        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }
}
