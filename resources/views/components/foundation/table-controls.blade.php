@props(['action', 'searchLabel', 'filterDefaults' => ['status' => ''], 'tableKey' => ''])
@use(App\Support\Foundation\TableQuery)

@php
    $table = new TableQuery(request(), $tableKey);
    $searchKey = $table->key('search');
    $search = request()->query($searchKey);
    $search = is_string($search) ? $search : '';
    $criteria = collect($filterDefaults)->mapWithKeys(fn ($default, $name) => [$table->key($name) => [request()->query($table->key($name)), $default]]);
    $active = $criteria->filter(fn ($pair) => is_scalar($pair[0]) && (string) $pair[0] !== '' && (string) $pair[0] !== (string) $pair[1])->count();
    $shared = collect(request()->query())->filter(fn ($value) => is_scalar($value))
        ->except([$table->key('page'), $searchKey, ...$criteria->keys()->all()]);
    $clearQuery = $search === '' ? $shared : $shared->merge([$searchKey => $search]);
    $clearUrl = $clearQuery->isEmpty() ? $action : $action.'?'.http_build_query($clearQuery->all());
    $dialogId = 'table-filters-'.bin2hex(random_bytes(8));
@endphp

<div class="table-toolbar live-table-controls" data-table-controls>
    <button class="button secondary" type="button" data-table-filters-open aria-haspopup="dialog" aria-controls="{{ $dialogId }}">
        <x-design.icon name="filter" />
        {{ __('Filters') }}
        @if($active)<span class="badge blue">{{ $active }}</span>@endif
    </button>
    <form method="GET" action="{{ $action }}" class="live-table-search" role="search" aria-label="{{ __($searchLabel) }}">
        @foreach($shared as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        @foreach($criteria as $name => [$value, $default])
            @if(is_scalar($value) && (string) $value !== (string) $default)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
        @endforeach
        <label class="search-field">
            <x-design.icon name="search" />
            <input type="search" name="{{ $searchKey }}" value="{{ $search }}" maxlength="150" placeholder="{{ __($searchLabel) }}" aria-label="{{ __($searchLabel) }}">
        </label>
        <button class="button secondary" type="submit">{{ __('Search') }}</button>
    </form>
    <dialog class="table-filter-dialog" id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title">
        <div class="table-filter-heading">
            <h2 id="{{ $dialogId }}-title">{{ __('Filters') }}</h2>
            <button class="icon-button" type="button" data-table-filters-close aria-label="{{ __('Close') }}"><x-design.icon name="close" /></button>
        </div>
        <form method="GET" action="{{ $action }}">
            @foreach($clearQuery as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <div class="table-filter-fields">{{ $slot }}</div>
            <div class="dialog-actions">
                @if($active)<a href="{{ $clearUrl }}" class="button secondary">{{ __('Clear filters') }}</a>@endif
                <button class="button primary" type="submit">{{ __('Apply filters') }}</button>
            </div>
        </form>
    </dialog>
</div>
