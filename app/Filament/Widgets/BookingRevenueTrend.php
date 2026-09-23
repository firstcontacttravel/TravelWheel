<?php

namespace App\Filament\Widgets;

use App\Models\FlightBooking;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class BookingRevenueTrend extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Paid Revenue Trend';

    protected ?string $description = 'Confirmed payment value by booking creation date over the last 14 days.';

    protected string $color = 'primary';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = today()->subDays(13);

        $rows = FlightBooking::query()
            ->selectRaw('DATE(created_at) as booking_date')
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN COALESCE(payment_charged_amount, payment_amount, total_price, 0) ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN COALESCE(markup_amount, 0) ELSE 0 END) as service_charges")
            ->whereDate('created_at', '>=', $start)
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->get()
            ->keyBy('booking_date');

        $labels = [];
        $revenue = [];
        $serviceCharges = [];
        $bookings = [];

        for ($day = 0; $day < 14; $day++) {
            $date = CarbonImmutable::parse($start)->addDays($day);
            $key = $date->toDateString();
            $row = $rows->get($key);

            $labels[] = $date->format('M j');
            $revenue[] = round((float) ($row->revenue ?? 0), 2);
            $serviceCharges[] = round((float) ($row->service_charges ?? 0), 2);
            $bookings[] = (int) ($row->bookings ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Paid revenue',
                    'data' => $revenue,
                    'backgroundColor' => '#0d1883',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Service charges',
                    'data' => $serviceCharges,
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookings,
                    'type' => 'line',
                    'borderColor' => '#00a85a',
                    'backgroundColor' => '#00a85a',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
