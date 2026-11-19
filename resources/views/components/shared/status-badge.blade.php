@props(['status'])

@php
    $key = str_replace(' ', '_', strtolower(trim($status)));
    $tone = match ($key) {
        'active', 'accepted', 'approved', 'completed', 'ready' => 'green',
        'awaiting_review', 'pending', 'invited' => 'amber',
        'deactivated', 'expired', 'failed', 'rejected', 'revoked' => 'red',
        'inactive', 'not_started' => 'neutral',
        default => 'blue',
    };
    $label = __($key);
    if ($label === $key) {
        $label = ucfirst(str_replace('_', ' ', $key));
    }
@endphp

<span {{ $attributes->class(['badge', 'status-badge', $tone]) }}>{{ $label }}</span>
