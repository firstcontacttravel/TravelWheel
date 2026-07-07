@component('mail::message')
# Airport Lounge Booking Confirmed

Dear **{{ $fullname }}**,

Your airport lounge booking has been received and confirmed.

To generate your lounge access pass, please click the button below:

@component('mail::button', ['url' => route('air.lounge_payment', ['trans_id' => $trans_id]), 'color' => 'green'])
Generate Lounge Pass
@endcomponent

Alternatively, copy this link into your browser:
{{ route('air.lounge_payment', ['trans_id' => $trans_id]) }}

If you have any questions, please contact our customer service team.

Thank you for choosing TravelWheel.

Best regards,
**TravelWheel Management**
@endcomponent
