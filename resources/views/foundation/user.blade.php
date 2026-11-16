@extends('foundation.layout')
@section('content')
<section class="panel form-panel"><div class="panel-heading"><h2>Account details</h2><span class="badge blue">{{ $person->USStatus }}</span></div><dl class="detail-list">@foreach(['Worker ID'=>$person->USWorkerID,'Team'=>$person->team?->TMName ?? '—','Company email'=>$person->USEmail ?? 'Not assigned','Mailbox'=>$person->mailbox?->MXStatus ?? 'Not started'] as $label=>$value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl></section>
@if($access->administrator($actor))
<form method="POST" action="{{ route('users.update', $person) }}" class="panel form-panel" data-confirm="Save this account change? Team and status changes end existing sessions.">@csrf<div class="panel-heading"><h2>Manage account</h2></div><div class="form-fields">
<x-foundation.field name="name" label="Name" :value="$person->USName" maxlength="150" />
<label class="field"><span>Team</span><select name="team"><option value="">No team</option>@foreach(\App\Models\Foundation\Team::where('TM_CONo', $actor->US_CONo)->where('TMActive', true)->orderBy('TMName')->get() as $team)<option value="{{ $team->TMNo }}" @selected(old('team', $person->US_TMNo) === $team->TMNo)>{{ $team->TMName }}</option>@endforeach</select></label>
<label class="field"><span>Account status</span><select name="status"><option value="active" @selected(old('status', $person->USStatus) === 'active')>Active</option><option value="deactivated" @selected(old('status', $person->USStatus) === 'deactivated')>Deactivated</option></select></label>
<p>Complete onboarding before activation. Replace a current PIC before transfer or deactivation. Deactivation does not disable the provider mailbox; complete that separately.</p></div><div class="form-actions"><button class="button primary">Save account</button></div></form>
@endif
@endsection
