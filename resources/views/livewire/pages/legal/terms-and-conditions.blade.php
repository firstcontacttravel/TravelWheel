@php
    $sections = [
        ['id' => 'definitions', 'label' => '1. Definitions'],
        ['id' => 'acceptance', 'label' => '2. Acceptance of Terms'],
        ['id' => 'eligibility', 'label' => '3. User Eligibility'],
        ['id' => 'account-registration', 'label' => '4. Account Registration'],
        ['id' => 'accuracy-of-information', 'label' => '5. Accuracy of Information'],
        ['id' => 'customer-responsibilities', 'label' => '6. Customer Responsibilities'],
        ['id' => 'company-responsibilities', 'label' => '7. Company Responsibilities'],
        ['id' => 'third-party-suppliers', 'label' => '8. Third-Party Suppliers'],
        ['id' => 'flight-bookings', 'label' => '9. Flight Bookings & Fare Rules'],
        ['id' => 'schedule-changes', 'label' => '10. Schedule Changes & Airline Cancellations'],
        ['id' => 'no-show-missed-flights', 'label' => '11. No-Show & Missed Flights'],
        ['id' => 'booking-modifications', 'label' => '12. Booking Modifications & Ticket Issuance'],
        ['id' => 'travel-documents', 'label' => '13. Travel Documents, Visas & Immigration'],
        ['id' => 'health-requirements', 'label' => '14. Health Requirements'],
        ['id' => 'other-services', 'label' => '15. Other TravelWheel Services'],
        ['id' => 'payments-overview', 'label' => '16. Payments Overview'],
        ['id' => 'limitation-of-liability', 'label' => '17. Limitation of Liability'],
        ['id' => 'force-majeure', 'label' => '18. Force Majeure'],
        ['id' => 'governing-law', 'label' => '19. Governing Law'],
        ['id' => 'dispute-resolution', 'label' => '20. Dispute Resolution & Arbitration'],
        ['id' => 'amendments', 'label' => '21. Amendments to these Terms'],
        ['id' => 'contact', 'label' => '22. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Terms & Conditions" updated="31 July 2026" :sections="$sections">

        <p>
            These Terms &amp; Conditions ("Terms") govern your access to and use of the TravelWheel website, mobile
            platforms, customer support channels, and all travel-related services offered by TravelWheel, including
            Flight Booking, Airport Protocol &amp; Meet-and-Greet Services, Airport Transfers, Hotel Reservations,
            Holiday &amp; Tour Packages, Visa Assistance, Travel Insurance, TravelFlex financing,
            Group Bookings, Corporate Travel, Travel Consultation, and other related services (collectively, the
            "Services"). These Terms should be read together with our
            <a href="{{ route('legal.privacy') }}">Privacy Policy</a>,
            <a href="{{ route('legal.payment') }}">Payment Policy</a>,
            <a href="{{ route('legal.refund') }}">Refund &amp; Cancellation Policy</a>,
            <a href="{{ route('legal.booking-agreement') }}">Booking &amp; Service Agreement</a>, and any
            product-specific terms referenced within, all of which form part of a single agreement between you and
            TravelWheel.
        </p>

        <x-legal.callout variant="info">
            <p><strong>By accessing this website or making a booking, you agree to these Terms.</strong> If you do not agree with any part of these Terms, you must not use TravelWheel's website or Services.</p>
        </x-legal.callout>

        <x-legal.section id="definitions" title="1. Definitions">
            <p>In these Terms, unless the context otherwise requires:</p>
            <ul>
                <li><strong>"TravelWheel", "Company", "we", "us", "our"</strong> refers to TravelWheel, a product of First Contact Travel (FCTF), a travel technology platform registered and operating under the laws of the Federal Republic of Nigeria, with its registered office at 74, Ayangburen Road, Ikorodu, Lagos, Nigeria.</li>
                <li><strong>"Customer", "User", "you", "your"</strong> refers to any individual, corporate entity, or group that accesses the website, creates an account, or makes a booking or payment through TravelWheel.</li>
                <li><strong>"Supplier" or "Third-Party Supplier"</strong> refers to airlines, hotels, insurers, tour operators, ground transport providers, visa processing centres, immigration agencies, payment processors, and any other independent third party that provides the underlying travel product or service booked through TravelWheel.</li>
                <li><strong>"Booking"</strong> refers to any reservation, purchase, or request for a Service made through TravelWheel's website, mobile platform, customer care team, or authorised agents.</li>
                <li><strong>"TravelFlex"</strong> refers to the option through which an eligible customer may apply to Fast Credit Limited for financing connected with a TravelWheel booking. TravelWheel is not the lender. The finance terms are governed by the <a href="{{ route('legal.pay-small-small') }}">TravelFlex Fast Credit Loan Agreement</a>.</li>
                <li><strong>"Services"</strong> refers collectively to all travel-related products and services made available by TravelWheel, whether provided directly or facilitated through Third-Party Suppliers.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="acceptance" title="2. Acceptance of Terms">
            <p>
                By visiting our website, registering for an account, requesting a quotation, or making a booking or
                payment, you confirm that you have read, understood, and agree to be legally bound by these Terms, as
                amended from time to time. If you are booking or transacting on behalf of another person, a group, or
                a corporate entity, you represent and warrant that you have the authority to bind that person, group,
                or entity to these Terms, and you accept joint responsibility for compliance with them.
            </p>
        </x-legal.section>

        <x-legal.section id="eligibility" title="3. User Eligibility">
            <p>
                Our Services are available to individuals who are at least 18 years old and who have the legal
                capacity to enter into a binding contract under Nigerian law. Bookings made for or on behalf of minors
                must be made by a parent, legal guardian, or an accompanying adult who accepts full responsibility for
                the minor's travel. TravelWheel reserves the right to refuse service, suspend an account, or cancel a
                booking where there is reasonable suspicion of fraud, misuse, misrepresentation, or a breach of these
                Terms.
            </p>
        </x-legal.section>

        <x-legal.section id="account-registration" title="4. Account Registration">
            <p>
                Certain features of our Services may require you to create an account. You agree to provide accurate,
                current, and complete information during registration and to keep this information up to date. You
                are solely responsible for maintaining the confidentiality of your login credentials and for all
                activities conducted under your account. You must notify TravelWheel immediately of any unauthorised
                use of your account or any other breach of security.
            </p>
        </x-legal.section>

        <x-legal.section id="accuracy-of-information" title="5. Accuracy of Information">
            <p>
                You are responsible for ensuring that all information you provide when making a booking, including
                passenger names, dates of birth, contact details, passport information, and travel dates, is complete
                and accurate. Passenger names must match the name on the travel document (passport or valid
                government-issued ID) exactly as it appears. TravelWheel is not liable for any loss, additional
                charges, denied boarding, or visa refusal arising from inaccurate, incomplete, or outdated information
                supplied by you.
            </p>
        </x-legal.section>

        <x-legal.section id="customer-responsibilities" title="6. Customer Responsibilities">
            <p>As a customer of TravelWheel, you are responsible for:</p>
            <ul>
                <li>Providing accurate personal, travel, and payment information at the time of booking;</li>
                <li>Reviewing and confirming all booking details (names, dates, routing, fare rules) before completing payment;</li>
                <li>Obtaining all travel documents required for your journey, including a valid passport, visas, and any transit or health documentation;</li>
                <li>Arriving at the airport, hotel, or designated pick-up point within the time frames communicated by TravelWheel or the relevant Supplier;</li>
                <li>Making all payments due to TravelWheel, and all loan instalments due to Fast Credit under TravelFlex, in full and on time; and</li>
                <li>Promptly notifying TravelWheel of any changes to your travel plans or contact information.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="company-responsibilities" title="7. Company Responsibilities">
            <p>
                TravelWheel undertakes to act as a diligent intermediary between you and our Third-Party Suppliers, to
                process bookings and payments promptly, to issue confirmations and travel documents within reasonable
                timeframes, to provide accurate information about our Services to the best of our knowledge, and to
                provide customer support in connection with bookings made through our platform. TravelWheel does not
                itself operate flights, hotels, or insurance products, and our responsibility is limited to the
                proper performance of our role as a travel booking and facilitation platform, subject to the
                <a href="#limitation-of-liability">Limitation of Liability</a> set out below.
            </p>
        </x-legal.section>

        <x-legal.section id="third-party-suppliers" title="8. Third-Party Suppliers">
            <p>
                Many of the Services offered through TravelWheel, including flights, hotel accommodation, travel
                insurance, visa processing, and ground transport, are provided by independent Third-Party Suppliers.
                Your booking is subject to the applicable Supplier's own terms and conditions, fare rules, and service
                standards, in addition to these Terms. TravelWheel acts as an intermediary or agent in facilitating
                these bookings and is not responsible for the acts, omissions, delays, cancellations, service quality,
                insolvency, or contractual defaults of any Third-Party Supplier, except to the extent required by
                applicable Nigerian law.
            </p>
        </x-legal.section>

        <x-legal.section id="flight-bookings" title="9. Flight Bookings & Fare Rules">
            <p>
                Flight bookings made through TravelWheel are subject to the fare rules, baggage allowances, change and
                cancellation fees, and conditions of carriage set by the operating airline. Fares displayed on our
                platform reflect pricing available at the time of search and may change without notice until a
                booking is confirmed and paid for in full. Some fares are non-refundable, non-transferable, or subject
                to significant restrictions; these conditions will be disclosed prior to payment and, once accepted,
                are binding.
            </p>
        </x-legal.section>

        <x-legal.section id="schedule-changes" title="10. Schedule Changes & Airline Cancellations">
            <p>
                Airlines may change flight schedules, aircraft, routings, or cancel flights entirely at their sole
                discretion and for reasons beyond TravelWheel's control. Where TravelWheel is notified of a schedule
                change or cancellation, we will make reasonable efforts to inform affected customers promptly and to
                assist with rebooking, refund requests, or alternative arrangements in accordance with the operating
                airline's policy. TravelWheel is not liable for any loss, inconvenience, or additional cost arising
                directly from an airline-initiated schedule change or cancellation.
            </p>
        </x-legal.section>

        <x-legal.section id="no-show-missed-flights" title="11. No-Show & Missed Flights">
            <x-legal.callout variant="warning">
                <p>Failure to check in or board a flight ("no-show") typically results in forfeiture of the full ticket value under most airline fare rules, and may also forfeit connecting or return flight segments.</p>
            </x-legal.callout>
            <p>
                Customers are responsible for arriving at the airport with sufficient time to complete check-in,
                security, and immigration procedures. TravelWheel is not responsible for missed flights resulting from
                late arrival, traffic, incomplete travel documents, or failure to reconfirm flight timings.
                No-show and missed-flight consequences are determined by the operating airline's fare rules and are
                outside TravelWheel's control.
            </p>
        </x-legal.section>

        <x-legal.section id="booking-modifications" title="12. Booking Modifications & Ticket Issuance">
            <p>
                Requests to modify a confirmed booking (date change, name correction, routing change, or upgrade) are
                subject to the availability and change fees of the relevant Supplier, in addition to any TravelWheel
                administrative service fee. Tickets and booking confirmations are issued only after full payment (or,
                where applicable, the required TravelFlex upfront payment following Fast Credit approval) has been received and verified.
                TravelWheel reserves the right to withhold ticket issuance where payment verification is pending or
                where fraud is suspected.
            </p>
        </x-legal.section>

        <x-legal.section id="travel-documents" title="13. Travel Documents, Visas & Immigration">
            <x-legal.callout variant="danger">
                <p><strong>Visa approval is never guaranteed.</strong> Decisions on visa applications rest solely with the relevant embassy, consulate, or immigration authority.</p>
            </x-legal.callout>
            <ul>
                <li>You are solely responsible for obtaining a valid passport, with the minimum validity period required by your destination (commonly six months beyond the return date), and any necessary visas, transit permits, or travel authorisations.</li>
                <li>Where TravelWheel provides Visa Assistance, our role is limited to guidance, document preparation support, and submission facilitation; final approval rests entirely with the relevant government authority.</li>
                <li>Immigration officers at your point of departure, transit, or arrival retain absolute discretion to grant or refuse entry, regardless of a confirmed booking, visa, or ticket.</li>
                <li>TravelWheel is not liable for denied boarding, deportation, or denied entry resulting from incomplete, invalid, or fraudulent travel documents.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="health-requirements" title="14. Health Requirements">
            <p>
                Certain destinations require proof of vaccination (such as Yellow Fever), health certificates, travel
                insurance, or other medical documentation as a condition of entry. It is your responsibility to
                ascertain and comply with all health requirements applicable to your itinerary before travel.
                TravelWheel may share general guidance where available but does not guarantee the completeness or
                currency of health-related entry requirements, which are set and may change at short notice by
                destination governments and health authorities.
            </p>
        </x-legal.section>

        <x-legal.section id="other-services" title="15. Other TravelWheel Services">
            <p>
                In addition to flight booking, TravelWheel facilitates Airport Protocol &amp; Meet-and-Greet Services,
                Airport Transfers, Hotel Reservations, Holiday &amp; Tour Packages, Visa Assistance, Travel Insurance,
                Group Bookings, Corporate Travel accounts, and Travel Consultation. Each of these Services may be
                subject to additional product-specific terms (including our
                <a href="{{ route('legal.protocol-terms') }}">Airport Protocol Service Terms</a> and
                <a href="{{ route('legal.insurance-terms') }}">Travel Insurance Terms</a>), which apply alongside
                these Terms. Group Bookings and Corporate Travel accounts may be subject to separately negotiated
                commercial agreements, which take precedence over these Terms only to the extent of any conflict.
            </p>
        </x-legal.section>

        <x-legal.section id="payments-overview" title="16. Payments Overview">
            <p>
                All payments made through TravelWheel are subject to our
                <a href="{{ route('legal.payment') }}">Payment Policy</a>, including provisions on payment
                verification, fraud prevention, currency conversion, failed payments, and chargebacks. Where you elect
                to apply for financing through TravelFlex, the additional terms of the
                <a href="{{ route('legal.pay-small-small') }}">TravelFlex Fast Credit Loan Agreement</a> apply to the
                financing relationship between you and Fast Credit. TravelWheel's role is limited as described in that agreement.
            </p>
        </x-legal.section>

        <x-legal.section id="limitation-of-liability" title="17. Limitation of Liability">
            <p>
                To the fullest extent permitted by Nigerian law, TravelWheel's liability in connection with any
                booking or Service is limited to the value of the booking fee paid to TravelWheel for that specific
                transaction. TravelWheel shall not be liable for indirect, incidental, consequential, or special
                damages, including loss of enjoyment, loss of income, or loss of opportunity, arising from the acts or
                omissions of airlines, hotels, insurers, immigration authorities, or any other Third-Party Supplier.
                Nothing in these Terms excludes or limits liability that cannot lawfully be excluded or limited under
                Nigerian law.
            </p>
        </x-legal.section>

        <x-legal.section id="force-majeure" title="18. Force Majeure">
            <p>
                TravelWheel shall not be held liable for any failure or delay in performance arising from
                circumstances beyond its reasonable control, including but not limited to natural disasters, extreme
                weather, acts of government, war, terrorism, civil unrest, epidemics or pandemics, strikes, airspace
                closures, airline or airport operational failures, and system or network outages affecting payment or
                travel infrastructure. In such events, TravelWheel will make reasonable efforts to assist customers
                with alternative arrangements, subject to the policies of the relevant Third-Party Supplier.
            </p>
        </x-legal.section>

        <x-legal.section id="governing-law" title="19. Governing Law">
            <p>
                These Terms and any dispute or claim arising out of or in connection with them shall be governed by
                and construed in accordance with the laws of the Federal Republic of Nigeria, without regard to its
                conflict of law provisions.
            </p>
        </x-legal.section>

        <x-legal.section id="dispute-resolution" title="20. Dispute Resolution & Arbitration">
            <p>
                In the event of any dispute arising from these Terms or your use of our Services, you agree to first
                notify TravelWheel in writing and attempt to resolve the matter amicably through our customer support
                channels. If a dispute is not resolved within thirty (30) days of written notice, either party may
                refer the dispute to arbitration seated in Lagos, Nigeria, in accordance with the Arbitration and Mediation Act 2023
                (as amended or replaced). The arbitration shall be
                conducted in English by a single arbitrator, and the arbitral award shall be final and binding on both
                parties, subject to any right of appeal available under Nigerian law.
            </p>
        </x-legal.section>

        <x-legal.section id="amendments" title="21. Amendments to these Terms">
            <p>
                TravelWheel reserves the right to amend, update, or replace these Terms at any time, at our sole
                discretion, to reflect changes in our Services, applicable law, or business practices. Amended Terms
                will be published on this page with an updated "Last Updated" date. Your continued use of our website
                or Services after such changes take effect constitutes your acceptance of the amended Terms.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="22. Contact Information">
            <p>If you have any questions about these Terms, please contact us:</p>
            <ul>
                <li><strong>TravelWheel</strong>, a product of First Contact Travel (FCTF)</li>
                <li>74, Ayangburen Road, Ikorodu, Lagos, Nigeria</li>
                <li>Via our <a href="{{ route('help') }}">Contact / Help</a> page or <a href="{{ route('faq') }}">FAQ</a></li>
            </ul>
        </x-legal.section>

    </x-legal.layout>
</div>
