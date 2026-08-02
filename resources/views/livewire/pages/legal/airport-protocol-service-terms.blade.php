@php
    $sections = [
        ['id' => 'overview', 'label' => '1. Overview'],
        ['id' => 'service-scope', 'label' => '2. Service Scope'],
        ['id' => 'meet-and-greet-limitations', 'label' => '3. Meet-and-Greet Limitations'],
        ['id' => 'vip-lounge-availability', 'label' => '4. VIP Lounge Availability'],
        ['id' => 'fast-track-immigration', 'label' => '5. Fast-Track Immigration'],
        ['id' => 'porter-services', 'label' => '6. Porter Services'],
        ['id' => 'security-regulations', 'label' => '7. Security Regulations'],
        ['id' => 'delays-beyond-control', 'label' => '8. Delays Beyond Our Control'],
        ['id' => 'airline-operational-changes', 'label' => '9. Airline Operational Changes'],
        ['id' => 'airport-authority-restrictions', 'label' => '10. Airport Authority Restrictions'],
        ['id' => 'customer-obligations', 'label' => '11. Customer Obligations'],
        ['id' => 'contact', 'label' => '12. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Airport Protocol Service Terms" updated="31 July 2026" :sections="$sections">

        <p>
            These Airport Protocol Service Terms apply to all Airport Protocol and Meet-and-Greet Services booked
            through TravelWheel and should be read together with our
            <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>.
        </p>

        <x-legal.callout variant="info">
            <p>Airport Protocol Services are facilitation services designed to make your airport experience smoother. They do not replace, override, or guarantee any outcome that is subject to the discretion of airlines, immigration officers, or airport authorities.</p>
        </x-legal.callout>

        <x-legal.section id="overview" title="1. Overview">
            <p>
                TravelWheel's Airport Protocol Service provides assistance to travellers at the airport, including
                meet-and-greet, escort through terminal processes, lounge access, and porter assistance, delivered
                directly by TravelWheel staff or through vetted protocol partners operating under applicable airport
                authority permits.
            </p>
        </x-legal.section>

        <x-legal.section id="service-scope" title="2. Service Scope">
            <p>
                The specific inclusions of your Airport Protocol booking (such as meet-and-greet only, meet-and-greet
                with lounge access, or full VIP handling) are as described in the plan you purchased. Any service not
                expressly included in your selected plan is not covered and may be available only as a separately
                priced add-on, subject to availability.
            </p>
        </x-legal.section>

        <x-legal.section id="meet-and-greet-limitations" title="3. Meet-and-Greet Limitations">
            <p>
                Our meet-and-greet representatives will meet you at the agreed point (such as the terminal entrance,
                arrivals hall, or aircraft steps, depending on your plan and airport permissions) and assist with
                escort through the relevant airport processes. Meet-and-greet access to certain restricted zones is
                strictly subject to the rules of the airport authority and may not be available at all airports or for
                all flights, including some domestic or low-cost carrier operations.
            </p>
        </x-legal.section>

        <x-legal.section id="vip-lounge-availability" title="4. VIP Lounge Availability">
            <p>
                Where your plan includes VIP lounge access, this is subject to the operating hours, capacity, and
                admission policy of the specific lounge, which may occasionally deny or limit access due to
                overcrowding, closure, or renovation beyond TravelWheel's control. Where a confirmed lounge is
                unavailable, TravelWheel will make reasonable efforts to arrange access to an alternative lounge of
                comparable standard where possible.
            </p>
        </x-legal.section>

        <x-legal.section id="fast-track-immigration" title="5. Fast-Track Immigration">
            <x-legal.callout variant="warning">
                <p>Fast-track immigration is a facilitation service only. It does not guarantee approval of entry, exit, or any immigration decision, which remains entirely at the discretion of the relevant immigration authority.</p>
            </x-legal.callout>
            <p>
                Fast-track immigration assistance, where included in your plan, is provided subject to the discretion,
                availability, and operating protocols of the airport authority and immigration service on the day of
                travel. TravelWheel cannot compel or guarantee expedited processing, as this remains under the full
                control of government authorities.
            </p>
        </x-legal.section>

        <x-legal.section id="porter-services" title="6. Porter Services">
            <p>
                Where porter (baggage handling) services are included, our representatives will assist with moving
                your checked and hand luggage within the terminal. You remain responsible for declaring the value of,
                and safeguarding, valuable or fragile items, and TravelWheel's liability for loss or damage to baggage
                handled during protocol services is limited to circumstances of proven negligence by our staff.
            </p>
        </x-legal.section>

        <x-legal.section id="security-regulations" title="7. Security Regulations">
            <p>
                All Airport Protocol Services are provided subject to the security regulations, screening procedures,
                and access control policies of the relevant airport and national aviation security authority (such as
                the Nigerian Civil Aviation Authority). TravelWheel staff and partners must comply with these
                regulations at all times, and any request that would require a breach of security protocol will be
                declined.
            </p>
        </x-legal.section>

        <x-legal.section id="delays-beyond-control" title="8. Delays Beyond Our Control">
            <p>
                TravelWheel is not liable for delays or service shortfalls arising from causes beyond our reasonable
                control, including flight delays, extended immigration or customs processing, airport congestion,
                security alerts, or restrictions imposed by the airport authority. Where such delays occur, our
                representatives will remain available to assist you for a reasonable extended period where
                practicable.
            </p>
        </x-legal.section>

        <x-legal.section id="airline-operational-changes" title="9. Airline Operational Changes">
            <p>
                Where your flight is delayed, rescheduled, or cancelled by the airline, TravelWheel will make
                reasonable efforts to adjust your protocol service timing accordingly, provided you notify us of the
                change as soon as you become aware of it. Additional charges may apply where a significant change
                requires redeployment of staff or resources.
            </p>
        </x-legal.section>

        <x-legal.section id="airport-authority-restrictions" title="10. Airport Authority Restrictions">
            <p>
                Access to certain terminal areas, tarmac zones, or VIP facilities is granted or withdrawn at the sole
                discretion of the airport authority and may be restricted at short notice for security, operational,
                or regulatory reasons. TravelWheel will provide the best available alternative assistance in such
                circumstances but is not liable for restrictions imposed by the airport authority.
            </p>
        </x-legal.section>

        <x-legal.section id="customer-obligations" title="11. Customer Obligations">
            <p>
                You are responsible for providing accurate flight details, arrival/departure times, and contact
                information, and for being reachable by phone on the day of travel so that our representatives can
                coordinate with you. Failure to provide accurate information or to be reachable may affect our ability
                to deliver the service as booked, without entitlement to a refund.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="12. Contact Information">
            <p>
                For questions about Airport Protocol Services, please contact us via our
                <a href="{{ route('help') }}">Contact / Help</a> page, quoting your booking reference, or write to us
                at 74, Ayangburen Road, Ikorodu, Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
