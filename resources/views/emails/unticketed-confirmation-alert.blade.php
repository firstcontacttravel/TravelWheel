<x-mail::message>
@php($passengers = \App\Support\FlightDisplay::passengers($data['passengers'] ?? []))
# Unticketed Confirmed Booking Alert

**Booking Reference:** {{ $data['uniqueId'] }}
**Status:** {{ $data['bookingStatus'] }} (Ticketing: {{ $data['ticketStatus'] }})
**Timestamp:** {{ $data['timestamp']->timezone('Africa/Lagos')->format('Y-m-d H:i:s') }}

## Flight Details
- **Route:** {{ $data['origin'] }} -> {{ $data['destination'] }}
- **Fare Type:** {{ $data['fareType'] }}

## Passengers
| Name | Type | Nationality | Email |
|------|------|-------------|-------|
@foreach($passengers as $pax)
| {{ $pax['title'] ?? '' }} {{ $pax['first_name'] ?? '' }} {{ $pax['last_name'] ?? '' }} | {{ $pax['type'] ?? 'ADT' }} | {{ $pax['nationality'] ?? '-' }} | {{ $pax['email'] ?? '-' }} |
@endforeach

## Action Required
This booking has been **confirmed** by the airline but has **not been ticketed yet**. This may indicate:
- Pending payment processing
- Ticketing system delay
- Manual ticketing required

**Please investigate and follow up immediately.**

---
<x-mail::footer>
Travelwheel Flight Support System
</x-mail::footer>
</x-mail::message>
