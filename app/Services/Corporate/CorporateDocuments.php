<?php

namespace App\Services\Corporate;

use App\Models\Company;
use App\Models\CompanyInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The files companies download: invoice and statement PDFs, and CSV exports. */
class CorporateDocuments
{
    public function invoicePdf(CompanyInvoice $invoice): Response
    {
        return $this->pdf('pdf.corporate-invoice', ['invoice' => $invoice], $invoice->number.'.pdf');
    }

    public function statementPdf(Company $company, array $statement): Response
    {
        return $this->pdf(
            'pdf.corporate-statement',
            ['company' => $company, 'statement' => $statement],
            'statement-'.$statement['month']->format('Y-m').'.pdf',
        );
    }

    /**
     * @param  iterable<array<int, mixed>>  $rows
     */
    public function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // So Excel reads naira signs and names properly.
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn (mixed $cell): mixed => $this->safeCell($cell), $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    /**
     * Text someone typed, like a cost code, could otherwise run as a formula
     * when the file is opened in a spreadsheet. Real numbers are left alone.
     */
    private function safeCell(mixed $cell): mixed
    {
        if (is_int($cell) || is_float($cell) || $cell === null) {
            return $cell;
        }

        $text = (string) $cell;

        return preg_match('/^[=+\-@\t\r]/', $text) === 1 ? "'".$text : $text;
    }

    private function pdf(string $view, array $data, string $filename): Response
    {
        $bytes = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'enable_php' => false,
                'enable_javascript' => false,
            ])
            ->output();

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
