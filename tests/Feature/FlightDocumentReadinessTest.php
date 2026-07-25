<?php

namespace Tests\Feature;

use App\Mail\BookingPendingMail;
use App\Mail\ETicketMail;
use App\Mail\PaymentReceiptMail;
use App\Mail\TravelFlexStatusMail;
use App\Mail\UnTicketedConfirmationAlert;
use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use App\Services\ETicketPdfService;
use App\Services\ItineraryPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightDocumentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_flight_mail_templates_render(): void
    {
        $held = $this->booking('on_hold', false);
        $ticketed = $this->booking('ticketed', true, 'TW-DOC-002');

        $application = TravelFlexApplication::create([
            'flight_booking_id' => $held->id,
            'booking_ref' => $held->booking_ref,
            'applicant_details' => ['email' => 'traveller@example.com', 'full_name' => 'Test Traveller'],
            'repayment_plan' => ['ticket_cost' => 100000, 'down_payment' => 30000],
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'pending',
            'approval_expires_at' => now()->addHours(2),
        ]);

        foreach ([
            new BookingPendingMail($held, 'hold'),
            new PaymentReceiptMail($ticketed),
            new ETicketMail($ticketed),
            new TravelFlexStatusMail($application->load('booking'), 'approved'),
            new UnTicketedConfirmationAlert([
                'uniqueId' => $ticketed->unique_id,
                'bookingStatus' => 'CONFIRMED',
                'ticketStatus' => 'UNTICKETED',
                'timestamp' => now(),
                'origin' => 'LOS',
                'destination' => 'ABV',
                'fareType' => 'Public',
                'passengers' => $ticketed->passengers_snapshot,
            ]),
        ] as $mail) {
            $html = $mail->render();
            $this->assertNotSame('', trim($html));
            $this->assertStringContainsString('TravelWheel', $html);
        }
    }

    public function test_itinerary_pdf_renders_one_way_round_trip_and_multicity(): void
    {
        $service = app(ItineraryPdfService::class);

        foreach (['oneway', 'return', 'multi'] as $tripType) {
            $booking = $this->booking('ticketed', true, 'TW-PDF-'.strtoupper($tripType));
            $snapshot = $booking->flight_snapshot;

            if ($tripType === 'return') {
                $snapshot['returnSegments'] = [$this->segment('ABV', 'LOS', '2026-08-10T16:00:00', '2026-08-10T17:15:00')];
            }

            if ($tripType === 'multi') {
                $snapshot['segments'] = [];
                $snapshot['multiLegs'] = [
                    ['segments' => [$this->segment('LOS', 'ABV', '2026-08-10T08:00:00', '2026-08-10T09:15:00')]],
                    ['segments' => [$this->segment('ABV', 'ACC', '2026-08-12T10:00:00', '2026-08-12T11:30:00')]],
                ];
            }

            $booking->update(['trip_type' => $tripType, 'flight_snapshot' => $snapshot]);
            $bytes = $service->generate($booking->fresh(), [], 'ticketed');

            $this->assertStringStartsWith('%PDF-', $bytes);
            $this->assertGreaterThan(5000, strlen($bytes));
        }
    }

    public function test_ticketed_booking_remains_ticketed_without_live_trip_details(): void
    {
        $booking = $this->booking('ticketed', true);

        $data = app(ETicketPdfService::class)->buildViewData($booking, []);

        $this->assertTrue($data['isTicketed']);
    }

    public function test_multicity_confirmation_summarises_the_actual_legs(): void
    {
        $booking = $this->booking('ticketed', true, 'TW-MULTI-VIEW');
        $snapshot = $booking->flight_snapshot;
        $snapshot['segments'] = [];
        $snapshot['multiLegs'] = [
            ['segments' => [$this->segment('LOS', 'ABV', '2026-08-10T08:00:00', '2026-08-10T09:15:00')]],
            ['segments' => [$this->segment('ABV', 'ACC', '2026-08-12T10:00:00', '2026-08-12T11:30:00')]],
        ];
        $booking->update([
            'trip_type' => 'multi',
            'route' => 'LOS → ABV → ACC',
            'flight_snapshot' => $snapshot,
        ]);

        $this->view('livewire.pages.flight.flight-confirmation', [
            'flight' => $snapshot,
            'dbBooking' => $booking->fresh(),
            'bookingRef' => $booking->booking_ref,
            'paymentMethod' => 'gateway',
            'ticketSuccess' => true,
            'tripDetails' => [],
        ])
            ->assertSee('Multi-city itinerary summary', false)
            ->assertSee('Leg 1')
            ->assertSee('LOS → ABV')
            ->assertSee('Leg 2')
            ->assertSee('ABV → ACC')
            ->assertDontSee('>Departure<', false)
            ->assertDontSee('>Arrival<', false);
    }

    private function booking(string $status, bool $ticketed, string $reference = 'TW-DOC-001'): FlightBooking
    {
        return FlightBooking::create([
            'booking_ref' => $reference,
            'unique_id' => 'SUPPLIER-'.$reference,
            'fare_source_code' => 'DOC-FARE-'.$reference,
            'fare_type' => 'Public',
            'trip_type' => 'oneway',
            'currency' => 'NGN',
            'total_price' => 100000,
            'booking_status' => $status,
            'payment_status' => $ticketed ? 'paid' : 'pending',
            'ticket_ordered' => $ticketed,
            'ticket_ordered_at' => $ticketed ? now() : null,
            'tkt_time_limit' => now()->addHours(6),
            'contact_email' => 'traveller@example.com',
            'contact_phone' => '+2348000000000',
            'passengers_snapshot' => [[
                'title' => 'Mr',
                'first_name' => 'Test',
                'last_name' => 'Traveller',
                'type' => 'ADT',
                'dob' => '1990-01-01',
                'nationality' => 'NG',
            ]],
            'flight_snapshot' => [
                'airline' => 'Test Air',
                'currency' => 'NGN',
                'price' => 100000,
                'cabin' => 'Economy',
                'segments' => [$this->segment('LOS', 'ABV', '2026-08-10T08:00:00', '2026-08-10T09:15:00')],
            ],
        ]);
    }

    private function segment(string $from, string $to, string $depart, string $arrive): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'fromCity' => $from === 'LOS' ? 'Lagos' : 'Abuja',
            'toCity' => $to === 'ABV' ? 'Abuja' : ($to === 'ACC' ? 'Accra' : 'Lagos'),
            'departDT' => $depart,
            'arriveDT' => $arrive,
            'departTime' => substr($depart, 11, 5),
            'arriveTime' => substr($arrive, 11, 5),
            'duration' => 75,
            'flightNo' => 'TA101',
            'airline' => 'Test Air',
            'airlineCode' => 'TA',
            'cabin' => 'Economy',
            'cabinCode' => 'Y',
        ];
    }
}
