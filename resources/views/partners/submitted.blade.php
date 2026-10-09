@component('layouts.app', ['title' => 'Application Received - TravelWheel'])

<div style="padding: 130px 16px 70px; background: #f5f7fb;">
    <div style="max-width: 640px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 4px 18px rgba(13, 24, 131, .07); padding: 36px 28px; text-align: center;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #e7f6ee; color: #0d9c53; font-size: 32px; line-height: 64px; margin: 0 auto 18px;">&#10003;</div>
        <h1 style="color: rgba(13, 24, 131, 1); font-size: 1.6rem; font-weight: 800;">Application received</h1>

        @if (session('vendor_application_reference'))
            <p style="margin: 14px 0 6px; color: #555;">Your application reference is</p>
            <p style="font-size: 1.35rem; font-weight: 800; letter-spacing: 1px; color: rgba(13, 24, 131, 1);">{{ session('vendor_application_reference') }}</p>
        @endif

        <p style="color: #555; margin-top: 14px;">
            Thank you for applying to partner with TravelWheel. We've emailed a copy of this confirmation to your contact
            address. Our team will review your information and documents and get back to you. If we need anything else, we'll contact you.
        </p>
        <p style="color: #555;">Questions? Email <a href="mailto:{{ config('vendor_onboarding.notify_email') }}">{{ config('vendor_onboarding.notify_email') }}</a> and quote your reference.</p>

        <a href="{{ url('/') }}" class="btn px-4 py-2 rounded-pill mt-2" style="background: rgba(13, 24, 131, 1); color: #fff;">Visit TravelWheel</a>
    </div>
</div>

@endcomponent
