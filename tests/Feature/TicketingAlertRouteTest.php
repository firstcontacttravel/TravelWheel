<?php

namespace Tests\Feature;

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

    private function alertHtml(array $flight): string
    {
        return view('emails.unticketed-confirmation-alert', ['data' => $this->alertData($flight)])->render();
    }

    private function alertData(array $flight): array
    {
        $booking = new FlightBooking([
            'booking_ref' => 'TW-ALERT1',
            'unique_id' => 'SKY-PNR-1',
            'flight_snapshot' => $flight,
            'passengers_snapshot' => [],
        ]);

        return app(AdminTicketingService::class)->failureAlertData($booking, 'Ticketing requires manual review.');
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
