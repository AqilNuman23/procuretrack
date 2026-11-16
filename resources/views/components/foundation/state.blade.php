@props(['kind' => 'empty', 'retry' => null])
<div {{ $attributes->class(['notice', 'error-notice' => $kind === 'error']) }} role="{{ $kind === 'error' ? 'alert' : 'status' }}" @if($kind === 'loading') aria-busy="true" @endif>{{ $slot }} @if($retry)<a href="{{ $retry }}">Retry</a>@endif</div>
