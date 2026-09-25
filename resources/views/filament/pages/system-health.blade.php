{{--
    System health.

    This page used to carry ~30 lines of minified inline <style> declaring its
    own palette (--ink:#10233e, --brand:#174f7c), its own radii and its own
    status colours — a fourth design system, embedded in a Blade file, that no
    token could reach. It now uses the console's parts, so "warning" is the
    same amber here as on the dashboard and in the queue table.
--}}
<x-filament-panels::page>
    @php
        $report = $this->report ?? [];
        $status = $report['overall_status'] ?? 'warning';

        // Health status maps onto the console's status roles rather than
        // inventing a third vocabulary.
        $tone = fn (string $state): string => match ($state) {
            'healthy' => 'positive',
            'warning' => 'warning',
            default => 'critical',
        };

        $statusLabel = match ($status) {
            'healthy' => 'All systems healthy',
            'warning' => 'Attention recommended',
            default => 'Action required',
        };

        $checkedAt = filled($report['checked_at'] ?? null)
            ? \Carbon\Carbon::parse($report['checked_at'])->format('d M Y, H:i:s')
            : 'Not run';
    @endphp

    <div class="tc-page">
        <section class="tc-panel">
            <header class="tc-panel-head">
                <div>
                    <h2 class="tc-panel-title">{{ $statusLabel }}</h2>
                    <p class="tc-panel-note">
                        Live operational diagnostics · checked {{ $checkedAt }}
                        · {{ $report['total_count'] ?? 0 }} checks
                        · {{ number_format($report['duration_ms'] ?? 0) }} ms
                        · {{ str($report['context']['environment'] ?? app()->environment())->headline() }}
                    </p>
                </div>

                {{-- Filament's own button component, not a hand-written
                     class="fi-btn fi-color-primary": the colour utilities are
                     generated server-side by the color() attribute macro, so
                     writing the class name by hand gets the name without any
                     of the styles. --}}
                <x-filament::button
                    wire:click="runHealthChecks"
                    wire:loading.attr="disabled"
                    wire:target="runHealthChecks"
                >
                    <span wire:loading.remove wire:target="runHealthChecks">Run checks again</span>
                    <span wire:loading wire:target="runHealthChecks">Checking system…</span>
                </x-filament::button>
            </header>

            <div class="tc-panel-body">
                <dl class="tc-metrics">
                    <div class="tc-metric tc-metric-{{ $status === 'healthy' ? 'good' : ($status === 'warning' ? 'warn' : 'bad') }}">
                        <dt>Overall</dt>
                        <dd>
                            <span class="tc-status tc-status-{{ $tone($status) }}">{{ str($status)->headline() }}</span>
                        </dd>
                        <small>Run #{{ $report['run_id'] ?? '---' }}</small>
                    </div>
                    <div class="tc-metric tc-metric-good">
                        <dt>Healthy</dt>
                        <dd class="tc-num">{{ $report['healthy_count'] ?? 0 }}</dd>
                        <small>Operating normally</small>
                    </div>
                    <div class="tc-metric tc-metric-warn">
                        <dt>Warnings</dt>
                        <dd class="tc-num">{{ $report['warning_count'] ?? 0 }}</dd>
                        <small>Review recommended</small>
                    </div>
                    <div class="tc-metric tc-metric-bad">
                        <dt>Failed</dt>
                        <dd class="tc-num">{{ $report['failed_count'] ?? 0 }}</dd>
                        <small>Immediate attention</small>
                    </div>
                    <div class="tc-metric tc-metric-info">
                        <dt>Connectivity</dt>
                        <dd style="font-size:var(--tc-text-title)">{{ ($report['context']['connectivity_included'] ?? false) ? 'Included' : 'Skipped' }}</dd>
                        <small>Supplier and mail probes</small>
                    </div>
                </dl>
            </div>
        </section>

        @foreach (($report['groups'] ?? []) as $group => $checks)
            <section class="tc-panel">
                <header class="tc-panel-head">
                    <h2 class="tc-panel-title">{{ $group }}</h2>
                    <span class="tc-t-micro">{{ count($checks) }} check{{ count($checks) === 1 ? '' : 's' }}</span>
                </header>

                <div class="tc-checks">
                    @foreach ($checks as $check)
                        @php($checkStatus = $check['status'] ?? 'failed')
                        <article class="tc-check">
                            <span class="tc-status tc-status-{{ $tone($checkStatus) }}" aria-hidden="true"></span>

                            <div>
                                <span class="tc-check-name">{{ $check['name'] ?? 'Unnamed check' }}</span>
                                @if (filled($check['summary'] ?? null))
                                    <p class="tc-check-summary">{{ $check['summary'] }}</p>
                                @endif

                                @if (! empty($check['details']))
                                    <dl class="tc-check-detail">
                                        @foreach ($check['details'] as $label => $value)
                                            <div>
                                                <dt>{{ $label }}</dt>
                                                <dd>
                                                    @if (is_array($value))
                                                        {{ $value === [] ? 'None' : collect($value)->map(fn ($item, $key) => (is_string($key) ? $key.': ' : '').(is_scalar($item) || $item === null ? ($item ?? 'None') : json_encode($item)))->implode(' · ') }}
                                                    @elseif (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif

                                @if (filled($check['action'] ?? null))
                                    <p class="tc-check-action">{{ $check['action'] }}</p>
                                @endif
                            </div>

                            <span class="tc-t-micro">{{ number_format($check['duration_ms'] ?? 0) }} ms</span>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="tc-panel">
            <header class="tc-panel-head">
                <div>
                    <h2 class="tc-panel-title">Recent health runs</h2>
                    <p class="tc-panel-note">Compare the current result with the last ten administrator checks.</p>
                </div>
            </header>

            <div class="tc-table-wrap">
                <table class="tc-table">
                    <thead>
                        <tr>
                            <th>Run</th>
                            <th>Time</th>
                            <th>Administrator</th>
                            <th>Status</th>
                            <th>Healthy</th>
                            <th>Warnings</th>
                            <th>Failed</th>
                            <th>Duration</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->recentRuns() as $run)
                            <tr>
                                <td class="tc-mono">#{{ $run->id }}</td>
                                <td>{{ $run->created_at->format('d M Y H:i:s') }}</td>
                                <td>{{ $run->user?->name ?? 'System' }}</td>
                                <td><span class="tc-status tc-status-{{ $tone($run->overall_status) }}">{{ str($run->overall_status)->headline() }}</span></td>
                                <td class="tc-num">{{ $run->healthy_count }}</td>
                                <td class="tc-num">{{ $run->warning_count }}</td>
                                <td class="tc-num">{{ $run->failed_count }}</td>
                                <td class="tc-num">{{ number_format($run->duration_ms) }} ms</td>
                                <td>
                                    <x-filament::button size="sm" color="gray" wire:click="loadRun({{ $run->id }})">View</x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr class="tc-table-empty">
                                <td colspan="9">No health runs recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
