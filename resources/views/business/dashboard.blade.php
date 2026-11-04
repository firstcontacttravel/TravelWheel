@extends('business.layout')
@section('title', 'Dashboard')

@section('content')
    <h1>{{ $company->name }}</h1>
    <p class="biz-sub">Welcome, {{ $companyUser->name }}. You're signed in as {{ $companyUser->isCompanyAdmin() ? 'a Company Admin' : 'a Booker' }}.</p>

    <div class="biz-card">
        <h2>Book a flight</h2>
        <p class="biz-sub">Search as usual. While you're signed in, every fare shows your company's rate.</p>
        <a class="biz-btn" href="{{ route('air') }}">Search flights</a>
        <a class="biz-btn biz-btn-light" href="{{ route('business.bookings.index') }}">View bookings</a>
    </div>

    @if ($companyUser->isCompanyAdmin())
        <div class="biz-card">
            <h2>Your team</h2>
            <p class="biz-sub" style="margin-bottom:0"><a href="{{ route('business.team.index') }}">Invite bookers and manage who has access.</a></p>
        </div>
    @endif
@endsection
