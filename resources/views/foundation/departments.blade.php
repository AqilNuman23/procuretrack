@extends('foundation.layout')
@section('page-actions')
@can('edit', $currentModule)
    <x-foundation.create-dialog id="create-department" title="Create department" :action="route('departments.store')">
        @include('foundation.partials.department-fields', ['department' => null])
    </x-foundation.create-dialog>
@endcan
@endsection
@section('content')
<x-foundation.table :records="$records" :headers="[__('Code'), __('Name'), __('Status')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('departments.index')" search-label="Search departments" :filter-defaults="['status' => '', 'sort' => 'name', 'direction' => 'asc']">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="[''=>__('All statuses'),'active'=>__('Active'),'inactive'=>__('Inactive')]" />
            <x-foundation.select name="sort" label="Sort by" :value="request()->query('sort', 'name')" :options="['name'=>__('Name'),'code'=>__('Code')]" />
            <x-foundation.select name="direction" label="Direction" :value="request()->query('direction', 'asc')" :options="['asc'=>__('Ascending'),'desc'=>__('Descending')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
@forelse($records as $record)<tr><td>{{ $record->DPCode }}</td><td><a href="{{ route('departments.show', $record) }}">{{ $record->DPName }}</a></td><td><x-shared.status-badge :status="$record->DPActive ? 'active' : 'inactive'" /></td></tr>@empty<tr><td colspan="3"><x-foundation.state>{{ __('No departments match. Clear the filters or add a department if permitted.') }}</x-foundation.state></td></tr>@endforelse</x-foundation.table>
@endsection
