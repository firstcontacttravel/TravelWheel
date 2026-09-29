<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Support\Admin\FlightBookingPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 of the console redesign: the booking workspace.
 *
 * The markup for this screen used to be built by string concatenation in
 * FlightBookingPresentation, which meant nothing shared could reach it — the
 * drawn route connector, the status dots, the mono face — and roughly 900
 * lines of tw-* CSS existed to compensate. It is Blade now, on the console's
 * own primitives.
 */
class ConsoleWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $attributes = []): FlightBooking
    {
        return FlightBooking::query()->create(array_merge([
            'fare_source_code' => 'test-fare-source',
            'booking_ref' => 'TW-WORK001',
            'route' => 'LHR → DEL → DXB',
            'airline' => 'Indigo Airlines',
            'currency' => 'NGN',
            'total_price' => 1084748.38,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'ticket_ordered' => false,
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'flight_snapshot' => [
                'airline' => 'Indigo Airlines',
                'airlineCode' => '6E',
                'currency' => 'NGN',
                'price' => 970525.87,
                'segments' => [
                    [
                        'from' => 'LHR', 'to' => 'DEL',
                        'fromCity' => 'London', 'toCity' => 'Delhi',
                        'departDT' => '2026-05-12T20:55:00',
                        'arriveDT' => '2026-05-13T12:20:00',
                        'flightNo' => '6E4',
                    ],
                    [
                        'from' => 'DEL', 'to' => 'DXB',
                        'fromCity' => 'Delhi', 'toCity' => 'Dubai',
                        // 6h 35m after the previous arrival.
                        'departDT' => '2026-05-13T18:55:00',
                        'arriveDT' => '2026-05-13T21:05:00',
                        'flightNo' => '6E1463',
                    ],
                ],
            ],
        ], $attributes));
    }

    /*
     * ── The markup moved ────────────────────────────────────────────────
     */

    /**
     * The point of this phase. If any renderer goes back to concatenating
     * class names in PHP, the console's primitives stop reaching this screen
     * and the tw-* CSS starts growing back.
     */
    public function test_the_presenter_no_longer_writes_css_class_names(): void
    {
        $source = (string) file_get_contents(base_path('app/Support/Admin/FlightBookingPresentation.php'));

        $this->assertDoesNotMatchRegularExpression(
            '/["\']tw-[a-z0-9-]+/',
            $source,
            'A renderer is writing tw-* class names in PHP again; the markup belongs in Blade.',
        );
    }

    public function test_the_hero_renders_on_console_primitives(): void
    {
        $html = FlightBookingPresentation::workspaceSummary($this->booking())->toHtml();

        $this->assertStringContainsString('tc-bk', $html);
        $this->assertStringContainsString('tc-status', $html);
        $this->assertStringNotContainsString('tw-booking-hero', $html);
    }

    /*
     * ── The route ───────────────────────────────────────────────────────
     */

    /**
     * U+2192 is in none of Inter's subsets, so a typed arrow falls back to a
     * system font in the middle of the route. Every connector on this screen
     * is drawn.
     */
    public function test_no_route_on_the_workspace_is_typed(): void
    {
        $booking = $this->booking();

        foreach ([
            'hero' => FlightBookingPresentation::workspaceSummary($booking)->toHtml(),
            'itinerary' => FlightBookingPresentation::flight($booking->flight_snapshot)->toHtml(),
        ] as $part => $html) {
            $this->assertStringNotContainsString('-&gt;', $html, "The {$part} still prints an ASCII arrow.");
            $this->assertStringNotContainsString('→', $html, "The {$part} types an arrow rather than drawing one.");
        }
    }

    public function test_the_hero_shows_every_leg_of_a_connecting_route(): void
    {
        $html = FlightBookingPresentation::workspaceSummary($this->booking())->toHtml();

        foreach (['LHR', 'DEL', 'DXB'] as $code) {
            $this->assertStringContainsString(">{$code}<", $html);
        }

        // The intermediate stop steps back so the endpoints stay dominant.
        $this->assertStringContainsString('tc-route-via', $html);
    }

    /*
     * ── The connection ──────────────────────────────────────────────────
     */

    /**
     * The number people actually worry about, and the previous layout had
     * nowhere to put it: each segment was a separate bordered card with
     * nothing between them, so "arrive DEL 12:20" and "depart DEL 18:55" read
     * as two unrelated flights.
     */
    public function test_the_itinerary_shows_the_connection_time(): void
    {
        $html = FlightBookingPresentation::flight($this->booking()->flight_snapshot)->toHtml();

        $this->assertStringContainsString('6h 35m connection', $html);
        $this->assertStringContainsString('Delhi', $html);
    }

    public function test_a_single_segment_journey_shows_no_connection(): void
    {
        $booking = $this->booking([
            'booking_ref' => 'TW-DIRECT1',
            'flight_snapshot' => [
                'airline' => 'Egyptair',
                'segments' => [[
                    'from' => 'LOS', 'to' => 'DXB',
                    'departDT' => '2026-05-12T14:00:00',
                    'arriveDT' => '2026-05-12T21:25:00',
                    'flightNo' => 'MS876',
                ]],
            ],
        ]);

        $html = FlightBookingPresentation::flight($booking->flight_snapshot)->toHtml();

        $this->assertStringNotContainsString('connection', $html);
        $this->assertStringContainsString('Non-stop', $html);
    }

    /** Clock times are printed as stored, not shifted into another zone. */
    public function test_segment_times_are_printed_as_given(): void
    {
        $html = FlightBookingPresentation::flight($this->booking()->flight_snapshot)->toHtml();

        $this->assertStringContainsString('20:55', $html);
        $this->assertStringContainsString('21:05', $html);
        $this->assertStringNotContainsString('21:55', $html, 'A segment time was shifted by an hour.');
    }

    /*
     * ── Passengers and feeds ────────────────────────────────────────────
     */

    public function test_passengers_render_as_cards_on_the_system(): void
    {
        $html = FlightBookingPresentation::passengers([
            ['title' => 'Mr', 'first_name' => 'Awobajo', 'last_name' => 'Oluwatoyin', 'type' => 'ADT', 'passport_no' => '37373822'],
        ])->toHtml();

        $this->assertStringContainsString('tc-pax-card', $html);
        $this->assertStringContainsString('Mr Awobajo Oluwatoyin', $html);
        $this->assertStringNotContainsString('tw-passenger', $html);
    }

    public function test_an_empty_snapshot_gets_a_designed_empty_state(): void
    {
        $html = FlightBookingPresentation::passengers([])->toHtml();

        $this->assertStringContainsString('tc-empty', $html);
    }

    /*
     * ── The CSS it replaced ─────────────────────────────────────────────
     */

    /**
     * ~900 lines of one-off CSS existed only because the markup could not be
     * reached by anything shared. If a prefix comes back, so has the problem.
     *
     * tw-booking-* and tw-timeline-* are deliberately NOT in this list: they
     * are still used by TravelFlexPresentation, which is a different screen
     * and a later phase.
     */
    public function test_the_replaced_stylesheets_are_gone(): void
    {
        $css = (string) file_get_contents(base_path('resources/css/filament/admin/theme.css'));

        foreach (['tw-flight-', 'tw-detail-', 'tw-passenger-', 'tw-history-', 'tw-ticket-'] as $prefix) {
            $this->assertStringNotContainsString(
                $prefix,
                $css,
                "{$prefix} CSS is back in the admin theme.",
            );
        }
    }

    public function test_the_workspace_layer_carries_no_dark_mode_overrides(): void
    {
        $css = (string) preg_replace(
            '#/\*.*?\*/#s',
            '',
            (string) file_get_contents(base_path('resources/css/admin/workspace.css')),
        );

        $this->assertStringNotContainsString('.tc-dark', $css);
        $this->assertStringNotContainsString('.dark ', $css);
    }
}
