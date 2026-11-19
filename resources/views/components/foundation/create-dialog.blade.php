@props(['id', 'title', 'action', 'submitLabel' => null, 'triggerLabel' => null, 'variant' => 'primary', 'icon' => 'plus'])

<div data-create-dialog>
    <button class="button {{ $variant }}" type="button" data-create-open aria-haspopup="dialog" aria-controls="{{ $id }}">
        @if($icon)<x-design.icon :name="$icon" />@endif
        {{ __($triggerLabel ?? $title) }}
    </button>

    <dialog class="create-dialog" id="{{ $id }}" aria-labelledby="{{ $id }}-title">
        <h2 id="{{ $id }}-title">{{ __($title) }}</h2>
        <form method="POST" action="{{ $action }}">
            @csrf
            {{ $slot }}
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-create-close>{{ __('Cancel') }}</button>
                <button class="button primary" type="submit">{{ __($submitLabel ?? $title) }}</button>
            </div>
        </form>
    </dialog>
</div>
