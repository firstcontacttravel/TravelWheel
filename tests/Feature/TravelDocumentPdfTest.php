<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Services\ETicketPdfService;
use App\Services\ItineraryPdfService;
use Tests\TestCase;

/**
 * The e-ticket and itinerary PDFs now share one stylesheet and one journey
 * component. These assert on the rendered HTML rather than the PDF bytes —
 * every defect below was visible in the markup, and rendering through DomPDF
 * in a test would only add minutes without adding coverage. The PDF output
 * itself is reviewed with `php artisan pdf:preview`.
 */
class TravelDocumentPdfTest extends TestCase
{
    /**
     * The old partial ran every flight time through ->timezone('Africa/Lagos').
     * Supplier datetimes arrive naive and are parsed as UTC (config app.timezone),
     * so that quietly pushed every departure and arrival forward by an hour: a
     * 22:35 departure printed as 23:35, and an Amsterdam connection at 08:25
     * local printed as 09:25. The itinerary parsed the same value without
     * shifting, so the two documents attached to one email disagreed about
     * when the flight left.
     */
    public function test_flight_times_are_printed_as_given_not_shifted_into_lagos_time(): void
    {
        $html = $this->eticketHtml();

        $this->assertStringContainsString('22:35', $html, 'The 22:35 departure is missing.');
        $this->assertStringNotContainsString('23:35', $html, 'A flight time was shifted forward by an hour.');

        $itinerary = $this->itineraryHtml();
        $this->assertStringContainsString('22:35', $itinerary);
        $this->assertStringNotContainsString('23:35', $itinerary);
    }

    /**
     * The previous layout drew each segment as a separate boxed card, so on
     * LOS-AMS-LHR the traveller saw "arrive AMS 06:10" and "depart AMS 08:25"
     * as two unrelated flights. The connection time could not be expressed.
     */
    public function test_a_connection_shows_how_long_the_layover_is(): void
    {
        foreach (['e-ticket' => $this->eticketHtml(), 'itinerary' => $this->itineraryHtml()] as $doc => $html) {
            $this->assertStringContainsString('2h 15m connection', $html, "The {$doc} does not show the layover.");
        }
    }

    /**
     * A multi-city booking carries its first leg in both flight_snapshot
     * .segments and .multiLegs[0]. Rendering the outbound section
     * unconditionally alongside the legs printed that leg twice.
     */
    public function test_a_multi_city_eticket_does_not_print_its_first_leg_twice(): void
    {
        $html = $this->eticketHtml($this->booking(multiCity: true));

        $this->assertStringContainsString('Leg 1', $html);
        $this->assertStringContainsString('Leg 3', $html);
        $this->assertStringNotContainsString('>Outbound<', $html, 'Multi-city printed an Outbound section as well as its legs.');
        $this->assertSame(1, substr_count($html, 'KL 588'), 'The first leg appears more than once.');
    }

    /**
     * The e-ticket painted its header #303191 and the itinerary painted its own
     * #39328f — two different brand blues on two PDFs attached to the same
     * email. Both now read config/brand.php through the shared stylesheet.
     */
    public function test_both_documents_are_built_from_the_shared_stylesheet(): void
    {
        foreach ([
            'resources/views/pdf/eticket.blade.php',
            'resources/views/pdf/itinerary.blade.php',
        ] as $view) {
            $source = (string) file_get_contents(base_path($view));

            $this->assertStringContainsString("@include('pdf.partials.styles')", $source, "{$view} does not use the shared stylesheet.");
            $this->assertDoesNotMatchRegularExpression(
                '/#(0[Dd]1883|39328[Ff]|303191|009933|2[Ff]2[Cc]90)/',
                $source,
                "{$view} hardcodes a brand colour instead of reading config('brand.colors').",
            );
        }

        // Same brand colour reaches both documents.
        $brand = config('brand.colors.brand');
        $this->assertStringContainsString($brand, $this->eticketHtml());
        $this->assertStringContainsString($brand, $this->itineraryHtml());
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
        foreach (['e-ticket' => $this->eticketHtml(), 'itinerary' => $this->itineraryHtml()] as $doc => $html) {
            // Assert the property that matters — no image is placed or embedded
            // at all — rather than a filename, which could legitimately appear
            // in prose explaining why there isn't one.
            $this->assertStringNotContainsString('<img', $html, "The {$doc} places an image.");
            $this->assertStringNotContainsString('data:image', $html, "The {$doc} embeds an image data URI.");
            $this->assertStringContainsString('TravelWheel', $html);
        }
    }

    /** A document that is not a ticket has to say so, in both places. */
    public function test_an_unticketed_document_is_marked_as_not_valid_for_travel(): void
    {
        $pending = $this->booking(ticketed: false);

        $eticket = $this->eticketHtml($pending);
        $this->assertStringContainsString('NOT YET A TICKET', $eticket);
        $this->assertStringContainsString('Do not travel on this document', $eticket);

        $itinerary = $this->itineraryHtml($pending);
        $this->assertStringContainsString('ITINERARY ONLY', $itinerary);
        $this->assertStringContainsString('not a ticket', $itinerary);
    }

    private function eticketHtml(?FlightBooking $booking = null): string
    {
        $booking ??= $this->booking();
        $service = app(ETicketPdfService::class);

        return view('pdf.eticket', $service->buildViewData($booking, $this->tripDetails($booking)))->render();
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
}
