@extends('business.layout')
@section('title', 'Enter your code')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Enter your code</h1>
            <p class="biz-sub">We've emailed a 6-digit code to {{ $email }}.</p>

            @include('business.partials.status')

            <form method="POST" action="{{ route('business.login.code.verify') }}">
                @csrf
                @include('business.partials.field', ['name' => 'code', 'label' => 'Sign-in code', 'autocomplete' => 'one-time-code', 'inputmode' => 'numeric', 'value' => ''])
                <button type="submit" class="biz-btn biz-btn-full">Continue</button>
            </form>

            <form method="POST" action="{{ route('business.login.code.resend') }}" class="biz-foot">
                @csrf
                <button type="submit" class="biz-link" style="color:var(--biz-brand)">Send a new code</button>
            </form>
        </div>
        <p class="biz-foot"><a href="{{ route('business.login') }}">Back to sign in</a></p>
    </div>
@endsection
