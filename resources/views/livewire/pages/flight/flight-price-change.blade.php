{{-- A company's fare rose before payment: nothing is charged until the booker decides. --}}
@component('layouts.app', ['title' => 'The fare has changed'])
    <div style="max-width:560px;margin:0 auto;padding:150px 16px 60px;">
        <div style="background:#fff;border:1px solid #e6e8ee;border-radius:12px;padding:24px;">
            <h1 style="font-size:24px;font-weight:700;color:#111827;margin:0 0 6px;">The fare has changed</h1>
            <p style="color:#667085;margin:0 0 18px;">
                The airline's price for this flight went up after you selected it. Nothing has been charged. Accept the new fare or go back to choose another flight.
            </p>

            @if (session('priceRoseAgain'))
                <div role="status" style="padding:10px 14px;border-radius:8px;background:#fffaf0;color:#92400e;font-size:14px;margin-bottom:14px;">
                    The fare went up again while you were deciding. Please check the new amount.
                </div>
            @endif

            @error('error')
                <div role="alert" style="padding:10px 14px;border-radius:8px;background:#fef3f2;color:#b42318;font-size:14px;margin-bottom:14px;">{{ $message }}</div>
            @enderror

            <table style="width:100%;border-collapse:collapse;font-size:15px;margin-bottom:20px;">
                <tr>
                    <td style="padding:8px 0;color:#667085;">Flight</td>
                    <td style="padding:8px 0;text-align:right;font-weight:600;">{{ $booking->route }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#667085;">Fare you selected</td>
                    <td style="padding:8px 0;text-align:right;">₦{{ number_format($oldFare, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#667085;">New fare</td>
                    <td style="padding:8px 0;text-align:right;font-weight:600;">₦{{ number_format($newFare, 2) }}</td>
                </tr>
                <tr style="border-top:1px solid #e6e8ee;">
                    <td style="padding:10px 0;font-weight:700;color:#111827;">New total to pay</td>
                    <td style="padding:10px 0;text-align:right;font-weight:700;color:#111827;">₦{{ number_format($newTotal, 2) }}</td>
                </tr>
            </table>

            <form method="POST" action="{{ route('flights.price-change.accept') }}">
                @csrf
                <button type="submit" style="width:100%;min-height:44px;border:0;border-radius:8px;background:#303191;color:#fff;font-weight:600;cursor:pointer;">Accept the new fare</button>
            </form>
            <a href="{{ route('air.flight-s') }}" style="display:block;text-align:center;margin-top:12px;color:#303191;font-weight:600;">Go back to results</a>
        </div>
    </div>
@endcomponent
