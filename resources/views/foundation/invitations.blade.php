@extends('foundation.layout')
@section('content')
<x-foundation.table :records="$invitations" :headers="[__('Person'), __('Team'), __('Status'), __('Actions')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('invitations.index')" search-label="Search invitations">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="[''=>__('All statuses'),'invited'=>__('Invited'),'awaiting_review'=>__('Awaiting review'),'approved'=>__('Approved'),'rejected'=>__('Rejected'),'expired'=>__('Expired'),'revoked'=>__('Revoked')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>

@forelse($invitations as $invitation)<tr><td>{{ $invitation->IVName }}<br>{{ $invitation->IVWorkerID }}<br>{{ $invitation->IVEmail }}</td><td>{{ $invitation->team->TMName }}</td><td><x-shared.status-badge :status="$invitation->IVStatus === 'invited' && $invitation->IVExpiresAt->isPast() ? 'expired' : $invitation->IVStatus" />@if($invitation->IVStatus === 'invited' && !$invitation->IVTokenHash)<br>{{ __('Email delivery needs retry.') }}@endif</td><td>
@if(in_array($invitation->IVStatus, ['invited','expired']))<form method="POST" action="{{ route('invitations.action', $invitation) }}" data-confirm="{{ __('Update this invitation? Earlier invitation links will stop working.') }}">@csrf<button name="action" value="resend" class="button secondary">{{ __('Resend') }}</button><button name="action" value="revoke" class="button secondary">{{ __('Revoke') }}</button></form>@endif
@if($invitation->IVStatus === 'awaiting_review' && $access->administrator($actor))<form method="POST" action="{{ route('invitations.action', $invitation) }}" data-confirm="{{ __('Record this review decision?') }}">@csrf<x-foundation.field name="reason" label="Review reason (required for rejection)" :required="false" maxlength="2000" /><button name="action" value="approved" class="button primary">{{ __('Approve') }}</button><button name="action" value="rejected" class="button secondary">{{ __('Reject') }}</button></form>@endif
</td></tr>@empty<tr><td colspan="4"><x-foundation.state>{{ __('No invitations match this filter.') }}</x-foundation.state></td></tr>@endforelse
</x-foundation.table>
<form method="POST" action="{{ route('invitations.store') }}" class="panel form-panel">@csrf<div class="panel-heading"><h2>{{ __('Invite a member') }}</h2></div><div class="form-fields"><x-foundation.field name="name" label="Name" maxlength="150" /><x-foundation.field name="worker_id" label="Worker ID" maxlength="50" /><x-foundation.field name="email" label="Invitation email" type="email" maxlength="254" /><x-foundation.select name="team" label="Team" :translate-options="false" :options="$teams->pluck('TMName','TMNo')->all()" /></div><div class="form-actions"><button class="button primary">{{ __('Send invitation') }}</button></div></form>
@endsection
