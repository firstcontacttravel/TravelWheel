@extends('business.layout')
@section('title', 'Request received')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Thank you</h1>
            <p class="biz-sub" style="margin-bottom:0">We've received your request. If your company is eligible, we'll email your named contact an invitation. Questions? Write to <a href="mailto:{{ config('brand.support_email') }}">{{ config('brand.support_email') }}</a>.</p>
        </div>
    </div>
@endsection
