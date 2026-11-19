@extends('foundation.layout')
@section('page-actions')
<x-foundation.create-dialog id="create-team" title="Create team" :action="route('teams.store')">
    <div class="form-fields">
        <x-foundation.field name="code" label="Team code" maxlength="30" />
        <x-foundation.field name="name" label="Team name" maxlength="150" />
    </div>
</x-foundation.create-dialog>
@endsection
@section('content')
<x-foundation.table :records="$teams" :headers="[__('Code'), __('Team'), __('PIC'), __('Members')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('teams.index')" search-label="Search teams">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="['' => __('All statuses'), 'active' => __('Active'), 'inactive' => __('Inactive')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>

@forelse($teams as $team)<tr><td>{{ $team->TMCode }}</td><td><a href="{{ route('teams.show', $team) }}">{{ $team->TMName }}</a></td><td>{{ $team->picAssignment?->user?->USName ?? __('Not assigned') }}</td><td>{{ $team->members_count }}</td></tr>@empty<tr><td colspan="4">{{ __('No matching teams. Clear your search or create a team.') }}</td></tr>@endforelse
</x-foundation.table>
@endsection
