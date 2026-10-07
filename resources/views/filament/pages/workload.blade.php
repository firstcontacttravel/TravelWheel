<x-filament-panels::page>
    @php
        $report = $this->report();
        $summary = $report['summary'];
        $duration = fn ($minutes) => \App\Filament\Pages\Workload::duration($minutes);
        $hours = fn ($value) => $value === null ? '-' : $duration($value * 60);
    @endphp

    {{-- Built from the report system's own classes (resources/css/admin/reports.css), so it reads as one product with Reports. --}}
    <div class="tw-bi">
        <section class="tw-bi-filter">
            <label class="tw-field"><span>Period</span>
                <select wire:model.live="days">
                    @foreach (\App\Filament\Pages\Workload::PERIODS as $period)
                        <option value="{{ $period }}">Last {{ $period }} days</option>
                    @endforeach
                </select>
            </label>
            <label class="tw-field"><span>Service</span>
                <select wire:model.live="service">
                    <option value="">Every service</option>
                    @foreach ($this->serviceOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </section>

        <section class="tw-bi-grid">
            <article class="tw-stat" style="--tone:var(--tc-status-warning-dot)"><label>Needs action now</label><strong>{{ number_format($summary['open']) }}</strong><small>{{ number_format($summary['unclaimed']) }} unclaimed · {{ number_format($summary['waiting']) }} waiting on customers</small></article>
            <article class="tw-stat" style="--tone:var(--tc-status-critical-dot)"><label>Overdue now</label><strong>{{ number_format($summary['overdue']) }}</strong><small>{{ number_format($summary['missed']) }} deadlines missed in the period</small></article>
            <article class="tw-stat" style="--tone:var(--tc-status-positive-dot)"><label>Completed</label><strong>{{ number_format($summary['completed']) }}</strong><small>{{ $summary['on_time_rate'] === null ? 'Nothing completed yet' : $summary['on_time_rate'].'% without missing a deadline' }}</small></article>
            <article class="tw-stat" style="--tone:var(--tc-brand)"><label>Escalations</label><strong>{{ number_format($summary['escalations']) }}</strong><small>Resolved in {{ $hours($summary['escalation_hours']) }} (median)</small></article>
        </section>

        <section class="tw-bi-grid">
            <article class="tw-panel full">
                <div class="tw-panel-head"><div><h3>Time in each step</h3><p>From entering a step to leaving it, for steps left in the period. Slowest first; the steps worth looking at are at the top.</p></div></div>
                <div class="tw-table-wrap"><table class="tw-table">
                    <thead><tr><th>Service</th><th>Step</th><th>Times done</th><th>Typical (median)</th><th>Slowest</th><th>Deadline</th><th>Over the deadline</th></tr></thead>
                    <tbody>
                        @forelse ($report['steps'] as $step)
                            <tr>
                                <td>{{ $step['service'] }}</td>
                                <td><strong>{{ $step['stage'] }}</strong></td>
                                <td class="tw-num">{{ number_format($step['count']) }}</td>
                                <td class="tw-num">{{ $duration($step['median_minutes']) }}</td>
                                <td class="tw-num">{{ $duration($step['slowest_minutes']) }}</td>
                                <td class="tw-num">{{ $duration($step['allowance_minutes']) }}</td>
                                <td class="tw-num">{{ $step['over_allowance'] === null ? '-' : $step['over_allowance'].' of '.$step['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="tw-empty">No steps were finished in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </article>

            <article class="tw-panel full">
                <div class="tw-panel-head"><div><h3>By department</h3><p>Each team's queue now, what it finished in the period, and how fast it answers escalations.</p></div></div>
                <div class="tw-table-wrap"><table class="tw-table">
                    <thead><tr><th>Department</th><th>Needs action</th><th>Unclaimed</th><th>Overdue</th><th>Completed</th><th>Escalations received</th><th>Answered in (median)</th></tr></thead>
                    <tbody>
                        @foreach ($report['departments'] as $department)
                            <tr>
                                <td><strong>{{ $department['name'] }}</strong></td>
                                <td class="tw-num">{{ number_format($department['open']) }}</td>
                                <td class="tw-num">{{ number_format($department['unclaimed']) }}</td>
                                <td class="tw-num">{{ number_format($department['overdue']) }}</td>
                                <td class="tw-num">{{ number_format($department['completed']) }}</td>
                                <td class="tw-num">{{ number_format($department['escalations_received']) }}</td>
                                <td class="tw-num">{{ $hours($department['response_hours']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </article>

            <article class="tw-panel full">
                <div class="tw-panel-head"><div><h3>By person</h3><p>What each person owns now, and what they did in the period. Completed counts bookings finished while they owned them.</p></div></div>
                <div class="tw-table-wrap"><table class="tw-table">
                    <thead><tr><th>Name</th><th>Department</th><th>Owns now</th><th>Overdue</th><th>Completed</th><th>Claimed</th><th>Escalations raised</th><th>Escalations resolved</th><th>Admin actions</th></tr></thead>
                    <tbody>
                        @forelse ($report['people'] as $person)
                            <tr>
                                <td><strong>{{ $person['name'] }}</strong></td>
                                <td>{{ $person['department'] }}</td>
                                <td class="tw-num">{{ number_format($person['owned']) }}</td>
                                <td class="tw-num">{{ number_format($person['overdue']) }}</td>
                                <td class="tw-num">{{ number_format($person['completed']) }}</td>
                                <td class="tw-num">{{ number_format($person['claimed']) }}</td>
                                <td class="tw-num">{{ number_format($person['escalations_raised']) }}</td>
                                <td class="tw-num">{{ number_format($person['escalations_resolved']) }}</td>
                                <td class="tw-num">{{ number_format($person['actions']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="tw-empty">No staff yet. Add them under Team → Staff.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
