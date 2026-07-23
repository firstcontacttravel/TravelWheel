<div class="tw-table-wrap">
    <table class="tw-table">
        <thead><tr><th>Product / reference</th><th>Created</th><th>Payment</th><th>Fulfilment</th><th>Value</th><th>Quality</th></tr></thead>
        <tbody>
        @forelse ($facts as $fact)
            <tr>
                <td><strong>{{ config("reporting.products.{$fact->product}.label", str($fact->product)->headline()) }}</strong><small>{{ $fact->reference ?: 'No reference' }}</small></td>
                <td>{{ $fact->created_at_source?->timezone('Africa/Lagos')->format('d M Y H:i') }}</td>
                <td><span class="tw-badge {{ $badge($fact->payment_status) }}">{{ str($fact->payment_status)->replace('_',' ')->headline() }}</span></td>
                <td><span class="tw-badge {{ $badge($fact->fulfillment_status) }}">{{ str($fact->fulfillment_status)->replace('_',' ')->headline() }}</span></td>
                <td>{{ $this->canViewFinancials() ? $money($fact->gross_value) : 'Restricted' }}</td>
                <td><small>{{ collect($fact->data_quality)->map(fn ($issue) => str($issue)->replace('_',' ')->headline())->implode(', ') ?: '—' }}</small></td>
            </tr>
        @empty
            <tr><td colspan="6" class="tw-empty">No records match this view.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
