<?php

namespace App\Http\Controllers;

use App\Support\Admin\AdminReportData;
use Illuminate\Http\Request;

class AdminReportExportController extends Controller
{
    public function __invoke(Request $request, string $report)
    {
        $user = $request->user();
        $adminEmails = collect(explode(',', (string) env('ADMIN_EMAILS', '')))
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter();

        abort_unless($user && ($user->is_admin || $adminEmails->contains(strtolower($user->email))), 403);

        return AdminReportData::csv(
            $report,
            $request->query('from'),
            $request->query('to'),
        );
    }
}
