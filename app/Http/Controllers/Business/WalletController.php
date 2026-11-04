<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\CompanyWalletTopUp;
use App\Services\Corporate\CompanyWalletTopUps;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** A company Admin's view of the wallet: balance, history and adding money. */
class WalletController extends Controller
{
    private const MIN_TOP_UP = 1000;

    private const MAX_TOP_UP = 100000000;

    public function index(Request $request, CompanyWalletTopUps $topUps): View
    {
        $company = $this->user($request)->company;

        return view('business.wallet', [
            'company' => $company,
            'entries' => $company->walletEntries()->latest('id')->paginate(25),
            'pending' => $company->walletTopUps()->where('status', 'pending')->where('method', 'bank_transfer')->latest()->get(),
            'cardFeePercent' => $topUps->cardFeePercent(),
            'bankAccounts' => $topUps->bankAccounts(),
            'minTopUp' => self::MIN_TOP_UP,
        ]);
    }

    public function store(Request $request, CompanyWalletTopUps $topUps): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.self::MIN_TOP_UP, 'max:'.self::MAX_TOP_UP, 'decimal:0,2'],
            'method' => ['required', Rule::in(array_keys(CompanyWalletTopUp::METHODS))],
            'bank_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $this->user($request);
        $amount = (float) $data['amount'];

        if ($data['method'] === 'bank_transfer') {
            if ($topUps->bankAccounts() === []) {
                return back()->withInput()->withErrors(['method' => 'Bank transfer is not available right now.']);
            }

            $topUp = $topUps->recordBankTransfer($user, $amount, $data['bank_reference'] ?? null);

            return back()->with('status', 'Recorded. Quote '.$topUp->reference.' on your transfer. We\'ll add the money once Finance confirms it has arrived.');
        }

        try {
            return redirect()->away($topUps->startCard($user, $amount));
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['method' => $exception->getMessage()]);
        }
    }

    /**
     * Where SeerBit sends the browser back. The session may be gone by then, so
     * this needs no login. It asks SeerBit, and never takes the browser's word.
     */
    public function callback(Request $request, CompanyWalletTopUps $topUps): RedirectResponse
    {
        $topUp = CompanyWalletTopUp::query()
            ->where('reference', (string) $request->query('paymentReference'))
            ->where('method', 'card')
            ->first();

        $redirect = redirect()->route('business.wallet.index');

        if (! $topUp) {
            return $redirect->withErrors(['method' => 'We could not find that payment.']);
        }

        return match ($topUps->settleCard($topUp)) {
            'confirmed' => $redirect->with('status', 'Your wallet has been topped up.'),
            'failed' => $redirect->withErrors(['method' => 'That card payment did not go through, so your wallet hasn\'t changed.']),
            default => $redirect->with('status', 'We\'re still confirming your payment. Your wallet will update once it clears.'),
        };
    }

    private function user(Request $request): CompanyUser
    {
        return $request->attributes->get('companyUser');
    }
}
