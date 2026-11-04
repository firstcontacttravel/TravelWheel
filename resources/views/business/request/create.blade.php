@extends('business.layout')
@section('title', 'Request a corporate account')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Request a corporate account</h1>
            <p class="biz-sub">Tell us about your company. Once we've checked your details, we'll email your named contact an invitation.</p>

            <form method="POST" action="{{ route('business.request.store') }}">
                @csrf
                {{-- display:none, so browsers never auto-fill it; only bots reading the HTML do --}}
                <div class="biz-trap" aria-hidden="true">
                    <label for="hp_check">Leave this empty</label>
                    <input id="hp_check" name="hp_check" type="text" tabindex="-1" autocomplete="off">
                </div>

                @include('business.partials.field', ['name' => 'company_name', 'label' => 'Registered company name', 'autocomplete' => 'organization'])
                @include('business.partials.field', ['name' => 'cac_number', 'label' => 'CAC number', 'help' => 'For example RC123456.'])
                @include('business.partials.field', ['name' => 'contact_name', 'label' => 'Your name', 'autocomplete' => 'name'])
                @include('business.partials.field', ['name' => 'contact_email', 'label' => 'Work email', 'type' => 'email', 'autocomplete' => 'email'])
                @include('business.partials.field', ['name' => 'contact_phone', 'label' => 'Phone number', 'type' => 'tel', 'autocomplete' => 'tel'])

                <button type="submit" class="biz-btn biz-btn-full">Send request</button>
            </form>
        </div>
        <p class="biz-foot">Already have an account? <a href="{{ route('business.login') }}">Sign in</a></p>
    </div>
@endsection
