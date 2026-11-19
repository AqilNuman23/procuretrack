@extends('foundation.layout')
@section('page-actions')
<x-foundation.create-dialog id="invite-member" title="Invite a member" :action="route('invitations.store')" submit-label="Send invitation">
    <div class="form-fields">
        <x-foundation.field name="name" label="Name" maxlength="150" />
        <x-foundation.field name="worker_id" label="Worker ID" maxlength="50" />
        <x-foundation.field name="email" label="Invitation email" type="email" maxlength="254" />
        <x-foundation.select name="team" label="Team" :translate-options="false" :options="$teams->pluck('TMName', 'TMNo')->all()" />
    </div>
</x-foundation.create-dialog>
@endsection
@section('content')
<x-foundation.table :records="$invitations" :headers="[__('Person'), __('Team'), __('Status'), __('Actions')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('invitations.index')" search-label="Search invitations">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="[''=>__('All statuses'),'invited'=>__('Invited'),'awaiting_review'=>__('Awaiting review'),'approved'=>__('Approved'),'rejected'=>__('Rejected'),'expired'=>__('Expired'),'revoked'=>__('Revoked')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>

    @forelse($invitations as $invitation)
        <tr>
            <td>
                <strong>{{ $invitation->IVName }}</strong>
                <span class="table-cell-detail">{{ $invitation->IVWorkerID }} · {{ $invitation->IVEmail }}</span>
            </td>
            <td>{{ $invitation->team->TMName }}</td>
            <td>
                <x-shared.status-badge :status="$invitation->IVStatus === 'invited' && $invitation->IVExpiresAt->isPast() ? 'expired' : $invitation->IVStatus" />
                @if($invitation->IVStatus === 'invited' && !$invitation->IVTokenHash)
                    <span class="table-cell-detail">{{ __('Email delivery needs retry.') }}</span>
                @endif
            </td>
            <td>
                <div class="table-row-actions">
                    @if(in_array($invitation->IVStatus, ['invited', 'expired']))
                        <form method="POST" action="{{ route('invitations.action', $invitation) }}" class="table-row-actions" data-confirm="{{ __('Update this invitation? Earlier invitation links will stop working.') }}">
                            @csrf
                            <button type="submit" name="action" value="resend" class="button secondary">{{ __('Resend') }}</button>
                            <button type="submit" name="action" value="revoke" class="button danger">{{ __('Revoke') }}</button>
                        </form>
                    @elseif($invitation->IVStatus === 'awaiting_review' && $access->administrator($actor))
                        <form method="POST" action="{{ route('invitations.action', $invitation) }}" data-confirm="{{ __('Record this review decision?') }}">
                            @csrf
                            <button type="submit" name="action" value="approved" class="button primary">{{ __('Approve') }}</button>
                        </form>
                        <x-foundation.create-dialog :id="'reject-'.$invitation->getKey()" title="Reject invitation" trigger-label="Reject" variant="danger" :icon="null" :action="route('invitations.action', $invitation)">
                            <input type="hidden" name="action" value="rejected">
                            <div class="form-fields">
                                <p>{{ $invitation->IVName }} · {{ $invitation->IVWorkerID }}</p>
                                <label class="field">
                                    <span>{{ __('Review reason (required for rejection)') }}</span>
                                    <textarea name="reason" required maxlength="2000" rows="3"></textarea>
                                </label>
                            </div>
                        </x-foundation.create-dialog>
                    @else
                        <span class="muted">—</span>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="4"><x-foundation.state>{{ __('No invitations match this filter.') }}</x-foundation.state></td></tr>
    @endforelse
</x-foundation.table>
@endsection
