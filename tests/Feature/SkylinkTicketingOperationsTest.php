<?php

namespace Tests\Feature;

use App\Filament\Resources\FlightBookings\Pages\ViewFlightBooking;
use App\Filament\Resources\FlightBookings\Tables\FlightBookingsTable;
use App\Mail\ETicketMail;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\TicketingRecord;
use App\Models\User;
use App\Services\AdminPostTicketingService;
use App\Services\AdminTicketingService;
use App\Services\DurableMailService;
use App\Services\ETicketPdfService;
use App\Services\ItineraryPdfService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SkyLink's reserve() returns a PNR and a ticketing deadline, never a ticket
 * number, and its API has no endpoint that ever will. Confirmed against the
 * sandbox (PNRs 9P5QB2, 9P5ZIA). These cover what the app does about that:
 * the deadline is kept where ops can see it, the customer is told what is
 * true, ops can record the tickets SkyLink sends out of band, and
 * TravelNext-only operations can no longer be pointed at a SkyLink PNR.
 */
class SkylinkTicketingOperationsTest extends TestCase
{
    use RefreshDatabase;

    // ── The ticketing deadline ────────────────────────────────────────────

    public function test_the_ticketing_deadline_from_reserve_is_stored_as_the_booking_deadline(): void
    {
        Mail::fake();
        $this->configureSkylink();
        $booking = $this->skylinkBooking([
            'payment_status' => 'pending',
            'booking_status' => 'pending_payment',
        ]);
        session(['flightBookingDbId' => $booking->id]);

        Http::fake(function ($request) {
            return match (true) {
                str_contains($request->url(), '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($request->url(), '/payments/query/') => Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => 750000, 'currency' => 'NGN',
                ]]]),
                str_contains($request->url(), '/api/login') => Http::response(['data' => ['access_token' => 'token']]),
                str_contains($request->url(), '/flights/reserve') => Http::response(['success' => true, 'data' => [
                    'pnr' => '9P5QB2',
                    'booking_reference' => '9P5QB2',
                    'status' => 'confirmed',
                    'ticket_time_limit_hours' => 48,
                    // Verbatim sandbox value: offset-less, Lagos time.
                    'ticket_deadline' => '2026-09-20 12:21:04',
                ]]),
                default => Http::response([]),
            };
        });

        $this->get(route('payments.seerbit.callback', ['paymentReference' => $booking->payment_reference]))
            ->assertRedirect(route('flights.confirmation'));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->booking_status);
        // 12:21:04 Lagos (UTC+1) is 11:21:04 UTC. Read as UTC it would land an
        // hour late, telling ops they had more time than they do.
        $this->assertTrue(
            $booking->tkt_time_limit->equalTo(Carbon::parse('2026-09-20 11:21:04', 'UTC')),
            'Stored '.$booking->tkt_time_limit?->toIso8601String()
        );
    }

    public function test_the_backfill_fills_only_skylink_bookings_still_missing_a_deadline(): void
    {
        $missing = $this->skylinkBooking(['booking_api_response' => ['ticketDeadline' => '2026-09-20 12:21:04']]);
        $alreadySet = $this->skylinkBooking([
            'booking_api_response' => ['ticketDeadline' => '2026-09-20 12:21:04'],
            'tkt_time_limit' => '2026-01-01 00:00:00',
        ]);
        $travelNext = $this->travelNextBooking(['booking_api_response' => ['ticketDeadline' => '2026-09-20 12:21:04']]);

        (require database_path('migrations/2026_09_18_000000_backfill_skylink_ticket_deadlines.php'))->up();

        $this->assertTrue($missing->fresh()->tkt_time_limit->equalTo(Carbon::parse('2026-09-20 11:21:04', 'UTC')));
        $this->assertSame('2026-01-01 00:00:00', $alreadySet->fresh()->tkt_time_limit->format('Y-m-d H:i:s'));
        $this->assertNull($travelNext->fresh()->tkt_time_limit);
    }

    // ── TravelNext-only operations ────────────────────────────────────────

    public function test_travelnext_ticketing_services_refuse_a_skylink_booking_without_calling_out(): void
    {
        Http::preventStrayRequests();
        $booking = $this->skylinkBooking();

        $order = app(AdminTicketingService::class)->ticketOrder($booking);
        $trip = app(AdminTicketingService::class)->tripDetails($booking);
        $cancel = app(AdminPostTicketingService::class)->call($booking, 'cancel');

        $this->assertFalse($order['ok']);
        $this->assertFalse($trip['ok']);
        $this->assertFalse($cancel['ok']);

        // ticket_order used to mark the booking ticketing_failed on the way out.
        $this->assertSame('confirmed', $booking->fresh()->booking_status);
    }

    public function test_travelnext_bookings_still_reach_travelnext(): void
    {
        $this->markTestSkipped('DEMO BRANCH: TravelNext is blocked here by design (AppServiceProvider tripwire). This case guards feature/ui-redesign, where both suppliers run.');

        config([
            'services.travelnext.base_url' => 'https://travelnext.test/api/',
            'services.travelnext.user_id' => 'user',
            'services.travelnext.password' => 'secret',
            'services.travelnext.access' => 'Test',
            'services.travelnext.ip' => '127.0.0.1',
        ]);
        Http::fake(['*trip_details' => Http::response(['TripDetailsResponse' => ['TripDetailsResult' => [
            'Success' => 'true',
            'TravelItinerary' => ['TicketStatus' => 'Ticketed', 'BookingStatus' => 'Booked'],
        ]]])]);

        $result = app(AdminTicketingService::class)->tripDetails($this->travelNextBooking());

        $this->assertTrue($result['ok']);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'trip_details'));
    }

    public function test_admin_actions_follow_the_supplier(): void
    {
        $skylink = $this->skylinkBooking();
        $travelNext = $this->travelNextBooking(['booking_status' => 'on_hold', 'payment_status' => 'paid']);

        $visible = fn ($action, FlightBooking $booking): bool => $action->record($booking)->isVisible();

        // "Order ticket" was visible on every paid SkyLink booking and would
        // have sent its PNR to TravelNext's ticket_order.
        $this->assertFalse($visible(FlightBookingsTable::orderTicketAction(), $skylink));
        $this->assertFalse($visible(FlightBookingsTable::fetchTripDetailsAction(), $skylink));
        $this->assertFalse($visible(FlightBookingsTable::cancelBookingAction(), $skylink));
        $this->assertTrue($visible(FlightBookingsTable::recordSkylinkTicketsAction(), $skylink));

        $this->assertTrue($visible(FlightBookingsTable::orderTicketAction(), $travelNext));
        $this->assertTrue($visible(FlightBookingsTable::fetchTripDetailsAction(), $travelNext));
        $this->assertTrue($visible(FlightBookingsTable::cancelBookingAction(), $travelNext));
        $this->assertFalse($visible(FlightBookingsTable::recordSkylinkTicketsAction(), $travelNext));

        // Once ticketed, the void/refund/reissue family appears — TravelNext only.
        $skylinkTicketed = $this->skylinkBooking(['booking_status' => 'ticketed', 'ticket_ordered' => true]);
        $travelNextTicketed = $this->travelNextBooking(['booking_status' => 'ticketed', 'ticket_ordered' => true, 'payment_status' => 'paid']);
        $this->assertFalse($visible(FlightBookingsTable::voidQuoteAction(), $skylinkTicketed));
        $this->assertTrue($visible(FlightBookingsTable::voidQuoteAction(), $travelNextTicketed));
        $this->assertFalse($visible(FlightBookingsTable::recordSkylinkTicketsAction(), $skylinkTicketed));
    }

    // ── Recording ticket numbers ──────────────────────────────────────────

    public function test_recording_ticket_numbers_tickets_the_booking_and_emails_the_customer(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        $booking = $this->skylinkBooking(['passengers_snapshot' => $this->twoPassengers()]);

        // The booking-confirmed email goes out at reservation under the default
        // outbox key. The ticket email must not reuse it: the outbox treats a
        // delivered key as done and returns success without sending.
        app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::FLIGHT_ETICKET, $booking->contact_email, $booking,
            ['trip_details' => []], 'flight-eticket:'.$booking->id,
        );
        Mail::assertSent(ETicketMail::class, 1);

        $this->recordTickets($booking, ['157-2345678901', '157 2345678902'])->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertSame('ticketed', $booking->booking_status);
        $this->assertTrue($booking->ticket_ordered);
        $this->assertNotNull($booking->ticket_ordered_at);
        $this->assertSame('1572345678901', $booking->passengers_snapshot[0]['eticket']);
        $this->assertSame('1572345678902', $booking->passengers_snapshot[1]['eticket']);

        Mail::assertSent(ETicketMail::class, 2);
        Mail::assertSent(ETicketMail::class, fn (ETicketMail $mail): bool => $mail->booking->isTicketed()
            && $mail->envelope()->subject === 'Your E-Ticket - '.$booking->booking_ref.' | TravelWheel');
        $this->assertTrue(NotificationOutbox::query()->where('unique_key', 'flight-eticket:'.$booking->id.':ticketed')->whereNotNull('sent_at')->exists());

        $this->assertDatabaseHas(TicketingRecord::class, [
            'flight_booking_id' => $booking->id,
            'action' => 'skylink_tickets_recorded',
            'previous_booking_status' => 'confirmed',
            'new_booking_status' => 'ticketed',
        ]);
    }

    public function test_a_malformed_ticket_number_is_rejected(): void
    {
        Mail::fake();
        $booking = $this->skylinkBooking();

        $this->recordTickets($booking, ['12345'])->assertHasActionErrors(['tickets.0' => 'regex']);

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
        Mail::assertNothingSent();
    }

    public function test_the_same_ticket_number_cannot_be_given_to_two_passengers(): void
    {
        Mail::fake();
        $booking = $this->skylinkBooking(['passengers_snapshot' => $this->twoPassengers()]);

        $this->recordTickets($booking, ['1572345678901', '157-234-567-8901']);

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
        Mail::assertNothingSent();
    }

    public function test_resending_a_skylink_e_ticket_actually_sends_it(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        $booking = $this->skylinkBooking(['booking_status' => 'ticketed', 'ticket_ordered' => true]);

        // Both the confirmation and the ticket email already went out; a
        // resend under either key would be silently skipped.
        foreach (['flight-eticket:'.$booking->id, 'flight-eticket:'.$booking->id.':ticketed'] as $key) {
            app(DurableMailService::class)->sendNowOrStore(DurableMailService::FLIGHT_ETICKET, $booking->contact_email, $booking, ['trip_details' => []], $key);
        }

        $this->adminPage($booking)->callAction('resendETicket')->assertHasNoActionErrors();

        Mail::assertSent(ETicketMail::class, 3);
    }

    // ── What the customer is told ────────────────────────────────────────

    public function test_the_e_ticket_email_is_not_titled_an_e_ticket_before_one_exists(): void
    {
        $awaiting = $this->skylinkBooking();
        $ticketed = $this->skylinkBooking(['booking_status' => 'ticketed', 'ticket_ordered' => true]);
        $travelNext = $this->travelNextBooking(['booking_status' => 'on_hold']);

        $this->assertSame('Booking confirmed - '.$awaiting->booking_ref.' | TravelWheel', (new ETicketMail($awaiting))->envelope()->subject);
        $this->assertSame('Your E-Ticket - '.$ticketed->booking_ref.' | TravelWheel', (new ETicketMail($ticketed))->envelope()->subject);
        // TravelNext is untouched.
        $this->assertSame('Your E-Ticket - '.$travelNext->booking_ref.' | TravelWheel', (new ETicketMail($travelNext))->envelope()->subject);
    }

    public function test_customer_documents_use_skylink_wording_and_never_show_the_deadline(): void
    {
        $booking = $this->skylinkBooking(['tkt_time_limit' => now()->addDay()]);

        $eticket = app(ETicketPdfService::class)->buildViewData($booking, []);
        $this->assertTrue($eticket['awaitingSupplierTicket']);
        // Was blank: the PNR came only from TravelNext's trip details.
        $this->assertSame('9P5QB2', $eticket['ticketPNR']);

        $customer = app(ItineraryPdfService::class)->buildViewData($booking, [], 'auto', 'customer');
        $internal = app(ItineraryPdfService::class)->buildViewData($booking, [], 'auto', 'internal');
        $this->assertTrue($customer['awaitingSupplierTicket']);
        $this->assertFalse($internal['awaitingSupplierTicket']);

        $this->assertStringNotContainsString('Hold expires', view('pdf.itinerary', $customer)->render());
        $this->assertStringContainsString('Hold expires', view('pdf.itinerary', $internal)->render());
        $this->assertStringContainsString('The airline issues your ticket separately', view('pdf.eticket', $eticket)->render());
    }

    public function test_the_confirmation_page_makes_no_timing_promise_for_skylink(): void
    {
        $this->withSession($this->confirmationSession('skylink'));

        $html = $this->get(route('flights.confirmation'))->assertOk()->getContent();

        $this->assertStringContainsString('Awaiting ticket', $html);
        $this->assertStringContainsString('The airline issues your ticket separately', $html);
        $this->assertStringNotContainsString('15 to 30 minutes', $html);
        $this->assertStringNotContainsString('Ticketing in progress', $html);
    }

    public function test_the_confirmation_page_is_unchanged_for_travelnext(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $this->withSession($this->confirmationSession('travelnext'));

        $html = $this->get(route('flights.confirmation'))->assertOk()->getContent();

        $this->assertStringContainsString('Ticketing in progress', $html);
        $this->assertStringContainsString('15 to 30 minutes', $html);
        $this->assertStringNotContainsString('Awaiting ticket', $html);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function recordTickets(FlightBooking $booking, array $numbers)
    {
        return $this->adminPage($booking)->callAction('recordSkylinkTickets', data: ['tickets' => $numbers]);
    }

    private function adminPage(FlightBooking $booking)
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::test(ViewFlightBooking::class, ['record' => $booking->getRouteKey()]);
    }

    private function configureSkylink(): void
    {
        config([
            'services.skylink.base_url' => 'https://247travels.test/api/',
            'services.skylink.email' => 'partner@example.test',
            'services.skylink.password' => 'secret',
            'services.skylink.timezone' => 'Africa/Lagos',
            'services.seerbit.public_key' => 'test-public',
            'services.seerbit.secret_key' => 'test-secret',
        ]);

        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    /** A paid SkyLink booking that reserve() confirmed and nobody has ticketed yet. */
    private function skylinkBooking(array $overrides = []): FlightBooking
    {
        return FlightBooking::create(array_merge([
            'booking_ref' => 'TW-SKY-'.strtoupper(str()->random(6)),
            'unique_id' => '9P5QB2',
            'fare_source_code' => 'btk_test',
            'supplier' => 'skylink',
            'fare_type' => 'Public',
            'payment_reference' => 'PAY-'.strtoupper(str()->random(12)),
            'payment_gateway' => 'seerbit',
            'payment_flow' => 'skylink_reserve_full',
            'payment_method' => 'gateway',
            'payment_amount' => 750000,
            'payment_currency' => 'NGN',
            'total_price' => 750000,
            'currency' => 'NGN',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
            'ticket_ordered' => false,
            'contact_email' => 'traveller@example.test',
            'adult_count' => 1,
            'child_count' => 0,
            'infant_count' => 0,
            'passengers_snapshot' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger']],
            'flight_snapshot' => ['source' => 'skylink', 'currency' => 'NGN', 'price' => 750000, 'airline' => 'Air Tanzania',
                'segments' => [['from' => 'LOS', 'to' => 'DXB', 'airline' => 'Air Tanzania', 'airlineCode' => 'TC', 'flightNo' => 'TC235']]],
        ], $overrides));
    }

    private function travelNextBooking(array $overrides = []): FlightBooking
    {
        return $this->skylinkBooking(array_merge([
            'booking_ref' => 'TW-TN-'.strtoupper(str()->random(6)),
            'unique_id' => 'TN-UNIQUE-1',
            'supplier' => 'travelnext',
            'payment_flow' => 'held_ticket_full',
            'flight_snapshot' => ['source' => 'travelnext', 'currency' => 'NGN', 'price' => 750000, 'segments' => [['from' => 'LOS', 'to' => 'DXB']]],
        ], $overrides));
    }

    private function twoPassengers(): array
    {
        return [
            ['type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ada', 'last_name' => 'Obi'],
            ['type' => 'CHD', 'title' => 'Master', 'first_name' => 'Tobi', 'last_name' => 'Obi'],
        ];
    }

    private function confirmationSession(string $source): array
    {
        return [
            'bookingFlight' => [
                'source' => $source,
                'currency' => 'NGN',
                'price' => 603838.70,
                'airline' => 'Royal Air Maroc', 'airlineCode' => 'AT',
                'segments' => [[
                    'from' => 'LOS', 'to' => 'DXB', 'fromCity' => 'Lagos (LOS)', 'toCity' => 'Dubai (DXB)',
                    'airline' => 'Royal Air Maroc', 'airlineCode' => 'AT', 'flightNo' => 'AT556',
                    'departTime' => '07:15 pm', 'arriveTime' => '11:50 pm',
                    'departDT' => '2026-09-24T19:15:00+00:00', 'arriveDT' => '2026-09-24T23:50:00+00:00',
                ]],
                'returnSegments' => [], 'multiLegs' => [], 'fareBreakdown' => [],
            ],
            'bookingUniqueId' => '9P2B7T',
            'bookingRef' => 'TW-AF7N36CX',
            'bookingStatus' => 'CONFIRMED',
            'paymentMethod' => 'gateway',
            'ticketSuccess' => false,
            'bookingContact' => ['email' => 'traveller@example.com'],
            'bookingPassengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Test', 'last_name' => 'Passenger']],
        ];
    }
}
