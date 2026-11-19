@props(['name' => 'grid'])
@php
$paths = [
    'grid' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
    'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
    'settings' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z',
    'book' => 'M12 5v16 M3 3c4 0 6 0 9 2 3-2 5-2 9-2v16c-4 0-6 0-9 2-3-2-5-2-9-2z',
    'arrow' => 'M5 12h14 M12 5l7 7-7 7',
    'back' => 'M19 12H5 M12 5l-7 7 7 7',
    'search' => 'M21 21l-5-5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
    'filter' => 'M3 5h18l-7 8v5l-4 2v-7z',
    'check' => 'M5 12l4 4L19 6',
    'clock' => 'M12 8v4l3 2 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
    'mail' => 'M3 5h18v14H3z M3 5l9 8 9-8',
    'shield' => 'M12 3l9 4v5c0 5-9 10-9 10S3 17 3 12V7z M8 12l3 3 5-6',
    'plus' => 'M12 5v14 M5 12h14',
    'chevron' => 'M9 5l7 7-7 7',
    'menu' => 'M4 6h16 M4 12h16 M4 18h16',
    'close' => 'M6 6l12 12 M6 18L18 6',
    'alert' => 'M12 8v5 M12 16h.01 M10 3L1 20h22L14 3z',
    'home' => 'M3 10l9-7 9 7v11h-6v-7H9v7H3z',
];
@endphp
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['grid'] }}" /></svg>
