@extends('business.layout')
@section('title', 'Invitation not available')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>This invitation isn't available</h1>
            <p class="biz-sub">It may have expired, been replaced by a newer one, or already been used. Please ask whoever invited you to send a new one, or write to <a href="mailto:{{ config('brand.support_email') }}">{{ config('brand.support_email') }}</a>.</p>
            <a class="biz-btn biz-btn-light biz-btn-full" href="{{ route('business.login') }}">Go to sign in</a>
        </div>
    </div>
@endsection
