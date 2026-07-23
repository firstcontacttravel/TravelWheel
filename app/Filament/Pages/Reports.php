<?php

namespace App\Filament\Pages;

use App\Models\ReportingAlert;
use App\Models\ReportingFact;
use App\Models\ReportingSavedView;
use App\Services\Reporting\ReportingAnalytics;
use App\Services\Reporting\ReportingSynchronizer;
use App\Support\Reporting\ReportingAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Insights';

    protected static ?string $title = 'Business Intelligence';

    protected static ?int $navigationSort = 40;

    public string $from = '';
    public string $to = '';
    public string $dateBasis = 'created';
    public string $section = 'overview';
    public array $products = [];
    public string $paymentStatus = '';
    public string $fulfillmentStatus = '';
    public string $paymentMethod = '';
    public string $savedViewName = '';

    public static function canAccess(): bool
    {
        return ReportingAccess::canView(auth()->user());
    }

    public function mount(ReportingSynchronizer $synchronizer): void
    {
        abort_unless(static::canAccess(), 403);

        $this->from = request()->query('from', now()->subDays(30)->toDateString());
        $this->to = request()->query('to', now()->toDateString());
        $this->section = request()->query('section', 'overview');
        $this->dateBasis = request()->query('date_basis', 'created');
        $synchronizer->syncIfStale();
    }

    public function filters(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'date_basis' => $this->dateBasis,
            'products' => $this->products,
            'payment_status' => $this->paymentStatus,
            'fulfillment_status' => $this->fulfillmentStatus,
            'payment_method' => $this->paymentMethod,
        ];
    }

    public function dashboard(): array
    {
        return app(ReportingAnalytics::class)->dashboard($this->filters());
    }

    public function productOptions(): array
    {
        return collect(config('reporting.products'))->mapWithKeys(
            fn (array $product, string $key): array => [$key => $product['label']],
        )->all();
    }

    public function paymentMethodOptions(): array
    {
        return ReportingFact::query()->whereNotNull('payment_method')->distinct()->orderBy('payment_method')
            ->pluck('payment_method', 'payment_method')->map(fn (string $value) => str($value)->replace('_', ' ')->headline())->all();
    }

    public function savedViews(): Collection
    {
        return ReportingSavedView::query()
            ->where(fn ($query) => $query->where('user_id', auth()->id())->orWhere('is_shared', true))
            ->latest()->get();
    }

    public function saveCurrentView(): void
    {
        $this->validate(['savedViewName' => ['required', 'string', 'max:120']]);

        ReportingSavedView::create([
            'user_id' => auth()->id(),
            'name' => $this->savedViewName,
            'section' => $this->section,
            'filters' => $this->filters(),
        ]);

        $this->savedViewName = '';
        Notification::make()->title('Report view saved')->success()->send();
    }

    public function applySavedView(int $id): void
    {
        $view = ReportingSavedView::query()
            ->where(fn ($query) => $query->where('user_id', auth()->id())->orWhere('is_shared', true))
            ->findOrFail($id);
        $filters = $view->filters;

        $this->section = $view->section;
        $this->from = $filters['from'] ?? $this->from;
        $this->to = $filters['to'] ?? $this->to;
        $this->dateBasis = $filters['date_basis'] ?? 'created';
        $this->products = (array) ($filters['products'] ?? []);
        $this->paymentStatus = $filters['payment_status'] ?? '';
        $this->fulfillmentStatus = $filters['fulfillment_status'] ?? '';
        $this->paymentMethod = $filters['payment_method'] ?? '';
    }

    public function deleteSavedView(int $id): void
    {
        ReportingSavedView::query()->where('user_id', auth()->id())->findOrFail($id)->delete();
    }

    public function refreshReportingData(ReportingSynchronizer $synchronizer): void
    {
        $run = $synchronizer->sync();
        Notification::make()
            ->title('Reporting data refreshed')
            ->body(number_format($run->row_count).' product records synchronized.')
            ->success()->send();
    }

    public function acknowledgeAlert(int $id): void
    {
        ReportingAlert::query()->findOrFail($id)->update([
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        Notification::make()->title('Alert acknowledged')->success()->send();
    }

    public function canViewFinancials(): bool
    {
        return ReportingAccess::canViewFinancials(auth()->user());
    }

    public function canManageReporting(): bool
    {
        return ReportingAccess::canManage(auth()->user());
    }

    public function exportUrl(string $report = 'transactions', string $format = 'csv'): string
    {
        return route('admin.reports.export', array_merge([
            'report' => $report,
            'format' => $format,
        ], $this->filters()));
    }
}
