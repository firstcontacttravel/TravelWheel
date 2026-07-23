<?php

namespace App\Http\Controllers;

use App\Models\ReportingExportAudit;
use App\Services\Reporting\ReportingSynchronizer;
use App\Services\Reporting\UnifiedReportExporter;
use App\Support\Reporting\ReportingAccess;
use Illuminate\Http\Request;

class AdminReportExportController extends Controller
{
    public function __invoke(
        Request $request,
        string $report,
        ReportingSynchronizer $synchronizer,
        UnifiedReportExporter $exporter,
    ) {
        abort_unless(ReportingAccess::canViewFinancials($request->user()), 403);
        $validated = $request->validate([
            'format' => ['nullable', 'in:csv,xlsx'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'date_basis' => ['nullable', 'in:created,paid,service,completed'],
            'products' => ['nullable', 'array'],
            'products.*' => ['string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:40'],
            'fulfillment_status' => ['nullable', 'string', 'max:40'],
            'payment_method' => ['nullable', 'string', 'max:80'],
        ]);
        $format = $validated['format'] ?? 'csv';
        unset($validated['format']);

        $synchronizer->syncIfStale();
        $rowCount = $exporter->rowCount($report, $validated);

        ReportingExportAudit::create([
            'user_id' => $request->user()->id,
            'report_key' => $report,
            'format' => $format,
            'filters' => $validated,
            'row_count' => $rowCount,
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(1000),
            'exported_at' => now(),
        ]);

        return $exporter->download($report, $format, $validated);
    }
}
