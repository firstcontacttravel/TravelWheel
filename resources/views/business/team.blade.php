@extends('business.layout')
@section('title', 'Team')

@section('content')
    <h1>Team</h1>
    <p class="biz-sub">Admins manage the team. Bookers search and book.</p>

    @include('business.partials.status')
    @error('team')
        <div class="biz-alert biz-alert-bad" role="alert">{{ $message }}</div>
    @enderror

    <div class="biz-card">
        <h2>Invite someone</h2>
        <p class="biz-sub">They'll get a link that works once and expires in {{ config('corporate.invitation_days') }} days.</p>
        <form method="POST" action="{{ route('business.team.invite') }}">
            @csrf
            <div class="biz-grid">
                @include('business.partials.field', ['name' => 'name', 'label' => 'Name (optional)', 'required' => false])
                @include('business.partials.field', ['name' => 'email', 'label' => 'Work email', 'type' => 'email'])
                <div class="biz-field">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        @foreach (\App\Models\CompanyUser::ROLES as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'booker') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="biz-btn">Send invitation</button>
        </form>
    </div>

    @if ($invitations->isNotEmpty())
        <div class="biz-card">
            <h2 style="margin-bottom:12px">Open invitations</h2>
            <table class="biz-table">
                <thead><tr><th>Email</th><th>Role</th><th>Expires</th><th><span style="position:absolute;left:-9999px">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($invitations as $invitation)
                        <tr>
                            <td>{{ $invitation->email }}</td>
                            <td>{{ \App\Models\CompanyUser::ROLES[$invitation->role] ?? $invitation->role }}</td>
                            <td>{{ $invitation->expires_at->timezone('Africa/Lagos')->format('j M Y') }}</td>
                            <td>
                                <div class="biz-actions">
                                    <form method="POST" action="{{ route('business.team.invitations.resend', $invitation->id) }}">
                                        @csrf
                                        <button type="submit" class="biz-btn biz-btn-light biz-btn-small">Resend</button>
                                    </form>
                                    <form method="POST" action="{{ route('business.team.invitations.revoke', $invitation->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="biz-btn biz-btn-danger biz-btn-small">Revoke</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="biz-card">
        <h2 style="margin-bottom:12px">People</h2>
        <table class="biz-table">
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Role and access</th></tr></thead>
            <tbody>
                @foreach ($members as $member)
                    <tr>
                        <td>{{ $member->name }} @if ($member->is($companyUser))<span class="biz-help">(you)</span>@endif</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            @if ($member->isDeactivated())
                                <span class="biz-badge biz-badge-off">Deactivated</span>
                            @else
                                <span class="biz-badge">Active</span>
                            @endif
                        </td>
                        <td>
                            <div class="biz-actions">
                                <form method="POST" action="{{ route('business.team.members.role', $member->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <label for="role-{{ $member->id }}" style="position:absolute;left:-9999px">Role for {{ $member->name }}</label>
                                    <select id="role-{{ $member->id }}" name="role">
                                        @foreach (\App\Models\CompanyUser::ROLES as $value => $label)
                                            <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="biz-btn biz-btn-light biz-btn-small">Save</button>
                                </form>
                                @if ($member->isDeactivated())
                                    <form method="POST" action="{{ route('business.team.members.reactivate', $member->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="biz-btn biz-btn-light biz-btn-small">Reactivate</button>
                                    </form>
                                @elseif (! $member->is($companyUser))
                                    <form method="POST" action="{{ route('business.team.members.deactivate', $member->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="biz-btn biz-btn-danger biz-btn-small">Deactivate</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
