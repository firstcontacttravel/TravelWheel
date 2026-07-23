<?php

namespace App\Services\Reporting;

use App\Models\ReportingAlert;
use App\Models\ReportingFact;
use App\Models\ReportingTarget;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportingAnalytics
{
    public function dashboard(array $filters): array
    {
        [$start, $end] = $this->range($filters);
        $facts = $this->query($filters)->get();
        $duration = max(1, $start->diffInDays($end) + 1);
        $previousFilters = array_merge($filters, [
            'from' => $start->subDays($duration)->toDateString(),
            'to' => $start->subDay()->toDateString(),
        ]);
        $previous = $this->query($previousFilters)->get();

        return [
            'range' => [$start, $end],
            'summary' => $this->summary($facts, $previous),
            'trend' => $this->trend($facts, $start, $end, $filters['date_basis'] ?? 'created'),
            'products' => $this->products($facts),
            'finance' => $this->finance($facts),
            'operations' => $this->operations($facts),
            'customers' => $this->customers($facts),
            'risk' => $this->risk($facts),
            'targets' => $this->targets($facts, $start, $end),
            'forecast' => $this->forecast($facts, $start, $end),
            'funnels' => $this->funnels($facts),
            'generated_at' => now(),
        ];
    }

    public function query(array $filters): Builder
    {
        [$start, $end] = $this->range($filters);
        $dateColumn = match ($filters['date_basis'] ?? 'created') {
            'paid' => 'paid_at',
            'service' => 'service_at',
            'completed' => 'completed_at',
            default => 'created_at_source',
        };

        return ReportingFact::query()
            ->whereBetween($dateColumn, [$start, $end])
            ->when(
                filled($filters['products'] ?? []),
                fn (Builder $query) => $query->whereIn('product', (array) $filters['products']),
            )
            ->when(
                filled($filters['payment_status'] ?? null),
                fn (Builder $query) => $query->where('payment_status', $filters['payment_status']),
            )
            ->when(
                filled($filters['fulfillment_status'] ?? null),
                fn (Builder $query) => $query->where('fulfillment_status', $filters['fulfillment_status']),
            )
            ->when(
                filled($filters['payment_method'] ?? null),
                fn (Builder $query) => $query->where('payment_method', $filters['payment_method']),
            );
    }

    public function range(array $filters): array
    {
        try {
            $start = CarbonImmutable::parse($filters['from'] ?? now()->subDays(30))->startOfDay();
        } catch (\Throwable) {
            $start = now()->subDays(30)->toImmutable()->startOfDay();
        }

        try {
            $end = CarbonImmutable::parse($filters['to'] ?? now())->endOfDay();
        } catch (\Throwable) {
            $end = now()->toImmutable()->endOfDay();
        }

        return $start->greaterThan($end)
            ? [$end->startOfDay(), $start->endOfDay()]
            : [$start, $end];
    }

    private function summary(Collection $facts, Collection $previous): array
    {
        $current = $this->summaryValues($facts);
        $before = $this->summaryValues($previous);

        $current['comparison'] = collect($current)
            ->only(['gross_value', 'verified_collections', 'travelwheel_revenue', 'transactions', 'paid_transactions'])
            ->mapWithKeys(function (mixed $value, string $key) use ($before): array {
                $old = (float) ($before[$key] ?? 0);

                return [$key => $old == 0 ? null : round((((float) $value - $old) / abs($old)) * 100, 1)];
            })
            ->all();

        return $current;
    }

    private function summaryValues(Collection $facts): array
    {
        $financial = $facts->where('financially_additive', true);
        $transactions = $facts->count();
        $paid = $facts->where('payment_status', 'paid')->count();
        $knownProfit = $financial->whereNotNull('gross_profit');

        return [
            'transactions' => $transactions,
            'paid_transactions' => $paid,
            'gross_value' => (float) $financial->sum('gross_value'),
            'verified_collections' => (float) $financial->sum('verified_collections'),
            'travelwheel_revenue' => (float) $financial->sum('travelwheel_revenue'),
            'supplier_cost' => (float) $financial->whereNotNull('supplier_cost')->sum('supplier_cost'),
            'gross_profit' => (float) $knownProfit->sum('gross_profit'),
            'profit_coverage' => $financial->count() === 0 ? 0 : round($knownProfit->count() / $financial->count() * 100, 1),
            'aov' => $financial->count() === 0 ? 0 : (float) $financial->avg('gross_value'),
            'payment_conversion' => $transactions === 0 ? 0 : round($paid / $transactions * 100, 1),
            'fulfillment_rate' => $transactions === 0 ? 0 : round($facts->where('fulfillment_status', 'completed')->count() / $transactions * 100, 1),
            'customers' => $facts->pluck('customer_hash')->filter()->unique()->count(),
            'open_exceptions' => $facts->filter(fn (ReportingFact $fact): bool => $this->isException($fact))->count(),
        ];
    }

    private function trend(Collection $facts, CarbonImmutable $start, CarbonImmutable $end, string $basis): array
    {
        $attribute = match ($basis) {
            'paid' => 'paid_at',
            'service' => 'service_at',
            'completed' => 'completed_at',
            default => 'created_at_source',
        };
        $grouped = $facts->groupBy(fn (ReportingFact $fact): string => $fact->{$attribute}->toDateString());
        $points = [];

        for ($day = $start->startOfDay(); $day->lte($end); $day = $day->addDay()) {
            $rows = $grouped->get($day->toDateString(), collect());
            $financial = $rows->where('financially_additive', true);
            $points[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('d M'),
                'transactions' => $rows->count(),
                'collections' => (float) $financial->sum('verified_collections'),
                'revenue' => (float) $financial->sum('travelwheel_revenue'),
            ];
        }

        $max = max(1, (float) collect($points)->max('collections'));

        return collect($points)->map(function (array $point) use ($max): array {
            $point['height'] = max(2, round($point['collections'] / $max * 100, 1));

            return $point;
        })->all();
    }

    private function products(Collection $facts): array
    {
        $totalCollections = max(1, (float) $facts->where('financially_additive', true)->sum('verified_collections'));

        return $facts->groupBy('product')->map(function (Collection $rows, string $product) use ($totalCollections): array {
            // TravelFlex is excluded only from company-wide totals because it overlays a flight sale.
            // Its own scorecard must still show financed value, deposits, and interest.
            $financial = $product === 'travel_flex' ? $rows : $rows->where('financially_additive', true);
            $collections = (float) $financial->sum('verified_collections');
            $paid = $rows->where('payment_status', 'paid')->count();

            return [
                'product' => $product,
                'label' => config("reporting.products.{$product}.label", str($product)->headline()->toString()),
                'color' => config("reporting.products.{$product}.color", '#64748b'),
                'transactions' => $rows->count(),
                'gross_value' => (float) $financial->sum('gross_value'),
                'collections' => $collections,
                'revenue' => (float) $financial->sum('travelwheel_revenue'),
                'share' => round($collections / $totalCollections * 100, 1),
                'conversion' => $rows->count() === 0 ? 0 : round($paid / $rows->count() * 100, 1),
                'completion' => $rows->count() === 0 ? 0 : round($rows->where('fulfillment_status', 'completed')->count() / $rows->count() * 100, 1),
                'aov' => $financial->count() === 0 ? 0 : (float) $financial->avg('gross_value'),
            ];
        })->sortByDesc('collections')->values()->all();
    }

    private function finance(Collection $facts): array
    {
        $financial = $facts->where('financially_additive', true);

        return [
            'by_payment_method' => $this->financialBreakdown($financial, 'payment_method'),
            'by_gateway' => $this->financialBreakdown($financial, 'payment_gateway'),
            'by_provider' => $this->financialBreakdown($financial, 'provider'),
            'uncollected' => $financial->whereNotIn('payment_status', ['paid', 'refunded'])
                ->sortByDesc('gross_value')->take(15)->values(),
            'refunds' => $financial->where('payment_status', 'refunded')->values(),
            'collection_gap' => max(0, (float) $financial->sum('gross_value') - (float) $financial->sum('verified_collections')),
        ];
    }

    private function financialBreakdown(Collection $facts, string $field): array
    {
        return $facts->groupBy(fn (ReportingFact $fact): string => $fact->{$field} ?: 'Unspecified')
            ->map(fn (Collection $rows, string $label): array => [
                'label' => str($label)->replace('_', ' ')->headline()->toString(),
                'transactions' => $rows->count(),
                'gross_value' => (float) $rows->sum('gross_value'),
                'collections' => (float) $rows->sum('verified_collections'),
                'revenue' => (float) $rows->sum('travelwheel_revenue'),
            ])
            ->sortByDesc('collections')->values()->all();
    }

    private function operations(Collection $facts): array
    {
        $open = $facts->whereIn('fulfillment_status', ['pending', 'in_progress', 'unknown']);
        $aging = ['0-24 hours' => 0, '1-3 days' => 0, '4-7 days' => 0, '8+ days' => 0];

        foreach ($open as $fact) {
            $hours = $fact->created_at_source->diffInHours(now());
            $bucket = match (true) {
                $hours <= 24 => '0-24 hours',
                $hours <= 72 => '1-3 days',
                $hours <= 168 => '4-7 days',
                default => '8+ days',
            };
            $aging[$bucket]++;
        }

        return [
            'status' => $facts->groupBy('fulfillment_status')->map->count()->sortDesc()->all(),
            'aging' => $aging,
            'backlog' => $open->sortBy('created_at_source')->take(20)->values(),
            'paid_not_completed' => $facts->where('payment_status', 'paid')
                ->whereNotIn('fulfillment_status', ['completed'])->sortBy('created_at_source')->take(20)->values(),
            'avg_completion_hours' => round((float) $facts->filter(fn (ReportingFact $fact): bool => $fact->completed_at !== null)
                ->avg(fn (ReportingFact $fact): float => abs($fact->created_at_source->diffInHours($fact->completed_at))), 1),
        ];
    }

    private function customers(Collection $facts): array
    {
        $known = $facts->filter(fn (ReportingFact $fact): bool => filled($fact->customer_hash));
        $groups = $known->groupBy('customer_hash');
        $repeat = $groups->filter(fn (Collection $rows): bool => $rows->count() > 1);
        $crossProduct = $groups->filter(fn (Collection $rows): bool => $rows->pluck('product')->unique()->count() > 1);

        return [
            'unique' => $groups->count(),
            'repeat' => $repeat->count(),
            'repeat_rate' => $groups->count() === 0 ? 0 : round($repeat->count() / $groups->count() * 100, 1),
            'cross_product' => $crossProduct->count(),
            'cross_product_rate' => $groups->count() === 0 ? 0 : round($crossProduct->count() / $groups->count() * 100, 1),
            'top' => $groups->map(fn (Collection $rows, string $hash): array => [
                'customer' => 'Customer '.strtoupper(substr($hash, 0, 6)),
                'transactions' => $rows->count(),
                'products' => $rows->pluck('product')->unique()->map(fn (string $product) => config("reporting.products.{$product}.label", str($product)->headline()))->values()->all(),
                'value' => (float) $rows->where('financially_additive', true)->sum('verified_collections'),
            ])->sortByDesc('value')->take(15)->values()->all(),
            'unknown_identity' => $facts->whereNull('customer_hash')->count(),
        ];
    }

    private function risk(Collection $facts): array
    {
        $quality = collect();

        foreach ($facts as $fact) {
            foreach ((array) $fact->data_quality as $issue) {
                $quality->push(['issue' => $issue, 'fact' => $fact]);
            }
        }

        $duplicateReferences = $facts->filter(fn (ReportingFact $fact): bool => filled($fact->reference))
            ->groupBy(fn (ReportingFact $fact): string => "{$fact->product}|{$fact->reference}")
            ->filter(fn (Collection $rows): bool => $rows->count() > 1);

        return [
            'quality_score' => $facts->count() === 0 ? 100 : max(0, round(100 - ($quality->count() / $facts->count() * 20), 1)),
            'issue_counts' => $quality->groupBy('issue')->map->count()->sortDesc()->all(),
            'exceptions' => $facts->filter(fn (ReportingFact $fact): bool => $this->isException($fact))
                ->sortByDesc('verified_collections')->take(25)->values(),
            'duplicate_references' => $duplicateReferences->count(),
            'missing_cost_records' => $facts->where('financially_additive', true)->whereNull('supplier_cost')->count(),
            'alerts' => ReportingAlert::query()->whereNull('resolved_at')->latest('detected_at')->limit(20)->get(),
        ];
    }

    private function targets(Collection $facts, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return ReportingTarget::query()
            ->whereDate('period_start', '<=', $end)
            ->whereDate('period_end', '>=', $start)
            ->get()
            ->map(function (ReportingTarget $target) use ($facts): array {
                $rows = $target->product ? $facts->where('product', $target->product) : $facts;
                $metricRows = $target->product === 'travel_flex'
                    ? $rows
                    : $rows->where('financially_additive', true);
                $actual = match ($target->metric) {
                    'transactions', 'orders' => $rows->count(),
                    'paid_transactions' => $rows->where('payment_status', 'paid')->count(),
                    default => (float) $metricRows->sum($target->metric),
                };
                $targetValue = (float) $target->target_value;

                return [
                    'label' => $target->label,
                    'metric' => $target->metric,
                    'product' => $target->product,
                    'actual' => $actual,
                    'target' => $targetValue,
                    'attainment' => $targetValue == 0 ? 0 : round($actual / $targetValue * 100, 1),
                ];
            })->all();
    }

    private function forecast(Collection $facts, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = max(1, $start->diffInDays(min($end, now()->toImmutable())) + 1);
        $financial = $facts->where('financially_additive', true);
        $dailyCollections = (float) $financial->sum('verified_collections') / $days;
        $dailyRevenue = (float) $financial->sum('travelwheel_revenue') / $days;

        return [
            'next_30_day_collections' => round($dailyCollections * 30, 2),
            'next_30_day_revenue' => round($dailyRevenue * 30, 2),
            'daily_collection_run_rate' => round($dailyCollections, 2),
            'method' => 'Straight-line run rate from the selected period',
        ];
    }

    private function funnels(Collection $facts): array
    {
        return $facts->groupBy('product')->map(function (Collection $rows, string $product): array {
            return [
                'product' => config("reporting.products.{$product}.label", str($product)->headline()->toString()),
                'created' => $rows->count(),
                'paid' => $rows->where('payment_status', 'paid')->count(),
                'in_progress' => $rows->where('fulfillment_status', 'in_progress')->count(),
                'completed' => $rows->where('fulfillment_status', 'completed')->count(),
                'failed' => $rows->filter(fn (ReportingFact $fact): bool => $fact->payment_status === 'failed' || $fact->fulfillment_status === 'failed')->count(),
            ];
        })->values()->all();
    }

    private function isException(ReportingFact $fact): bool
    {
        return ($fact->payment_status === 'paid' && $fact->fulfillment_status !== 'completed')
            || ($fact->fulfillment_status === 'completed' && $fact->payment_status !== 'paid')
            || count((array) $fact->data_quality) > 0;
    }
}
