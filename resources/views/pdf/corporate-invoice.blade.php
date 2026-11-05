@php
    $c = config('brand.colors');
    $money = fn (float $value): string => 'NGN '.number_format($value, 2);
    $company = $invoice->company_details;
    $booking = $invoice->booking_details ?? [];
    $methods = ['wallet' => 'Company wallet', 'gateway' => 'Card', 'bank_transfer' => 'Bank transfer'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: {{ $c['text'] }}; }
        h1 { font-size: 20px; color: {{ $c['brand'] }}; margin: 0; }
        .muted { color: {{ $c['muted'] }}; }
        .head td { vertical-align: top; }
        table { width: 100%; border-collapse: collapse; }
        .lines { margin-top: 18px; }
        .lines th { text-align: left; font-size: 10px; color: {{ $c['muted'] }}; border-bottom: 1px solid {{ $c['line'] }}; padding: 6px 0; }
        .lines td { padding: 8px 0; border-bottom: 1px solid {{ $c['line_soft'] }}; }
        .amount, .lines th.amount, .entries th.amount { text-align: right; }
        .total td { font-weight: bold; font-size: 13px; border-bottom: 0; padding-top: 10px; }
        .box { margin-top: 18px; }
        .label { font-size: 9px; color: {{ $c['muted'] }}; text-transform: uppercase; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <h1>{{ config('brand.name') }}</h1>
                <div class="muted">{{ config('brand.address') }}</div>
                <div class="muted">{{ config('brand.support_email') }}</div>
                @if (config('corporate.invoice.tax_id'))
                    <div class="muted">Tax ID: {{ config('corporate.invoice.tax_id') }}</div>
                @endif
            </td>
            <td style="text-align:right;">
                <h1>{{ $invoice->typeLabel() }}</h1>
                <div><strong>{{ $invoice->number }}</strong></div>
                <div class="muted">Issued {{ $invoice->issued_at->timezone('Africa/Lagos')->format('j F Y') }}</div>
                @if ($invoice->isCreditNote() && $invoice->creditedInvoice)
                    <div class="muted">Against invoice {{ $invoice->creditedInvoice->number }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="box">
        <tr>
            <td style="vertical-align:top;width:50%;">
                <div class="label">{{ $invoice->isCreditNote() ? 'Credited to' : 'Billed to' }}</div>
                <div><strong>{{ $company['name'] ?? '' }}</strong></div>
                <div>CAC: {{ $company['cac_number'] ?? '' }}</div>
                <div>{{ $company['contact_name'] ?? '' }}</div>
                <div>{{ $company['contact_email'] ?? '' }}</div>
            </td>
            <td style="vertical-align:top;">
                <div class="label">Booking</div>
                <div><strong>{{ $booking['reference'] ?? '' }}</strong></div>
                <div>{{ $booking['route'] ?? '' }}</div>
                <div>{{ collect([$booking['airline'] ?? null, $booking['cabin'] ?? null])->filter()->join(' · ') }}</div>
                @if (! empty($booking['cost_code']))
                    <div>Cost code: {{ $booking['cost_code'] }}</div>
                @endif
                @if (! empty($booking['booked_by']))
                    <div>Booked by: {{ $booking['booked_by'] }}</div>
                @endif
                @if (! empty($booking['passengers']))
                    <div>Travellers: {{ implode(', ', $booking['passengers']) }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead><tr><th>Description</th><th class="amount">Amount</th></tr></thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="amount">{{ $money((float) $line['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>{{ $invoice->isCreditNote() ? 'Total credited' : 'Total' }}</td>
                <td class="amount">{{ $money((float) $invoice->total) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($invoice->vat_percent !== null && (float) $invoice->vat_amount > 0)
        <p class="muted">Includes VAT at {{ rtrim(rtrim(number_format((float) $invoice->vat_percent, 2), '0'), '.') }}%: {{ $money((float) $invoice->vat_amount) }}.</p>
    @endif

    @if ($invoice->reason)
        <p><strong>Reason:</strong> {{ $invoice->reason }}</p>
    @endif

    @if (! $invoice->isCreditNote())
        <p class="muted">Paid by {{ $methods[$booking['payment_method'] ?? ''] ?? 'other method' }}. No further payment is due.</p>
    @else
        <p class="muted">This amount has been returned to your company wallet.</p>
    @endif
</body>
</html>
