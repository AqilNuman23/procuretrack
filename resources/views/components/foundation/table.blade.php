@props(['headers' => [], 'records' => null])
<section {{ $attributes->class(['panel']) }}>
@if(isset($heading)){{ $heading }}@endif
@if(isset($toolbar)){{ $toolbar }}@endif
<div class="table-scroll" tabindex="0" role="region" aria-label="{{ __('Scrollable data table') }}"><table><thead><tr>@foreach($headers as $heading)<th scope="col">{{ __($heading) }}</th>@endforeach</tr></thead><tbody>{{ $slot }}</tbody></table></div>
@if($records)<x-foundation.pagination :records="$records" />@endif
@if(isset($footnote)){{ $footnote }}@endif
</section>
