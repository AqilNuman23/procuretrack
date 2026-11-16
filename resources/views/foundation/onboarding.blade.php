@extends('foundation.layout')
@section('content')
<form class="panel form-panel" method="POST" action="{{ route($step === 'invitation' ? 'invitation.accept' : 'activation.store') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><div class="form-fields">
@if($step === 'invitation')<p>Accepting sends your application for administrator review. You can sign in after approval, company mailbox verification, and password setup.</p>
@else<x-foundation.field name="worker_id" label="Your Worker ID" maxlength="50" autocomplete="username" /><x-foundation.field name="password" label="Choose a private password (at least 12 characters)" type="password" autocomplete="new-password" /><x-foundation.field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />@endif
</div><div class="form-actions"><button class="button primary">{{ $step === 'invitation' ? 'Accept invitation' : 'Activate account' }}</button></div></form>
@endsection
