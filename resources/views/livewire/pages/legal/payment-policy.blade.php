@php
    $sections = [
        ['id' => 'accepted-payment-methods', 'label' => '1. Accepted Payment Methods'],
        ['id' => 'payment-verification', 'label' => '2. Payment Verification'],
        ['id' => 'fraud-prevention', 'label' => '3. Fraud Prevention & Identity Verification'],
        ['id' => 'payment-authorization', 'label' => '4. Payment Authorization'],
        ['id' => 'currency-conversion', 'label' => '5. Currency Conversion & Exchange Rate Fluctuations'],
        ['id' => 'failed-payments', 'label' => '6. Failed Payments'],
        ['id' => 'chargeback-policy', 'label' => '7. Chargeback Policy'],
        ['id' => 'installment-payments', 'label' => '8. Installment Payments (Pay Small Small)'],
        ['id' => 'late-payment', 'label' => '9. Late Payment Consequences'],
        ['id' => 'default-policy', 'label' => '10. Default Policy'],
        ['id' => 'outstanding-balances', 'label' => '11. Outstanding Balances'],
        ['id' => 'contact', 'label' => '12. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Payment Policy" updated="July 2026" :sections="$sections">

        <p>
            This Payment Policy sets out the terms on which TravelWheel accepts, verifies, and processes payments for
            all Services booked on our platform. It applies to full upfront payments and to installment payments made
            under our Pay Small Small plan, and should be read together with our
            <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            <a href="{{ route('legal.pay-small-small') }}">Pay Small Small Agreement</a>.
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
                specified amount to your chosen payment method, including any applicable service fees, taxes, and
                (where relevant) subsequent installment amounts under a Pay Small Small plan you have enrolled in.
                You confirm that you are authorised to use the payment method provided.
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

        <x-legal.section id="installment-payments" title="8. Installment Payments (Pay Small Small)">
            <p>
                TravelWheel's Pay Small Small plan allows eligible customers to pay for a booking in installments
                rather than in full at the time of booking. Enrollment in Pay Small Small, down payment requirements,
                installment schedules, and consequences of late or missed payments are governed in full by the
                <a href="{{ route('legal.pay-small-small') }}">Pay Small Small Agreement</a>, which forms part of
                these Terms once you enroll in the plan.
            </p>
        </x-legal.section>

        <x-legal.section id="late-payment" title="9. Late Payment Consequences">
            <p>
                Where any payment, including an installment payment, is not received by its due date, TravelWheel may
                apply a late payment charge, suspend processing of your booking (including ticket issuance or travel
                document release), and send payment reminders via email, SMS, or phone. Continued non-payment may
                result in cancellation of the booking in accordance with our default policy below.
            </p>
        </x-legal.section>

        <x-legal.section id="default-policy" title="10. Default Policy">
            <p>
                A customer is considered in default where a scheduled payment remains outstanding beyond the grace
                period communicated by TravelWheel. Upon default, TravelWheel reserves the right to cancel the
                associated booking, apply the cancellation and administrative charges set out in our
                <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>, and retain part or all of
                the amounts already paid to offset the value of services rendered, fees incurred, and any loss suffered
                as a result of the default, before refunding any residual balance.
            </p>
        </x-legal.section>

        <x-legal.section id="outstanding-balances" title="11. Outstanding Balances">
            <p>
                Any outstanding balance owed to TravelWheel, including unpaid installments, chargeback-related
                shortfalls, or unpaid service fees, remains a debt due and payable by the customer. TravelWheel
                reserves the right to withhold future bookings, travel documents, or services until outstanding
                balances are settled, and to pursue lawful recovery of unpaid amounts.
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
