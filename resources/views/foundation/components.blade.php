@extends('foundation.layout')
@use(App\Support\Foundation\TableQuery)
@use(Illuminate\Pagination\Paginator)
@section('content')
<section class="panel prose"><h2>{{ __('Shared functional patterns') }}</h2><p>{{ __('These are the same Blade components used by Teams, Invitations, and Departments. This showcase does not save records.') }}</p></section>
<div class="panel form-panel"><div class="form-fields"><x-foundation.field name="example_name" label="Example text field" :required="false" value="Department name" /><x-foundation.select name="example_status" label="Example select" :options="[1=>__('Active'),0=>__('Inactive')]" :value="1" /><button class="button primary" type="button">{{ __('Primary action') }}</button><button class="button secondary" type="button">{{ __('Secondary action') }}</button><button class="button secondary" type="button" disabled>{{ __('Disabled action') }}</button></div></div>
<x-foundation.state kind="loading">{{ __('Loading records…') }}</x-foundation.state>
<x-foundation.state>{{ __('No records yet. Create a record to get started.') }}</x-foundation.state>
<x-foundation.state kind="error" :retry="route('components.index')">{{ __('Records could not be loaded. Try again.') }}</x-foundation.state>
@php
    $table = new TableQuery(request());
    $search = request()->query('search');
    $page = Paginator::resolveCurrentPage();
    $exampleName = __('Example department');
    $matches = $table->value('status', ['active', 'inactive']) !== 'inactive'
        && (!is_string($search) || mb_stripos('EXAMPLE '.$exampleName, trim($search)) !== false);
    $examples = new Paginator($matches && $page === 1 ? ['EXAMPLE'] : [], $table->pageSize(), $page, ['path' => request()->url()]);
@endphp
<x-foundation.table :headers="[__('Code'), __('Name'), __('Status')]" :records="$examples->withQueryString()">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('components.index')" search-label="Search departments">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="['' => __('All statuses'), 'active' => __('Active'), 'inactive' => __('Inactive')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
    @forelse($examples as $code)
        <tr><td>{{ $code }}</td><td>{{ $exampleName }}</td><td><x-shared.status-badge status="active" /></td></tr>
    @empty
        <tr><td colspan="3">{{ __('No departments match. Clear the filters or add a department if permitted.') }}</td></tr>
    @endforelse
</x-foundation.table>
<section class="panel prose"><h2>{{ __('Validation and saving') }}</h2><p>{{ __('Functional forms display server validation errors beside fields and in the page summary. Submitting shows a saving message and blocks repeated submission. Sensitive actions ask for confirmation. Use the Departments form to check real validation, search, pagination, and permissions.') }}</p></section>
@endsection
