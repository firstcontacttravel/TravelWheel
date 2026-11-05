<?php

namespace App\Services\Corporate;

use App\Models\ActivityLog;
use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Money moving between a company's wallet and its flight bookings.
 *
 * The booking and the ledger change together or not at all, and each booking
 * can be debited once. Money goes back automatically only when the supplier
 * clearly refused. After a timeout or an unclear answer the seat may exist, so
 * it stays debited for operations to resolve, and Finance returns it if needed.
 */
class CorporateWalletPayments
{
    private const DEADLOCK_ATTEMPTS = 5;

    public function __construct(
        private readonly CompanyWallet $wallet,
        private readonly CompanyInvoices $invoices,
    ) {}

    /**
     * Takes the fare from the wallet and marks the booking paid, before the
     * supplier is called.
     *
     * @throws InsufficientWalletBalance
     */
    public function debit(FlightBooking $booking, CompanyUser $user, float $amount, string $flow): void
    {
        DB::transaction(function () use ($booking, $user, $amount, $flow): void {
            $this->wallet->post($user->company, 'booking_debit', -$amount, 'booking_debit:'.$booking->id, [
                'flight_booking_id' => $booking->id,
                'company_user_id' => $user->id,
                'user_id' => app(CorporateContext::class)->actingStaff()?->id,
                'reason' => trim($booking->booking_ref.' '.$booking->route),
            ]);

            $booking->update([
                'payment_method' => 'wallet',
                'payment_gateway' => 'wallet',
                'payment_flow' => $flow,
                'payment_status' => 'paid',
                'payment_amount' => $amount,
                'payment_charged_amount' => $amount,
                'payment_currency' => 'NGN',
                'payment_verified_at' => now(),
                // A card attempt keeps its reference, so a late charge on it
                // can still be matched to this booking.
                'payment_reference' => $booking->payment_reference ?: 'WALLET-'.$booking->booking_ref,
            ]);
        }, self::DEADLOCK_ATTEMPTS);

        ActivityLog::recordForCompanyUser($user, 'company_wallet_payment', 'Paid '.$booking->booking_ref.' from the wallet', $booking, ['amount' => $amount]);
    }

    /**
     * The supplier clearly refused, so nothing exists there. The money goes back
     * and the booking closes. Safe to call twice.
     *
     * @return float what was returned
     */
    public function returnAfterRefusal(FlightBooking $booking, string $supplierMessage): float
    {
        return DB::transaction(function () use ($booking, $supplierMessage): float {
            $locked = FlightBooking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $amount = $this->walletDebited($locked);

            $entry = $this->wallet->post($locked->company, 'booking_return', $amount, 'booking_return:'.$locked->id, [
                'flight_booking_id' => $locked->id,
                'reason' => 'Airline did not confirm '.$locked->booking_ref,
            ]);

            $this->invoices->creditNoteFor($locked, $entry, 'The airline did not confirm this booking.');

            $locked->update([
                'booking_status' => 'cancelled',
                'payment_status' => 'refunded',
                'reconciliation_note' => 'Supplier refused the booking; wallet money returned automatically: '.Str::limit($supplierMessage, 400),
                'last_reconciled_at' => now(),
            ]);

            return $amount;
        }, self::DEADLOCK_ATTEMPTS);
    }

    /** What a company has paid on this booking and not yet had back. */
    public function refundable(FlightBooking $booking): float
    {
        if (! $booking->company_id || ! in_array($booking->payment_status, ['paid', 'partially_paid'], true)) {
            return 0.0;
        }

        return max(0.0, round($this->amountPaid($booking) - $this->amountReturned($booking), 2));
    }

    /**
     * Finance sending money to the wallet, for a refund after penalties or to
     * close a booking that won't go ahead. Card-paid bookings refund to the
     * wallet too. Returning everything closes a booking that was never issued.
     *
     * @throws DomainException when the amount is more than was paid
     */
    public function creditRefund(FlightBooking $booking, float $amount, string $reason, User $staff): void
    {
        DB::transaction(function () use ($booking, $amount, $reason, $staff): void {
            $locked = FlightBooking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $available = $this->refundable($locked);

            if ($amount <= 0 || round($amount, 2) > $available) {
                throw new DomainException('You can return up to NGN '.number_format($available, 2).' on this booking.');
            }

            $entry = $this->wallet->post($locked->company, 'refund', $amount, 'refund:'.Str::uuid(), [
                'flight_booking_id' => $locked->id,
                'user_id' => $staff->getKey(),
                'reason' => Str::limit($locked->booking_ref.': '.$reason, 490),
            ]);

            $this->invoices->creditNoteFor($locked, $entry, $reason);

            if (round($available - $amount, 2) > 0) {
                return;
            }

            $wasIssued = $locked->ticket_ordered || in_array($locked->booking_status, ['ticketed', 'confirmed'], true);

            $locked->update(array_merge(['payment_status' => 'refunded'], $wasIssued ? [] : ['booking_status' => 'cancelled']));
        }, self::DEADLOCK_ATTEMPTS);
    }

    private function amountPaid(FlightBooking $booking): float
    {
        return (float) ($booking->payment_charged_amount ?: $booking->payment_amount ?: $booking->total_price);
    }

    private function amountReturned(FlightBooking $booking): float
    {
        return (float) $booking->company->walletEntries()
            ->where('flight_booking_id', $booking->id)
            ->whereIn('type', ['refund', 'booking_return'])
            ->sum('amount');
    }

    private function walletDebited(FlightBooking $booking): float
    {
        return abs((float) $booking->company->walletEntries()
            ->where('flight_booking_id', $booking->id)
            ->where('type', 'booking_debit')
            ->sum('amount'));
    }
}
