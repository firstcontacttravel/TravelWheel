{{-- Pay from the company wallet. Only ever shown to a signed-in company. Expects $total. --}}
@if ($corporateCompany ?? null)
    @php
        $walletUser = app(\App\Services\Corporate\CorporateContext::class)->user();
        $walletBalance = (float) $corporateCompany->fresh()->wallet_balance;
        $walletCovers = $walletBalance >= (float) $total;
        $walletIsStaff = (bool) $walletUser?->is_system;
        $walletIsAdmin = $walletUser?->isCompanyAdmin() ?? false;
    @endphp
    <div style="border:1px solid #d0d5dd;border-radius:10px;padding:16px;margin-bottom:16px;background:#fff;">
        <div style="font-weight:700;color:#111827;font-size:16px;">Pay from company wallet</div>

        @if ($walletCovers)
            <p style="color:#667085;font-size:14px;margin:4px 0 12px;">
                {{ $walletIsAdmin || $walletIsStaff ? 'Balance: ₦'.number_format($walletBalance, 2).'.' : 'Your company wallet covers this booking.' }}
                ₦{{ number_format((float) $total, 2) }} is taken straight away, with no card needed.
            </p>
            <form method="POST" action="{{ route('flights.payment.wallet') }}" onsubmit="this.querySelector('button').disabled = true;">
                @csrf
                <button type="submit" style="width:100%;min-height:44px;border:0;border-radius:8px;background:#303191;color:#fff;font-weight:600;cursor:pointer;">
                    Pay ₦{{ number_format((float) $total, 2) }} from wallet
                </button>
            </form>
        @elseif ($walletIsAdmin)
            <p style="color:#667085;font-size:14px;margin:4px 0 0;">
                The balance is ₦{{ number_format($walletBalance, 2) }}, which is ₦{{ number_format((float) $total - $walletBalance, 2) }} short.
                <a href="{{ route('business.wallet.index') }}" target="_blank" rel="noopener" style="color:#303191;font-weight:600;">Top up</a> and come back, or pay the whole amount another way below.
            </p>
        @elseif ($walletIsStaff)
            <p style="color:#667085;font-size:14px;margin:4px 0 0;">
                The balance is ₦{{ number_format($walletBalance, 2) }}, which is ₦{{ number_format((float) $total - $walletBalance, 2) }} short. The company needs to top up, or pay the whole amount another way below.
            </p>
        @else
            <p style="color:#667085;font-size:14px;margin:4px 0 0;">
                The company wallet doesn't have enough for this booking. Ask your Company Admin to top it up, or pay the whole amount another way below.
            </p>
        @endif
    </div>
@endif
