@extends('business.layout')
@section('title', 'Bookings')

@php($statuses = \App\Http\Controllers\Business\BookingController::STATUSES)

@section('content')
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start;">
        <div>
            <h1>Bookings</h1>
            <p class="biz-sub">{{ $companyUser->isCompanyAdmin() ? 'Every booking made for your company.' : 'The bookings you have made.' }}</p>
        </div>
        <div class="biz-actions">
            <a class="biz-btn biz-btn-light" href="{{ route('business.bookings.export') }}">Export CSV</a>
            <a class="biz-btn" href="{{ route('air') }}">Book a flight</a>
        </div>
    </div>

    @error('wallet')
        <div class="biz-alert biz-alert-bad" role="alert">{{ $message }}</div>
    @enderror

    <form method="GET" action="{{ route('business.bookings.index') }}" class="biz-card">
        <div class="biz-grid">
            <div class="biz-field">
                <label for="q">Search</label>
                <input class="biz-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Reference, route, airline or cost code">
            </div>
            <div class="biz-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (array_unique($statuses) as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="biz-field">
                <label for="apply-filters" style="visibility:hidden">Apply</label>
                <button id="apply-filters" type="submit" class="biz-btn biz-btn-light biz-btn-full">Apply</button>
            </div>
        </div>
    </form>

    <div class="biz-card">
        @if ($bookings->isEmpty())
            <p class="biz-sub" style="margin:0">No bookings to show.</p>
        @else
            <table class="biz-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Trip</th>
                        <th>Total</th>
                        <th>Status</th>
                        @if ($companyUser->isCompanyAdmin())<th>Booked by</th>@endif
                        <th>Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td>
                                <strong>{{ $booking->booking_ref }}</strong>
                                <div class="biz-help">{{ $booking->created_at->timezone('Africa/Lagos')->format('j M Y') }}</div>
                                @if ($booking->cost_code)
                                    <div class="biz-help">Cost code: {{ $booking->cost_code }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $booking->route }}
                                <div class="biz-help">{{ collect([$booking->airline, $booking->cabin])->filter()->join(' · ') }}</div>
                            </td>
                            <td>₦{{ number_format((float) $booking->total_price, 2) }}</td>
                            <td><span class="biz-badge">{{ $statuses[$booking->booking_status] ?? 'In progress' }}</span></td>
                            @if ($companyUser->isCompanyAdmin())
                                <td>{{ $booking->companyUser?->name ?? '-' }}</td>
                            @endif
                            <td>
                                @if (\App\Http\Controllers\Business\BookingController::hasTicket($booking))
                                    <a href="{{ route('business.bookings.ticket', $booking->booking_ref) }}">Download</a>
                                @elseif ($booking->hold_type === \App\Services\Flights\FlightPlatformHold::PAY_LATER && $booking->booking_status === 'on_hold' && $booking->payment_status === 'pending' && $booking->tkt_time_limit?->isFuture())
                                    {{-- Saved with "hold and pay later": nothing is held with the airline yet. --}}
                                    <a href="{{ URL::temporarySignedRoute('flights.hold.resume', $booking->tkt_time_limit, ['bookingRef' => $booking->booking_ref]) }}">Pay now</a>
                                    <div class="biz-help">By {{ $booking->tkt_time_limit->timezone('Africa/Lagos')->format('H:i, j M') }}</div>
                                @else
                                    <span class="biz-help">Not ready</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($bookings->hasPages())
                <div style="display:flex;justify-content:space-between;margin-top:14px;font-size:14px;">
                    @if ($bookings->previousPageUrl())<a href="{{ $bookings->previousPageUrl() }}">Previous</a>@else<span></span>@endif
                    @if ($bookings->nextPageUrl())<a href="{{ $bookings->nextPageUrl() }}">Next</a>@endif
                </div>
            @endif
        @endif
    </div>
@endsection
