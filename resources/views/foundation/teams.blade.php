@extends('foundation.layout')
@section('content')
<form method="GET" class="panel form-panel"><div class="form-fields"><x-foundation.field name="search" label="Search teams" :required="false" :value="request('search')" maxlength="150" /><button class="button secondary">{{ __('Search') }}</button><a href="{{ route('teams.index') }}">{{ __('Clear filters') }}</a></div></form>
<x-foundation.table :headers="[__('Code'), __('Team'), __('PIC'), __('Members')]">
@forelse($teams as $team)<tr><td>{{ $team->TMCode }}</td><td><a href="{{ route('teams.show', $team) }}">{{ $team->TMName }}</a></td><td>{{ $team->picAssignment?->user?->USName ?? __('Not assigned') }}</td><td>{{ $team->members_count }}</td></tr>@empty<tr><td colspan="4">{{ __('No matching teams. Clear your search or create a team below.') }}</td></tr>@endforelse
</x-foundation.table><div class="live-pagination">{{ $teams->links() }}</div>
<form method="POST" action="{{ route('teams.store') }}" class="panel form-panel">@csrf<div class="panel-heading"><h2>{{ __('Create team') }}</h2></div><div class="form-fields"><x-foundation.field name="code" label="Team code" maxlength="30" /><x-foundation.field name="name" label="Team name" maxlength="150" /></div><div class="form-actions"><button class="button primary">{{ __('Create team') }}</button></div></form>
@endsection
