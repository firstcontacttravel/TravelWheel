@extends('business.layout')
@section('title', 'Dashboard')

@php($statuses = \App\Http\Controllers\Business\BookingController::STATUSES)

@section('content')
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start;">
        <div>
            <h1>{{ $company->name }}</h1>
            <p class="biz-sub">Welcome, {{ $companyUser->name }}. While you're signed in, every fare shows your company's rate.</p>
        </div>
        <a class="biz-btn" href="{{ route('air') }}">Search flights</a>
    </div>

    <div class="biz-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;margin-bottom:18px;">
        @if ($companyUser->isCompanyAdmin())
            <div class="biz-card" style="margin:0;">
                <div class="biz-help" style="margin-top:0">Wallet balance</div>
                <div style="font-size:24px;font-weight:700;color:var(--biz-ink);line-height:1.3;">₦{{ number_format((float) $company->wallet_balance, 2) }}</div>
                <p style="margin:8px 0 0;font-size:14px;"><a href="{{ route('business.wallet.index') }}">Top up or see history</a></p>
            </div>
        @endif

        <div class="biz-card" style="margin:0;">
            <div class="biz-help" style="margin-top:0">Your account manager</div>
            @if ($company->accountManager && ! $company->accountManager->isDeactivated())
                <div style="font-weight:700;color:var(--biz-ink);">{{ $company->accountManager->name }}</div>
                <p style="margin:4px 0 0;font-size:14px;"><a href="mailto:{{ $company->accountManager->email }}">{{ $company->accountManager->email }}</a></p>
            @else
                <div style="font-weight:700;color:var(--biz-ink);">TravelWheel support</div>
                <p style="margin:4px 0 0;font-size:14px;"><a href="mailto:{{ config('brand.support_email') }}">{{ config('brand.support_email') }}</a></p>
            @endif
        </div>
    </div>

    <div class="biz-card">
        <h2 style="margin-bottom:12px">Upcoming trips</h2>
        @if ($upcoming->isEmpty())
            <p class="biz-sub" style="margin:0">No upcoming trips.</p>
        @else
            <table class="biz-table">
                <thead><tr><th>Departs</th><th>Trip</th><th>Reference</th></tr></thead>
                <tbody>
                    @foreach ($upcoming as $trip)
                        <tr>
                            <td><strong>{{ $trip['departs']->format('j M Y, H:i') }}</strong></td>
                            <td>
                                {{ $trip['booking']->route }}
                                <div class="biz-help">{{ $trip['booking']->airline }}</div>
                            </td>
                            <td>{{ $trip['booking']->booking_ref }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="biz-card">
        <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <h2 style="margin:0">Recent bookings</h2>
            <a href="{{ route('business.bookings.index') }}" style="font-size:14px;">View all</a>
        </div>
        @if ($recent->isEmpty())
            <p class="biz-sub" style="margin:0">No bookings yet.</p>
        @else
            <table class="biz-table">
                <thead><tr><th>Reference</th><th>Trip</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($recent as $booking)
                        <tr>
                            <td><strong>{{ $booking->booking_ref }}</strong></td>
                            <td>{{ $booking->route }}</td>
                            <td>₦{{ number_format((float) $booking->total_price, 2) }}</td>
                            <td><span class="biz-badge">{{ $statuses[$booking->booking_status] ?? 'In progress' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($companyUser->isCompanyAdmin())
        <p style="font-size:14px;margin:0;"><a href="{{ route('business.team.index') }}">Invite bookers and manage who has access</a></p>
    @endif
@endsection
