@extends('business.layout')
@section('title', 'Wallet statement')

@php($monthKey = $statement['month']->format('Y-m'))

@section('content')
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start;">
        <div>
            <h1>Wallet statement</h1>
            <p class="biz-sub">{{ $statement['month']->format('F Y') }}</p>
        </div>
        <div class="biz-actions">
            <a class="biz-btn biz-btn-light" href="{{ route('business.statements.pdf', ['month' => $monthKey]) }}">Download PDF</a>
            <a class="biz-btn biz-btn-light" href="{{ route('business.statements.csv', ['month' => $monthKey]) }}">Download CSV</a>
        </div>
    </div>

    <form method="GET" action="{{ route('business.statements.show') }}" class="biz-card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label for="month">Month</label>
            <select id="month" name="month">
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}" @selected($value === $monthKey)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="biz-btn biz-btn-light">Show</button>
    </form>

    <div class="biz-card">
        <div class="biz-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;">
            @foreach (['Opening balance' => $statement['opening'], 'Money in' => $statement['money_in'], 'Money out' => $statement['money_out'], 'Closing balance' => $statement['closing']] as $label => $value)
                <div>
                    <div class="biz-help" style="margin-top:0">{{ $label }}</div>
                    <div style="font-weight:700;font-size:18px;color:var(--biz-ink);">₦{{ number_format($value, 2) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="biz-card">
        @if ($statement['entries']->isEmpty())
            <p class="biz-sub" style="margin:0">No activity this month.</p>
        @else
            <table class="biz-table">
                <thead><tr><th>Date</th><th>Details</th><th>Amount</th><th>Balance</th></tr></thead>
                <tbody>
                    @foreach ($statement['entries'] as $entry)
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
        @endif
    </div>
@endsection
