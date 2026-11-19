@php($rows = [['Create teams','Allowed','—','—'],['Invite team members','Policy open','Own team','—'],['Review applicants','Allowed','—','—'],['Monitor provisioning','Allowed','—','—'],['View team members','Allowed','Own team','Policy open'],['Manage roles','Allowed','—','—'],['Maintain own profile','Own account','Own account','Own account']])
<section class="panel data-panel" data-table>
    <div class="panel-heading"><div><h2>Foundation access matrix</h2><p>A proposed visual representation of the documented role boundaries.</p></div></div>
    @include('design.partials.table-controls', ['filterLabel' => 'Team PIC access', 'allOptions' => 'All access levels', 'filterOptions' => array_unique(array_column($rows, 2)), 'showDataState' => false])
    <div class="table-scroll" data-table-content>
        <table>
            <thead><tr><th scope="col">Action</th><th scope="col">Administrator</th><th scope="col">Team PIC</th><th scope="col">Team member</th></tr></thead>
            <tbody>
                @foreach($rows as $row)
                    <tr data-record data-filter-value="{{ $row[2] }}">@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('design.partials.table-pagination')
    <div class="panel-bottom"><x-design.icon name="shield" />This matrix is read-only. Backend permissions are not implemented.</div>
</section>
<a class="button secondary" href="{{ $url($pageKey === 'roles' ? 'role-details' : 'roles') }}">{{ $design['pages'][$pageKey === 'roles' ? 'role-details' : 'roles']['title'] }} <x-design.icon name="arrow" /></a>
