@extends('foundation.layout')
@section('content')
<section class="panel prose"><p>Reference: {{ $department->DPNo }} · Code: {{ $department->DPCode }} · {{ $department->DPActive ? 'Active' : 'Inactive' }}</p></section>
@can('edit', $currentModule)<form method="POST" action="{{ route('departments.update', $department) }}" class="panel form-panel">@csrf<div class="panel-heading"><h2>Edit department</h2></div>@include('foundation.partials.department-fields')<div class="form-actions"><button class="button primary">Save department</button></div></form>@endcan
<a href="{{ route('departments.index') }}">Back to departments</a>
@endsection
