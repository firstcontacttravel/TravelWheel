<x-mail::message>
# ⚠️ Untickleted Confirmed Booking Alert

**Booking Reference:** {{ $data['uniqueId'] }}  
**Status:** {{ $data['bookingStatus'] }} (Ticketing: {{ $data['ticketStatus'] }})  
**Timestamp:** {{ $data['timestamp']->format('Y-m-d H:i:s') }}

## Flight Details
- **Route:** {{ $data['origin'] }} → {{ $data['destination'] }}
- **Fare Type:** {{ $data['fareType'] }}

## Passengers
| Name | Type | Nationality | Email |
|------|------|-------------|-------|
@foreach($data['passengers'] as $pax)
| {{ $pax['PassengerTitle'] ?? '' }} {{ $pax['PassengerFirstName'] ?? '' }} {{ $pax['PassengerLastName'] ?? '' }} | {{ $pax['PassengerType'] ?? 'ADT' }} | {{ $pax['PassengerNationality'] ?? '—' }} | {{ $pax['EmailAddress'] ?? '—' }} |
@endforeach

## Action Required
This booking has been **confirmed** by the airline but **has NOT been ticketed yet**. This may indicate:
- Pending payment processing
- Ticketing system delay
- Manual ticketing required

**Please investigate and follow up immediately.**

---
<x-mail::footer>
Travelwheel Flight Support System
</x-mail::footer>
</x-mail::message>