@component('layouts.app', ['title' => 'Verify Visa Portal Access'])
<link rel="stylesheet" href="{{ asset('css/visa-portal.css') }}?v={{ filemtime(public_path('css/visa-portal.css')) }}">
<div class="vp-auth-shell"><section class="vp-auth-card">
    <div class="vp-auth-icon"><x-ui.icon name="lock" :size="30" /></div><p class="vp-eyebrow">Identity check</p><h1>Enter your access code</h1>
    <p>We sent a six-digit code for application <strong>{{ $reference }}</strong>.</p>
    @if(session('status'))<div class="vp-alert vp-alert--success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="vp-alert vp-alert--error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('visa.portal.verify') }}" class="vp-form">@csrf
        <label>Six-digit code<input class="vp-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code"></label>
        <button class="vp-button" type="submit">Open application <x-ui.icon name="arrow-right" :size="17" /></button>
    </form>
    <a class="vp-text-link" href="{{ route('visa.portal.entry') }}">Use a different application</a>
</section></div>
@endcomponent
