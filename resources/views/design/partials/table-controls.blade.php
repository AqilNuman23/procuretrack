    <div class="table-toolbar">
        <button class="button secondary" type="button" data-preview-filters-open aria-haspopup="dialog">
            <x-design.icon name="filter" /> Filters
        </button>
        <label class="search-field"><x-design.icon name="search" /><input type="search" placeholder="{{ $ui['search'] }}" aria-label="{{ $ui['search'] }}" data-search></label>
        <dialog class="table-filter-dialog" aria-labelledby="preview-filters-title">
            <div class="table-filter-heading">
                <h2 id="preview-filters-title">Filters</h2>
                <button type="button" class="icon-button" data-preview-filters-close aria-label="Close"><x-design.icon name="close" /></button>
            </div>
            <form method="dialog" data-preview-filters>
                <div class="table-filter-fields">
                    <label class="field"><span>{{ $filterLabel }}</span><select data-filter><option value="">{{ $allOptions }}</option>@foreach($filterOptions as $option)<option>{{ $option }}</option>@endforeach</select></label>
                    @if($showDataState)<label class="field"><span>Preview data state</span><select data-state><option value="success">State: populated</option><option value="empty">State: empty</option><option value="loading">State: loading</option><option value="error">State: error</option></select></label>@endif
                </div>
                <div class="dialog-actions"><button class="button primary" type="submit">Apply filters</button></div>
            </form>
        </dialog>
    </div>
