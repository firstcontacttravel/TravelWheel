<?php

namespace App\Services\Corporate;

use App\Models\AppSetting;
use App\Models\CompanyInvoice;
use App\Models\CompanyWalletEntry;
use App\Models\FlightBooking;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Invoices and credit notes for a company's flight bookings.
 *
 * An invoice is issued once a booking is both paid and confirmed, so a booking
 * the airline refused never gets one. VAT, when switched on, is treated as
 * included in the service fee, so the invoice total always equals what was paid.
 * The timing and VAT treatment are for the accountant to confirm.
 */
class CompanyInvoices
{
    public const VAT_SETTING = 'corporate.vat_percent';

    private const DEADLOCK_ATTEMPTS = 5;

    /** Null means VAT isn't shown on documents. */
    public function vatPercent(): ?float
    {
        $percent = AppSetting::get(self::VAT_SETTING);

        return is_numeric($percent) && (float) $percent > 0 ? (float) $percent : null;
    }

    public function canInvoice(FlightBooking $booking): bool
    {
        return $booking->company_id !== null
            && $booking->payment_status === 'paid'
            && (in_array($booking->booking_status, ['confirmed', 'ticketed'], true) || $booking->ticket_ordered);
    }

    /** Issues the invoice if it's due. Safe to call any number of times. */
    public function issueForBooking(FlightBooking $booking): ?CompanyInvoice
    {
        if (! $this->canInvoice($booking)) {
            return null;
        }

        $key = 'invoice:booking:'.$booking->id;

        $lines = $this->lines($booking);
        $total = round(array_sum(array_column($lines, 'amount')), 2);
        $vatPercent = $this->vatPercent();

        return $this->create($key, 'invoice', [
            'company_id' => $booking->company_id,
            'flight_booking_id' => $booking->id,
            'total' => $total,
            'vat_percent' => $vatPercent,
            'vat_amount' => $this->vatIncluded((float) $booking->markup_amount, $vatPercent),
            'lines' => $lines,
            'company_details' => $this->companyDetails($booking),
            'booking_details' => $this->bookingDetails($booking),
            'issued_at' => now(),
        ]);
    }

    /**
     * A credit note for money sent back to the wallet. With no invoice there's
     * nothing to credit: the booking was never issued.
     */
    public function creditNoteFor(FlightBooking $booking, CompanyWalletEntry $entry, string $reason): ?CompanyInvoice
    {
        $invoice = CompanyInvoice::query()
            ->where('flight_booking_id', $booking->id)
            ->where('type', 'invoice')
            ->first();

        if (! $invoice) {
            return null;
        }

        $amount = round(abs((float) $entry->amount), 2);
        $share = (float) $invoice->total > 0 ? $amount / (float) $invoice->total : 0;

        return $this->create('credit:entry:'.$entry->id, 'credit_note', [
            'company_id' => $invoice->company_id,
            'flight_booking_id' => $booking->id,
            'credited_invoice_id' => $invoice->id,
            'company_wallet_entry_id' => $entry->id,
            'total' => $amount,
            'vat_percent' => $invoice->vat_percent,
            'vat_amount' => round((float) $invoice->vat_amount * $share, 2),
            'lines' => [['label' => 'Credit against invoice '.$invoice->number, 'amount' => $amount]],
            'company_details' => $invoice->company_details,
            'booking_details' => $invoice->booking_details,
            'reason' => mb_substr($reason, 0, 500),
            'issued_at' => now(),
        ]);
    }

    /** Fare, service fee and extras, plus any gateway charge, adding up to what was paid. */
    private function lines(FlightBooking $booking): array
    {
        $payable = round((float) ($booking->payment_amount ?: $booking->total_price), 2);
        $fee = round((float) $booking->markup_amount, 2);
        $fare = round((float) data_get($booking->flight_snapshot, 'price', $booking->total_price), 2);
        $extras = round(max(0, $payable - $fare), 2);
        $supplierFare = round($payable - $extras - $fee, 2);
        $gatewayCharge = round(max(0, (float) $booking->payment_charged_amount - $payable), 2);

        $lines = [['label' => 'Airline fare and taxes', 'amount' => $supplierFare]];
        $lines[] = ['label' => 'TravelWheel service fee', 'amount' => $fee];

        if ($extras > 0) {
            $lines[] = ['label' => 'Extra services', 'amount' => $extras];
        }

        if ($gatewayCharge > 0) {
            $lines[] = ['label' => 'Card processing charge', 'amount' => $gatewayCharge];
        }

        return $lines;
    }

    private function vatIncluded(float $amount, ?float $percent): float
    {
        return $percent === null ? 0.0 : round($amount - $amount / (1 + $percent / 100), 2);
    }

    private function companyDetails(FlightBooking $booking): array
    {
        $company = $booking->company;

        return [
            'name' => $company->name,
            'cac_number' => $company->cac_number,
            'contact_name' => $company->contact_name,
            'contact_email' => $company->contact_email,
        ];
    }

    /** Names only from the passengers: no passports or dates of birth on a document for accounts. */
    private function bookingDetails(FlightBooking $booking): array
    {
        return [
            'reference' => $booking->booking_ref,
            'route' => $booking->route,
            'airline' => $booking->airline,
            'cabin' => $booking->cabin,
            'cost_code' => $booking->cost_code,
            'booked_by' => $booking->companyUser?->name,
            'payment_method' => $booking->payment_method,
            'passengers' => collect((array) $booking->passengers_snapshot)
                ->map(fn (array $passenger): string => trim(($passenger['title'] ?? '').' '.($passenger['first_name'] ?? '').' '.($passenger['last_name'] ?? '')))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function create(string $key, string $type, array $attributes): CompanyInvoice
    {
        $existing = CompanyInvoice::query()->where('idempotency_key', $key)->first();

        if ($existing) {
            return $existing;
        }

        // Created before the lock is taken: on MySQL, several requests inserting
        // the year's first row while locking it deadlock each other.
        $sequence = $type.':'.now()->timezone('Africa/Lagos')->year;
        DB::table('company_invoice_sequences')->insertOrIgnore(['name' => $sequence, 'last_number' => 0]);

        try {
            return DB::transaction(fn (): CompanyInvoice => CompanyInvoice::query()->create(array_merge($attributes, [
                'type' => $type,
                'idempotency_key' => $key,
                'number' => $this->nextNumber($type, $sequence),
            ])), self::DEADLOCK_ATTEMPTS);
        } catch (UniqueConstraintViolationException) {
            return CompanyInvoice::query()->where('idempotency_key', $key)->firstOrFail();
        }
    }

    /** The row lock is held until the document is saved, so numbers can't repeat. */
    private function nextNumber(string $type, string $sequence): string
    {
        $last = (int) DB::table('company_invoice_sequences')->where('name', $sequence)->lockForUpdate()->value('last_number');
        $next = $last + 1;

        DB::table('company_invoice_sequences')->where('name', $sequence)->update(['last_number' => $next]);

        return ($type === 'invoice' ? 'TWINV-' : 'TWCN-').explode(':', $sequence)[1].'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
