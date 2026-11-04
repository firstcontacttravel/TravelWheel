@extends('business.layout')
@section('title', 'Sign in')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Sign in</h1>
            <p class="biz-sub">To your company's TravelWheel Corporate account.</p>

            @include('business.partials.status')

            <form method="POST" action="{{ route('business.login.store') }}">
                @csrf
                @include('business.partials.field', ['name' => 'email', 'label' => 'Work email', 'type' => 'email', 'autocomplete' => 'username'])
                @include('business.partials.field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'autocomplete' => 'current-password'])
                <button type="submit" class="biz-btn biz-btn-full">Sign in</button>
            </form>

            <p class="biz-foot"><a href="{{ route('business.password.request') }}">Forgot your password?</a></p>
        </div>
        <p class="biz-foot">No account yet? <a href="{{ route('business.request.create') }}">Request a corporate account</a></p>
    </div>
@endsection
