@extends('foundation.layout', ['authPage'=>true])
@section('content')
@php($actions = ['login'=>'login.store','forgot'=>'password.email','assistance'=>'recovery.store','reset'=>'password.update','verify'=>'recovery.verify.store'])
<form class="panel auth-form" action="{{ route($actions[$form]) }}" method="POST">@csrf<div class="form-fields">
@if($form === 'login')
<x-foundation.field name="login" label="Worker ID or administrator username" autocomplete="username" maxlength="50" autofocus />
<x-foundation.field name="password" label="Password" type="password" autocomplete="current-password" maxlength="128" />
<a class="form-forgot" href="{{ route('password.request') }}">Forgot password?</a>
@elseif(in_array($form, ['forgot','assistance']))
<x-foundation.field name="worker_id" label="Worker ID" maxlength="50" autofocus />
@if($form === 'forgot')<x-foundation.field name="email" label="Current recovery email" type="email" autocomplete="email" maxlength="254" />@else<x-foundation.field name="new_email" label="Replacement recovery email (optional)" type="email" :required="false" maxlength="254" /><p class="auth-footnote">If you provide a replacement address, you must verify it after resetting your password. If you cannot access your company mailbox either, contact an administrator through an established channel.</p>@endif
@else
<input type="hidden" name="token" value="{{ $token }}">
@if($form === 'reset')<x-foundation.field name="password" label="New password" type="password" autocomplete="new-password" minlength="12" maxlength="128" /><x-foundation.field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" minlength="12" maxlength="128" /><p class="auth-footnote">Use at least 12 characters. Your existing sessions will end.</p>@if($replacement ?? null)<label class="checkbox"><input type="checkbox" name="confirm_recovery_email" value="1">I requested {{ $replacement }} as my new recovery email. Send a verification link there.</label><p class="auth-footnote">Leave this unchecked if you do not recognize the address. Your recovery email will stay unchanged.</p>@endif @else<p>Confirm access to this address. The previous recovery email will be kept in restricted history and will no longer receive password reset links.</p>@endif
@endif
</div><div class="form-actions"><button class="button primary" type="submit">{{ ['login'=>'Sign in','forgot'=>'Send reset instructions','assistance'=>'Request PIC verification','reset'=>'Set new password','verify'=>'Verify recovery email'][$form] }}<x-design.icon name="arrow" /></button></div></form>
@if($form === 'forgot')<p class="auth-footnote"><a href="{{ route('recovery.request') }}">Forgot invitation email or lost access to it?</a></p>@endif
@if($form !== 'login')<p class="auth-footnote"><a href="{{ route('login') }}">Back to sign in</a></p>@else<p class="auth-footnote">Accounts are invitation-only. Contact your team PIC to get started.</p>@endif
@endsection
