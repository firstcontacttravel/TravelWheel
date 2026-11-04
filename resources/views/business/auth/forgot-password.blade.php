@extends('business.layout')
@section('title', 'Forgot your password')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Forgot your password?</h1>
            <p class="biz-sub">Enter your work email and we'll send you a link to choose a new one.</p>

            @include('business.partials.status')

            <form method="POST" action="{{ route('business.password.email') }}">
                @csrf
                @include('business.partials.field', ['name' => 'email', 'label' => 'Work email', 'type' => 'email', 'autocomplete' => 'username'])
                <button type="submit" class="biz-btn biz-btn-full">Send reset link</button>
            </form>
        </div>
        <p class="biz-foot"><a href="{{ route('business.login') }}">Back to sign in</a></p>
    </div>
@endsection
