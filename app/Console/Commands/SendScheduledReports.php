<?php

namespace App\Console\Commands;

use App\Models\ReportingExportAudit;
use App\Models\ReportingSchedule;
use App\Services\Reporting\ReportingSynchronizer;
use App\Services\Reporting\UnifiedReportExporter;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';
    protected $description = 'Email due scheduled TravelWheel reports';

    public function handle(ReportingSynchronizer $synchronizer, UnifiedReportExporter $exporter): int
    {
        $synchronizer->syncIfStale();
        $sent = 0;

        ReportingSchedule::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('next_send_at')->orWhere('next_send_at', '<=', now()))
            ->each(function (ReportingSchedule $schedule) use ($exporter, &$sent): void {
                $filters = $this->filters($schedule);
                $format = in_array($schedule->format, ['csv', 'xlsx'], true) ? $schedule->format : 'csv';
                $contents = $exporter->contents($schedule->report_key, $format, $filters);
                $filename = 'travelwheel-'.$schedule->report_key.'-'.now()->format('Ymd').'.'.$format;
                $mime = $format === 'xlsx'
                    ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    : 'text/csv';

                Mail::raw(
                    "Attached is the scheduled TravelWheel {$schedule->name} report.\n\nReporting period: {$filters['from']} to {$filters['to']}.",
                    function (Message $message) use ($schedule, $contents, $filename, $mime): void {
                        $message->to($schedule->recipients)
                            ->subject('TravelWheel report: '.$schedule->name)
                            ->attachData($contents, $filename, ['mime' => $mime]);
                    },
                );

                $rowCount = $exporter->rowCount($schedule->report_key, $filters);
                ReportingExportAudit::create([
                    'user_id' => $schedule->user_id,
                    'report_key' => $schedule->report_key,
                    'format' => $format,
                    'filters' => $filters,
                    'row_count' => $rowCount,
                    'user_agent' => 'scheduled-report',
                    'exported_at' => now(),
                ]);

                $schedule->update([
                    'last_sent_at' => now(),
                    'next_send_at' => match ($schedule->frequency) {
                        'daily' => now()->addDay(),
                        'monthly' => now()->addMonthNoOverflow(),
                        default => now()->addWeek(),
                    },
                ]);
                $sent++;
            });

        $this->info("Scheduled reports sent: {$sent}");

        return self::SUCCESS;
    }

    private function filters(ReportingSchedule $schedule): array
    {
        $filters = (array) $schedule->filters;
        $filters['date_basis'] ??= 'created';
        if (is_string($filters['products'] ?? null)) {
            $filters['products'] = array_values(array_filter(array_map('trim', explode(',', $filters['products']))));
        }

        if (blank($filters['from'] ?? null) || blank($filters['to'] ?? null)) {
            $filters['to'] = now()->subDay()->toDateString();
            $filters['from'] = match ($schedule->frequency) {
                'monthly' => now()->subMonthNoOverflow()->startOfDay()->toDateString(),
                'weekly' => now()->subWeek()->startOfDay()->toDateString(),
                default => now()->subDay()->startOfDay()->toDateString(),
            };
        }

        return $filters;
    }
}
