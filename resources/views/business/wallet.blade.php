@extends('business.layout')
@section('title', 'Wallet')

@section('content')
    <h1>Wallet</h1>
    <p class="biz-sub">Add money here, then choose the company wallet when you pay for a flight.</p>

    @include('business.partials.status')

    <div class="biz-card">
        <div class="biz-help" style="margin-top:0">Available balance</div>
        <div style="font-size:30px;font-weight:700;color:var(--biz-ink);line-height:1.3;">₦{{ number_format((float) $company->wallet_balance, 2) }}</div>
    </div>

    <div class="biz-card">
        <h2>Top up</h2>
        <p class="biz-sub">
            Bank transfers are added once Finance confirms the money has arrived.
            @if ($cardFeePercent !== null)
                Card payments are added straight away, with a {{ rtrim(rtrim(number_format($cardFeePercent, 2), '0'), '.') }}% card fee on top, which doesn't go into your wallet.
            @endif
        </p>

        <form method="POST" action="{{ route('business.wallet.top-ups.store') }}" style="max-width:420px">
            @csrf
            @include('business.partials.field', ['name' => 'amount', 'label' => 'Amount to add (₦)', 'type' => 'number', 'inputmode' => 'decimal', 'help' => 'Minimum ₦'.number_format($minTopUp).'.'])

            <div class="biz-field">
                <label for="method">How will you pay?</label>
                <select id="method" name="method" required>
                    @if ($bankAccounts !== [])
                        <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>Bank transfer</option>
                    @endif
                    @if ($cardFeePercent !== null)
                        <option value="card" @selected(old('method') === 'card')>Card</option>
                    @endif
                </select>
                @error('method')
                    <div class="biz-error">{{ $message }}</div>
                @enderror
            </div>

            @if ($bankAccounts !== [])
                @include('business.partials.field', ['name' => 'bank_reference', 'label' => 'Your bank reference (optional)', 'required' => false, 'help' => 'For a bank transfer, if you already have one.'])
            @endif

            @if ($bankAccounts === [] && $cardFeePercent === null)
                <div class="biz-alert biz-alert-bad" role="alert">Top-ups aren't available yet. Please contact TravelWheel.</div>
            @else
                <button type="submit" class="biz-btn">Continue</button>
            @endif
        </form>
    </div>

    @if ($pending->isNotEmpty())
        <div class="biz-card">
            <h2>Waiting for confirmation</h2>
            <p class="biz-sub">Send each amount to an account below, quoting its reference.</p>

            <table class="biz-table" style="margin-bottom:16px">
                <thead><tr><th>Reference to quote</th><th>Amount</th><th>Recorded</th></tr></thead>
                <tbody>
                    @foreach ($pending as $topUp)
                        <tr>
                            <td><strong>{{ $topUp->reference }}</strong></td>
                            <td>₦{{ number_format((float) $topUp->amount, 2) }}</td>
                            <td>{{ $topUp->created_at->timezone('Africa/Lagos')->format('j M Y, g:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @foreach ($bankAccounts as $account)
                <p style="margin:0 0 8px;font-size:14px;">
                    <strong>{{ $account['bank'] }}</strong> · {{ $account['account_number'] }} · {{ $account['account_name'] }}
                </p>
            @endforeach
        </div>
    @endif

    <div class="biz-card">
        <h2 style="margin-bottom:12px">History</h2>
        @if ($entries->isEmpty())
            <p class="biz-sub" style="margin:0">Nothing yet.</p>
        @else
            <table class="biz-table">
                <thead><tr><th>Date</th><th>Details</th><th>Amount</th><th>Balance</th></tr></thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr>
                            <td>{{ $entry->created_at->timezone('Africa/Lagos')->format('j M Y, g:i A') }}</td>
                            <td>
                                {{ $entry->typeLabel() }}
                                @if ($entry->reason)
                                    <div class="biz-help">{{ $entry->reason }}</div>
                                @endif
                            </td>
                            <td style="font-weight:600;color:{{ (float) $entry->amount < 0 ? 'var(--biz-danger)' : 'var(--biz-success)' }}">
                                {{ (float) $entry->amount < 0 ? '-' : '+' }}₦{{ number_format(abs((float) $entry->amount), 2) }}
                            </td>
                            <td>₦{{ number_format((float) $entry->balance_after, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($entries->hasPages())
                <div style="display:flex;justify-content:space-between;margin-top:14px;font-size:14px;">
                    @if ($entries->previousPageUrl())<a href="{{ $entries->previousPageUrl() }}">Newer</a>@else<span></span>@endif
                    @if ($entries->nextPageUrl())<a href="{{ $entries->nextPageUrl() }}">Older</a>@endif
                </div>
            @endif
        @endif
    </div>
@endsection
