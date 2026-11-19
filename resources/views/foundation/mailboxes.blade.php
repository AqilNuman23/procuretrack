@extends('foundation.layout')
@section('content')
<section class="panel prose"><p>{{ __('Create or verify the mailbox with your email provider first. Confirm it can send and receive mail and belongs to the correct employee. This page records that verification; it does not create a mailbox or change provider passwords.') }}</p></section>
<section class="panel">
    <x-foundation.table-controls :action="route('mailboxes.index')" search-label="Search mailboxes">
        <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="['' => __('All statuses'), 'not_started' => __('Not started'), 'pending' => __('Pending'), 'ready' => __('Ready'), 'failed' => __('Failed')]" />
    </x-foundation.table-controls>
</section>
@forelse($mailboxes as $mailbox)<section class="panel form-panel"><div class="panel-heading"><h2>{{ $mailbox->user->USName }}</h2><span><x-shared.status-badge :status="$mailbox->MXStatus" /> {{ $mailbox->MXIsMock ? __('(local mock)') : '' }}</span></div><div class="form-fields"><p>{{ $mailbox->MXEmail ?? __('No company address assigned') }}</p>
@if($mailbox->user->USStatus === 'pending_activation' && $mailbox->user->USApprovedAt)
<form method="POST" action="{{ route('mailboxes.update', $mailbox->user) }}">@csrf<input type="hidden" name="action" value="verify"><x-foundation.field name="email" label="Verified company mailbox" type="email" :value="$mailbox->MXEmail" maxlength="254" /><label class="field"><span><input type="checkbox" name="verified" value="1" required> {{ __('I verified mailbox ownership and send/receive access with the provider.') }}</span></label><button class="button primary">{{ __('Record verification & send activation') }}</button></form>
@if($mailbox->MXStatus === 'ready' && !$mailbox->MXIsMock)<form method="POST" action="{{ route('mailboxes.update', $mailbox->user) }}">@csrf<input type="hidden" name="action" value="resend"><button class="button secondary">{{ __('Resend activation') }}</button></form>@endif
@else<p>{{ __('Initial mailbox verification is available only for approved accounts awaiting activation.') }}</p>@endif
</div></section>@empty<x-foundation.state>{{ __('No mailbox assignments yet. Approve an accepted invitation first.') }}</x-foundation.state>@endforelse
<x-foundation.pagination :records="$mailboxes" />
@endsection
