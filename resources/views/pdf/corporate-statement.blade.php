@php
    $c = config('brand.colors');
    $money = fn (float $value): string => 'NGN '.number_format($value, 2);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: {{ $c['text'] }}; }
        h1 { font-size: 18px; color: {{ $c['brand'] }}; margin: 0; }
        .logo { height: 30px; width: auto; display: block; margin-bottom: 6px; }
        .muted { color: {{ $c['muted'] }}; }
        table { width: 100%; border-collapse: collapse; }
        .entries { margin-top: 16px; }
        .entries th { text-align: left; font-size: 9px; color: {{ $c['muted'] }}; border-bottom: 1px solid {{ $c['line'] }}; padding: 5px 4px 5px 0; }
        .entries td { padding: 6px 4px 6px 0; border-bottom: 1px solid {{ $c['line_soft'] }}; vertical-align: top; }
        .amount, .lines th.amount, .entries th.amount { text-align: right; }
        .summary td { padding: 4px 0; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <img class="logo" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/travelwheel-pdf.png'))) }}" alt="{{ config('brand.name') }}">
                <div class="muted">{{ config('brand.address') }}</div>
            </td>
            <td style="text-align:right;">
                <h1>Wallet statement</h1>
                <div><strong>{{ $statement['month']->format('F Y') }}</strong></div>
            </td>
        </tr>
    </table>

    <p><strong>{{ $company->name }}</strong> · CAC {{ $company->cac_number }}</p>

    <table class="summary" style="width:50%;">
        <tr><td>Opening balance</td><td class="amount">{{ $money($statement['opening']) }}</td></tr>
        <tr><td>Money in</td><td class="amount">{{ $money($statement['money_in']) }}</td></tr>
        <tr><td>Money out</td><td class="amount">{{ $money($statement['money_out']) }}</td></tr>
        <tr><td><strong>Closing balance</strong></td><td class="amount"><strong>{{ $money($statement['closing']) }}</strong></td></tr>
    </table>

    <table class="entries">
        <thead><tr><th>Date</th><th>Type</th><th>Details</th><th class="amount">Amount</th><th class="amount">Balance</th></tr></thead>
        <tbody>
            @forelse ($statement['entries'] as $entry)
                <tr>
                    <td>{{ $entry->created_at->timezone('Africa/Lagos')->format('j M Y, H:i') }}</td>
                    <td>{{ $entry->typeLabel() }}</td>
                    <td>{{ $entry->reason }}</td>
                    <td class="amount">{{ number_format((float) $entry->amount, 2) }}</td>
                    <td class="amount">{{ number_format((float) $entry->balance_after, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No activity this month.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
