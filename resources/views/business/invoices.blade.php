@extends('business.layout')
@section('title', 'Invoices')

@section('content')
    <h1>Invoices</h1>
    <p class="biz-sub">
        {{ $companyUser->isCompanyAdmin() ? 'Invoices and credit notes for your company.' : 'Invoices and credit notes for your bookings.' }}
        An invoice appears once a booking is paid and confirmed.
    </p>

    <form method="GET" action="{{ route('business.invoices.index') }}" class="biz-card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label for="q">Search</label>
            <input class="biz-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Number, booking reference or cost code">
        </div>
        <button type="submit" class="biz-btn biz-btn-light">Search</button>
    </form>

    <div class="biz-card">
        @if ($invoices->isEmpty())
            <p class="biz-sub" style="margin:0">Nothing to show yet.</p>
        @else
            <table class="biz-table">
                <thead><tr><th>Number</th><th>Type</th><th>Booking</th><th>Issued</th><th>Total</th><th>File</th></tr></thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->number }}</strong></td>
                            <td><span class="biz-badge {{ $invoice->isCreditNote() ? 'biz-badge-off' : '' }}">{{ $invoice->typeLabel() }}</span></td>
                            <td>
                                {{ $invoice->booking_details['reference'] ?? '-' }}
                                @if (! empty($invoice->booking_details['cost_code']))
                                    <div class="biz-help">Cost code: {{ $invoice->booking_details['cost_code'] }}</div>
                                @endif
                            </td>
                            <td>{{ $invoice->issued_at->timezone('Africa/Lagos')->format('j M Y') }}</td>
                            <td>₦{{ number_format((float) $invoice->total, 2) }}</td>
                            <td><a href="{{ route('business.invoices.download', $invoice->number) }}">Download PDF</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($invoices->hasPages())
                <div style="display:flex;justify-content:space-between;margin-top:14px;font-size:14px;">
                    @if ($invoices->previousPageUrl())<a href="{{ $invoices->previousPageUrl() }}">Previous</a>@else<span></span>@endif
                    @if ($invoices->nextPageUrl())<a href="{{ $invoices->nextPageUrl() }}">Next</a>@endif
                </div>
            @endif
        @endif
    </div>
@endsection
