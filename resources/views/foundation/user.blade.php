@extends('foundation.layout')
@section('content')
<section class="panel form-panel"><div class="panel-heading"><h2>Account details</h2><span class="badge blue">{{ $person->USStatus }}</span></div><dl class="detail-list">@foreach(['Worker ID'=>$person->USWorkerID,'Team'=>$person->team?->TMName ?? '—','Company email'=>$person->USEmail ?? 'Not assigned','Mailbox'=>$person->mailbox?->MXStatus ?? 'Not started'] as $label=>$value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl></section>
@endsection
