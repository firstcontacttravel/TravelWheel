@component('mail::message')
# Thank You for Your Booking

Dear **{{ $fullname }}**,

Thank you for booking your airport lounge access with TravelWheel! We're delighted to help make your travel more comfortable.

Your booking has been received and confirmed. Your lounge pass will be sent to you shortly — one of our personnel will reach out to you directly to finalise the details of your lounge access.

@component('mail::button', ['url' => route('air.lounge_payment', ['trans_id' => $trans_id]), 'color' => 'green'])
View Booking Details
@endcomponent

If you have any questions in the meantime, please don't hesitate to contact our customer service team.

Thank you for choosing TravelWheel.

Best regards,
**TravelWheel Management**
@endcomponent
