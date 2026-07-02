@component('layouts.app', ['title' => 'Track Visa Application'])
<link rel="stylesheet" href="{{ asset('css/visa-portal.css') }}?v={{ filemtime(public_path('css/visa-portal.css')) }}">
<div class="vp-auth-shell">
    <section class="vp-auth-card">
        <div class="vp-auth-icon"><x-ui.icon name="shield" :size="30" /></div>
        <p class="vp-eyebrow">Secure customer portal</p>
        <h1>Track your visa application</h1>
        <p>Enter the reference and email used for your application. We’ll email you a one-time access code.</p>
        @if($errors->any())<div class="vp-alert vp-alert--error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('visa.portal.code.request') }}" class="vp-form">@csrf
            <label>Application reference<input name="reference" value="{{ old('reference') }}" placeholder="e.g. 01H..." required autocomplete="off"></label>
            <label>Email address<input name="email" value="{{ old('email') }}" type="email" placeholder="you@example.com" required autocomplete="email"></label>
            <button class="vp-button" type="submit">Send access code <x-ui.icon name="arrow-right" :size="17" /></button>
        </form>
        <small>Access codes expire after 10 minutes and can only be used once.</small>
    </section>
</div>
@endcomponent
