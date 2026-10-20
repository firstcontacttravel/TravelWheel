<?php

namespace Tests\Feature;

use App\Mail\UnTicketedConfirmationAlert;
use App\Models\FlightBooking;
use App\Services\AdminTicketingService;
use Tests\TestCase;

/**
 * The ticketing alert read only flight_snapshot.segments: a SkyLink
 * multi-city booking (segments empty) sent ops an alert with a blank route,
 * and a TravelNext one (segments holding leg 1) showed leg 1 only.
 */
class TicketingAlertRouteTest extends TestCase
{
    public function test_a_skylink_multi_city_alert_shows_every_leg(): void
    {
        $html = $this->alertHtml(['segments' => [], 'returnSegments' => [], 'multiLegs' => $this->multiLegs()]);

        $this->assertStringContainsString('LOS → LHR → DXB → LOS', $html);
        $this->assertStringContainsString('Multi-city', $html);
        $this->assertMatchesRegularExpression('/Leg 2.*LHR &rarr; DXB.*Fri, 27 Nov 2026.*MS778, MS901/s', $html);
        $this->assertMatchesRegularExpression('/Leg 3.*DXB &rarr; LOS.*MS906/s', $html);
    }

    public function test_a_travelnext_multi_city_alert_does_not_stop_at_leg_one(): void
    {
        $legs = $this->multiLegs();
        $data = $this->alertData(['segments' => $legs[0]['segments'], 'returnSegments' => [], 'multiLegs' => $legs]);

        $this->assertSame('LOS', $data['origin']);
        $this->assertSame('LOS', $data['destination']);
        $this->assertSame(['Leg 1', 'Leg 2', 'Leg 3'], array_column($data['legs'], 'label'));
        $this->assertSame(['MS876, MS777', 'MS778, MS901', 'MS906'], array_column($data['legs'], 'flights'));
    }

    public function test_a_return_alert_routes_to_the_destination_and_lists_both_ways(): void
    {
        $data = $this->alertData([
            'segments' => [$this->segment('LOS', 'LHR', '2026-11-20T10:00:00', 'BA75')],
            'returnSegments' => [$this->segment('LHR', 'LOS', '2026-12-04T11:00:00', 'BA74')],
            'multiLegs' => [],
        ]);

        $this->assertSame(['LOS', 'LHR', 'Return'], [$data['origin'], $data['destination'], $data['tripType']]);
        $this->assertSame(['Outbound', 'Return'], array_column($data['legs'], 'label'));
    }

    /**
     * Every alert said the airline returned CONFIRMED without a ticket
     * number. A SkyLink alert after payment means nothing was reserved, and
     * its blank PNR left the subject and reference panel empty.
     */
    public function test_a_skylink_reservation_that_failed_after_payment_says_nothing_is_reserved(): void
    {
        $data = $this->alertData(['segments' => [], 'multiLegs' => $this->multiLegs()], [
            'supplier' => 'skylink', 'unique_id' => null,
        ], 'SkyLink reservation failed after payment.');
        $html = view('emails.unticketed-confirmation-alert', ['data' => $data])->render();

        $this->assertSame('TW-ALERT1', $data['uniqueId']);
        $this->assertSame('ALERT: Paid Booking Not Reserved - TW-ALERT1', (new UnTicketedConfirmationAlert($data))->envelope()->subject);
        $this->assertStringContainsString('Paid booking has no seat reserved', $html);
        $this->assertStringContainsString('SkyLink reservation failed after payment.', $html);
        $this->assertStringContainsString('Rebook the same or another fare, or refund', $html);
        $this->assertStringNotContainsString('returned CONFIRMED', $html);
    }

    public function test_a_reserved_skylink_booking_is_flagged_as_awaiting_its_ticket(): void
    {
        $data = $this->alertData(['segments' => [], 'multiLegs' => $this->multiLegs()], [
            'supplier' => 'skylink', 'tkt_time_limit' => '2026-11-18 16:00:00',
        ], 'Payment received, but ticket issuance needs manual processing.');
        $html = view('emails.unticketed-confirmation-alert', ['data' => $data])->render();

        $this->assertSame('ALERT: SkyLink Booking Needs Ticketing - SKY-PNR-1', (new UnTicketedConfirmationAlert($data))->envelope()->subject);
        $this->assertStringContainsString('Paid SkyLink booking still needs its ticket', $html);
        $this->assertStringContainsString('Seat held until', $html);
        $this->assertStringNotContainsString('returned CONFIRMED', $html);
    }

    public function test_a_travelnext_alert_keeps_its_wording_and_now_shows_the_reason(): void
    {
        $data = $this->alertData(['segments' => [$this->segment('LOS', 'LHR', '2026-11-20T10:00:00', 'BA75')]], [], 'Supplier status is NOT_TICKETED while booking status is CONFIRMED.');
        $html = view('emails.unticketed-confirmation-alert', ['data' => $data])->render();

        $this->assertSame('ALERT: Booking Confirmed but Not Ticketed - SKY-PNR-1', (new UnTicketedConfirmationAlert($data))->envelope()->subject);
        $this->assertStringContainsString('returned CONFIRMED without a ticket number', $html);
        $this->assertStringContainsString('Supplier status is NOT_TICKETED while booking status is CONFIRMED.', $html);
    }

    private function alertHtml(array $flight): string
    {
        return view('emails.unticketed-confirmation-alert', ['data' => $this->alertData($flight)])->render();
    }

    private function alertData(array $flight, array $booking = [], string $message = 'Ticketing requires manual review.'): array
    {
        $booking = new FlightBooking(array_merge([
            'booking_ref' => 'TW-ALERT1',
            'unique_id' => 'SKY-PNR-1',
            'flight_snapshot' => $flight,
            'passengers_snapshot' => [],
        ], $booking));

        return app(AdminTicketingService::class)->failureAlertData($booking, $message);
    }

    private function multiLegs(): array
    {
        return [
            ['from' => 'LOS', 'to' => 'LHR', 'segments' => [
                $this->segment('LOS', 'CAI', '2026-11-20T14:45:00', 'MS876'),
                $this->segment('CAI', 'LHR', '2026-11-21T09:20:00', 'MS777'),
            ]],
            ['from' => 'LHR', 'to' => 'DXB', 'segments' => [
                $this->segment('LHR', 'CAI', '2026-11-27T14:00:00', 'MS778'),
                $this->segment('CAI', 'DXB', '2026-11-28T04:25:00', 'MS901'),
            ]],
            ['from' => 'DXB', 'to' => 'LOS', 'segments' => [
                $this->segment('DXB', 'LOS', '2026-12-05T20:00:00', 'MS906'),
            ]],
        ];
    }

    private function segment(string $from, string $to, string $departs, string $flightNo): array
    {
        return ['from' => $from, 'to' => $to, 'departDT' => $departs, 'flightNo' => $flightNo];
    }
}
