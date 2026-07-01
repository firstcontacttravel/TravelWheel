<?php

namespace App\Console\Commands;

use App\Models\VisaFunnelEvent;
use Illuminate\Console\Command;

class VisaFunnelReport extends Command
{
    protected $signature = 'visa:funnel-report {--days= : Reporting window in days}';

    protected $description = 'Show visa discovery, application, quote, and payment funnel totals';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('visa.monitoring_window_days', 30)));
        $events = ['search_started', 'search_completed', 'application_started', 'quote_created', 'payment_started', 'payment_verified', 'application_submitted'];
        $counts = VisaFunnelEvent::query()->where('created_at', '>=', now()->subDays($days))->selectRaw('event, count(*) as aggregate')->groupBy('event')->pluck('aggregate', 'event');
        $searches = (int) ($counts['search_started'] ?? 0);
        $this->info("Visa funnel for the last {$days} day(s)");
        $this->table(['Stage', 'Events', 'Conversion from search'], collect($events)->map(fn ($event) => [str($event)->replace('_', ' ')->headline(), (int) ($counts[$event] ?? 0), $searches ? number_format(((int) ($counts[$event] ?? 0) / $searches) * 100, 1).'%' : '-'])->all());

        return self::SUCCESS;
    }
}
