<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $cards = [
            ['label' => 'Bookings', 'value' => number_format($summary['bookings']), 'tone' => 'blue', 'note' => 'Total records in range'],
            ['label' => 'Paid Revenue', 'value' => 'NGN ' . number_format($summary['paid_revenue'], 2), 'tone' => 'green', 'note' => 'Verified paid value'],
            ['label' => 'Ticketed', 'value' => number_format($summary['ticketed']), 'tone' => 'green', 'note' => 'Issued tickets'],
            ['label' => 'Awaiting Transfer', 'value' => number_format($summary['awaiting_bank_transfer']), 'tone' => 'amber', 'note' => 'Needs reconciliation'],
            ['label' => 'Ticketing Failures', 'value' => number_format($summary['ticketing_failures']), 'tone' => 'red', 'note' => 'Paid but failed'],
            ['label' => 'Pending Payment', 'value' => number_format($summary['pending_payment']), 'tone' => 'slate', 'note' => 'Checkout incomplete'],
            ['label' => 'Open PTR', 'value' => number_format($summary['post_ticketing']), 'tone' => 'blue', 'note' => 'Active post-ticketing'],
            ['label' => 'TravelFlex', 'value' => number_format($summary['travelflex']), 'tone' => 'green', 'note' => 'Applications created'],
        ];

        $exports = [
            ['label' => 'Bookings', 'report' => 'bookings'],
            ['label' => 'Bank Transfers', 'report' => 'bank-transfers'],
            ['label' => 'Ticketing Failures', 'report' => 'ticketing-failures'],
            ['label' => 'Post-Ticketing', 'report' => 'post-ticketing'],
            ['label' => 'TravelFlex', 'report' => 'travelflex'],
        ];

        $statusClass = fn (?string $status) => match ($status) {
            'paid', 'ticketed', 'approved', 'sent', 'submitted' => 'tw-report-badge tw-report-badge-good',
            'awaiting_bank_transfer', 'pending', 'in_process', 'inprocess' => 'tw-report-badge tw-report-badge-warn',
            'failed', 'ticketing_failed', 'rejected', 'cancelled' => 'tw-report-badge tw-report-badge-bad',
            default => 'tw-report-badge',
        };
    @endphp

    <div class="tw-report-page">
        <section class="tw-report-hero">
            <div>
                <div class="tw-report-eyebrow">Reporting window</div>
                <h2>Reconciliation dashboard</h2>
                <p>{{ \Carbon\Carbon::parse($this->from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($this->to)->format('d M Y') }}</p>
            </div>

            <div class="tw-report-filters">
                <label>
                    <span>From</span>
                    <input type="date" wire:model.live="from">
                </label>

                <label>
                    <span>To</span>
                    <input type="date" wire:model.live="to">
                </label>
            </div>
        </section>

        <section class="tw-report-export-bar">
            <div>
                <h3>Exports</h3>
                <p>Download CSV files for finance, ticketing, and provider reconciliation.</p>
            </div>

            <div class="tw-report-export-actions">
                @foreach ($exports as $export)
                    <a href="{{ $this->exportUrl($export['report']) }}">{{ $export['label'] }}</a>
                @endforeach
            </div>
        </section>

        <section class="tw-report-card-grid">
            @foreach ($cards as $card)
                <article class="tw-report-stat tw-report-stat-{{ $card['tone'] }}">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['note'] }}</small>
                </article>
            @endforeach
        </section>

        <section class="tw-report-grid">
            @include('filament.pages.partials.report-breakdown', ['title' => 'Sales by Airline', 'rows' => $this->byAirline()])
            @include('filament.pages.partials.report-breakdown', ['title' => 'Sales by Route', 'rows' => $this->byRoute()])
            @include('filament.pages.partials.report-breakdown', ['title' => 'Sales by Fare Type', 'rows' => $this->byFareType()])
            @include('filament.pages.partials.report-breakdown', ['title' => 'Sales by Payment Method', 'rows' => $this->byPaymentMethod()])
        </section>

        <section class="tw-report-grid">
            <article class="tw-report-panel">
                <header>
                    <div>
                        <h3>Bank Transfer Reconciliation</h3>
                        <p>Recent transfer-based bookings in this range.</p>
                    </div>
                    <a href="{{ $this->exportUrl('bank-transfers') }}">CSV</a>
                </header>
                <div class="tw-report-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->bankTransfers() as $booking)
                                <tr>
                                    <td><strong>{{ $booking->booking_ref }}</strong><span>{{ $booking->unique_id ?: '-' }}</span></td>
                                    <td>{{ $booking->bank_transfer_reference ?: '-' }}</td>
                                    <td><span class="{{ $statusClass($booking->payment_status) }}">{{ str($booking->payment_status)->replace('_', ' ')->headline() }}</span></td>
                                    <td>{{ $booking->currency }} {{ number_format((float) ($booking->payment_charged_amount ?: ($booking->payment_amount ?: $booking->total_price)), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="tw-report-empty">No bank transfer records in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="tw-report-panel">
                <header>
                    <div>
                        <h3>Ticketing Failure Report</h3>
                        <p>Paid bookings that need ticketing follow-up.</p>
                    </div>
                    <a href="{{ $this->exportUrl('ticketing-failures') }}">CSV</a>
                </header>
                <div class="tw-report-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Latest Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->ticketingFailures() as $booking)
                                <tr>
                                    <td><strong>{{ $booking->booking_ref }}</strong><span>{{ $booking->unique_id ?: '-' }}</span></td>
                                    <td>{{ $booking->route ?: '-' }}</td>
                                    <td><span class="{{ $statusClass($booking->booking_status) }}">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span></td>
                                    <td>{{ $booking->ticketingRecords->first()?->message ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="tw-report-empty">No ticketing failures in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="tw-report-panel">
                <header>
                    <div>
                        <h3>Refund / Void / Reissue Report</h3>
                        <p>Post-ticketing requests and PTR status.</p>
                    </div>
                    <a href="{{ $this->exportUrl('post-ticketing') }}">CSV</a>
                </header>
                <div class="tw-report-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Operation</th>
                                <th>PTR</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->postTicketing() as $request)
                                <tr>
                                    <td><strong>{{ $request->booking?->booking_ref ?: '-' }}</strong><span>{{ $request->unique_id ?: '-' }}</span></td>
                                    <td>{{ str($request->operation_type)->headline() }}</td>
                                    <td>{{ $request->ptr_unique_id ?: '-' }}</td>
                                    <td><span class="{{ $statusClass($request->status) }}">{{ str($request->status)->replace('_', ' ')->headline() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="tw-report-empty">No post-ticketing requests in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="tw-report-panel">
                <header>
                    <div>
                        <h3>TravelFlex Report</h3>
                        <p>Applications and provider review status.</p>
                    </div>
                    <a href="{{ $this->exportUrl('travelflex') }}">CSV</a>
                </header>
                <div class="tw-report-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Applicant</th>
                                <th>Status</th>
                                <th>Grand Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->travelFlex() as $application)
                                <tr>
                                    <td><strong>{{ $application->booking_ref ?: '-' }}</strong><span>{{ $application->unique_id ?: '-' }}</span></td>
                                    <td>{{ data_get($application->applicant_details, 'full_name') ?: '-' }}</td>
                                    <td>
                                        <span class="{{ $statusClass($application->application_status) }}">{{ str($application->application_status)->replace('_', ' ')->headline() }}</span>
                                        <span class="{{ $statusClass($application->provider_status) }}">{{ str($application->provider_status)->replace('_', ' ')->headline() }}</span>
                                    </td>
                                    <td>NGN {{ number_format((float) $application->grand_total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="tw-report-empty">No TravelFlex applications in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
