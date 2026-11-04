@extends('business.layout')
@section('title', 'Accept your invitation')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Join {{ $invitation->company->name }}</h1>
            <p class="biz-sub">You've been invited as {{ $invitation->role === 'admin' ? 'the Company Admin' : 'a Booker' }}. You'll sign in with {{ $invitation->email }}.</p>

            <form method="POST" action="{{ route('business.invitation.accept', $token) }}">
                @csrf
                @include('business.partials.field', ['name' => 'name', 'label' => 'Your full name', 'autocomplete' => 'name', 'value' => old('name', $invitation->name)])
                @include('business.partials.field', ['name' => 'password', 'label' => 'Choose a password', 'type' => 'password', 'autocomplete' => 'new-password', 'help' => 'At least 10 characters, with letters and numbers.'])
                @include('business.partials.field', ['name' => 'password_confirmation', 'label' => 'Confirm password', 'type' => 'password', 'autocomplete' => 'new-password'])
                <button type="submit" class="biz-btn biz-btn-full">Create account</button>
            </form>
        </div>
    </div>
@endsection
