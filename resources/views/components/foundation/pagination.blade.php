@props(['records'])
@use(App\Support\Foundation\TableQuery)

@php
    $pageKey = $records->getPageName();
    $sizeKey = $pageKey === 'page' ? 'per_page' : substr($pageKey, 0, -4).'per_page';
    $sizes = TableQuery::PAGE_SIZES;
@endphp

<div class="live-pagination table-page-controls">
    <form method="GET" action="{{ request()->url() }}" class="table-page-size">
        @foreach(collect(request()->query())->except([$pageKey, $sizeKey]) as $name => $value)
            @if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
        @endforeach
        <x-foundation.select :name="$sizeKey" label="Rows per page" :required="false" :value="$records->perPage()" :options="array_combine($sizes, $sizes)" :translate-options="false" />
        <button class="button secondary" type="submit">{{ __('Apply') }}</button>
    </form>
    <nav class="table-page-navigation" aria-label="{{ __('Pagination') }}">
        @if($records->previousPageUrl())<a class="button secondary" rel="prev" href="{{ $records->previousPageUrl() }}">{{ __('Previous') }}</a>@endif
        <span>{{ __('Page :page', ['page' => $records->currentPage()]) }}</span>
        @if($records->nextPageUrl())<a class="button secondary" rel="next" href="{{ $records->nextPageUrl() }}">{{ __('Next') }}</a>@endif
    </nav>
</div>
