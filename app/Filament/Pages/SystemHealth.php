<?php

namespace App\Filament\Pages;

use App\Models\SystemHealthRun;
use App\Services\SystemHealthService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class SystemHealth extends Page
{
    protected string $view = 'filament.pages.system-health';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'System Health';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $title = 'System Health';

    protected static ?int $navigationSort = 90;

    public array $report = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isVisaAdministrator() ?? false;
    }

    public function mount(SystemHealthService $health): void
    {
        abort_unless(static::canAccess(), 403);

        $this->report = $health->runAndStore(
            auth()->user(),
            includeConnectivity: ! app()->environment('testing'),
        );
    }

    public function runHealthChecks(SystemHealthService $health): void
    {
        abort_unless(static::canAccess(), 403);

        $this->report = $health->runAndStore(
            auth()->user(),
            includeConnectivity: ! app()->environment('testing'),
        );

        $status = $this->report['overall_status'];

        Notification::make()
            ->title('System health checks completed')
            ->body(match ($status) {
                'healthy' => 'Every check passed.',
                'warning' => $this->report['warning_count'].' warning(s) need review.',
                default => $this->report['failed_count'].' check(s) failed.',
            })
            ->color(match ($status) {
                'healthy' => 'success',
                'warning' => 'warning',
                default => 'danger',
            })
            ->send();
    }

    public function loadRun(int $runId): void
    {
        abort_unless(static::canAccess(), 403);

        $run = SystemHealthRun::query()->findOrFail($runId);
        $checks = $run->results ?? [];

        $this->report = [
            'run_id' => $run->id,
            'overall_status' => $run->overall_status,
            'healthy_count' => $run->healthy_count,
            'warning_count' => $run->warning_count,
            'failed_count' => $run->failed_count,
            'total_count' => count($checks),
            'duration_ms' => $run->duration_ms,
            'checked_at' => $run->created_at->toIso8601String(),
            'checks' => $checks,
            'groups' => collect($checks)->groupBy('group')->map->values()->all(),
            'context' => $run->context ?? [],
        ];
    }

    public function recentRuns(): Collection
    {
        return SystemHealthRun::query()
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get();
    }
}
