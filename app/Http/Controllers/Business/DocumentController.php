<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\CompanyInvoice;
use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Services\Corporate\CompanyStatements;
use App\Services\Corporate\CorporateDocuments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A company's invoices, statements and exports. Admins get everything, and
 * Bookers get what relates to their own bookings. Statements are about the
 * wallet, so they're for Admins (see routes/business.php).
 */
class DocumentController extends Controller
{
    public function invoices(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('business.invoices', [
            'invoices' => $this->visibleInvoices($this->user($request))
                ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                    ->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('booking', fn (Builder $booking) => $booking
                        ->where('booking_ref', 'like', '%'.$search.'%')
                        ->orWhere('cost_code', 'like', '%'.$search.'%'))))
                ->latest('issued_at')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function downloadInvoice(Request $request, CorporateDocuments $documents, string $number): Response
    {
        $invoice = $this->visibleInvoices($this->user($request))->where('number', $number)->firstOrFail();

        return $documents->invoicePdf($invoice);
    }

    public function statement(Request $request, CompanyStatements $statements): View
    {
        $company = $this->user($request)->company;
        $month = $statements->month($request->query('month'));

        return view('business.statement', [
            'statement' => $statements->forMonth($company, $month),
            'months' => $statements->availableMonths($company),
        ]);
    }

    public function statementPdf(Request $request, CompanyStatements $statements, CorporateDocuments $documents): Response
    {
        $company = $this->user($request)->company;

        return $documents->statementPdf($company, $statements->forMonth($company, $statements->month($request->query('month'))));
    }

    public function statementCsv(Request $request, CompanyStatements $statements, CorporateDocuments $documents): StreamedResponse
    {
        $statement = $statements->forMonth($this->user($request)->company, $statements->month($request->query('month')));

        return $documents->csv(
            'wallet-'.$statement['month']->format('Y-m').'.csv',
            ['Date', 'Type', 'Details', 'Amount (NGN)', 'Balance (NGN)'],
            $statement['entries']->map(fn ($entry): array => [
                $entry->created_at->timezone('Africa/Lagos')->format('Y-m-d H:i'),
                $entry->typeLabel(),
                (string) $entry->reason,
                (float) $entry->amount,
                (float) $entry->balance_after,
            ]),
        );
    }

    public function bookingsCsv(Request $request, CorporateDocuments $documents): StreamedResponse
    {
        $user = $this->user($request);

        $bookings = FlightBooking::query()
            ->where('company_id', $user->company_id)
            ->when(! $user->isCompanyAdmin(), fn (Builder $query) => $query->where('company_user_id', $user->id))
            ->with('companyUser')
            ->latest('id')
            ->lazy();

        return $documents->csv(
            'bookings-'.now()->timezone('Africa/Lagos')->format('Y-m-d').'.csv',
            ['Date', 'Reference', 'Route', 'Airline', 'Cabin', 'Cost code', 'Booked by', 'Status', 'Paid by', 'Fare (NGN)', 'Service fee (NGN)', 'Total (NGN)'],
            $bookings->map(fn (FlightBooking $booking): array => [
                $booking->created_at->timezone('Africa/Lagos')->format('Y-m-d H:i'),
                (string) $booking->booking_ref,
                (string) $booking->route,
                (string) $booking->airline,
                (string) $booking->cabin,
                (string) $booking->cost_code,
                (string) $booking->companyUser?->name,
                BookingController::STATUSES[$booking->booking_status] ?? 'In progress',
                ['wallet' => 'Wallet', 'gateway' => 'Card', 'bank_transfer' => 'Bank transfer'][$booking->payment_method] ?? '',
                (float) $booking->total_price - (float) $booking->markup_amount,
                (float) $booking->markup_amount,
                (float) $booking->total_price,
            ]),
        );
    }

    private function visibleInvoices(CompanyUser $user): Builder
    {
        return CompanyInvoice::query()
            ->where('company_id', $user->company_id)
            ->when(! $user->isCompanyAdmin(), fn (Builder $query) => $query
                ->whereHas('booking', fn (Builder $booking) => $booking->where('company_user_id', $user->id)));
    }

    private function user(Request $request): CompanyUser
    {
        return $request->attributes->get('companyUser');
    }
}
