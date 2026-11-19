@extends('foundation.layout')
@section('content')
@if(!$currentModule->MOParent_MONo)
<div class="settings-grid module-overview-grid">
    @forelse($children as $child)
        <a class="panel setting-card module-overview-card" href="{{ route('modules.show', $child->MOCode) }}">
            <span class="tile-icon"><x-design.icon :name="$registry[$child->MOCode]['icon'] ?? 'grid'" /></span>
            <h2>{{ __($child->MOName) }}</h2>
        </a>
    @empty
        <section class="panel prose"><p>{{ __('No pages are assigned in this module.') }}</p></section>
    @endforelse
</div>
@elseif($users !== null)
<x-foundation.table :records="$users" :headers="[__('Name'), __('Worker ID'), __('Team'), __('Account status')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('modules.show', $currentModule->MOCode)" search-label="Search people">
            <x-foundation.select name="status" label="Account status" :required="false" :value="request()->query('status')" :options="[''=>__('All statuses'),'active'=>__('Active'),'pending_activation'=>__('Pending activation'),'deactivated'=>__('Deactivated')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
@forelse($users as $person)<tr><td><a href="{{ route('users.show', $person) }}">{{ $person->USName }}</a></td><td>{{ $person->USWorkerID }}</td><td>{{ $person->team?->TMName ?? '—' }}</td><td><x-shared.status-badge :status="$person->USStatus" /></td></tr>@empty<tr><td colspan="4"><x-foundation.state>{{ __('No people match these filters.') }}</x-foundation.state></td></tr>@endforelse</x-foundation.table>
@elseif($currentModule->MOCode === 'settings.company')
<form class="panel form-panel" action="{{ route('company.update') }}" method="POST">@csrf<div class="panel-heading"><h2>{{ __('Company identity') }}</h2><span class="badge blue">{{ $access->can($actor, $currentModule, 'edit') ? __('Read & Edit') : __('Read') }}</span></div><div class="form-fields"><x-foundation.field name="name" label="Company name" :value="$actor->company->COName" :disabled="!$access->can($actor, $currentModule, 'edit')" maxlength="150" /><p>{{ __('Timezone:') }} {{ $actor->company->COTimezone }} · {{ __('Currency:') }} {{ $actor->company->COCurrency }}</p></div>@can('edit', $currentModule)<div class="form-actions"><button class="button primary">{{ __('Save changes') }}</button></div>@endcan</form>
@elseif($currentModule->MOCode === 'help.manual')
<section class="panel prose"><h2>{{ __('Signing in and recovering access') }}</h2><p>{{ __('Use your Worker ID and application password. Accepting an invitation does not activate an account: review, mailbox readiness, and password setup must be complete.') }}</p><p>{{ __('For a forgotten password, provide your Worker ID and current recovery email. If that address is inaccessible, request PIC verification. Approved assisted-reset links go to your company mailbox.') }}</p><h2>{{ __('Your permissions') }}</h2><p>{{ __('Read allows viewing authorized records. Read & Edit adds creating and updating where the module supports those actions. It does not grant approval, deletion, sensitive export, or access-administration authority.') }}</p><p>{{ __('Contact your administrator if neither mailbox is accessible. A PIC cannot approve their own recovery request.') }}</p></section>
@elseif(isset($registry[$currentModule->MOCode]['tickets']))
@include('foundation.partials.module-starter', ['tickets' => $registry[$currentModule->MOCode]['tickets']])
@else
<section class="panel prose"><span class="tile-icon purple"><x-design.icon name="shield" /></span><h2>{{ __($currentModule->MOName) }}</h2><p>{{ __('Your current level is') }} <strong>{{ $access->can($actor, $currentModule, 'edit') ? __('Read & Edit') : __('Read') }}</strong>.</p><p>{{ __('This workspace verifies the module’s access boundary. Financial records and procurement workflows will be introduced in later tickets.') }}</p></section>
@endif
@endsection
