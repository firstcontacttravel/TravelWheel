@php
    $sections = [
        ['id' => 'overview', 'label' => '1. Overview'],
        ['id' => 'third-party-insurer', 'label' => '2. Third-Party Insurer Responsibility'],
        ['id' => 'policy-issuance', 'label' => '3. Policy Issuance'],
        ['id' => 'coverage-limitations', 'label' => '4. Coverage Limitations'],
        ['id' => 'exclusions', 'label' => '5. Exclusions'],
        ['id' => 'customer-disclosure', 'label' => '6. Customer Disclosure Obligations'],
        ['id' => 'claims-process', 'label' => '7. Claims Process'],
        ['id' => 'premiums-refunds', 'label' => '8. Premiums & Refunds'],
        ['id' => 'travelwheel-role', 'label' => "9. TravelWheel's Role"],
        ['id' => 'contact', 'label' => '10. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Travel Insurance Terms" updated="July 2026" :sections="$sections">

        <p>
            These Travel Insurance Terms apply whenever you purchase a travel insurance policy through TravelWheel.
            They should be read together with our <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            the specific policy wording, schedule, and certificate issued to you by the underwriting insurer.
        </p>

        <x-legal.callout variant="danger">
            <p><strong>TravelWheel is an intermediary, not the insurer.</strong> Your travel insurance policy is underwritten and administered entirely by a licensed third-party insurance company. Coverage, claims decisions, and payouts are the sole responsibility of that insurer.</p>
        </x-legal.callout>

        <x-legal.section id="overview" title="1. Overview">
            <p>
                TravelWheel facilitates the sale of travel insurance policies underwritten by licensed insurance
                companies regulated by the National Insurance Commission (NAICOM) or, where applicable, an equivalent
                foreign regulator. When you purchase travel insurance through TravelWheel, you are entering into a
                contract of insurance directly with the underwriting insurer, and TravelWheel's role is limited to
                facilitating your application, payment, and policy issuance.
            </p>
        </x-legal.section>

        <x-legal.section id="third-party-insurer" title="2. Third-Party Insurer Responsibility">
            <p>
                The insurer named on your policy schedule is solely responsible for underwriting your risk,
                determining coverage, assessing and paying claims, and handling any dispute arising from your policy.
                TravelWheel does not underwrite risk, does not guarantee the approval of any claim, and is not a party
                to the contract of insurance between you and the insurer.
            </p>
        </x-legal.section>

        <x-legal.section id="policy-issuance" title="3. Policy Issuance">
            <p>
                Your travel insurance policy is issued based on the information you provide during the application
                process, including your age, destination, travel dates, and any declared pre-existing medical
                conditions. Your policy schedule and certificate, once issued, constitute the definitive statement of
                your coverage, limits, and conditions, and prevail over any marketing description on our website in
                the event of any inconsistency.
            </p>
        </x-legal.section>

        <x-legal.section id="coverage-limitations" title="4. Coverage Limitations">
            <p>
                All travel insurance policies are subject to coverage limits, sub-limits, deductibles/excesses, and
                geographic or activity restrictions set out in the policy wording. Common limitations include capped
                amounts for medical expenses, baggage loss, and trip cancellation, as well as restrictions on coverage
                for hazardous activities, pre-existing medical conditions, and travel to certain high-risk
                destinations. You are responsible for reviewing these limitations before purchase.
            </p>
        </x-legal.section>

        <x-legal.section id="exclusions" title="5. Exclusions">
            <p>
                Policies typically exclude claims arising from, among other things: undisclosed pre-existing medical
                conditions; travel undertaken against government or medical advice; self-inflicted injury or
                intoxication; participation in extreme sports not specifically covered; war, terrorism, or civil
                unrest (unless specifically included); and losses arising from a failure to take reasonable care of
                personal belongings. The complete list of exclusions is set out in your policy wording.
            </p>
        </x-legal.section>

        <x-legal.section id="customer-disclosure" title="6. Customer Disclosure Obligations">
            <x-legal.callout variant="warning">
                <p>Failure to disclose a material fact, such as a pre-existing medical condition, may entitle the insurer to reject a claim or void your policy entirely.</p>
            </x-legal.callout>
            <p>
                You are required to provide complete and accurate information when applying for travel insurance,
                including any pre-existing medical conditions, planned activities, and prior travel insurance claims
                history. Non-disclosure or misrepresentation of material information may result in a claim being
                declined or the policy being voided by the insurer.
            </p>
        </x-legal.section>

        <x-legal.section id="claims-process" title="7. Claims Process">
            <p>
                In the event of a loss, illness, or incident covered by your policy, you must notify the insurer (and,
                where applicable, any emergency assistance number provided) as soon as reasonably possible and follow
                the claims procedure set out in your policy documents, which typically requires supporting evidence
                such as medical reports, police reports, or receipts. TravelWheel can assist in directing you to the
                correct insurer contact but does not process, assess, or approve claims.
            </p>
        </x-legal.section>

        <x-legal.section id="premiums-refunds" title="8. Premiums & Refunds">
            <p>
                Insurance premiums are generally non-refundable once a policy has been issued and coverage has
                commenced, except where the insurer's own policy permits a cooling-off period or cancellation prior to
                the start of your trip. Any refund of premium is processed in accordance with the insurer's terms, and
                TravelWheel will pass on refunds received from the insurer, less any applicable service fee.
            </p>
        </x-legal.section>

        <x-legal.section id="travelwheel-role" title="9. TravelWheel's Role">
            <p>
                TravelWheel's responsibility is limited to accurately transmitting your application details to the
                insurer, processing your premium payment, and delivering your policy documents. TravelWheel is not
                liable for the insurer's underwriting decisions, claims outcomes, payout amounts, or solvency.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="10. Contact Information">
            <p>
                For questions about purchasing travel insurance through TravelWheel, please contact us via our
                <a href="{{ route('help') }}">Contact / Help</a> page. For claims and policy-specific queries, please
                use the contact details provided on your policy schedule and certificate.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
