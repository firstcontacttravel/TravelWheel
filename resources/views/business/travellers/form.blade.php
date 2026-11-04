@extends('business.layout')
@section('title', $traveller ? 'Edit traveller' : 'Add a traveller')

@php
    $passport = $traveller ? (array) $traveller->passport : [];
    $select = fn (string $name, mixed $saved, string $default = '') => old($name, $saved ?? $default);
@endphp

@section('content')
    <h1>{{ $traveller ? 'Edit traveller' : 'Add a traveller' }}</h1>
    <p class="biz-sub">Enter names exactly as printed on the passport.</p>

    <form method="POST" action="{{ $traveller ? route('business.travellers.update', $traveller->id) : route('business.travellers.store') }}" autocomplete="off">
        @csrf
        @if ($traveller)
            @method('PUT')
        @endif

        <div class="biz-card">
            <h2 style="margin-bottom:12px">Traveller</h2>
            <div class="biz-grid">
                <div class="biz-field">
                    <label for="title">Title</label>
                    <select id="title" name="title" required>
                        <option value="">Select</option>
                        @foreach (['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Master'] as $option)
                            <option value="{{ $option }}" @selected($select('title', $traveller?->title) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('title')<div class="biz-error">{{ $message }}</div>@enderror
                </div>
                @include('business.partials.field', ['name' => 'first_name', 'label' => 'First name', 'value' => old('first_name', $traveller?->first_name)])
                @include('business.partials.field', ['name' => 'middle_name', 'label' => 'Middle name (optional)', 'required' => false, 'value' => old('middle_name', $traveller?->middle_name)])
                @include('business.partials.field', ['name' => 'last_name', 'label' => 'Last name', 'value' => old('last_name', $traveller?->last_name)])
                @include('business.partials.field', ['name' => 'dob', 'label' => 'Date of birth', 'type' => 'date', 'value' => old('dob', $traveller?->dob)])
                <div class="biz-field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select</option>
                        <option value="M" @selected($select('gender', $traveller?->gender) === 'M')>Male</option>
                        <option value="F" @selected($select('gender', $traveller?->gender) === 'F')>Female</option>
                    </select>
                    @error('gender')<div class="biz-error">{{ $message }}</div>@enderror
                </div>
                <div class="biz-field">
                    <label for="nationality">Nationality</label>
                    <select id="nationality" name="nationality" required>
                        @foreach ($nationalities as $code => $name)
                            <option value="{{ $code }}" @selected($select('nationality', $traveller?->nationality, 'NG') === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('nationality')<div class="biz-error">{{ $message }}</div>@enderror
                </div>
                @include('business.partials.field', ['name' => 'email', 'label' => 'Email (optional)', 'type' => 'email', 'required' => false, 'value' => old('email', $traveller?->email), 'help' => 'They\'ll get a copy of their e-tickets.'])
                @include('business.partials.field', ['name' => 'phone', 'label' => 'Phone (optional)', 'type' => 'tel', 'required' => false, 'value' => old('phone', $traveller?->phone)])
                @include('business.partials.field', ['name' => 'frequent_flyer_number', 'label' => 'Frequent flyer number (optional)', 'required' => false, 'value' => old('frequent_flyer_number', $traveller?->frequent_flyer_number)])
            </div>
        </div>

        <div class="biz-card">
            <h2>Passport</h2>
            <p class="biz-sub">Stored encrypted. Once saved, nobody can read the full number here, including you and TravelWheel staff on this page.</p>
            <div class="biz-grid">
                <div class="biz-field">
                    <label for="passport_no">Passport number{{ $traveller?->hasPassport() ? '' : ' (optional)' }}</label>
                    {{-- Never filled in with the saved number. Blank keeps it. --}}
                    <input class="biz-input" id="passport_no" name="passport_no" type="text" autocomplete="off"
                           placeholder="{{ $traveller?->maskedPassport() ?? '' }}">
                    @if ($traveller?->hasPassport())
                        <div class="biz-help">Saved number {{ $traveller->maskedPassport() }}. Leave blank to keep it, or enter a new one to replace it.</div>
                    @endif
                    @error('passport_no')<div class="biz-error">{{ $message }}</div>@enderror
                </div>
                <div class="biz-field">
                    <label for="passport_issue_country">Issuing country</label>
                    <select id="passport_issue_country" name="passport_issue_country">
                        @foreach ($nationalities as $code => $name)
                            <option value="{{ $code }}" @selected($select('passport_issue_country', $passport['passport_issue_country'] ?? null, 'NG') === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('passport_issue_country')<div class="biz-error">{{ $message }}</div>@enderror
                </div>
                @include('business.partials.field', ['name' => 'passport_issue_date', 'label' => 'Issue date', 'type' => 'date', 'required' => false, 'value' => old('passport_issue_date', $passport['passport_issue_date'] ?? null)])
                @include('business.partials.field', ['name' => 'passport_exp', 'label' => 'Expiry date', 'type' => 'date', 'required' => false, 'value' => old('passport_exp', $passport['passport_exp'] ?? null)])
            </div>
        </div>

        <button type="submit" class="biz-btn">Save traveller</button>
        <a class="biz-btn biz-btn-light" href="{{ route('business.travellers.index') }}">Cancel</a>
    </form>
@endsection
