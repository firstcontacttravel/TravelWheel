@component('mail::message')
# Protocol Booking Confirmed

Dear **{{ $fullname }}**,

Your payment of **NGN {{ number_format($amount, 2) }}** for the Airport Protocol Service has been successfully processed.

To generate your boarding pass, please click the button below:

@component('mail::button', ['url' => route('air.protocol_payment', ['trans_id' => $trans_id]), 'color' => 'green'])
Generate Boarding Pass
@endcomponent

Alternatively, copy this link into your browser:
{{ route('air.protocol_payment', ['trans_id' => $trans_id]) }}

If you have any questions, please contact our customer service team.

Thank you for choosing TravelWheel.

Best regards,
**TravelWheel Management**
@endcomponent
