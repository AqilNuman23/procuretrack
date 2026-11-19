@extends('foundation.layout')
@section('content')
<x-foundation.table :records="$emailHistory" :headers="[__('Worker'), __('Previous email'), __('Current replacement'), __('Approved by'), __('Verified')]">
    <x-slot:heading><div class="panel-heading"><h2>{{ __('Recovery email history') }}</h2><span class="badge amber">{{ __('Administrator only') }}</span></div></x-slot:heading>
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('activity.index')" search-label="Search recovery email history" table-key="emails" :filter-defaults="['sort' => 'desc']">
            <x-foundation.select name="emails_sort" label="Sort by" :required="false" :value="request()->query('emails_sort') ?? 'desc'" :options="['desc' => __('Newest first'), 'asc' => __('Oldest first')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
    @forelse($emailHistory as $entry)<tr><td>{{ $entry->user->USName }}</td><td>{{ $entry->EHOldEmail }}</td><td>{{ $entry->EHNewEmail }}</td><td>{{ $entry->approver->USName }}</td><td>{{ $entry->EHVerifiedAt->format('d M Y H:i') }} UTC</td></tr>@empty<tr><td colspan="5">{{ __('No verified recovery-email changes.') }}</td></tr>@endforelse
</x-foundation.table>
<x-foundation.table :records="$logs" :headers="[__('Reference'), __('Action'), __('Table / record'), __('Actor'), __('Time')]">
    <x-slot:heading><div class="panel-heading"><h2>{{ __('Latest committed changes') }}</h2></div></x-slot:heading>
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('activity.index')" search-label="Search committed changes" table-key="logs" :filter-defaults="['sort' => 'desc']">
            <x-foundation.select name="logs_sort" label="Sort by" :required="false" :value="request()->query('logs_sort') ?? 'desc'" :options="['desc' => __('Newest first'), 'asc' => __('Oldest first')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
    @forelse($logs as $entry)<tr><td>{{ $entry->FLNo }}</td><td>{{ $entry->FLAction }}</td><td>{{ $entry->FLTable }} / {{ $entry->FLRecordNo }}<details><summary>{{ __('Safe changes') }}</summary><pre>{{ json_encode(['before'=>$entry->FLBefore, 'after'=>$entry->FLAfter], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td><td>{{ $entry->FLCB ?? __('System') }}</td><td>{{ $entry->FLCD->format('d M Y H:i') }} UTC</td></tr>@empty<tr><td colspan="5">{{ __('No committed changes match this search.') }}</td></tr>@endforelse
</x-foundation.table>
<x-foundation.table :records="$failures" :headers="[__('Reference'), __('Table'), __('Code'), __('Summary'), __('Correlation')]">
    <x-slot:heading><div class="panel-heading"><h2>{{ __('Latest failed writes') }}</h2></div></x-slot:heading>
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('activity.index')" search-label="Search failed writes" table-key="failures" :filter-defaults="['sort' => 'desc']">
            <x-foundation.select name="failures_sort" label="Sort by" :required="false" :value="request()->query('failures_sort') ?? 'desc'" :options="['desc' => __('Newest first'), 'asc' => __('Oldest first')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
    @forelse($failures as $entry)<tr><td>{{ $entry->FENo }}{{ $entry->FEIsExample ? ' · Example' : '' }}</td><td>{{ $entry->FETable }}</td><td>{{ $entry->FECode }}</td><td>{{ $entry->FEMessage }}</td><td>{{ $entry->FECorrelation }}</td></tr>@empty<tr><td colspan="5">{{ __('No failed writes available in this view.') }}</td></tr>@endforelse
</x-foundation.table>
@endsection
