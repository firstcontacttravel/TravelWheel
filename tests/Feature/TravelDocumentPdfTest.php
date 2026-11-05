<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Services\ItineraryPdfService;
use Tests\TestCase;

/**
 * The flight document: one template for every state, from a seat on hold to an
 * issued e-ticket. These assert on the rendered HTML rather than the PDF bytes.
 * The PDF output itself is reviewed with `php artisan pdf:preview`.
 */
class TravelDocumentPdfTest extends TestCase
{
    /**
     * Supplier datetimes arrive naive and are parsed as UTC, so converting them
     * to Lagos time pushed a 22:35 departure to 23:35. The time is frozen because
     * the footer prints when the document was made.
     */
    public function test_flight_times_are_printed_as_given_not_shifted_into_lagos_time(): void
    {
        $this->travelTo('2026-09-20 09:00:00');

        $html = $this->itineraryHtml();

        $this->assertStringContainsString('22:35', $html, 'The 22:35 departure is missing.');
        $this->assertStringNotContainsString('23:35', $html, 'A flight time was shifted forward by an hour.');
    }

    public function test_a_connection_shows_how_long_the_layover_is(): void
    {
        $this->assertStringContainsString('2h 15m connection', $this->itineraryHtml());
    }

    /**
     * A multi-city booking carries its first leg in both flight_snapshot
     * .segments and .multiLegs[0], and that leg must only print once.
     */
    public function test_a_multi_city_document_does_not_print_its_first_leg_twice(): void
    {
        $html = $this->itineraryHtml($this->booking(multiCity: true));

        $this->assertMatchesRegularExpression('/leg 1/i', $html);
        $this->assertMatchesRegularExpression('/leg 3/i', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*outbound\s*</i', $html, 'Multi-city printed an Outbound section as well as its legs.');
        $this->assertSame(1, substr_count($html, 'KL 588'), 'The first leg appears more than once.');
    }

    /**
     * A multi-city header must name the whole route, not read as a circular
     * LOS to LOS, whether the supplier fills the top-level segments
     * (TravelNext) or leaves them empty (SkyLink).
     */
    public function test_a_multi_city_document_header_shows_its_whole_route(): void
    {
        $travelnext = $this->booking(multiCity: true);
        $skylink = $this->booking(multiCity: true);
        $skylink->flight_snapshot = ['segments' => []] + $skylink->flight_snapshot;

        foreach (['TravelNext' => $travelnext, 'SkyLink' => $skylink] as $shape => $booking) {
            $data = app(ItineraryPdfService::class)->buildViewData($booking);

            $this->assertSame('LOS City to AMS City to CDG City to LOS City', $data['tripTitle'], $shape);
            $this->assertSame('Multi-City', $data['tripLabel'], $shape);
            $this->assertCount(3, $data['segmentGroups'], $shape);
            $this->assertStringNotContainsString('282h', view('pdf.itinerary', $data)->render(), $shape);
        }
    }

    public function test_the_document_reads_its_colours_from_the_brand_config(): void
    {
        $source = (string) file_get_contents(resource_path('views/pdf/itinerary.blade.php'));

        $this->assertStringContainsString("@include('pdf.partials.itinerary-styles')", $source);
        $this->assertDoesNotMatchRegularExpression('/#(0[Dd]1883|39328[Ff]|303191|009933|2[Ff]2[Cc]90)/', $source);
        $this->assertStringContainsString(config('brand.colors.brand'), $this->itineraryHtml());
    }

    /**
     * public/assets/img/alt-logo.png is a white wordmark on a near-opaque white
     * field, so on the itinerary's white header it rendered invisible — while
     * still embedding ~272 KB twice, which is why that PDF weighed 347 KB
     * against the e-ticket's 51 KB. Until a usable asset exists the masthead is
     * set in type.
     */
    public function test_the_documents_do_not_embed_the_unusable_logo(): void
    {
        // Assert the property that matters — no image is placed or embedded at
        // all — rather than a filename.
        $html = $this->itineraryHtml();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('data:image', $html);
        $this->assertStringContainsString('TravelWheel', $html);
    }

    /**
     * DomPDF converts px through the dpi option, which ItineraryPdfService
     * sets to 150, so the itinerary's 9px body text printed at 4.3pt and its
     * labels at 3.6pt. Sizes are in pt so they print as written.
     */
    public function test_itinerary_type_sizes_are_set_in_points(): void
    {
        $css = (string) file_get_contents(resource_path('views/pdf/partials/itinerary-styles.blade.php'));

        $this->assertDoesNotMatchRegularExpression('/font-size:\s*[\d.]+px/', $css);
        $this->assertMatchesRegularExpression('/font-size:\s*9\.5pt/', $css);
    }

    /**
     * Without merging over config/dompdf.php, DomPDF's chroot is its own
     * vendor folder, the bundled fonts are refused, and every PDF silently
     * falls back to DejaVu.
     */
    public function test_the_itinerary_embeds_its_bundled_typeface(): void
    {
        $pdf = app(ItineraryPdfService::class)->generate($this->booking(), $this->tripDetails($this->booking()));

        $this->assertStringContainsString('IBMPlexSans', $pdf);
        $this->assertStringContainsString('IBMPlexMono', $pdf);
    }

    /** A document that is not a ticket has to say so. */
    public function test_an_unticketed_document_is_marked_as_not_valid_for_travel(): void
    {
        $itinerary = $this->itineraryHtml($this->booking(ticketed: false));

        $this->assertStringContainsString('ITINERARY ONLY', $itinerary);
        $this->assertStringContainsString('not a ticket', $itinerary);
    }

    private function itineraryHtml(?FlightBooking $booking = null): string
    {
        $booking ??= $this->booking();
        $service = app(ItineraryPdfService::class);

        return view('pdf.itinerary', $service->buildViewData($booking, $this->tripDetails($booking)))->render();
    }

    /** @return array<string, mixed> */
    private function tripDetails(FlightBooking $booking): array
    {
        return $booking->ticket_ordered
            ? ['BookingStatus' => 'CONFIRMED', 'TicketStatus' => 'TICKETED', 'ItineraryInfo' => ['AirlinePNR' => 'KL7X2MQ']]
            : [];
    }

    private function booking(bool $ticketed = true, bool $multiCity = false): FlightBooking
    {
        $leg1 = $this->segment('LOS', 'AMS', '2026-10-14T22:35:00', '2026-10-15T06:10:00', 'KL 588');
        $leg2 = $this->segment('AMS', 'LHR', '2026-10-15T08:25:00', '2026-10-15T08:45:00', 'KL 1007');

        return new FlightBooking([
            'booking_ref' => 'TW-8F3K2A',
            'unique_id' => $ticketed ? 'KL7X2MQ' : '',
            'airline' => 'KLM Royal Dutch Airlines',
            'total_price' => 1284500,
            'currency' => 'NGN',
            'contact_email' => 'traveller@example.test',
            'payment_status' => 'paid',
            'ticket_ordered' => $ticketed,
            'booking_status' => $ticketed ? 'ticketed' : 'confirmed',
            'tkt_time_limit' => $ticketed ? null : '2026-09-26 18:00:00',
            'passengers_snapshot' => [
                ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Adebayo', 'last_name' => 'Okonkwo', 'passport_no' => 'A01234567', 'nationality' => 'Nigerian'],
            ],
            'flight_snapshot' => [
                'airline' => 'KLM Royal Dutch Airlines',
                'cabin' => 'Economy',
                'currency' => 'NGN',
                'segments' => [$leg1, $leg2],
                'returnSegments' => [],
                'multiLegs' => $multiCity ? [
                    ['from' => 'LOS', 'to' => 'AMS', 'segments' => [$leg1]],
                    ['from' => 'AMS', 'to' => 'CDG', 'segments' => [$this->segment('AMS', 'CDG', '2026-10-19T11:15:00', '2026-10-19T12:35:00', 'KL 1223')]],
                    ['from' => 'CDG', 'to' => 'LOS', 'segments' => [$this->segment('CDG', 'LOS', '2026-10-26T10:40:00', '2026-10-26T17:05:00', 'AF 874')]],
                ] : [],
                'fareBreakdown' => [['passengerType' => 'ADT', 'qty' => 1, 'totalFare' => 1284500]],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function segment(string $from, string $to, string $depart, string $arrive, string $flightNo): array
    {
        return [
            'from' => $from, 'to' => $to,
            'fromCity' => $from.' City', 'toCity' => $to.' City',
            'fromAirport' => $from.' Airport', 'toAirport' => $to.' Airport',
            'departDT' => $depart, 'arriveDT' => $arrive,
            'airline' => 'KLM Royal Dutch Airlines', 'airlineCode' => 'KL', 'flightNo' => $flightNo,
            'equipment' => 'Boeing 777-300ER', 'duration' => 455, 'stops' => 0,
            'cabin' => 'Economy', 'cabinCode' => 'Y', 'resBookCode' => 'T', 'baggage' => '2 Pieces',
        ];
    }

    // ── The right state for each situation ─────────────────────────────────

    /** @return array<string, array{0: array<string, mixed>, 1: string, 2: string}> */
    public static function situations(): array
    {
        return [
            'ticketed' => [['booking_status' => 'ticketed', 'ticket_ordered' => true, 'payment_status' => 'paid'], 'E-ticket itinerary', 'Ticketed'],
            'seat on hold, unpaid' => [['booking_status' => 'on_hold', 'ticket_ordered' => false, 'payment_status' => 'pending'], 'Booking itinerary', 'On hold'],
            'told us they paid by transfer' => [['booking_status' => 'on_hold', 'ticket_ordered' => false, 'payment_status' => 'awaiting_bank_transfer'], 'Booking itinerary', 'Payment pending'],
            'card not confirmed yet' => [['booking_status' => 'pending_payment', 'ticket_ordered' => false, 'payment_status' => 'pending'], 'Booking itinerary', 'Payment pending'],
            'paid, ticket on its way' => [['booking_status' => 'confirmed', 'ticket_ordered' => false, 'payment_status' => 'paid'], 'Booking itinerary', 'Ticket being issued'],
        ];
    }

    /**
     * @dataProvider situations
     *
     * @param  array<string, mixed>  $attributes
     */
    public function test_each_situation_gets_the_right_document(array $attributes, string $title, string $label): void
    {
        $booking = $this->booking();
        $booking->forceFill($attributes);

        $data = app(ItineraryPdfService::class)->buildViewData($booking);

        $this->assertSame($title, $data['documentTitle']);
        $this->assertSame($label, $data['statusLabel']);
        $this->assertSame($label !== 'Ticketed', $data['showWatermark']);
    }

    public function test_a_transfer_the_customer_says_they_paid_does_not_read_as_unpaid(): void
    {
        $booking = $this->booking(ticketed: false);
        $booking->forceFill(['booking_status' => 'on_hold', 'payment_status' => 'awaiting_bank_transfer']);

        $html = view('pdf.itinerary', app(ItineraryPdfService::class)->buildViewData($booking))->render();

        $this->assertStringContainsString('waiting for your payment to be confirmed', $html);
        $this->assertStringNotContainsString('not yet paid for', $html);
    }

    public function test_the_filename_matches_what_the_document_says(): void
    {
        $service = app(ItineraryPdfService::class);
        $pending = $this->booking(ticketed: false);

        $this->assertSame('eticket-TW-8F3K2A.pdf', $service->filename($this->booking()));
        $this->assertSame('booking-itinerary-TW-8F3K2A.pdf', $service->filename($pending));
        // Asking for a ticket doesn't make one exist.
        $this->assertSame('booking-itinerary-TW-8F3K2A.pdf', $service->filename($pending, [], 'ticketed'));
        // A TravelFlex letter about a ticketed booking still isn't the ticket.
        $this->assertSame('booking-itinerary-TW-8F3K2A.pdf', $service->filename($this->booking(), [], 'travelflex_approved'));
    }

    public function test_the_old_ticket_design_is_gone(): void
    {
        foreach (['pdf.eticket', 'pdf.partials.styles', 'pdf.partials.journey'] as $view) {
            $this->assertFalse(view()->exists($view), "{$view} should have been deleted.");
        }

        $this->assertFalse(class_exists(\App\Services\ETicketPdfService::class));
    }
}
