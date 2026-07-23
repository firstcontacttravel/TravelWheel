<?php

namespace App\Services\Reporting;

use App\Models\ReportingFact;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnifiedReportExporter
{
    public function __construct(private readonly ReportingAnalytics $analytics) {}

    public function download(string $report, string $format, array $filters): StreamedResponse|BinaryFileResponse
    {
        [$headers, $rows] = $this->dataset($report, $filters);
        $filename = "travelwheel-{$report}-".now()->format('Ymd-His').".{$format}";

        if ($format === 'xlsx') {
            $path = $this->temporaryPath('travelwheel-report-', 'xlsx');
            $writer = new Writer();
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues($headers));
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();

            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function contents(string $report, string $format, array $filters): string
    {
        [$headers, $rows] = $this->dataset($report, $filters);
        $path = $this->temporaryPath('travelwheel-scheduled-', $format);

        if ($format === 'xlsx') {
            $writer = new Writer();
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues($headers));
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();
        } else {
            $handle = fopen($path, 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }

        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }

    public function rowCount(string $report, array $filters): int
    {
        return $this->rows($report, $filters)->count();
    }

    /** @return array{array<int, string>, Collection<int, array<int, mixed>>} */
    private function dataset(string $report, array $filters): array
    {
        $headers = [
            'Product', 'Sub Product', 'Reference', 'Created At', 'Paid At', 'Service At',
            'Completed At', 'Currency', 'Gross Value', 'Verified Collections',
            'TravelWheel Revenue', 'Supplier Cost', 'Gross Profit', 'Payment Status',
            'Fulfilment Status', 'Payment Method', 'Gateway', 'Provider', 'Quantity',
            'Data Quality Issues',
        ];

        $rows = $this->rows($report, $filters)->map(fn (ReportingFact $fact): array => [
            config("reporting.products.{$fact->product}.label", str($fact->product)->headline()->toString()),
            $fact->sub_product,
            $fact->reference,
            $this->date($fact->created_at_source),
            $this->date($fact->paid_at),
            $this->date($fact->service_at),
            $this->date($fact->completed_at),
            $fact->currency,
            $fact->gross_value,
            $fact->verified_collections,
            $fact->travelwheel_revenue,
            $fact->supplier_cost,
            $fact->gross_profit,
            $fact->payment_status,
            $fact->fulfillment_status,
            $fact->payment_method,
            $fact->payment_gateway,
            $fact->provider,
            $fact->quantity,
            collect($fact->data_quality)->implode('; '),
        ]);

        return [$headers, $rows];
    }

    private function rows(string $report, array $filters): Collection
    {
        abort_unless(in_array($report, ['transactions', 'reconciliation', 'operations', 'exceptions'], true), 404);

        $rows = $this->analytics->query($filters)->orderByDesc('created_at_source')->get();

        return match ($report) {
            'reconciliation' => $rows->whereNotIn('payment_status', ['paid', 'refunded'])->values(),
            'operations' => $rows->whereIn('fulfillment_status', ['pending', 'in_progress', 'unknown'])->values(),
            'exceptions' => $rows->filter(fn (ReportingFact $fact): bool => count((array) $fact->data_quality) > 0
                || ($fact->payment_status === 'paid' && $fact->fulfillment_status !== 'completed')
                || ($fact->fulfillment_status === 'completed' && $fact->payment_status !== 'paid'))->values(),
            default => $rows,
        };
    }

    private function date(mixed $value): string
    {
        return $value?->timezone('Africa/Lagos')->format('Y-m-d H:i:s') ?? '';
    }

    private function temporaryPath(string $prefix, string $extension): string
    {
        $base = tempnam(sys_get_temp_dir(), $prefix);
        $path = $base.'.'.$extension;
        @unlink($base);

        return $path;
    }
}
