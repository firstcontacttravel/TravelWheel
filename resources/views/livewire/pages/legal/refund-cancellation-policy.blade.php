@php
    $sections = [
        ['id' => 'general-principle', 'label' => '1. General Principle'],
        ['id' => 'airline-refund-rules', 'label' => '2. Airline Refund Rules'],
        ['id' => 'hotel-refund-policies', 'label' => '3. Hotel Refund Policies'],
        ['id' => 'supplier-specific-conditions', 'label' => '4. Supplier-Specific Conditions'],
        ['id' => 'non-refundable-bookings', 'label' => '5. Non-Refundable Bookings'],
        ['id' => 'partial-refunds', 'label' => '6. Partial Refunds'],
        ['id' => 'service-fees', 'label' => '7. Service Fees & Administrative Charges'],
        ['id' => 'processing-timelines', 'label' => '8. Processing Timelines'],
        ['id' => 'how-to-request', 'label' => '9. How to Request a Cancellation or Refund'],
        ['id' => 'pay-small-small-cancellations', 'label' => '10. Cancellations Under Pay Small Small'],
        ['id' => 'contact', 'label' => '11. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Refund & Cancellation Policy" updated="July 2026" :sections="$sections">

        <p>
            This Refund &amp; Cancellation Policy explains how cancellations, changes, and refunds are handled for
            bookings made through TravelWheel, across Flight Booking, Hotel Reservations, Holiday &amp; Tour Packages,
            Airport Protocol, Airport Transfers, Travel Insurance, Visa Assistance, and other Services. It should be
            read together with our <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            <a href="{{ route('legal.payment') }}">Payment Policy</a>.
        </p>

        <x-legal.callout variant="warning">
            <p>Refund eligibility, timelines, and fees are ultimately determined by the airline, hotel, insurer, or other Third-Party Supplier's own policy, and not solely by TravelWheel. Always review the fare or rate rules shown at the time of booking.</p>
        </x-legal.callout>

        <x-legal.section id="general-principle" title="1. General Principle">
            <p>
                TravelWheel facilitates cancellation and refund requests on your behalf with the relevant Third-Party
                Supplier. Because the underlying travel product (flight ticket, hotel room, insurance policy, tour
                package) is provided by that Supplier, refunds are subject to the Supplier's terms and are not
                guaranteed simply because a cancellation request has been submitted.
            </p>
        </x-legal.section>

        <x-legal.section id="airline-refund-rules" title="2. Airline Refund Rules">
            <p>
                Flight refunds are governed by the fare rules of the operating airline at the time of ticket issuance.
                Some fares are fully refundable, some are refundable subject to a penalty, and others are entirely
                non-refundable. Where a refund is approved by the airline, TravelWheel will process it to you upon
                receipt of the funds from the airline, less any applicable TravelWheel service fee. Airline-initiated
                cancellations or significant schedule changes may entitle you to a refund or rebooking in accordance
                with the airline's own policy and applicable aviation consumer protection regulations.
            </p>
        </x-legal.section>

        <x-legal.section id="hotel-refund-policies" title="3. Hotel Refund Policies">
            <p>
                Hotel bookings are subject to the cancellation policy displayed at the time of booking, which may
                range from "free cancellation" up to a specified date, to "non-refundable" promotional rates. Where a
                booking is cancelled outside the free cancellation window, the hotel may charge a cancellation fee up
                to the full value of the first night, or the entire stay, depending on the rate booked.
            </p>
        </x-legal.section>

        <x-legal.section id="supplier-specific-conditions" title="4. Supplier-Specific Conditions">
            <p>
                Holiday &amp; Tour Packages, Airport Protocol services, Airport Transfers, and Travel Insurance
                policies each carry their own cancellation windows and refund conditions, which will be communicated
                to you at the point of booking or set out in the relevant product-specific terms (see our
                <a href="{{ route('legal.protocol-terms') }}">Airport Protocol Service Terms</a> and
                <a href="{{ route('legal.insurance-terms') }}">Travel Insurance Terms</a>). Where a Service is bundled
                (for example, a package including flights and hotel), each component may be subject to a different
                cancellation policy.
            </p>
        </x-legal.section>

        <x-legal.section id="non-refundable-bookings" title="5. Non-Refundable Bookings">
            <p>
                Certain fares, rates, and promotional offers are explicitly marked as non-refundable at the time of
                booking. By proceeding with payment for a non-refundable booking, you acknowledge and accept that no
                refund will be due in the event of cancellation, except where required by law or where the Supplier
                itself cancels or significantly alters the booking.
            </p>
        </x-legal.section>

        <x-legal.section id="partial-refunds" title="6. Partial Refunds">
            <p>
                Where a booking includes multiple components (for example, flights, hotel, and protocol services) and
                only some components are cancelled or refundable, TravelWheel will process a partial refund
                reflecting only the refundable portion, less applicable service fees and any Supplier-imposed
                penalties on the retained components.
            </p>
        </x-legal.section>

        <x-legal.section id="service-fees" title="7. Service Fees & Administrative Charges">
            <p>
                In addition to any Supplier cancellation or change penalty, TravelWheel applies a non-refundable
                administrative service fee to cover the cost of processing cancellations, refund requests, and
                rebookings. The applicable service fee will be disclosed to you before you confirm a cancellation or
                refund request.
            </p>
        </x-legal.section>

        <x-legal.section id="processing-timelines" title="8. Processing Timelines">
            <p>
                Once a refund is approved by the relevant Supplier, TravelWheel will process the refund to your
                original payment method or preferred bank account within a reasonable period, typically between
                7 and 30 business days, depending on the Supplier's own processing time and the payment channel used.
                Refund timelines communicated by TravelWheel are estimates and may vary based on factors outside our
                control, including airline and bank processing schedules.
            </p>
        </x-legal.section>

        <x-legal.section id="how-to-request" title="9. How to Request a Cancellation or Refund">
            <p>
                To request a cancellation or refund, please contact TravelWheel customer support through our
                <a href="{{ route('help') }}">Contact / Help</a> page, quoting your booking reference. We will confirm
                the applicable cancellation charges and expected refund amount, if any, before proceeding, and will
                only cancel a booking upon your written confirmation.
            </p>
        </x-legal.section>

        <x-legal.section id="pay-small-small-cancellations" title="10. Cancellations Under Pay Small Small">
            <p>
                If you cancel a booking made under our Pay Small Small installment plan before completing full
                payment, refund eligibility on amounts already paid is subject to the cancellation and refund terms
                of the <a href="{{ route('legal.pay-small-small') }}">Pay Small Small Agreement</a>, which take
                precedence over this Policy on matters specific to installment bookings.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="11. Contact Information">
            <p>
                For all cancellation and refund enquiries, please reach us via our
                <a href="{{ route('help') }}">Contact / Help</a> page or write to us at 74, Ayangburen Road, Ikorodu,
                Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
