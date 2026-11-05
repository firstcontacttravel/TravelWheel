<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyInvoice;
use App\Services\Corporate\CompanyStatements;
use App\Services\Corporate\CorporateDocuments;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Staff downloading a company's documents, limited to Finance and the CEO. */
class AdminDocumentController extends Controller
{
    public function invoice(CompanyInvoice $invoice, CorporateDocuments $documents): Response|RedirectResponse
    {
        return $this->refusal() ?? $documents->invoicePdf($invoice);
    }

    public function statement(Request $request, Company $company, CompanyStatements $statements, CorporateDocuments $documents): Response|RedirectResponse
    {
        return $this->refusal()
            ?? $documents->statementPdf($company, $statements->forMonth($company, $statements->month($request->query('month'))));
    }

    /** There's no plain login route on this site, so signing in happens on the panel. */
    private function refusal(): ?RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
        }

        abort_unless(auth()->user()->canHandleMoney(), 403);

        return null;
    }
}
