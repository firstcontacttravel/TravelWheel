<?php

namespace App\Services\Reporting;

use App\Models\ReportingAlert;
use App\Models\ReportingFact;
use App\Models\ReportingSyncRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ReportingSynchronizer
{
    public function __construct(private readonly ReportingAdapterRegistry $registry) {}

    public function syncIfStale(): void
    {
        if (! Schema::hasTable('reporting_facts')) {
            return;
        }

        $last = ReportingSyncRun::query()->where('status', 'completed')->latest('completed_at')->value('completed_at');

        if (! $last || now()->diffInMinutes($last) >= (int) config('reporting.fresh_for_minutes', 5)) {
            Cache::lock('reporting-fact-sync', 300)->get(fn () => $this->sync());
        }
    }

    public function sync(): ReportingSyncRun
    {
        $run = ReportingSyncRun::create(['status' => 'running', 'started_at' => now()]);
        $counts = [];
        $errors = [];
        $total = 0;

        foreach ($this->registry->adapters() as $adapter) {
            if (! $adapter->available()) {
                continue;
            }

            try {
                $ids = [];
                $count = 0;

                foreach ($adapter->facts() as $fact) {
                    $ids[] = $fact['source_id'];
                    ReportingFact::updateOrCreate(
                        ['source_type' => $fact['source_type'], 'source_id' => $fact['source_id']],
                        $fact,
                    );
                    $count++;
                }

                $query = ReportingFact::query()->where('source_type', $adapter->sourceType());
                if ($ids === []) {
                    $query->delete();
                } else {
                    $query->whereNotIn('source_id', $ids)->delete();
                }

                $counts[$adapter->sourceType()] = $count;
                $total += $count;
            } catch (\Throwable $exception) {
                report($exception);
                $errors[$adapter->sourceType()] = $exception->getMessage();
            }
        }

        $run->update([
            'status' => $errors === [] ? 'completed' : 'partial',
            'row_count' => $total,
            'product_counts' => $counts,
            'errors' => $errors ?: null,
            'completed_at' => now(),
        ]);

        $this->refreshAlerts();

        return $run->refresh();
    }

    private function refreshAlerts(): void
    {
        $openFingerprints = [];

        ReportingFact::query()
            ->where(function ($query): void {
                $query->where(fn ($q) => $q->where('payment_status', 'paid')->where('fulfillment_status', '!=', 'completed'))
                    ->orWhere(fn ($q) => $q->where('fulfillment_status', 'completed')->where('payment_status', '!=', 'paid'));
            })
            ->each(function (ReportingFact $fact) use (&$openFingerprints): void {
                $type = $fact->payment_status === 'paid' ? 'paid_not_fulfilled' : 'fulfilled_not_paid';
                $fingerprint = hash('sha256', "{$type}|{$fact->source_type}|{$fact->source_id}");
                $openFingerprints[] = $fingerprint;

                ReportingAlert::updateOrCreate(
                    ['fingerprint' => $fingerprint],
                    [
                        'type' => $type,
                        'severity' => $fact->payment_status === 'paid' ? 'critical' : 'warning',
                        'product' => $fact->product,
                        'metric' => 'transaction_state',
                        'observed_value' => $fact->verified_collections,
                        'message' => ucfirst(str_replace('_', ' ', $type))." for {$fact->reference}.",
                        'detected_at' => now(),
                        'resolved_at' => null,
                    ],
                );
            });

        $products = ReportingFact::query()->where('financially_additive', true)->distinct()->pluck('product');
        foreach ($products as $product) {
            $history = ReportingFact::query()
                ->where('product', $product)
                ->where('financially_additive', true)
                ->where('payment_status', 'paid')
                ->whereBetween('paid_at', [now()->subDays(8), now()->subDay()])
                ->get();

            if ($history->count() < 3) {
                continue;
            }

            $expected = (float) $history->sum('verified_collections') / 7;
            $observed = (float) ReportingFact::query()
                ->where('product', $product)
                ->where('financially_additive', true)
                ->where('payment_status', 'paid')
                ->where('paid_at', '>=', now()->subDay())
                ->sum('verified_collections');

            if ($expected < 1000 || ($observed >= $expected * .4 && $observed <= $expected * 1.6)) {
                continue;
            }

            $direction = $observed < $expected ? 'drop' : 'spike';
            $type = "collection_{$direction}";
            $fingerprint = hash('sha256', "{$type}|{$product}|".now()->toDateString());
            $openFingerprints[] = $fingerprint;

            ReportingAlert::updateOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'type' => $type,
                    'severity' => $direction === 'drop' ? 'critical' : 'warning',
                    'product' => $product,
                    'metric' => 'verified_collections',
                    'observed_value' => $observed,
                    'expected_value' => $expected,
                    'message' => ucfirst($product)." collections {$direction}: observed ".number_format($observed)." versus a ".number_format($expected).' daily baseline.',
                    'detected_at' => now(),
                    'resolved_at' => null,
                ],
            );
        }

        ReportingAlert::query()
            ->whereNull('resolved_at')
            ->when($openFingerprints !== [], fn ($query) => $query->whereNotIn('fingerprint', $openFingerprints))
            ->when($openFingerprints === [], fn ($query) => $query)
            ->update(['resolved_at' => now()]);
    }
}
