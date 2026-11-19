@extends('foundation.layout')
@section('content')
<x-foundation.table :records="$teams" :headers="[__('Code'), __('Team'), __('PIC'), __('Members')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('teams.index')" search-label="Search teams">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status')" :options="['' => __('All statuses'), 'active' => __('Active'), 'inactive' => __('Inactive')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>

@forelse($teams as $team)<tr><td>{{ $team->TMCode }}</td><td><a href="{{ route('teams.show', $team) }}">{{ $team->TMName }}</a></td><td>{{ $team->picAssignment?->user?->USName ?? __('Not assigned') }}</td><td>{{ $team->members_count }}</td></tr>@empty<tr><td colspan="4">{{ __('No matching teams. Clear your search or create a team below.') }}</td></tr>@endforelse
</x-foundation.table>
<form method="POST" action="{{ route('teams.store') }}" class="panel form-panel">@csrf<div class="panel-heading"><h2>{{ __('Create team') }}</h2></div><div class="form-fields"><x-foundation.field name="code" label="Team code" maxlength="30" /><x-foundation.field name="name" label="Team name" maxlength="150" /></div><div class="form-actions"><button class="button primary">{{ __('Create team') }}</button></div></form>
@endsection
