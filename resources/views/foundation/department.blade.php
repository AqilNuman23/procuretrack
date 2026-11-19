@extends('foundation.layout')
@section('content')
<section class="panel prose"><p>{{ __('Reference') }}: {{ $department->DPNo }} · {{ __('Code') }}: {{ $department->DPCode }} · {{ $department->DPActive ? __('Active') : __('Inactive') }}</p></section>
@can('edit', $currentModule)<form method="POST" action="{{ route('departments.update', $department) }}" class="panel form-panel">@csrf<div class="panel-heading"><h2>{{ __('Edit department') }}</h2></div>@include('foundation.partials.department-fields')<div class="form-actions"><button class="button primary">{{ __('Save department') }}</button></div></form>@endcan
<a href="{{ route('departments.index') }}">{{ __('Back to departments') }}</a>
@endsection
