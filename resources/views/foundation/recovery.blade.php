@extends('foundation.layout')
@section('content')
<x-foundation.table :records="$requests" :headers="[__('Employee'), __('Worker ID'), __('Status'), __('Requested'), __('Action')]">
    <x-slot:toolbar>
        <x-foundation.table-controls :action="route('recovery.index')" search-label="Search recovery requests">
            <x-foundation.select name="status" label="Status" :required="false" :value="request()->query('status') ?? ''" :options="['' => __('All statuses'), 'pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected'), 'completed' => __('Completed')]" />
        </x-foundation.table-controls>
    </x-slot:toolbar>
    @forelse($requests as $item)<tr><td>{{ $item->user->USName }}</td><td>{{ $item->user->USWorkerID }}</td><td><x-shared.status-badge :status="$item->RQStatus" /></td><td>{{ $item->RQCD->format('d M Y H:i') }} UTC</td><td><a class="quiet-link" href="{{ route('recovery.show', $item) }}">{{ __('Review') }} <x-design.icon name="arrow" /></a></td></tr>@empty<tr><td colspan="5">{{ __('No recovery requests available for your review.') }}</td></tr>@endforelse
</x-foundation.table>
@endsection
