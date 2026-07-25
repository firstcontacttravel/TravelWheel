@php
    $sections = [
        ['id' => 'overview', 'label' => '1. Overview'],
        ['id' => 'eligibility-enrollment', 'label' => '2. Eligibility & Enrollment'],
        ['id' => 'down-payment', 'label' => '3. Down Payment Requirements'],
        ['id' => 'installment-schedule', 'label' => '4. Installment Schedule'],
        ['id' => 'payment-reminders', 'label' => '5. Payment Reminders'],
        ['id' => 'late-payment-penalties', 'label' => '6. Late Payment Penalties'],
        ['id' => 'default-consequences', 'label' => '7. Default Consequences'],
        ['id' => 'cancellation-before-full-payment', 'label' => '8. Cancellation Before Full Payment'],
        ['id' => 'refund-eligibility', 'label' => '9. Refund Eligibility'],
        ['id' => 'travel-commencement', 'label' => '10. Travel Commencement Requirements'],
        ['id' => 'ownership-of-booking', 'label' => '11. Ownership of Booking Before Completion'],
        ['id' => 'customer-consent', 'label' => '12. Customer Consent'],
        ['id' => 'contact', 'label' => '13. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Pay Small Small Agreement" updated="July 2026" :sections="$sections">

        <p>
            This Pay Small Small Agreement ("Agreement") governs your use of TravelWheel's installment payment plan
            ("Pay Small Small"), which allows you to pay for an eligible booking in scheduled installments rather than
            in full at the time of booking. This Agreement forms part of, and should be read together with, our
            <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a>,
            <a href="{{ route('legal.payment') }}">Payment Policy</a>, and
            <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>. Where there is a conflict
            between this Agreement and those documents on a matter specific to installment payments, this Agreement
            takes precedence.
        </p>

        <x-legal.callout variant="warning">
            <p><strong>Your booking is not fully secured until payment is complete.</strong> Prices, fares, and availability are only guaranteed once TravelWheel confirms receipt of your final installment.</p>
        </x-legal.callout>

        <x-legal.section id="overview" title="1. Overview">
            <p>
                Pay Small Small enables you to spread the cost of a qualifying booking (such as flights, holiday
                packages, or other high-value Services) across a down payment and one or more subsequent installments,
                over a schedule agreed with TravelWheel at the time of enrollment. Not all Services, fares, or dates
                are eligible for Pay Small Small, and eligibility is determined by TravelWheel at its sole discretion.
            </p>
        </x-legal.section>

        <x-legal.section id="eligibility-enrollment" title="2. Eligibility & Enrollment">
            <p>
                To enroll in Pay Small Small, you must provide accurate personal and contact information, agree to
                the specific down payment amount, installment schedule, and total price quoted to you, and expressly
                accept this Agreement. TravelWheel may require identity verification before approving enrollment and
                reserves the right to decline or limit participation in the plan.
            </p>
        </x-legal.section>

        <x-legal.section id="down-payment" title="3. Down Payment Requirements">
            <p>
                A non-refundable-in-part down payment (as disclosed to you at enrollment) is required to reserve your
                booking under Pay Small Small. The down payment secures a price hold on the fare or rate quoted but
                does not constitute a fully confirmed and ticketed booking; full confirmation only occurs once all
                installments have been paid in accordance with Section 11 (Ownership of Booking Before Completion).
            </p>
        </x-legal.section>

        <x-legal.section id="installment-schedule" title="4. Installment Schedule">
            <p>
                The number, amount, and due dates of installments will be set out in your Pay Small Small payment
                schedule at the time of enrollment, and are calculated to ensure full payment is received before your
                intended travel date. You agree to pay each installment on or before its due date using an accepted
                payment method. TravelWheel reserves the right to require a shorter or accelerated schedule where a
                booking is made close to the travel date.
            </p>
        </x-legal.section>

        <x-legal.section id="payment-reminders" title="5. Payment Reminders">
            <p>
                TravelWheel will send payment reminders ahead of each installment due date via email, SMS, or
                WhatsApp, using the contact details you provided at enrollment. Reminders are provided as a courtesy;
                the responsibility to track and meet each due date without a reminder remains yours at all times.
            </p>
        </x-legal.section>

        <x-legal.section id="late-payment-penalties" title="6. Late Payment Penalties">
            <p>
                Where an installment is not received by its due date, TravelWheel may apply a late payment charge as
                disclosed in your payment schedule, and may place your booking on hold until the outstanding
                installment (plus any late fee) is settled. Continued delay may result in a revised (typically higher)
                fare or rate being applied if the original price hold expires, at TravelWheel's discretion.
            </p>
        </x-legal.section>

        <x-legal.section id="default-consequences" title="7. Default Consequences">
            <x-legal.callout variant="danger">
                <p>Defaulting on your installment schedule can result in cancellation of your booking and forfeiture of part or all of the amounts already paid.</p>
            </x-legal.callout>
            <p>
                You are considered in default if an installment remains unpaid after the grace period communicated by
                TravelWheel (typically stated in your payment schedule or reminder communications). Upon default,
                TravelWheel may cancel the associated booking, release the reserved fare or rate back to the market,
                and retain part or all of the amounts you have already paid to cover TravelWheel's administrative
                costs, any non-refundable Supplier charges already incurred, and the value of the price hold provided,
                before considering any refund of the residual balance under Section 9 below.
            </p>
        </x-legal.section>

        <x-legal.section id="cancellation-before-full-payment" title="8. Cancellation Before Full Payment">
            <p>
                You may request to cancel a Pay Small Small booking at any time before full payment is completed by
                contacting TravelWheel customer support. Cancellation requests are processed in accordance with
                Section 9 (Refund Eligibility) below and our general
                <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>, adjusted to reflect the
                installment nature of the booking.
            </p>
        </x-legal.section>

        <x-legal.section id="refund-eligibility" title="9. Refund Eligibility">
            <p>
                The down payment made under Pay Small Small is generally non-refundable, as it secures a price hold
                with the relevant Supplier and compensates TravelWheel for administrative processing. Where you have
                paid one or more installments beyond the down payment and cancel before full payment, TravelWheel will
                refund amounts paid in excess of the non-refundable down payment and any incurred Supplier or
                administrative charges, less applicable service fees, in accordance with the timelines set out in our
                <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>.
            </p>
        </x-legal.section>

        <x-legal.section id="travel-commencement" title="10. Travel Commencement Requirements">
            <p>
                You may not commence travel, and TravelWheel will not release final tickets, hotel vouchers, or other
                travel documents, until your Pay Small Small plan has been paid in full. Any attempt to commence
                travel on a booking with an outstanding balance will be treated as a material breach of this
                Agreement.
            </p>
        </x-legal.section>

        <x-legal.section id="ownership-of-booking" title="11. Ownership of Booking Before Completion">
            <p>
                Until your Pay Small Small plan is paid in full, the booking remains provisional and is held by
                TravelWheel on your behalf; no ticket, confirmed reservation, or right to travel vests in you. Full,
                unconditional ownership and confirmation of the booking, including issuance of tickets and travel
                documents, occurs only upon receipt and verification of the final installment payment.
            </p>
        </x-legal.section>

        <x-legal.section id="customer-consent" title="12. Customer Consent">
            <p>
                By enrolling in Pay Small Small and making your down payment, you expressly consent to: (a) the
                installment schedule, amounts, and due dates presented to you; (b) receiving payment reminder
                communications; (c) the late payment and default consequences set out in this Agreement; and (d) the
                refund treatment described above in the event of cancellation or default. This consent is given
                freely and constitutes your acceptance of a binding installment payment arrangement with TravelWheel.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="13. Contact Information">
            <p>
                For questions about your Pay Small Small plan, please contact us via our
                <a href="{{ route('help') }}">Contact / Help</a> page, quoting your booking reference, or write to us
                at 74, Ayangburen Road, Ikorodu, Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
