@php
    $sections = [
        ['id' => 'accepted-payment-methods', 'label' => '1. Accepted Payment Methods'],
        ['id' => 'payment-verification', 'label' => '2. Payment Verification'],
        ['id' => 'fraud-prevention', 'label' => '3. Fraud Prevention & Identity Verification'],
        ['id' => 'payment-authorization', 'label' => '4. Payment Authorization'],
        ['id' => 'currency-conversion', 'label' => '5. Currency Conversion & Exchange Rate Fluctuations'],
        ['id' => 'failed-payments', 'label' => '6. Failed Payments'],
        ['id' => 'chargeback-policy', 'label' => '7. Chargeback Policy'],
        ['id' => 'travelflex-financing', 'label' => '8. TravelFlex Financing'],
        ['id' => 'late-payment', 'label' => '9. Late Loan Instalments'],
        ['id' => 'default-policy', 'label' => '10. Loan Default and Booking Effects'],
        ['id' => 'outstanding-balances', 'label' => '11. Outstanding Balances'],
        ['id' => 'contact', 'label' => '12. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Payment Policy" updated="31 July 2026" :sections="$sections">

        <p>
            This Payment Policy sets out the terms on which TravelWheel accepts, verifies, and processes payments for
            all Services booked on our platform. It also explains TravelWheel's limited payment-facilitation role where
            a customer applies for Fast Credit financing through TravelFlex. It should be read together with our
            <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            <a href="{{ route('legal.pay-small-small') }}">TravelFlex Fast Credit Loan Agreement</a>.
        </p>

        <x-legal.section id="accepted-payment-methods" title="1. Accepted Payment Methods">
            <p>
                TravelWheel accepts payments via debit/credit card, bank transfer, and other payment channels made
                available through our licensed third-party payment processors from time to time. All online card and
                transfer payments are processed through PCI-DSS-compliant payment gateways; TravelWheel does not
                store your full card details.
            </p>
        </x-legal.section>

        <x-legal.section id="payment-verification" title="2. Payment Verification">
            <p>
                All payments are subject to verification before a booking is confirmed or a ticket is issued. This may
                include confirmation of successful debit from your card or bank account, reconciliation with our
                payment processor's records, and, in some cases, direct confirmation from your bank. TravelWheel
                reserves the right to place a booking on hold pending successful verification of payment.
            </p>
        </x-legal.section>

        <x-legal.section id="fraud-prevention" title="3. Fraud Prevention & Identity Verification">
            <x-legal.callout variant="info">
                <p>To protect our customers and our platform, TravelWheel applies fraud-screening checks to bookings and payments. This may occasionally result in delays while additional verification is carried out.</p>
            </x-legal.callout>
            <p>
                We may request additional identity or payment verification documents, such as a valid ID, proof of
                address, or a bank statement, particularly for high-value bookings, corporate accounts, or where a
                transaction is flagged by our fraud-detection systems. TravelWheel reserves the right to decline,
                delay, or reverse any transaction reasonably suspected to be fraudulent, and to report suspected
                fraud to the relevant financial institution or law enforcement authority.
            </p>
        </x-legal.section>

        <x-legal.section id="payment-authorization" title="4. Payment Authorization">
            <p>
                By submitting payment details, you authorise TravelWheel and our payment processors to charge the
                specified booking or upfront amount to your chosen payment method, including any applicable service
                fees and taxes. Payroll deductions, recurring loan instalments, or other finance collections require
                the separate authorisation stated in the Fast Credit loan agreement. You confirm that you are
                authorised to use the payment method provided.
            </p>
        </x-legal.section>

        <x-legal.section id="currency-conversion" title="5. Currency Conversion & Exchange Rate Fluctuations">
            <p>
                Certain fares and supplier rates are sourced in foreign currency (such as US Dollars) and are
                converted to Nigerian Naira (NGN) using an exchange rate determined by TravelWheel at the time of
                booking, which may be periodically updated to reflect prevailing market conditions. The Naira price
                displayed and charged at the point of payment is final for that transaction. Where a booking is
                cancelled, amended, or refunded, any applicable refund will be calculated based on the exchange rate
                and amount originally charged, and TravelWheel is not liable for any loss arising from subsequent
                exchange rate fluctuations between the date of payment and the date of refund.
            </p>
        </x-legal.section>

        <x-legal.section id="failed-payments" title="6. Failed Payments">
            <p>
                Where a payment fails, is declined, or is not successfully verified, the associated booking will not
                be confirmed and any fare or rate quoted is not guaranteed and may change on retry. If funds are
                debited from your account for a failed transaction that TravelWheel did not successfully process, we
                will investigate and, where confirmed, arrange for a reversal through our payment processor within a
                reasonable timeframe.
            </p>
        </x-legal.section>

        <x-legal.section id="chargeback-policy" title="7. Chargeback Policy">
            <p>
                If you dispute a charge directly with your bank or card issuer ("chargeback") without first contacting
                TravelWheel to resolve the matter, we reserve the right to suspend your account, contest the
                chargeback with supporting evidence (including booking confirmations, delivery of tickets, and usage
                records), and recover any costs, penalties, or losses arising from an unwarranted or fraudulent
                chargeback. We encourage customers to contact our support team to resolve billing concerns before
                initiating a chargeback.
            </p>
        </x-legal.section>

        <x-legal.section id="travelflex-financing" title="8. TravelFlex Financing">
            <p>
                TravelFlex allows an eligible customer to apply to Fast Credit for financing connected with a
                TravelWheel booking. Fast Credit is the lender and is solely responsible for credit assessment,
                approval, final finance terms, loan administration, and recovery. TravelWheel may transmit application
                information, communicate status updates, facilitate a designated payment channel, and apply verified
                funds to the travel booking. These activities do not make TravelWheel the lender, a co-lender, or a
                guarantor. The financing relationship is governed by the
                <a href="{{ route('legal.pay-small-small') }}">TravelFlex Fast Credit Loan Agreement</a>.
            </p>
        </x-legal.section>

        <x-legal.section id="late-payment" title="9. Late Loan Instalments">
            <p>
                Fast Credit determines and administers the consequences of a late or missed loan instalment, including
                any lawful interest, charge, reporting, collection, or revised repayment arrangement. TravelWheel does
                not impose finance late fees. TravelWheel may send an administrative reminder or relay information
                received from Fast Credit, but this does not transfer Fast Credit's lending responsibilities to
                TravelWheel. A separate amount owed directly for the travel booking remains subject to TravelWheel's
                booking and payment terms.
            </p>
        </x-legal.section>

        <x-legal.section id="default-policy" title="10. Loan Default and Booking Effects">
            <p>
                Fast Credit determines whether the Borrower is in default under the loan agreement and is responsible
                for any finance-related enforcement or recovery. TravelWheel may cancel, withhold, or amend the related
                travel booking only where a booking amount due to TravelWheel has not been received, the airline hold
                has expired, a Supplier condition requires cancellation, or another right arises under TravelWheel's
                <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> or
                <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>. TravelWheel will not act as
                Fast Credit's debt collector merely because the loan relates to a TravelWheel booking.
            </p>
        </x-legal.section>

        <x-legal.section id="outstanding-balances" title="11. Outstanding Balances">
            <p>
                An outstanding amount owed to TravelWheel for a booking, chargeback-related shortfall, or TravelWheel
                service fee remains payable to TravelWheel. An outstanding loan principal, interest amount, insurance
                charge, management fee, penalty, or recovery cost under TravelFlex is owed to Fast Credit and is
                governed by the Fast Credit loan agreement. TravelWheel may withhold future bookings or services only
                in relation to amounts properly owed to TravelWheel or where otherwise permitted by the applicable
                booking terms and law.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="12. Contact Information">
            <p>
                For questions regarding payments, please contact us via our
                <a href="{{ route('help') }}">Contact / Help</a> page or write to us at 74, Ayangburen Road, Ikorodu,
                Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
