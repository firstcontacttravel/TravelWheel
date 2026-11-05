<?php

namespace App\Services\Corporate;

use App\Mail\CompanyTopUpDecidedMail;
use App\Mail\CompanyTopUpRecordedMail;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyWalletTopUp;
use App\Models\User;
use App\Services\SeerbitPaymentService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Adding money to a company's wallet.
 *
 * A bank transfer waits for Finance to confirm that it arrived. A card top-up
 * is credited once SeerBit confirms the payment, whichever of the browser's
 * return or the webhook gets here first. Card fees are on top and never reach
 * the wallet.
 *
 * SeerBit bills its own fee to the customer at checkout (feeBearer CUSTOMER on
 * our payments, 1.5% up to NGN 2,000 on Nigerian cards). TravelWheel's percentage
 * is on top of that, so it stays at 0 unless the CEO chooses to add more. What
 * was really charged is recorded from SeerBit's answer.
 */
class CompanyWalletTopUps
{
    public const FEE_SETTING = 'corporate.card_topup_fee_percent';

    public function __construct(
        private readonly CompanyWallet $wallet,
        private readonly SeerbitPaymentService $seerbit,
    ) {}

    /** Null until the CEO sets it, and card top-ups are unavailable until then. */
    public function cardFeePercent(): ?float
    {
        $percent = AppSetting::get(self::FEE_SETTING);

        return is_numeric($percent) ? (float) $percent : null;
    }

    /** @return list<array{bank: ?string, account_number: ?string, account_name: ?string}> */
    public function bankAccounts(): array
    {
        return config('travelwheel.travelflex_bank_accounts', []);
    }

    public function recordBankTransfer(CompanyUser $user, float $amount, ?string $bankReference): CompanyWalletTopUp
    {
        $topUp = $user->company->walletTopUps()->create([
            'reference' => $this->newReference(),
            'method' => 'bank_transfer',
            'amount' => $amount,
            'total_charged' => $amount,
            'bank_reference' => $bankReference,
            'requested_by_company_user_id' => $user->getKey(),
        ]);

        ActivityLog::recordForCompanyUser($user, 'company_top_up_recorded', 'Recorded a bank transfer top-up '.$topUp->reference, $topUp, ['amount' => $amount]);

        try {
            Mail::to(config('corporate.notify_email'))->send(new CompanyTopUpRecordedMail($topUp));
        } catch (Throwable $exception) {
            // It's in the Finance queue with a badge either way.
            Log::error('Corporate top-up notification failed', ['top_up_id' => $topUp->id, 'error' => $exception->getMessage()]);
        }

        return $topUp;
    }

    /**
     * Creates the top-up and returns the link to pay on.
     *
     * @throws DomainException when card top-ups aren't available or the gateway refuses
     */
    public function startCard(CompanyUser $user, float $amount): string
    {
        $percent = $this->cardFeePercent();

        if ($percent === null) {
            throw new DomainException('Card top-ups are not available yet. Please use bank transfer.');
        }

        $fee = round($amount * $percent / 100, 2);
        $company = $user->company;

        $topUp = $company->walletTopUps()->create([
            'reference' => $this->newReference(),
            'method' => 'card',
            'amount' => $amount,
            'fee_percent' => $percent,
            'fee_amount' => $fee,
            'total_charged' => round($amount + $fee, 2),
            'requested_by_company_user_id' => $user->getKey(),
        ]);

        ActivityLog::recordForCompanyUser($user, 'company_top_up_started', 'Started a card top-up '.$topUp->reference, $topUp, ['amount' => $amount, 'fee' => $fee]);

        try {
            $checkout = $this->seerbit->initializePayment([
                'amount' => number_format((float) $topUp->total_charged, 2, '.', ''),
                'currency' => 'NGN',
                'paymentReference' => $topUp->reference,
                'email' => $user->email,
                'fullName' => $user->name,
                'mobileNumber' => $company->contact_phone,
                'callbackUrl' => route('business.wallet.callback', ['paymentReference' => $topUp->reference]),
                'productDescription' => 'TravelWheel wallet top-up '.$topUp->reference,
            ]);
        } catch (Throwable $exception) {
            Log::error('Corporate card top-up could not start', ['top_up_id' => $topUp->id, 'error' => $exception->getMessage()]);
            $topUp->update(['status' => 'failed']);

            throw new DomainException('We could not start the card payment. Nothing has been charged. Please try again.');
        }

        $topUp->update(['gateway_response' => $checkout['raw']]);

        return $checkout['redirect_link'];
    }

    /**
     * Asks SeerBit about a card top-up and credits it if it was paid in full.
     * Safe to call any number of times, from any of the routes that reach it.
     * A failed top-up can still succeed, because a card can be approved late.
     *
     * @return string the top-up's status afterwards
     */
    public function settleCard(CompanyWalletTopUp $topUp): string
    {
        if (! $topUp->isCard() || $topUp->status === 'confirmed') {
            return $topUp->status;
        }

        try {
            $verification = $this->seerbit->verifyPayment($topUp->reference);
        } catch (Throwable $exception) {
            Log::error('Corporate top-up verification could not start', ['top_up_id' => $topUp->id, 'error' => $exception->getMessage()]);

            return $topUp->status;
        }

        // SeerBit couldn't answer, which says nothing about the payment.
        if (! ($verification['query_succeeded'] ?? true)) {
            return $topUp->status;
        }

        $paidInFull = ($verification['ok'] ?? false)
            && ($verification['amount'] ?? null) !== null
            && round((float) $verification['amount'], 2) >= round((float) $topUp->total_charged, 2)
            && (empty($verification['currency']) || strtoupper((string) $verification['currency']) === 'NGN');

        if (! $paidInFull) {
            $topUp->update(['status' => 'failed', 'gateway_response' => $verification['raw'] ?? []]);

            return 'failed';
        }

        $charged = round((float) $verification['amount'], 2);

        $this->credit($topUp, (float) $topUp->amount, [
            'gateway_response' => $verification['raw'] ?? [],
            'total_charged' => $charged,
            'fee_amount' => round($charged - (float) $topUp->amount, 2),
        ], null, 'Card top-up');
        $this->tellCompany($topUp->fresh());

        return 'confirmed';
    }

    /**
     * Finance confirming a transfer arrived. It credits what actually arrived,
     * which can differ from what the company recorded.
     */
    public function confirmBankTransfer(CompanyWalletTopUp $topUp, float $receivedAmount, User $staff, ?string $note = null): void
    {
        if ($topUp->isCard()) {
            throw new DomainException('Card top-ups are confirmed by SeerBit.');
        }

        $this->credit($topUp, $receivedAmount, [
            'amount' => $receivedAmount,
            'total_charged' => $receivedAmount,
            'decided_by_user_id' => $staff->getKey(),
            'decided_at' => now(),
            'decision_note' => $note,
        ], $staff, 'Bank transfer top-up');

        $this->tellCompany($topUp->fresh());
    }

    public function rejectBankTransfer(CompanyWalletTopUp $topUp, string $reason, User $staff): void
    {
        if ($topUp->isCard()) {
            throw new DomainException('Card top-ups are confirmed by SeerBit.');
        }

        DB::transaction(function () use ($topUp, $reason, $staff): void {
            $locked = CompanyWalletTopUp::query()->whereKey($topUp->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new DomainException('This top-up has already been decided.');
            }

            $locked->update([
                'status' => 'rejected',
                'decided_by_user_id' => $staff->getKey(),
                'decided_at' => now(),
                'decision_note' => $reason,
            ]);
        });

        $this->tellCompany($topUp->fresh());
    }

    /** The status change and the ledger entry succeed or fail together. */
    private function credit(CompanyWalletTopUp $topUp, float $amount, array $changes, ?User $staff, string $reason): void
    {
        DB::transaction(function () use ($topUp, $amount, $changes, $staff, $reason): void {
            $locked = CompanyWalletTopUp::query()->whereKey($topUp->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'confirmed') {
                return;
            }

            if ($locked->status === 'rejected') {
                throw new DomainException('This top-up has already been decided.');
            }

            $locked->update(array_merge($changes, ['status' => 'confirmed']));

            $this->wallet->post($locked->company, 'top_up', $amount, 'top_up:'.$locked->id, [
                'company_wallet_top_up_id' => $locked->id,
                'user_id' => $staff?->getKey(),
                'reason' => $reason.' '.$locked->reference,
            ]);
        });
    }

    private function tellCompany(CompanyWalletTopUp $topUp): void
    {
        $admins = $topUp->company->users()
            ->where('role', 'admin')
            ->whereNull('deactivated_at')
            ->pluck('email')
            ->all();

        if ($admins === []) {
            return;
        }

        try {
            Mail::to($admins)->send(new CompanyTopUpDecidedMail($topUp));
        } catch (Throwable $exception) {
            // The money is already right, and the wallet page shows it.
            Log::error('Corporate top-up email failed', ['top_up_id' => $topUp->id, 'error' => $exception->getMessage()]);
        }
    }

    private function newReference(): string
    {
        return CompanyWalletTopUp::REFERENCE_PREFIX.strtoupper(Str::random(10));
    }
}
