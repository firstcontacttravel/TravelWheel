<div class="flight-card">
    <table class="flight-head">
        <tr>
            <td style="width:48px;">@if($segment['airline_logo'])<img src="{{ $segment['airline_logo'] }}" class="segment-logo" alt="">@endif</td>
            <td>
                <div class="segment-airline">{{ $segment['airline'] }}</div>
                <div class="segment-meta">{{ $segment['flight_number'] ?: '-' }} &nbsp; · &nbsp; {{ $segment['aircraft'] }}</div>
            </td>
            <td class="right"><span class="class-chip">{{ $segment['cabin'] }} · Class {{ $segment['booking_class'] }}</span></td>
        </tr>
    </table>
    <table class="segment-route">
        <tr>
            <td class="segment-point">
                <div class="segment-time">{{ $segment['depart_at']?->format('H:i') ?? '-' }}</div>
                <div class="segment-date">{{ $segment['depart_at']?->format('D, M j, Y') ?? '-' }}</div>
                <div class="segment-place">{{ $segment['from'] }} · {{ $segment['from_airport'] ?: $segment['from_city'] }}</div>
            </td>
            <td class="segment-center">
                <div class="segment-duration">{{ $segment['duration'] }}</div>
                <div class="segment-line">✈</div>
                <div class="segment-stops">{{ $segment['stops'] === 0 ? 'Nonstop' : $segment['stops'].' stop'.($segment['stops'] === 1 ? '' : 's') }}</div>
            </td>
            <td class="segment-point right">
                <div class="segment-time">{{ $segment['arrive_at']?->format('H:i') ?? '-' }}</div>
                <div class="segment-date">{{ $segment['arrive_at']?->format('D, M j, Y') ?? '-' }}</div>
                <div class="segment-place">{{ $segment['to'] }} · {{ $segment['to_airport'] ?: $segment['to_city'] }}</div>
            </td>
        </tr>
    </table>
    <div class="segment-details">Aircraft: {{ $segment['aircraft'] }} · Cabin: {{ $segment['cabin'] }} · Fare basis: {{ $segment['fare_basis'] }} · Carry-on: {{ $segment['carry_on'] }} · Checked baggage: {{ $segment['baggage'] }} · Meals: {{ $segment['meals'] }}</div>
</div>
