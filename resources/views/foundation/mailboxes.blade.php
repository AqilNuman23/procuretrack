@extends('foundation.layout')
@section('page-help')
<p>{{ __('Create or verify the mailbox with your email provider first. Confirm it can send and receive mail and belongs to the correct employee. This page records that verification; it does not create a mailbox or change provider passwords.') }}</p>
@endsection
@section('content')
<x-foundation.table :records="$mailboxes" :headers="[__('Employee'), __('Company mailbox'), __('Status'), __('Actions')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('mailboxes.index')" search-label="Search mailboxes">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="['' => __('All statuses'), 'not_started' => __('Not started'), 'pending' => __('Pending'), 'ready' => __('Ready'), 'failed' => __('Failed')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
    @forelse($mailboxes as $mailbox)
        <tr>
            <td>
                <strong>{{ $mailbox->user->USName }}</strong>
                <span class="table-cell-detail">{{ $mailbox->user->USWorkerID }}</span>
            </td>
            <td>{{ $mailbox->MXEmail ?? '—' }}</td>
            <td>
                <x-shared.status-badge :status="$mailbox->MXStatus" />
                @if($mailbox->MXIsMock)<span class="table-cell-detail">{{ __('(local mock)') }}</span>@endif
            </td>
            <td>
                @if($mailbox->user->USStatus === 'pending_activation' && $mailbox->user->USApprovedAt)
                    <div class="table-row-actions">
                        <x-foundation.create-dialog :id="'verify-'.$mailbox->getKey()" title="Verify mailbox" variant="secondary" :icon="null" :action="route('mailboxes.update', $mailbox->user)" submit-label="Record verification & send activation">
                            <input type="hidden" name="action" value="verify">
                            <div class="form-fields">
                                <p>{{ $mailbox->user->USName }} · {{ $mailbox->user->USWorkerID }}</p>
                                <x-foundation.field name="email" label="Verified company mailbox" type="email" :value="$mailbox->MXEmail" maxlength="254" />
                                <label class="checkbox">
                                    <input type="checkbox" name="verified" value="1" required>
                                    <span>{{ __('I verified mailbox ownership and send/receive access with the provider.') }}</span>
                                </label>
                            </div>
                        </x-foundation.create-dialog>
                        @if($mailbox->MXStatus === 'ready' && !$mailbox->MXIsMock)
                            <form method="POST" action="{{ route('mailboxes.update', $mailbox->user) }}" data-confirm="{{ __('Resend activation instructions?') }}">
                                @csrf
                                <button type="submit" class="button secondary" name="action" value="resend">{{ __('Resend activation') }}</button>
                            </form>
                        @endif
                    </div>
                @else
                    <span class="muted" title="{{ __('Initial mailbox verification is available only for approved accounts awaiting activation.') }}">—</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="4"><x-foundation.state>{{ __('No mailbox assignments match these filters.') }}</x-foundation.state></td></tr>
    @endforelse
</x-foundation.table>
@endsection
