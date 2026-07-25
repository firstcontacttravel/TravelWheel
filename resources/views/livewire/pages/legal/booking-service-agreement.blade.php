@php
    $sections = [
        ['id' => 'scope', 'label' => '1. Scope of this Agreement'],
        ['id' => 'booking-process', 'label' => '2. Booking Process & Confirmation'],
        ['id' => 'flight-booking-conditions', 'label' => '3. Flight Booking Conditions'],
        ['id' => 'airline-fare-rules', 'label' => '4. Airline Fare Rules'],
        ['id' => 'schedule-changes-cancellations', 'label' => '5. Schedule Changes & Airline Cancellations'],
        ['id' => 'no-show-policy', 'label' => '6. No-Show Policy'],
        ['id' => 'missed-flights', 'label' => '7. Missed Flights'],
        ['id' => 'booking-modifications', 'label' => '8. Booking Modifications'],
        ['id' => 'ticket-issuance', 'label' => '9. Ticket Issuance'],
        ['id' => 'travel-documents-visas', 'label' => '10. Travel Documents & Visa Requirements'],
        ['id' => 'passport-validity', 'label' => '11. Passport Validity'],
        ['id' => 'health-requirements', 'label' => '12. Health Requirements'],
        ['id' => 'immigration-responsibility', 'label' => '13. Immigration Responsibility'],
        ['id' => 'hotel-package-bookings', 'label' => '14. Hotel & Holiday Package Bookings'],
        ['id' => 'group-corporate-bookings', 'label' => '15. Group Bookings & Corporate Travel'],
        ['id' => 'customer-company-responsibilities', 'label' => '16. Customer & Company Responsibilities'],
        ['id' => 'contact', 'label' => '17. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Booking & Service Agreement" updated="July 2026" :sections="$sections">

        <p>
            This Booking &amp; Service Agreement sets out the specific operational terms that apply once you make a
            booking through TravelWheel for Flight Booking, Hotel Reservations, Holiday &amp; Tour Packages, Group
            Bookings, or Corporate Travel. It supplements and should be read together with our
            <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a>,
            <a href="{{ route('legal.payment') }}">Payment Policy</a>, and
            <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>.
        </p>

        <x-legal.section id="scope" title="1. Scope of this Agreement">
            <p>
                This Agreement applies to every booking confirmed through TravelWheel's website, mobile platform, or
                customer service team. Where a booking involves a Third-Party Supplier (airline, hotel, tour operator,
                ground transport provider), that Supplier's own conditions of carriage, rate rules, and service
                standards apply in addition to this Agreement, and in the event of any direct conflict on a matter
                that TravelWheel does not control, the Supplier's conditions will govern that specific aspect of the
                booking.
            </p>
        </x-legal.section>

        <x-legal.section id="booking-process" title="2. Booking Process & Confirmation">
            <p>
                A booking request becomes a confirmed booking only once (a) TravelWheel has received and verified full
                payment or the required Pay Small Small down payment, and (b) TravelWheel has issued a written booking
                confirmation, itinerary, or e-ticket. Quoted fares, rates, and availability are not guaranteed until
                this point and may change without notice.
            </p>
        </x-legal.section>

        <x-legal.section id="flight-booking-conditions" title="3. Flight Booking Conditions">
            <p>
                Flight bookings are made on your behalf with the operating or marketing airline named on your
                itinerary. You must ensure that the passenger name, date of birth, and travel document details
                supplied match your valid identification exactly, as airlines strictly enforce name-matching rules and
                may deny boarding for mismatches. TravelWheel is not responsible for denied boarding arising from
                inaccurate information supplied by you.
            </p>
        </x-legal.section>

        <x-legal.section id="airline-fare-rules" title="4. Airline Fare Rules">
            <p>
                Every airfare is subject to fare rules set exclusively by the airline, covering refundability, change
                fees, baggage allowance, mileage accrual, and seat selection. These rules are disclosed prior to
                payment and, once you complete payment, are deemed accepted by you. TravelWheel has no authority to
                override, waive, or vary an airline's fare rules.
            </p>
        </x-legal.section>

        <x-legal.section id="schedule-changes-cancellations" title="5. Schedule Changes & Airline Cancellations">
            <p>
                Where an airline changes a flight schedule or cancels a flight, TravelWheel will notify you as soon as
                reasonably possible using the contact details provided at booking, and will assist you in exercising
                the options made available by the airline (rebooking, alternative routing, or refund, as applicable).
                TravelWheel does not guarantee any specific outcome, as this is determined solely by the airline's
                policy for the affected fare class.
            </p>
        </x-legal.section>

        <x-legal.section id="no-show-policy" title="6. No-Show Policy">
            <x-legal.callout variant="warning">
                <p>Failing to check in for any flight segment, including an outbound sector, may automatically cancel all remaining segments on that ticket under the airline's no-show policy.</p>
            </x-legal.callout>
            <p>
                If you do not check in or board a booked flight without prior cancellation or change, the airline may
                treat this as a "no-show" and cancel some or all remaining flight segments on your itinerary, with no
                or limited refund, depending on the fare rules. TravelWheel strongly recommends notifying us in advance
                if you cannot use a booked flight segment.
            </p>
        </x-legal.section>

        <x-legal.section id="missed-flights" title="7. Missed Flights">
            <p>
                You are responsible for arriving at the airport in good time to complete check-in, security, and
                immigration formalities before your flight's scheduled departure. TravelWheel is not liable for
                missed flights due to late arrival, traffic, queue delays, incomplete travel documents, or failure to
                monitor flight status updates issued by the airline or TravelWheel.
            </p>
        </x-legal.section>

        <x-legal.section id="booking-modifications" title="8. Booking Modifications">
            <p>
                Requests to change travel dates, correct a name, upgrade cabin class, or add services to an existing
                booking are subject to availability and the Supplier's change fee, in addition to TravelWheel's
                administrative service fee. Some fare types do not permit changes under any circumstance. All
                modification requests must be submitted through our official support channels and are only effective
                once confirmed in writing by TravelWheel.
            </p>
        </x-legal.section>

        <x-legal.section id="ticket-issuance" title="9. Ticket Issuance">
            <p>
                E-tickets, hotel vouchers, and other booking confirmations are issued only after full and verified
                payment has been received. TravelWheel aims to issue travel documents promptly upon payment
                confirmation but is not liable for delays caused by the Supplier's own ticketing systems, payment
                verification processes, or circumstances described under Force Majeure in our
                <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a>.
            </p>
        </x-legal.section>

        <x-legal.section id="travel-documents-visas" title="10. Travel Documents & Visa Requirements">
            <p>
                You are solely responsible for holding all travel documents required for your journey, including a
                valid passport, any necessary visas or transit permits, and return or onward travel proof where
                required by your destination or transit country. Visa requirements vary by nationality, destination,
                and purpose of travel, and TravelWheel strongly recommends confirming requirements with the relevant
                embassy or consulate directly, in addition to any Visa Assistance guidance we may provide.
            </p>
        </x-legal.section>

        <x-legal.section id="passport-validity" title="11. Passport Validity">
            <p>
                Many countries require a passport to remain valid for a minimum period beyond your intended return
                date (commonly six months) and to contain a minimum number of blank visa pages. You are responsible
                for confirming and meeting these requirements before booking or travelling; TravelWheel is not liable
                for denied boarding or entry resulting from a passport that does not meet the destination's validity
                requirements.
            </p>
        </x-legal.section>

        <x-legal.section id="health-requirements" title="12. Health Requirements">
            <p>
                Some destinations require proof of vaccination, a health declaration, or specific travel insurance as
                a condition of entry. You are responsible for ascertaining and satisfying these requirements ahead of
                travel. TravelWheel may share general guidance where available but does not warrant the completeness
                of health-entry information, which can change at short notice.
            </p>
        </x-legal.section>

        <x-legal.section id="immigration-responsibility" title="13. Immigration Responsibility">
            <p>
                Entry into, transit through, or exit from any country is determined solely by that country's
                immigration authorities, who retain absolute discretion regardless of a confirmed booking, visa, or
                ticket. TravelWheel is not liable for denied entry, deportation, or related costs arising from an
                immigration officer's decision.
            </p>
        </x-legal.section>

        <x-legal.section id="hotel-package-bookings" title="14. Hotel & Holiday Package Bookings">
            <p>
                Hotel reservations and Holiday &amp; Tour Packages are subject to the specific rate plan, occupancy
                rules, and inclusions confirmed at the time of booking. Any special requests (room type, dietary
                requirements, accessibility needs) are passed on to the Supplier on a best-efforts basis and are not
                guaranteed unless expressly confirmed in writing by TravelWheel.
            </p>
        </x-legal.section>

        <x-legal.section id="group-corporate-bookings" title="15. Group Bookings & Corporate Travel">
            <p>
                Group Bookings (typically ten or more travellers on a single itinerary) and Corporate Travel accounts
                may be subject to a separately negotiated service agreement covering pricing, payment terms, and
                cancellation conditions. Where no separate agreement exists, the standard terms of this Agreement,
                our <a href="{{ route('legal.payment') }}">Payment Policy</a>, and
                <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a> apply.
            </p>
        </x-legal.section>

        <x-legal.section id="customer-company-responsibilities" title="16. Customer & Company Responsibilities">
            <p>
                You are responsible for reviewing your itinerary carefully, meeting all payment deadlines, and
                supplying accurate and timely information. TravelWheel is responsible for processing your booking
                diligently, communicating material updates promptly, and providing customer support in connection
                with your booking, subject always to the Limitation of Liability set out in our
                <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a>.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="17. Contact Information">
            <p>
                For booking enquiries, please contact us via our <a href="{{ route('help') }}">Contact / Help</a>
                page, quoting your booking reference, or write to us at 74, Ayangburen Road, Ikorodu, Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
