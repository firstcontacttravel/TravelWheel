{{--
    A revenue breakdown panel.

    Rebuilt on the console's panel and table parts, which is what lets the
    tw-report-* block leave the admin theme. The geometry deliberately matches
    the resource tables: a report that looks like a different product from the
    queue it summarises is a report people distrust.
--}}
<article class="tc-panel">
    <header class="tc-panel-head">
        <div>
            <h3 class="tc-panel-title">{{ $title }}</h3>
            <p class="tc-panel-note">Top contributors by paid revenue.</p>
        </div>
    </header>

    <div class="tc-table-wrap">
        <table class="tc-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="tc-num">Bookings</th>
                    <th class="tc-num">Paid revenue</th>
                    <th class="tc-num">Service charge</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><strong>{{ $row->label }}</strong></td>
                        <td class="tc-num">{{ number_format((int) $row->bookings) }}</td>
                        <td class="tc-money">NGN {{ number_format((float) $row->revenue, 2) }}</td>
                        <td class="tc-money">NGN {{ number_format((float) ($row->service_charges ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr class="tc-table-empty">
                        <td colspan="4">No records in this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
