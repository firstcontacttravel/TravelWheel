<article class="tw-report-panel">
    <header>
        <div>
            <h3>{{ $title }}</h3>
            <p>Top contributors by paid revenue.</p>
        </div>
    </header>

    <div class="tw-report-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Bookings</th>
                    <th>Paid Revenue</th>
                    <th>Service Charge</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><strong>{{ $row->label }}</strong></td>
                        <td>{{ number_format((int) $row->bookings) }}</td>
                        <td>NGN {{ number_format((float) $row->revenue, 2) }}</td>
                        <td>NGN {{ number_format((float) ($row->service_charges ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="tw-report-empty">No records in this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
