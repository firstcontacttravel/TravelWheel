<?php

namespace App\Filament\Pages;

use App\Support\Admin\WorkloadReport;
use App\Workflow\WorkflowRegistry;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Insights → Workload: where bookings get stuck, whether deadlines are met,
 * and how the work is spread across departments and people. CEO only,
 * because it names individuals.
 */
class Workload extends Page
{
    protected string $view = 'filament.pages.workload';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Insights';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Workload';

    protected static ?string $title = 'Workload';

    /** @var list<int> */
    public const PERIODS = [7, 30, 90];

    public int $days = 30;

    public string $service = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function getSubheading(): ?string
    {
        return 'Where bookings wait, which steps run over their deadline, and how the work is spread. Open and overdue counts are as of now; everything else is for the period.';
    }

    /** @return array<string, mixed> */
    public function report(): array
    {
        $days = in_array($this->days, self::PERIODS, true) ? $this->days : 30;
        $service = array_key_exists($this->service, $this->serviceOptions()) ? $this->service : null;

        return app(WorkloadReport::class)->build(now()->subDays($days), now(), $service);
    }

    /** @return array<string, string> */
    public function serviceOptions(): array
    {
        return collect(app(WorkflowRegistry::class)->all())->map->label()->all();
    }

    public static function duration(?float $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        return match (true) {
            $minutes < 60 => round($minutes).' min',
            $minutes < 2880 => round($minutes / 60, 1).' h',
            default => round($minutes / 1440, 1).' days',
        };
    }
}
