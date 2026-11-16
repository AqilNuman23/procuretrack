@extends('foundation.layout')
@section('content')
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Employee</th><th>Worker ID</th><th>Status</th><th>Requested</th><th>Action</th></tr></thead><tbody>@forelse($requests as $item)<tr><td>{{ $item->user->USName }}</td><td>{{ $item->user->USWorkerID }}</td><td><span class="badge blue">{{ $item->RQStatus }}</span></td><td>{{ $item->RQCD->format('d M Y H:i') }} UTC</td><td><a class="quiet-link" href="{{ route('recovery.show', $item) }}">Review <x-design.icon name="arrow" /></a></td></tr>@empty<tr><td colspan="5">No recovery requests available for your review.</td></tr>@endforelse</tbody></table></div></section>
@endsection
