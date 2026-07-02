@component('mail::message')

Dear <strong>{{ $shipment->fullname }}</strong>,

Please find attached your shipment details document.

Your Shipment ID is **{{ $shipment->shipping_id }}**.

Thank you for choosing our services.

---

<small>Thank you for choosing TravelWheel.<br>
We look forward to serving you.</small>

<small>
Best regards,<br>
Management<br>
TravelWheel
</small>
@endcomponent
