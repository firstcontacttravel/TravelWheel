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

    /** Kept in step with config/brand.php — see AdminThemeUsesBrandPaletteTest. */
    private const BRAND = '#303191';

    private const ACCENT = '#00a859';

    private const AMBER = '#f79009';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getMaxHeight(): ?string
    {
        return '18rem';
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
                    'backgroundColor' => self::BRAND,
                    'borderRadius' => 4,
                    'yAxisID' => 'money',
                ],
                [
                    'label' => 'Service charges',
                    'data' => $serviceCharges,
                    'backgroundColor' => self::AMBER,
                    'borderRadius' => 4,
                    'yAxisID' => 'money',
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookings,
                    'type' => 'line',
                    'borderColor' => self::ACCENT,
                    'backgroundColor' => self::ACCENT,
                    'borderWidth' => 2,
                    'pointRadius' => 2,
                    'pointBackgroundColor' => self::ACCENT,
                    'pointBorderColor' => self::ACCENT,
                    'tension' => 0.35,
                    'yAxisID' => 'count',
                ],
            ],
            'labels' => $labels,
        ];
    }

    /*
     * Revenue is in hundreds of thousands of naira and the booking count is in
     * single digits. Sharing one axis meant the axis was scaled by whichever
     * number happened to be larger, and on a quiet fortnight it settled on the
     * count's 0-1 range and drew revenue and the booking line as one flat rule
     * along the bottom. Money on the left, volume on the right.
     */
    protected function getOptions(): array | \Filament\Support\RawJs | null
    {
        return [
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => [
                // Every dataset names an axis explicitly, but Chart.js still
                // materialises its default 'y' and drew a second, empty 0-1
                // ruler alongside the money one.
                'y' => ['display' => false],
                'money' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'NGN'],
                    'grid' => ['drawOnChartArea' => true],
                ],
                'count' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Bookings'],
                    'ticks' => ['precision' => 0],
                    // A second set of gridlines over the same plot area reads as
                    // noise, so only the money axis draws them.
                    'grid' => ['drawOnChartArea' => false],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'usePointStyle' => true]],
            ],
        ];
    }
}
