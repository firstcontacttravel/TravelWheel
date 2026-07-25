@php
    $sections = [
        ['id' => 'what-are-cookies', 'label' => '1. What Are Cookies'],
        ['id' => 'how-we-use-cookies', 'label' => '2. How We Use Cookies'],
        ['id' => 'types-of-cookies', 'label' => '3. Types of Cookies We Use'],
        ['id' => 'third-party-cookies', 'label' => '4. Third-Party Cookies'],
        ['id' => 'device-information', 'label' => '5. Device & Usage Information'],
        ['id' => 'managing-cookies', 'label' => '6. Managing Your Cookie Preferences'],
        ['id' => 'consent', 'label' => '7. Consent'],
        ['id' => 'changes', 'label' => '8. Changes to this Policy'],
        ['id' => 'contact', 'label' => '9. Contact Information'],
    ];
@endphp

<div>
    <x-legal.layout title="Cookie Policy" updated="July 2026" :sections="$sections">

        <p>
            This Cookie Policy explains how TravelWheel uses cookies and similar tracking technologies on our website
            and mobile platforms. It should be read together with our
            <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.
        </p>

        <x-legal.section id="what-are-cookies" title="1. What Are Cookies">
            <p>
                Cookies are small text files placed on your device when you visit a website. They allow the website to
                recognise your device, remember your preferences, and collect information about how you use the site.
                Similar technologies, such as web beacons, pixels, and local storage, may be used for comparable
                purposes.
            </p>
        </x-legal.section>

        <x-legal.section id="how-we-use-cookies" title="2. How We Use Cookies">
            <ul>
                <li>To keep you securely logged into your TravelWheel account;</li>
                <li>To remember your search preferences, currency, and language settings;</li>
                <li>To maintain your booking session as you move through the search, selection, and payment steps;</li>
                <li>To analyse website traffic and usage patterns so we can improve our Services; and</li>
                <li>To measure the performance of our marketing campaigns, where you have consented to marketing cookies.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="types-of-cookies" title="3. Types of Cookies We Use">
            <ul>
                <li><strong>Strictly necessary cookies:</strong> Required for core website functionality, such as security, session management, and completing a booking. These cannot be disabled without affecting the site's operation.</li>
                <li><strong>Performance and analytics cookies:</strong> Help us understand how visitors interact with our website so we can improve performance and usability.</li>
                <li><strong>Functionality cookies:</strong> Remember your preferences, such as your preferred airport, currency, or previously searched routes.</li>
                <li><strong>Marketing and advertising cookies:</strong> Used to deliver relevant offers and measure the effectiveness of our marketing, where you have provided consent.</li>
            </ul>
        </x-legal.section>

        <x-legal.section id="third-party-cookies" title="4. Third-Party Cookies">
            <p>
                Some cookies on our website are set by trusted third parties, such as payment processors, analytics
                providers, and advertising partners, to support functions like secure payment processing, traffic
                analysis, and marketing measurement. These third parties are responsible for their own cookies and
                privacy practices, and we encourage you to review their respective policies.
            </p>
        </x-legal.section>

        <x-legal.section id="device-information" title="5. Device & Usage Information">
            <p>
                In addition to cookies, we may automatically collect certain device and usage information, including
                IP address, browser type, device identifiers, and pages visited, as described in our
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>, to help secure our platform and improve
                your experience.
            </p>
        </x-legal.section>

        <x-legal.section id="managing-cookies" title="6. Managing Your Cookie Preferences">
            <p>
                You can manage or disable cookies at any time through your browser settings, which typically allow you
                to block or delete cookies, or to be notified before a cookie is placed. Please note that disabling
                strictly necessary cookies may prevent you from completing a booking or using certain features of our
                website.
            </p>
        </x-legal.section>

        <x-legal.section id="consent" title="7. Consent">
            <p>
                By continuing to browse or use our website after being presented with our cookie notice, you consent
                to our use of cookies as described in this Policy, except where your browser or device settings are
                configured to block them. You may withdraw consent for non-essential cookies at any time through your
                browser settings.
            </p>
        </x-legal.section>

        <x-legal.section id="changes" title="8. Changes to this Policy">
            <p>
                We may update this Cookie Policy from time to time to reflect changes in the cookies we use or for
                legal or regulatory reasons. Material changes will be indicated by an updated "Last Updated" date at
                the top of this page.
            </p>
        </x-legal.section>

        <x-legal.section id="contact" title="9. Contact Information">
            <p>
                For questions about our use of cookies, please contact us via our
                <a href="{{ route('help') }}">Contact / Help</a> page or write to us at 74, Ayangburen Road, Ikorodu,
                Lagos, Nigeria.
            </p>
        </x-legal.section>

    </x-legal.layout>
</div>
