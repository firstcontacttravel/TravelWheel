@extends('business.layout')
@section('title', 'Travellers')

@section('content')
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start;">
        <div>
            <h1>Saved travellers</h1>
            <p class="biz-sub">People your company books for often. Choose them at checkout instead of retyping their details.</p>
        </div>
        <a class="biz-btn" href="{{ route('business.travellers.create') }}">Add a traveller</a>
    </div>

    @include('business.partials.status')

    <form method="GET" action="{{ route('business.travellers.index') }}" class="biz-card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label for="q">Search by name</label>
            <input class="biz-input" id="q" name="q" type="search" value="{{ $search }}">
        </div>
        <button type="submit" class="biz-btn biz-btn-light">Search</button>
    </form>

    <div class="biz-card">
        @if ($travellers->isEmpty())
            <p class="biz-sub" style="margin:0">No saved travellers yet.</p>
        @else
            <table class="biz-table">
                <thead><tr><th>Name</th><th>Passport</th><th>Passport expiry</th><th><span style="position:absolute;left:-9999px">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($travellers as $traveller)
                        <tr>
                            <td>
                                <strong>{{ $traveller->title }} {{ $traveller->fullName() }}</strong>
                                @if ($traveller->email)
                                    <div class="biz-help">{{ $traveller->email }}</div>
                                @endif
                            </td>
                            <td>{{ $traveller->maskedPassport() ?? 'None saved' }}</td>
                            <td>
                                @if ($traveller->passport_expires_on)
                                    {{ $traveller->passport_expires_on->format('j M Y') }}
                                    @if ($traveller->passport_expires_on->isPast())
                                        <span class="biz-badge biz-badge-off">Expired</span>
                                    @elseif ($traveller->passport_expires_on->lt(now()->addMonths(6)))
                                        <span class="biz-badge biz-badge-off">Expiring soon</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="biz-actions">
                                    <a class="biz-btn biz-btn-light biz-btn-small" href="{{ route('business.travellers.edit', $traveller->id) }}">Edit</a>
                                    @if ($companyUser->isCompanyAdmin())
                                        <form method="POST" action="{{ route('business.travellers.destroy', $traveller->id) }}" onsubmit="return confirm('Delete this saved traveller?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="biz-btn biz-btn-danger biz-btn-small">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($travellers->hasPages())
                <div style="display:flex;justify-content:space-between;margin-top:14px;font-size:14px;">
                    @if ($travellers->previousPageUrl())<a href="{{ $travellers->previousPageUrl() }}">Previous</a>@else<span></span>@endif
                    @if ($travellers->nextPageUrl())<a href="{{ $travellers->nextPageUrl() }}">Next</a>@endif
                </div>
            @endif
        @endif
    </div>
@endsection
