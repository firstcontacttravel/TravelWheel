<?php

namespace App\Filament\Pages;

use App\Support\Admin\AdminReportData;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Insights';

    protected static ?string $title = 'Reporting & Reconciliation';

    protected static ?int $navigationSort = 40;

    public string $from;

    public string $to;

    public function mount(): void
    {
        $this->from = request()->query('from', now()->subDays(30)->toDateString());
        $this->to = request()->query('to', now()->toDateString());
    }

    public function summary(): array
    {
        return AdminReportData::summary($this->from, $this->to);
    }

    public function byAirline(): Collection
    {
        return AdminReportData::breakdown('airline', $this->from, $this->to);
    }

    public function byRoute(): Collection
    {
        return AdminReportData::breakdown('route', $this->from, $this->to);
    }

    public function byFareType(): Collection
    {
        return AdminReportData::breakdown('fare_type', $this->from, $this->to);
    }

    public function byPaymentMethod(): Collection
    {
        return AdminReportData::breakdown('payment_method', $this->from, $this->to);
    }

    public function bankTransfers(): Collection
    {
        return AdminReportData::bankTransferRows($this->from, $this->to)->take(10);
    }

    public function ticketingFailures(): Collection
    {
        return AdminReportData::ticketingFailureRows($this->from, $this->to)->take(10);
    }

    public function postTicketing(): Collection
    {
        return AdminReportData::postTicketingRows($this->from, $this->to)->take(10);
    }

    public function travelFlex(): Collection
    {
        return AdminReportData::travelFlexRows($this->from, $this->to)->take(10);
    }

    public function exportUrl(string $report): string
    {
        return route('admin.reports.export', [
            'report' => $report,
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }
}
