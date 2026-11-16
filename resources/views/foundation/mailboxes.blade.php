@extends('foundation.layout')
@section('content')
<section class="panel prose"><p>Create or verify the mailbox with your email provider first. Confirm it can send and receive mail and belongs to the correct employee. This page records that verification; it does not create a mailbox or change provider passwords.</p></section>
@forelse($mailboxes as $mailbox)<section class="panel form-panel"><div class="panel-heading"><h2>{{ $mailbox->user->USName }}</h2><span>{{ $mailbox->MXStatus }} {{ $mailbox->MXIsMock ? '(local mock)' : '' }}</span></div><div class="form-fields"><p>{{ $mailbox->MXEmail ?? 'No company address assigned' }}</p>
@if($mailbox->user->USStatus === 'pending_activation' && $mailbox->user->USApprovedAt)
<form method="POST" action="{{ route('mailboxes.update', $mailbox->user) }}">@csrf<input type="hidden" name="action" value="verify"><x-foundation.field name="email" label="Verified company mailbox" type="email" :value="$mailbox->MXEmail" maxlength="254" /><label class="field"><span><input type="checkbox" name="verified" value="1" required> I verified mailbox ownership and send/receive access with the provider.</span></label><button class="button primary">Record verification & send activation</button></form>
@if($mailbox->MXStatus === 'ready' && !$mailbox->MXIsMock)<form method="POST" action="{{ route('mailboxes.update', $mailbox->user) }}">@csrf<input type="hidden" name="action" value="resend"><button class="button secondary">Resend activation</button></form>@endif
@else<p>Initial mailbox verification is available only for approved accounts awaiting activation.</p>@endif
</div></section>@empty<x-foundation.state>No mailbox assignments yet. Approve an accepted invitation first.</x-foundation.state>@endforelse
<x-foundation.pagination :records="$mailboxes" />
@endsection
