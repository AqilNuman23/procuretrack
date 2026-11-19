@php
    $columns = ['Person', 'Team', 'Worker ID', 'Status'];
    $destination = 'user-details';
    $rows = array_map(fn ($r) => [$r[0], $r[2], $r[4], $r[3], $r[1]], $design['records']);
    if (in_array($pageKey, ['teams', 'departments'])) {
        $columns = ['Name', 'Team PIC', 'Members', 'Status'];
        $rows = array_map(fn ($r) => [...$r, 'Company team'], $design['teams']);
        $destination = $pageKey === 'teams' ? 'team-details' : 'department-details';
    } elseif ($pageKey === 'invitations') {
        $columns = ['Person', 'Team', 'Sent', 'Status'];
        $rows = [['Nur Aisyah Rahman', 'Operations', '12 Nov 2026', 'Accepted', 'aisyah@personal.example'], ['Hana Ismail', 'Operations', '12 Nov 2026', 'Invited', 'hana@personal.example'], ['Sofia Abdullah', 'Finance', '10 Nov 2026', 'Expired', 'sofia@personal.example']];
        $destination = 'invitation-details';
    } elseif ($pageKey === 'reviews') {
        $columns = ['Person', 'Team', 'Accepted', 'Status'];
        $rows = [['Nur Aisyah Rahman', 'Operations', 'Today, 09:12', 'Awaiting review', 'aisyah@personal.example'], ['Hana Ismail', 'Operations', 'Yesterday', 'Awaiting review', 'hana@personal.example'], ['Sofia Abdullah', 'Finance', 'Yesterday', 'Awaiting review', 'sofia@personal.example'], ['Arjun Kumar', 'Technology', '12 Nov 2026', 'Awaiting review', 'arjun@personal.example']];
        $destination = 'review-details';
    } elseif ($pageKey === 'mailboxes') {
        $columns = ['Person', 'Provider', 'Last attempt', 'Status'];
        $rows = [['Nur Aisyah Rahman', 'Mock adapter', 'Today, 10:45', 'Ready', 'aisyah@company.example'], ['Hana Ismail', 'Mock adapter', 'Today, 09:30', 'Failed', 'hana@company.example'], ['Sofia Abdullah', 'Mock adapter', 'Today, 08:15', 'Pending', 'sofia@company.example'], ['Arjun Kumar', 'Mock adapter', 'Yesterday', 'Failed', 'arjun@company.example']];
        $destination = 'mailbox-details';
    }
@endphp
<section class="panel data-panel" data-table>
    @include('design.partials.table-controls', ['filterLabel' => 'Status', 'allOptions' => 'All statuses', 'filterOptions' => array_unique(array_column($rows, 3)), 'showDataState' => true])
    <div class="table-scroll" data-table-content><table><thead><tr>@foreach($columns as $column)<th scope="col" aria-sort="none"><button type="button" data-sort="{{ $loop->index }}">{{ $column }} <span aria-hidden="true">↕</span></button></th>@endforeach<th scope="col"><span class="sr-only">Open details</span></th></tr></thead><tbody>@foreach($rows as $row)<tr data-record data-filter-value="{{ $row[3] }}"><td><a class="person-cell" href="{{ $url($destination) }}"><span class="avatar avatar-{{ $loop->index % 3 }}">{{ implode('', array_map(fn ($word) => mb_substr($word, 0, 1), array_slice(explode(' ', $row[0]), 0, 2))) }}</span><span><strong>{{ $row[0] }}</strong><small>{{ $row[4] }}</small></span></a></td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td><td><x-shared.status-badge :status="$row[3]" /></td><td><a class="row-link" href="{{ $url($destination) }}" aria-label="Open example {{ $screen['title'] }} detail"><x-design.icon name="chevron" /></a></td></tr>@endforeach</tbody></table></div>
    @include('design.partials.table-pagination')
</section>
<p class="muted form-footnote">Rows open a representative detail screen. These independent fixtures illustrate different workflow states.</p>
