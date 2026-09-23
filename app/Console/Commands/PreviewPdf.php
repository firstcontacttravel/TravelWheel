<?php

namespace App\Console\Commands;

use App\Models\FlightBooking;
use App\Services\ETicketPdfService;
use App\Services\ItineraryPdfService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Renders the e-ticket and itinerary PDFs with representative sample data to
 * public/pdf-preview/, so the real DomPDF output can be reviewed rather than an
 * HTML approximation of it — DomPDF has no flexbox, no CSS variables and its
 * own idea of table layout, so a browser preview of the Blade would flatter
 * the design in ways the actual PDF does not.
 *
 * Output is gitignored and the command refuses to run in production.
 */
class PreviewPdf extends Command
{
    protected $signature = 'pdf:preview';

    protected $description = 'Render the e-ticket and itinerary PDFs to public/pdf-preview for visual review';

    public function handle(ETicketPdfService $eticket, ItineraryPdfService $itinerary): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to write previews into a production document root.');

            return self::FAILURE;
        }

        $dir = public_path('pdf-preview');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $cases = [
            'eticket-roundtrip-ticketed' => fn () => $eticket->generate($this->booking(), $this->tripDetails()),
            'eticket-oneway-pending' => fn () => $eticket->generate($this->booking(ticketed: false, roundTrip: false), []),
            'eticket-multicity' => fn () => $eticket->generate($this->booking(multiCity: true), $this->tripDetails()),
            'itinerary-ticketed' => fn () => $itinerary->generate($this->booking(), $this->tripDetails()),
            'itinerary-not-ticketed' => fn () => $itinerary->generate($this->booking(ticketed: false), []),
        ];

        $failed = 0;
        foreach ($cases as $name => $make) {
            try {
                file_put_contents($dir.'/'.$name.'.pdf', $make());
                $this->line("  <fg=green>ok</> {$name}.pdf");
            } catch (Throwable $e) {
                $failed++;
                $this->line("  <fg=red>fail</> {$name} — ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info((count($cases) - $failed).' rendered, '.$failed.' failed → public/pdf-preview/');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function booking(bool $ticketed = true, bool $roundTrip = true, bool $multiCity = false): FlightBooking
    {
        $out = [
            $this->segment('LOS', 'Lagos', 'Murtala Muhammed International', 'AMS', 'Amsterdam', 'Schiphol', '2026-10-14T22:35:00', '2026-10-15T06:10:00', 'KL', 'KLM Royal Dutch Airlines', 'KL 588', 'Boeing 777-300ER', 455),
            $this->segment('AMS', 'Amsterdam', 'Schiphol', 'LHR', 'London', 'Heathrow', '2026-10-15T08:25:00', '2026-10-15T08:45:00', 'KL', 'KLM Royal Dutch Airlines', 'KL 1007', 'Embraer 190', 80),
        ];
        $back = [
            $this->segment('LHR', 'London', 'Heathrow', 'LOS', 'Lagos', 'Murtala Muhammed International', '2026-10-28T20:05:00', '2026-10-29T04:40:00', 'KL', 'KLM Royal Dutch Airlines', 'KL 602', 'Airbus A330-300', 395),
        ];

        $flight = [
            'airline' => 'KLM Royal Dutch Airlines',
            'airlineCode' => 'KL',
            'cabin' => 'Economy',
            'currency' => 'NGN',
            'price' => 1284500,
            'segments' => $out,
            'returnSegments' => $roundTrip && ! $multiCity ? $back : [],
            'multiLegs' => $multiCity ? [
                ['from' => 'LOS', 'to' => 'AMS', 'segments' => [$out[0]]],
                ['from' => 'AMS', 'to' => 'CDG', 'segments' => [$this->segment('AMS', 'Amsterdam', 'Schiphol', 'CDG', 'Paris', 'Charles de Gaulle', '2026-10-19T11:15:00', '2026-10-19T12:35:00', 'KL', 'KLM Royal Dutch Airlines', 'KL 1223', 'Boeing 737-800', 80)]],
                ['from' => 'CDG', 'to' => 'LOS', 'segments' => [$this->segment('CDG', 'Paris', 'Charles de Gaulle', 'LOS', 'Lagos', 'Murtala Muhammed International', '2026-10-26T10:40:00', '2026-10-26T17:05:00', 'AF', 'Air France', 'AF 874', 'Airbus A350-900', 385)]],
            ] : [],
            'fareBreakdown' => [
                ['passengerType' => 'ADT', 'qty' => 2, 'totalFare' => 486000],
                ['passengerType' => 'CHD', 'qty' => 1, 'totalFare' => 312500],
            ],
        ];

        return new FlightBooking([
            'booking_ref' => 'TW-8F3K2A',
            'unique_id' => $ticketed ? 'KL7X2MQ' : '',
            'route' => 'Lagos (LOS) → London (LHR)',
            'airline' => 'KLM Royal Dutch Airlines',
            'total_price' => 1284500,
            'currency' => 'NGN',
            'contact_email' => 'adebayo.okonkwo@example.com',
            'contact_phone' => '+234 803 111 2233',
            'payment_method' => 'gateway',
            'payment_status' => 'paid',
            'ticket_ordered' => $ticketed,
            'ticket_ordered_at' => $ticketed ? '2026-09-20 11:24:00' : null,
            'tkt_time_limit' => $ticketed ? null : '2026-09-26 18:00:00',
            'booking_status' => $ticketed ? 'ticketed' : 'confirmed',
            'passengers_snapshot' => [
                ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Adebayo', 'last_name' => 'Okonkwo', 'passport_no' => 'A01234567', 'nationality' => 'Nigerian', 'gender' => 'Male', 'date_of_birth' => '1988-04-12'],
                ['type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Chidinma', 'last_name' => 'Okonkwo', 'passport_no' => 'A07654321', 'nationality' => 'Nigerian', 'gender' => 'Female', 'date_of_birth' => '1990-11-03'],
                ['type' => 'CHD', 'title' => 'Miss', 'first_name' => 'Ada', 'last_name' => 'Okonkwo', 'passport_no' => 'A09998887', 'nationality' => 'Nigerian', 'gender' => 'Female', 'date_of_birth' => '2017-06-21'],
            ],
            'flight_snapshot' => $flight,
            'extra_services_snapshot' => ['total_amount' => 57500],
        ]);
    }

    /** @return array<string, mixed> */
    private function segment(string $from, string $fromCity, string $fromAirport, string $to, string $toCity, string $toAirport, string $dep, string $arr, string $code, string $airline, string $flightNo, string $aircraft, int $minutes): array
    {
        return [
            'from' => $from, 'to' => $to,
            'fromCity' => $fromCity.' ('.$from.')', 'toCity' => $toCity.' ('.$to.')',
            'fromAirport' => $fromAirport, 'toAirport' => $toAirport,
            'departDT' => $dep, 'arriveDT' => $arr,
            'airline' => $airline, 'airlineCode' => $code, 'flightNo' => $flightNo,
            'equipment' => $aircraft, 'duration' => $minutes, 'stops' => 0,
            'cabin' => 'Economy', 'cabinCode' => 'Y', 'resBookCode' => 'T',
            'baggage' => '2 Pieces', 'cabinBaggage' => '12KG', 'terminal' => '3',
            'fareBasis' => 'TLRNG', 'mealCode' => 'M',
        ];
    }

    /** @return array<string, mixed> */
    private function tripDetails(): array
    {
        return [
            'BookingStatus' => 'CONFIRMED',
            'TicketStatus' => 'TICKETED',
            'ItineraryInfo' => [
                'AirlinePNR' => 'KL7X2MQ',
                'CustomerInfos' => [
                    ['CustomerInfo' => ['eTicketNumber' => '074-4410882751']],
                    ['CustomerInfo' => ['eTicketNumber' => '074-4410882752']],
                    ['CustomerInfo' => ['eTicketNumber' => '074-4410882753']],
                ],
            ],
        ];
    }
}
