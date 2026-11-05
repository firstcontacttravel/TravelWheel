<?php

namespace App\Services\Corporate;

use App\Models\Company;
use App\Models\CompanyWalletEntry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The only code that writes to a wallet.
 *
 * Each entry locks the company row inside a transaction, so two bookers can't
 * spend the same money. The idempotency key means an event retried by a webhook,
 * a double-click or a queue posts once. The balance may go down to minus the
 * company's credit limit, which is 0 until credit terms exist.
 */
class CompanyWallet
{
    private const DEADLOCK_ATTEMPTS = 5;

    /**
     * @param  float  $amount  positive adds to the balance, negative takes from it
     * @param  array<string, mixed>  $details  optional: company_wallet_top_up_id, flight_booking_id, user_id, company_user_id, reason
     *
     * @throws InsufficientWalletBalance
     */
    public function post(Company $company, string $type, float $amount, string $idempotencyKey, array $details = []): CompanyWalletEntry
    {
        try {
            return DB::transaction(function () use ($company, $type, $amount, $idempotencyKey, $details): CompanyWalletEntry {
                $locked = Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();

                $existing = CompanyWalletEntry::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }

                $balance = round((float) $locked->wallet_balance + $amount, 2);

                if ($amount < 0 && $balance < -(float) $locked->credit_limit) {
                    throw new InsufficientWalletBalance('The wallet balance is too low.');
                }

                $entry = CompanyWalletEntry::query()->create(array_merge($details, [
                    'company_id' => $locked->getKey(),
                    'type' => $type,
                    'amount' => round($amount, 2),
                    'balance_after' => $balance,
                    'idempotency_key' => $idempotencyKey,
                ]));

                $locked->forceFill(['wallet_balance' => $balance])->save();
                $company->setAttribute('wallet_balance', $locked->wallet_balance);

                return $entry;
            }, self::DEADLOCK_ATTEMPTS);
        } catch (UniqueConstraintViolationException) {
            return CompanyWalletEntry::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
        }
    }

    /** Companies whose stored balance doesn't match their ledger. Should always be empty. */
    public function mismatchedCompanyIds(): array
    {
        return Company::query()
            ->withSum('walletEntries as ledger_total', 'amount')
            ->get()
            ->filter(fn (Company $company): bool => abs((float) $company->wallet_balance - (float) $company->ledger_total) >= 0.01)
            ->pluck('id')
            ->all();
    }
}
