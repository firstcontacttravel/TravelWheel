{{-- resources/views/mail/eticket.blade.php --}}
@php
    $passengers = $passengers ?? \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $awaiting = $awaitingSupplierTicket ?? false;
    $routeText = trim((string) ($booking->route ?? ''));
    $totalLabel = ($currencySymbol ?? '').number_format((float) ($totalAmount ?? $booking->total_price ?? 0), 2);
    $cabinLabel = $cabin ?? \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);

    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
    $mono = "'SFMono-Regular',Consolas,'Liberation Mono',Menlo,Courier,monospace";

    $intro = $isTicketed
        ? 'Your ticket has been issued and a PDF copy is attached to this email.'
        : ($awaiting
            ? 'Your seat is reserved and your payment is complete. The airline issues the ticket separately — we will email it the moment it comes through.'
            : 'Your seat is reserved and ticketing is in progress. We will email your ticket shortly.');
@endphp

<x-mail.layout
    :title="'Your e-ticket — '.$bookingRef"
    :preheader="($isTicketed ? 'Ticket issued' : 'Booking confirmed').' · '.($routeText ?: 'Your trip').' · '.$bookingRef"
    eyebrow="Electronic ticket"
    :heading="$isTicketed ? 'Your e-ticket is ready' : 'Your booking is confirmed'"
    :intro="'Hi '.$firstName.', '.lcfirst($intro)"
    :badge="$isTicketed ? 'Ticketed' : 'Confirmed'"
    :tone="$isTicketed ? 'success' : 'warning'"
>

    <x-mail.panel
        label="Booking reference"
        :value="$bookingRef"
        mono
        alt-label="Total paid"
        :alt-value="$totalLabel"
        :alt-sublabel="$cabinLabel"
    />

    <x-mail.heading>Trip overview</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Route">{{ $routeText ?: '—' }}</x-mail.row>
        <x-mail.row label="Trip type">{{ $tripLabel ?? 'Flight' }}</x-mail.row>
        <x-mail.row label="Airline">{{ $airline ?: ($booking->airline ?? '—') }}</x-mail.row>
        <x-mail.row label="Airline reference (PNR)" strong>{{ $ticketPNR ?? '—' }}</x-mail.row>
    </x-mail.rows>

    @if (! empty($passengers))
        <x-mail.heading>{{ count($passengers) === 1 ? 'Traveller' : 'Travellers' }}</x-mail.heading>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $c['line'] }};border-radius:10px;border-collapse:separate;border-spacing:0;margin-bottom:20px;">
            <tr>
                <th align="left" style="background-color:{{ $c['panel'] }};padding:10px 14px;font-family:{{ $font }};font-size:11.5px;font-weight:600;color:{{ $c['muted'] }};border-bottom:1px solid {{ $c['line'] }};border-radius:10px 0 0 0;">Name</th>
                <th align="left" style="background-color:{{ $c['panel'] }};padding:10px 14px;font-family:{{ $font }};font-size:11.5px;font-weight:600;color:{{ $c['muted'] }};border-bottom:1px solid {{ $c['line'] }};">Type</th>
                <th align="left" style="background-color:{{ $c['panel'] }};padding:10px 14px;font-family:{{ $font }};font-size:11.5px;font-weight:600;color:{{ $c['muted'] }};border-bottom:1px solid {{ $c['line'] }};border-radius:0 10px 0 0;">Ticket number</th>
            </tr>
            @foreach ($passengers as $pax)
                <tr>
                    <td style="padding:12px 14px;font-family:{{ $font }};font-size:13px;font-weight:600;color:{{ $c['ink'] }};{{ $loop->first ? '' : 'border-top:1px solid '.$c['line_soft'].';' }}">{{ trim(($pax['title'] ?? '').' '.strtoupper($pax['first_name'] ?? '').' '.strtoupper($pax['last_name'] ?? '')) }}</td>
                    <td style="padding:12px 14px;font-family:{{ $font }};font-size:13px;color:{{ $c['muted'] }};{{ $loop->first ? '' : 'border-top:1px solid '.$c['line_soft'].';' }}">{{ match ($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }}</td>
                    <td style="padding:12px 14px;font-family:{{ $mono }};font-size:13px;color:{{ $pax['eticket'] ?? false ? $c['brand'] : $c['muted'] }};{{ $loop->first ? '' : 'border-top:1px solid '.$c['line_soft'].';' }}">{{ $pax['eticket'] ?? ($isTicketed ? '—' : 'Pending') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($isTicketed)
        <x-mail.callout tone="neutral" title="Before you travel">
            Arrive 2 hours before domestic flights and 3 hours before international ones. Bring valid ID or a passport,
            and check that the name on it matches the name on your ticket exactly.
        </x-mail.callout>
    @else
        <x-mail.callout tone="warning" title="What happens next">
            {{ $awaiting
                ? 'Nothing is needed from you. As soon as the airline issues your ticket we will email it to this address.'
                : 'Nothing is needed from you. We are completing ticketing now and will email your ticket shortly.' }}
        </x-mail.callout>
    @endif

    <div style="font-family:{{ $font }};font-size:13px;line-height:1.65;color:{{ $c['muted'] }};">
        Quote your booking reference <strong style="font-family:{{ $mono }};color:{{ $c['ink'] }};">{{ $bookingRef }}</strong> whenever you contact us about this trip.
    </div>

</x-mail.layout>
