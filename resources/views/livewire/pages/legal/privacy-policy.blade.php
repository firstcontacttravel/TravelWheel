@php
    $sections = [
        ['id' => 'introduction', 'label' => '1. Introduction'],
        ['id' => 'information-we-collect', 'label' => '2. Information We Collect'],
        ['id' => 'identity-travel-documents', 'label' => '3. Identity & Travel Documents'],
        ['id' => 'payment-information', 'label' => '4. Payment Information'],
        ['id' => 'device-information', 'label' => '5. Device & Usage Information'],
        ['id' => 'cookies', 'label' => '6. Cookies'],
        ['id' => 'how-we-use-information', 'label' => '7. How We Use Your Information'],
        ['id' => 'marketing-communications', 'label' => '8. Marketing Communications'],
        ['id' => 'document-handling', 'label' => '9. Document Handling & Confidentiality'],
        ['id' => 'data-retention', 'label' => '10. Data Retention'],
        ['id' => 'data-protection', 'label' => '11. Data Protection & Security'],
        ['id' => 'third-party-sharing', 'label' => '12. Third-Party Sharing'],
        ['id' => 'regulatory-compliance', 'label' => '13. Regulatory Compliance'],
        ['id' => 'your-rights', 'label' => '14. Your Rights'],
        ['id' => 'deletion-requests', 'label' => '15. Data Deletion Requests'],
        ['id' => 'changes', 'label' => '16. Changes to this Policy'],
        ['id' => 'contact', 'label' => '17. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Privacy Policy" updated="July 2026" :sections="$sections">

        <p>
            This Privacy Policy explains how TravelWheel collects, uses, stores, shares, and protects your personal
            information when you use our website, mobile platforms, or any of our Services, including Flight Booking,
            Airport Protocol, Airport Transfers, Hotel Reservations, Holiday &amp; Tour Packages, Visa Assistance,
            Travel Insurance, Pay Small Small, Group Bookings, Corporate Travel, and Travel Consultation. This Policy
            should be read together with our <a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a> and
            <a href="{{ route('legal.cookies') }}">Cookie Policy</a>.
        </p>

        <x-legal.section id="introduction" title="1. Introduction">
            <p>
                TravelWheel is committed to protecting your privacy and handling your personal information responsibly,
                in accordance with the Nigeria Data Protection Act 2023 (NDPA) and applicable regulations issued by the
                Nigeria Data Protection Commission (NDPC), as well as relevant international data protection standards
                where you are booking travel to or from other jurisdictions.
            </p>
        </x-legal.section>

        <x-legal.section id="information-we-collect" title="2. Information We Collect">
            <p>We may collect the following categories of personal information:</p>
            <ul>
                <li>Full name, date of birth, gender, nationality, and contact details (phone number, email address, residential address);</li>
                <li>Account credentials, booking history, and customer support communications;</li>
                <li>Travel details, including itineraries, flight preferences, hotel and package selections, and next-of-kin or emergency contact information where required;</li>
                <li>Identity and travel documents necessary to complete your booking (see "Identity &amp; Travel Documents" below);</li>
                <li>Payment and billing information (see "Payment Information" below);</li>
                <li>Device, browser, and usage information collected automatically when you use our website; and</li>
                <li>Information you voluntarily provide through forms, surveys, or promotional entries.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="identity-travel-documents" title="3. Identity & Travel Documents">
            <p>
                To process bookings, visa applications, insurance policies, and airport protocol services, we may
                collect copies of your passport bio-data page, national identity card, visa pages, birth certificate
                (for minors), and other government-issued identification. This information is collected strictly for
                the purpose of fulfilling your booking, verifying your identity, meeting airline and immigration
                requirements, and complying with applicable law, and is handled in accordance with the "Document
                Handling &amp; Confidentiality" section below.
            </p>
        </x-legal.section>

        <x-legal.section id="payment-information" title="4. Payment Information">
            <p>
                Payment card details and bank account information you provide are processed through licensed,
                PCI-DSS-compliant third-party payment processors and financial institutions. TravelWheel does not
                store full payment card numbers on its own servers. We retain payment references, transaction status,
                and billing history necessary for reconciliation, dispute resolution, and regulatory record-keeping.
            </p>
        </x-legal.section>

        <x-legal.section id="device-information" title="5. Device & Usage Information">
            <p>
                When you access our website or mobile platforms, we automatically collect certain technical
                information, including your IP address, browser type, device identifiers, operating system, referring
                pages, and interaction data (such as pages visited and search queries). This information helps us
                secure our platform, diagnose technical issues, and improve the performance and relevance of our
                Services.
            </p>
        </x-legal.section>

        <x-legal.section id="cookies" title="6. Cookies">
            <p>
                TravelWheel uses cookies and similar tracking technologies to operate our website, remember your
                preferences, and analyse traffic. Full details of the categories of cookies we use, their purpose, and
                how to manage your preferences are set out in our dedicated
                <a href="{{ route('legal.cookies') }}">Cookie Policy</a>.
            </p>
        </x-legal.section>

        <x-legal.section id="how-we-use-information" title="7. How We Use Your Information">
            <ul>
                <li>To process bookings, payments, ticket issuance, visa applications, and insurance policies;</li>
                <li>To verify your identity and prevent fraud;</li>
                <li>To communicate booking confirmations, schedule changes, payment reminders, and customer support responses;</li>
                <li>To comply with legal, immigration, aviation security, and regulatory obligations;</li>
                <li>To improve our website, products, and customer experience; and</li>
                <li>To send you marketing communications, where you have consented to receive them.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="marketing-communications" title="8. Marketing Communications">
            <p>
                With your consent, TravelWheel may send you promotional offers, newsletters, and updates about our
                Services via email, SMS, or WhatsApp. You may withdraw your consent and opt out of marketing
                communications at any time by using the "unsubscribe" link in our emails or by contacting our support
                team. Opting out of marketing communications does not affect transactional messages relating to your
                bookings, which we are required to send.
            </p>
        </x-legal.section>

        <x-legal.section id="document-handling" title="9. Document Handling & Confidentiality">
            <ul>
                <li><strong>Passport handling:</strong> Passport copies are used solely for booking, ticketing, visa, and insurance purposes and are stored securely with restricted, role-based staff access.</li>
                <li><strong>Visa documentation:</strong> Application forms, supporting documents, and correspondence with embassies or visa processing centres are handled confidentially and shared only with the relevant authority processing your application.</li>
                <li><strong>Travel itineraries and boarding passes:</strong> Electronic itineraries, e-tickets, and boarding passes are transmitted through secure channels and retained for record-keeping and customer support purposes.</li>
                <li><strong>Confidentiality:</strong> All personal and travel documents are treated as confidential and are not disclosed to any party other than those necessary to complete your booking (airlines, hotels, insurers, visa authorities, payment processors) or as required by law.</li>
                <li><strong>Secure storage:</strong> Documents are stored on access-controlled systems, with encryption applied to sensitive data both in transit and, where technically applicable, at rest.</li>
                <li><strong>Verification process:</strong> We may independently verify the authenticity of submitted documents with the issuing authority, employer, or bank where necessary to prevent fraud or comply with regulatory obligations.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="data-retention" title="10. Data Retention">
            <p>
                We retain personal information for as long as necessary to fulfil the purposes described in this
                Policy, including ongoing bookings, warranty or dispute periods, tax and financial record-keeping
                obligations under Nigerian law, and any applicable statutory limitation periods. Where information is
                no longer required, we securely delete or anonymise it in accordance with our internal data retention
                schedules.
            </p>
        </x-legal.section>

        <x-legal.section id="data-protection" title="11. Data Protection & Security">
            <p>
                TravelWheel implements administrative, technical, and physical safeguards designed to protect your
                personal information against unauthorised access, alteration, disclosure, or destruction, including
                encrypted data transmission, access controls, and regular review of our security practices. While we
                take reasonable steps to protect your data, no method of electronic transmission or storage is
                completely secure, and we cannot guarantee absolute security.
            </p>
        </x-legal.section>

        <x-legal.section id="third-party-sharing" title="12. Third-Party Sharing">
            <p>
                We share personal information only where necessary to deliver the Services you have requested,
                including with airlines, hotels, tour operators, insurers, visa processing centres and embassies,
                payment processors and banks, identity verification providers, and regulatory or law enforcement
                authorities where legally required. We do not sell your personal information to third parties. Any
                third party with whom we share your information is required to handle it in a manner consistent with
                this Policy and applicable data protection law.
            </p>
        </x-legal.section>

        <x-legal.section id="regulatory-compliance" title="13. Regulatory Compliance">
            <p>
                TravelWheel processes personal data in accordance with the Nigeria Data Protection Act 2023 and
                applicable guidance from the Nigeria Data Protection Commission. Where your travel involves
                jurisdictions with their own data protection regimes (such as the EU General Data Protection
                Regulation), we take reasonable steps to ensure that international data transfers necessary to fulfil
                your booking (for example, to an overseas airline or hotel) are conducted lawfully.
            </p>
        </x-legal.section>

        <x-legal.section id="your-rights" title="14. Your Rights">
            <p>Subject to applicable law, you have the right to:</p>
            <ul>
                <li>Request access to the personal information we hold about you;</li>
                <li>Request correction of inaccurate or incomplete information;</li>
                <li>Object to or restrict certain processing of your information, including for marketing purposes;</li>
                <li>Request a copy of your data in a portable format, where technically feasible; and</li>
                <li>Lodge a complaint with the Nigeria Data Protection Commission if you believe your data protection rights have been infringed.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="deletion-requests" title="15. Data Deletion Requests">
            <p>
                You may request the deletion of your personal information by contacting our support team. We will
                honour such requests except where we are required or permitted by law to retain certain information,
                such as records needed for tax, regulatory, fraud-prevention, or dispute-resolution purposes, or where
                deletion would affect an active or pending booking. We will inform you of the outcome of your request
                within a reasonable timeframe.
            </p>
        </x-legal.section>

        <x-legal.section id="changes" title="16. Changes to this Policy">
            <p>
                We may update this Privacy Policy from time to time to reflect changes in our practices, technology,
                or legal requirements. Material changes will be indicated by an updated "Last Updated" date at the top
                of this page. Your continued use of our Services after such changes constitutes acceptance of the
                revised Policy.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="17. Contact Information">
            <p>
                For questions about this Privacy Policy or to exercise your data protection rights, please contact us
                via our <a href="{{ route('help') }}">Contact / Help</a> page, or write to us at 74, Ayangburen Road,
                Ikorodu, Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
