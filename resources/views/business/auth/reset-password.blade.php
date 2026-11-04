@extends('business.layout')
@section('title', 'Choose a new password')

@section('content')
    <div class="biz-narrow">
        <div class="biz-card">
            <h1>Choose a new password</h1>
            <p class="biz-sub">Use at least 10 characters, with letters and numbers.</p>

            <form method="POST" action="{{ route('business.password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                @include('business.partials.field', ['name' => 'email', 'label' => 'Work email', 'type' => 'email', 'autocomplete' => 'username', 'value' => old('email', $email)])
                @include('business.partials.field', ['name' => 'password', 'label' => 'New password', 'type' => 'password', 'autocomplete' => 'new-password'])
                @include('business.partials.field', ['name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'autocomplete' => 'new-password'])
                <button type="submit" class="biz-btn biz-btn-full">Change password</button>
            </form>
        </div>
    </div>
@endsection
