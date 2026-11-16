@props(['headers' => []])
<section {{ $attributes->class(['panel']) }}><div class="table-scroll" tabindex="0" role="region" aria-label="Scrollable data table"><table><thead><tr>@foreach($headers as $heading)<th scope="col">{{ $heading }}</th>@endforeach</tr></thead><tbody>{{ $slot }}</tbody></table></div></section>
