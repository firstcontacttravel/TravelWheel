@extends('business.layout')
@section('title', 'Account')

@section('content')
    <h1>Your account</h1>
    <p class="biz-sub">{{ $companyUser->name }} · {{ $companyUser->email }}</p>

    @include('business.partials.status')

    <div class="biz-card">
        <h2>Sign-in code</h2>
        @if ($companyUser->isCompanyAdmin())
            <p class="biz-sub" style="margin-bottom:0">Company Admins always get an email code when they sign in.</p>
        @else
            <p class="biz-sub">Get an email code each time you sign in, for extra protection.</p>
            <form method="POST" action="{{ route('business.account.login-code') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="login_code_enabled" value="{{ $companyUser->login_code_enabled ? 0 : 1 }}">
                <button type="submit" class="biz-btn biz-btn-light">{{ $companyUser->login_code_enabled ? 'Turn off sign-in codes' : 'Turn on sign-in codes' }}</button>
            </form>
        @endif
    </div>

    <div class="biz-card">
        <h2>Change password</h2>
        <p class="biz-sub">Use at least 10 characters, with letters and numbers. Other devices will be signed out.</p>
        <form method="POST" action="{{ route('business.account.password') }}" style="max-width:420px">
            @csrf
            @method('PUT')
            @include('business.partials.field', ['name' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'autocomplete' => 'current-password'])
            @include('business.partials.field', ['name' => 'password', 'label' => 'New password', 'type' => 'password', 'autocomplete' => 'new-password'])
            @include('business.partials.field', ['name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'autocomplete' => 'new-password'])
            <button type="submit" class="biz-btn">Change password</button>
        </form>
    </div>
@endsection
