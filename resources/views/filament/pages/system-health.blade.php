<x-filament-panels::page>
    @php
        $status = $this->report['overall_status'] ?? 'warning';
        $statusLabel = match ($status) {
            'healthy' => 'All systems healthy',
            'warning' => 'Attention recommended',
            default => 'Action required',
        };
        $checkedAt = filled($this->report['checked_at'] ?? null)
            ? \Carbon\Carbon::parse($this->report['checked_at'])->format('d M Y, H:i:s')
            : 'Not run';
    @endphp

    <style>
        .tw-health{--ink:#10233e;--muted:#69788c;--line:#dde6ef;--soft:#f5f8fb;--brand:#174f7c;display:grid;gap:18px;color:var(--ink)}
        .tw-health *{box-sizing:border-box}
        .tw-health-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:24px;padding:25px 27px;border-radius:20px;color:#fff;background:linear-gradient(120deg,#092f52 0%,#105c82 57%,#0c8a79 125%);box-shadow:0 16px 36px rgba(7,42,70,.17)}
        .tw-health-hero:after{content:"";position:absolute;right:-45px;top:-80px;width:250px;height:250px;border:45px solid rgba(255,255,255,.07);border-radius:50%}
        .tw-health-hero-main,.tw-health-actions{position:relative;z-index:1}.tw-health-eyebrow{text-transform:uppercase;letter-spacing:.15em;font-size:10px;font-weight:850;color:#9ddfd7}
        .tw-health-hero h2{margin:6px 0 7px;font-size:27px;line-height:1.15}.tw-health-hero p{margin:0;color:#d8eaf2;font-size:12px}
        .tw-health-actions{display:flex;align-items:center;gap:10px}.tw-health-run{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 16px;border:0;border-radius:10px;background:#fff;color:#164e72;font-size:12px;font-weight:850;cursor:pointer;box-shadow:0 5px 18px rgba(0,0,0,.11)}
        .tw-health-run:disabled{cursor:wait;opacity:.72}.tw-health-spin{width:14px;height:14px;border:2px solid #a8c2d4;border-top-color:#164e72;border-radius:50%;animation:tw-health-spin .7s linear infinite}@keyframes tw-health-spin{to{transform:rotate(360deg)}}
        .tw-health-summary{display:grid;grid-template-columns:1.35fr repeat(4,1fr);gap:12px}.tw-health-stat{padding:16px;border:1px solid var(--line);border-radius:14px;background:#fff}
        .tw-health-stat label{display:block;margin-bottom:7px;color:var(--muted);font-size:9px;font-weight:850;letter-spacing:.09em;text-transform:uppercase}.tw-health-stat strong{display:block;font-size:22px;line-height:1.2}
        .tw-health-stat small{display:block;margin-top:5px;color:var(--muted);font-size:10px}.tw-health-stat.overall{border-left:5px solid var(--tone)}
        .tw-health-status{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:9px;font-weight:900;letter-spacing:.05em;text-transform:uppercase}
        .tw-health-status:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}.tw-health-status.healthy{color:#08745c;background:#dff7ee}.tw-health-status.warning{color:#966314;background:#fff1cf}.tw-health-status.failed{color:#aa2940;background:#fde6e9}
        .tw-health-group{display:grid;gap:10px}.tw-health-group-head{display:flex;justify-content:space-between;align-items:end;gap:15px;padding:0 2px}.tw-health-group-head h3{margin:0;font-size:15px}.tw-health-group-head span{color:var(--muted);font-size:10px}
        .tw-health-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}.tw-health-check{position:relative;overflow:hidden;padding:15px;border:1px solid var(--line);border-radius:14px;background:#fff}
        .tw-health-check:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--tone,#7190a6)}.tw-health-check-head{display:flex;justify-content:space-between;align-items:start;gap:12px}
        .tw-health-check h4{margin:0;font-size:13px}.tw-health-check-summary{margin:7px 0 0;color:var(--muted);font-size:11px;line-height:1.55}.tw-health-duration{margin-top:6px;color:#94a3b8;font-size:9px}
        .tw-health-details{display:grid;gap:7px;margin:13px 0 0;padding:11px;border-radius:10px;background:var(--soft)}.tw-health-detail{display:grid;grid-template-columns:minmax(120px,.8fr) minmax(0,1.5fr);gap:10px;font-size:10px;line-height:1.45}
        .tw-health-detail dt{color:#64748b;font-weight:750}.tw-health-detail dd{margin:0;overflow-wrap:anywhere}.tw-health-list{display:flex;flex-wrap:wrap;gap:4px}.tw-health-list span{padding:3px 6px;border-radius:5px;background:#e7edf3;color:#3d5368}
        .tw-health-action{margin-top:10px;padding:9px 10px;border-radius:8px;background:#fff6df;color:#7d5514;font-size:10px;line-height:1.5}.tw-health-action strong{display:block;margin-bottom:2px}
        .tw-health-history{padding:16px;border:1px solid var(--line);border-radius:15px;background:#fff}.tw-health-history-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:12px}.tw-health-history h3{margin:0;font-size:14px}.tw-health-history p{margin:3px 0 0;color:var(--muted);font-size:10px}
        .tw-health-table-wrap{overflow:auto}.tw-health-table{width:100%;min-width:700px;border-collapse:collapse}.tw-health-table th{text-align:left;padding:8px;border-bottom:1px solid var(--line);color:var(--muted);font-size:8px;letter-spacing:.08em;text-transform:uppercase}
        .tw-health-table td{padding:9px 8px;border-bottom:1px solid #edf1f5;font-size:10px}.tw-health-table tr:last-child td{border-bottom:0}.tw-health-view{border:0;background:#e8f1f7;color:#1a567c;border-radius:7px;padding:6px 9px;font-size:9px;font-weight:800;cursor:pointer}
        @media(max-width:1050px){.tw-health-summary{grid-template-columns:repeat(3,1fr)}.tw-health-stat.overall{grid-column:span 2}.tw-health-grid{grid-template-columns:1fr}}
        @media(max-width:650px){.tw-health-hero{align-items:flex-start;flex-direction:column}.tw-health-summary{grid-template-columns:repeat(2,1fr)}.tw-health-stat.overall{grid-column:1/-1}.tw-health-detail{grid-template-columns:1fr}.tw-health-hero h2{font-size:23px}}
    </style>

    <div class="tw-health">
        <section class="tw-health-hero">
            <div class="tw-health-hero-main">
                <div class="tw-health-eyebrow">Live operational diagnostics</div>
                <h2>{{ $statusLabel }}</h2>
                <p>
                    Checked {{ $checkedAt }}
                    · {{ $this->report['total_count'] ?? 0 }} checks
                    · {{ number_format($this->report['duration_ms'] ?? 0) }} ms
                    · {{ str($this->report['context']['environment'] ?? app()->environment())->headline() }}
                </p>
            </div>
            <div class="tw-health-actions">
                <button class="tw-health-run" wire:click="runHealthChecks" wire:loading.attr="disabled" wire:target="runHealthChecks">
                    <span wire:loading.remove wire:target="runHealthChecks">Run checks again</span>
                    <span wire:loading wire:target="runHealthChecks" style="display:inline-flex;align-items:center;gap:8px"><i class="tw-health-spin"></i> Checking system…</span>
                </button>
            </div>
        </section>

        <section class="tw-health-summary">
            <article class="tw-health-stat overall" style="--tone:{{ $status === 'healthy' ? '#11966f' : ($status === 'warning' ? '#e0a022' : '#d4455d') }}">
                <label>Overall status</label>
                <span class="tw-health-status {{ $status }}">{{ $statusLabel }}</span>
                <small>Run #{{ $this->report['run_id'] ?? '—' }}</small>
            </article>
            <article class="tw-health-stat"><label>Healthy</label><strong style="color:#0b8063">{{ $this->report['healthy_count'] ?? 0 }}</strong><small>Checks operating normally</small></article>
            <article class="tw-health-stat"><label>Warnings</label><strong style="color:#b47713">{{ $this->report['warning_count'] ?? 0 }}</strong><small>Review recommended</small></article>
            <article class="tw-health-stat"><label>Failed</label><strong style="color:#b72d46">{{ $this->report['failed_count'] ?? 0 }}</strong><small>Immediate attention</small></article>
            <article class="tw-health-stat"><label>Connectivity</label><strong style="font-size:16px">{{ ($this->report['context']['connectivity_included'] ?? false) ? 'Included' : 'Skipped' }}</strong><small>Supplier and mail network probes</small></article>
        </section>

        @foreach(($this->report['groups'] ?? []) as $group => $checks)
            <section class="tw-health-group">
                <div class="tw-health-group-head">
                    <h3>{{ $group }}</h3>
                    <span>{{ count($checks) }} check{{ count($checks) === 1 ? '' : 's' }}</span>
                </div>
                <div class="tw-health-grid">
                    @foreach($checks as $check)
                        @php($checkStatus = $check['status'] ?? 'failed')
                        <article class="tw-health-check" style="--tone:{{ $checkStatus === 'healthy' ? '#19a47d' : ($checkStatus === 'warning' ? '#e2a42a' : '#d94a61') }}">
                            <div class="tw-health-check-head">
                                <div>
                                    <h4>{{ $check['name'] ?? 'Unnamed check' }}</h4>
                                    <p class="tw-health-check-summary">{{ $check['summary'] ?? '' }}</p>
                                </div>
                                <span class="tw-health-status {{ $checkStatus }}">{{ $checkStatus }}</span>
                            </div>
                            <div class="tw-health-duration">Completed in {{ number_format($check['duration_ms'] ?? 0) }} ms</div>

                            @if(! empty($check['details']))
                                <dl class="tw-health-details">
                                    @foreach($check['details'] as $label => $value)
                                        <div class="tw-health-detail">
                                            <dt>{{ $label }}</dt>
                                            <dd>
                                                @if(is_array($value))
                                                    @if($value === [])
                                                        <span style="color:#8290a2">None</span>
                                                    @else
                                                        <span class="tw-health-list">
                                                            @foreach($value as $key => $item)
                                                                <span>{{ is_string($key) ? $key.': ' : '' }}{{ is_scalar($item) || $item === null ? ($item ?? 'None') : json_encode($item) }}</span>
                                                            @endforeach
                                                        </span>
                                                    @endif
                                                @elseif(is_bool($value))
                                                    {{ $value ? 'Yes' : 'No' }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif

                            @if(filled($check['action'] ?? null))
                                <div class="tw-health-action"><strong>Recommended action</strong>{{ $check['action'] }}</div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="tw-health-history">
            <div class="tw-health-history-head">
                <div>
                    <h3>Recent health runs</h3>
                    <p>Compare the current result with the last ten administrator checks.</p>
                </div>
            </div>
            <div class="tw-health-table-wrap">
                <table class="tw-health-table">
                    <thead><tr><th>Run</th><th>Time</th><th>Administrator</th><th>Status</th><th>Healthy</th><th>Warnings</th><th>Failed</th><th>Duration</th><th></th></tr></thead>
                    <tbody>
                        @forelse($this->recentRuns() as $run)
                            <tr>
                                <td>#{{ $run->id }}</td>
                                <td>{{ $run->created_at->format('d M Y H:i:s') }}</td>
                                <td>{{ $run->user?->name ?? 'System' }}</td>
                                <td><span class="tw-health-status {{ $run->overall_status }}">{{ $run->overall_status }}</span></td>
                                <td>{{ $run->healthy_count }}</td>
                                <td>{{ $run->warning_count }}</td>
                                <td>{{ $run->failed_count }}</td>
                                <td>{{ number_format($run->duration_ms) }} ms</td>
                                <td><button class="tw-health-view" wire:click="loadRun({{ $run->id }})">View</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;color:#7b899a">No health runs recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
