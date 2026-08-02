@php
    $sections = [
        ['id' => 'general-disclaimer', 'label' => '1. General Disclaimer'],
        ['id' => 'airline-operational-risks', 'label' => '2. Airline Operational Risks'],
        ['id' => 'weather-disruptions', 'label' => '3. Weather Disruptions'],
        ['id' => 'government-travel-restrictions', 'label' => '4. Government Travel Restrictions'],
        ['id' => 'visa-approval', 'label' => '5. Visa Approval Not Guaranteed'],
        ['id' => 'hotel-overbooking', 'label' => '6. Hotel Overbooking'],
        ['id' => 'supplier-failures', 'label' => '7. Supplier Failures'],
        ['id' => 'third-party-services', 'label' => '8. Third-Party Services'],
        ['id' => 'exchange-rate-changes', 'label' => '9. Exchange Rate Changes'],
        ['id' => 'technical-errors', 'label' => '10. Technical Errors'],
        ['id' => 'website-availability', 'label' => '11. Website Availability'],
        ['id' => 'contact', 'label' => '12. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Disclaimer" updated="31 July 2026" :sections="$sections">

        <p>
            This Disclaimer applies to your use of the TravelWheel website, mobile platforms, and Services, and
            should be read together with our <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a>, in
            particular the Limitation of Liability and Force Majeure provisions.
        </p>

        <x-legal.callout variant="info">
            <p>Travel involves inherent risks and factors outside TravelWheel's control. This Disclaimer highlights the main categories of risk you accept by booking travel Services through our platform.</p>
        </x-legal.callout>

        <x-legal.section id="general-disclaimer" title="1. General Disclaimer">
            <p>
                TravelWheel acts as a booking facilitator and intermediary between you and independent Third-Party
                Suppliers, including airlines, hotels, insurers, and ground transport providers. While we take
                reasonable care in selecting and working with reputable Suppliers, TravelWheel does not control, and
                is not liable for, the acts, omissions, service standards, or financial stability of those Suppliers.
            </p>
        </x-legal.section>

        <x-legal.section id="airline-operational-risks" title="2. Airline Operational Risks">
            <p>
                Flights are subject to operational risks entirely outside TravelWheel's control, including delays,
                diversions, equipment changes, crew availability, and cancellations. TravelWheel is not liable for any
                loss or inconvenience arising from an airline's operational decisions.
            </p>
        </x-legal.section>

        <x-legal.section id="weather-disruptions" title="3. Weather Disruptions">
            <p>
                Adverse weather conditions, including storms, fog, and other natural events, may cause flight delays,
                cancellations, or airport closures. TravelWheel is not responsible for losses arising from
                weather-related disruptions, which are governed by the applicable Supplier's own policies.
            </p>
        </x-legal.section>

        <x-legal.section id="government-travel-restrictions" title="4. Government Travel Restrictions">
            <p>
                Governments may impose travel restrictions, entry bans, quarantine requirements, or advisories at
                short notice, for reasons including public health, security, or diplomatic considerations. TravelWheel
                is not liable for losses arising from such government-imposed restrictions, whether announced before
                or after your booking is made.
            </p>
        </x-legal.section>

        <x-legal.section id="visa-approval" title="5. Visa Approval Not Guaranteed">
            <p>
                TravelWheel's Visa Assistance service supports the preparation and submission of your application, but
                approval of any visa or travel authorisation rests solely with the relevant embassy, consulate, or
                immigration authority. TravelWheel does not guarantee visa approval and is not liable for a refusal,
                delay, or condition imposed by that authority.
            </p>
        </x-legal.section>

        <x-legal.section id="hotel-overbooking" title="6. Hotel Overbooking">
            <p>
                On rare occasions, a hotel may be unable to honour a confirmed reservation due to overbooking or
                operational issues. Where this occurs, TravelWheel will assist you in liaising with the hotel for
                comparable alternative accommodation or a refund, but TravelWheel is not liable for the hotel's
                failure to honour a reservation it has confirmed.
            </p>
        </x-legal.section>

        <x-legal.section id="supplier-failures" title="7. Supplier Failures">
            <p>
                In the event that a Third-Party Supplier ceases operations, becomes insolvent, or otherwise fails to
                deliver a booked Service, TravelWheel will make reasonable efforts to assist affected customers in
                seeking recourse from the Supplier, but TravelWheel is not liable for losses arising from a Supplier's
                insolvency or business failure, except to the extent required by applicable Nigerian law.
            </p>
        </x-legal.section>

        <x-legal.section id="third-party-services" title="8. Third-Party Services">
            <p>
                Links, integrations, or references to third-party services on our website (including payment
                gateways, mapping tools, and partner offers) are provided for convenience. TravelWheel does not
                control and is not responsible for the content, accuracy, or availability of third-party services.
            </p>
        </x-legal.section>

        <x-legal.section id="exchange-rate-changes" title="9. Exchange Rate Changes">
            <p>
                Fares and rates sourced in foreign currency are converted to Naira using TravelWheel's prevailing
                exchange rate at the time of booking, which may fluctuate over time. TravelWheel is not liable for any
                perceived loss resulting from exchange rate movements occurring after your payment has been processed.
            </p>
        </x-legal.section>

        <x-legal.section id="technical-errors" title="10. Technical Errors">
            <p>
                While we take reasonable steps to ensure the accuracy of pricing, availability, and content displayed
                on our platform, technical or human errors (such as mispriced fares or incorrect availability) may
                occasionally occur. TravelWheel reserves the right to correct such errors and to cancel or amend any
                booking made in reliance on a clear pricing or system error, with a full refund of any amount paid for
                that booking.
            </p>
        </x-legal.section>

        <x-legal.section id="website-availability" title="11. Website Availability">
            <p>
                TravelWheel does not guarantee that our website or mobile platforms will be available at all times,
                free from interruption, or error-free. We are not liable for any loss arising from website downtime,
                maintenance, or technical failure, including any missed booking opportunity resulting from such
                unavailability.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="12. Contact Information">
            <p>
                For questions about this Disclaimer, please contact us via our
                <a href="{{ route('help') }}">Contact / Help</a> page or write to us at 74, Ayangburen Road, Ikorodu,
                Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
