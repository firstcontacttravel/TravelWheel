<x-filament-panels::page>
    @php
        $dashboard = $this->dashboard();
        $summary = $dashboard['summary'];
        $money = fn (float|int|string|null $value) => '₦'.number_format((float) $value, 0);
        $percent = fn (float|int|string|null $value) => number_format((float) $value, 1).'%';
        $change = fn (string $key) => $summary['comparison'][$key] ?? null;
        $badge = fn (?string $status) => match ($status) {
            'paid', 'completed' => 'good',
            'processing', 'in_progress', 'pending' => 'warn',
            'failed', 'refunded' => 'bad',
            default => 'neutral',
        };
        $sections = [
            'overview' => ['Executive overview', 'Company-wide pulse'],
            'finance' => ['Revenue & finance', 'Collections and margin'],
            'products' => ['Product performance', 'Portfolio and funnels'],
            'operations' => ['Operations & SLA', 'Backlog and fulfilment'],
            'customers' => ['Customer insights', 'Retention and cross-sell'],
            'risk' => ['Risk & data quality', 'Exceptions and controls'],
        ];
    @endphp

    <style>
        .tw-bi{--ink:#10233e;--muted:#68778c;--line:#dfe7f0;--surface:#fff;--soft:#f4f7fb;--brand:#1261a6;--teal:#0f8a83;display:grid;gap:18px;color:var(--ink)}
        .tw-bi *{box-sizing:border-box}.tw-bi-hero{background:linear-gradient(118deg,#092d50 0%,#0d5587 55%,#0f8a83 130%);color:#fff;border-radius:20px;padding:24px 26px;display:flex;justify-content:space-between;gap:24px;align-items:center;box-shadow:0 14px 35px rgba(8,42,72,.16)}
        .tw-bi-eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:.16em;font-weight:800;color:#91d9d2}.tw-bi-hero h2{font-size:25px;line-height:1.2;margin:5px 0}.tw-bi-hero p{margin:0;color:#d5e6f2;font-size:13px}.tw-bi-hero-actions{display:flex;gap:9px;flex-wrap:wrap}
        .tw-bi button,.tw-bi .tw-btn{border:0;border-radius:9px;padding:9px 13px;font-weight:750;font-size:12px;cursor:pointer;text-decoration:none;display:inline-flex;gap:6px;align-items:center}.tw-btn-light{background:#fff;color:#164d72}.tw-btn-ghost{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25)!important}.tw-btn-primary{background:var(--brand);color:#fff}.tw-btn-soft{background:#eaf2f9;color:#245474}
        .tw-bi-filter{background:var(--surface);border:1px solid var(--line);border-radius:15px;padding:16px;display:grid;grid-template-columns:repeat(7,minmax(110px,1fr));gap:11px;align-items:end}.tw-field{display:grid;gap:5px}.tw-field span{font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:800}.tw-field input,.tw-field select{width:100%;height:37px;border:1px solid #cad6e3;border-radius:8px;background:#fff;color:var(--ink);padding:0 9px;font-size:12px}.tw-field select[multiple]{height:70px;padding:5px}
        .tw-bi-saved{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.tw-bi-saved input{height:35px;border:1px solid #cad6e3;border-radius:8px;padding:0 10px;font-size:12px}.tw-saved-pill{display:inline-flex;align-items:center;background:#edf4f9;border-radius:999px;overflow:hidden}.tw-saved-pill button{background:transparent;color:#2a5876;padding:7px 10px}.tw-saved-pill button:last-child{padding-left:3px;color:#9a4351}
        .tw-bi-tabs{display:grid;grid-template-columns:repeat(6,1fr);gap:7px;background:#eaf0f6;padding:6px;border-radius:13px}.tw-bi-tab{background:transparent!important;color:#64748b;padding:10px!important;justify-content:center}.tw-bi-tab.active{background:#fff!important;color:#104d78;box-shadow:0 2px 8px rgba(22,45,75,.08)}.tw-bi-tab small{display:none}
        .tw-bi-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:15px}.tw-stat{grid-column:span 3;background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;position:relative;overflow:hidden}.tw-stat:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--tone,#2f70b7)}.tw-stat label{display:block;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.tw-stat strong{display:block;font-size:22px;margin:7px 0 4px}.tw-stat small{font-size:11px;color:var(--muted)}.tw-change{margin-left:5px;font-size:10px;font-weight:800}.tw-change.up{color:#078467}.tw-change.down{color:#c3424f}
        .tw-panel{background:#fff;border:1px solid var(--line);border-radius:15px;padding:17px;grid-column:span 6;min-width:0}.tw-panel.wide{grid-column:span 8}.tw-panel.narrow{grid-column:span 4}.tw-panel.full{grid-column:1/-1}.tw-panel-head{display:flex;justify-content:space-between;gap:12px;align-items:start;margin-bottom:15px}.tw-panel h3{font-size:14px;margin:0 0 3px}.tw-panel-head p{font-size:11px;color:var(--muted);margin:0}.tw-panel-head a{font-size:11px;font-weight:800;color:var(--brand);text-decoration:none}
        .tw-chart{height:200px;display:flex;align-items:end;gap:4px;padding-top:16px;border-bottom:1px solid var(--line);overflow:hidden}.tw-bar-col{height:100%;flex:1;display:flex;align-items:end;min-width:3px;position:relative}.tw-bar{width:100%;background:linear-gradient(#2a80be,#0f8a83);border-radius:4px 4px 0 0;min-height:2px;opacity:.9}.tw-bar-col:hover .tw-bar{opacity:1;background:#0d5587}.tw-chart-labels{display:flex;justify-content:space-between;font-size:10px;color:var(--muted);margin-top:7px}
        .tw-product-row,.tw-breakdown-row,.tw-target-row{display:grid;grid-template-columns:minmax(125px,1.3fr) 2fr repeat(2,minmax(85px,1fr));gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid #edf1f5;font-size:12px}.tw-product-row:last-child,.tw-breakdown-row:last-child,.tw-target-row:last-child{border:0}.tw-product-name{display:flex;gap:8px;align-items:center;font-weight:750}.tw-dot{width:9px;height:9px;border-radius:50%;flex:none}.tw-progress{height:7px;background:#e9eef4;border-radius:20px;overflow:hidden}.tw-progress i{display:block;height:100%;border-radius:20px;background:var(--bar,#2772b5)}.tw-num{text-align:right}.tw-num strong{display:block}.tw-num small{color:var(--muted)}
        .tw-table-wrap{overflow:auto}.tw-table{width:100%;border-collapse:collapse;min-width:680px}.tw-table th{text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:9px;border-bottom:1px solid var(--line)}.tw-table td{font-size:11px;padding:10px 9px;border-bottom:1px solid #edf1f5;vertical-align:top}.tw-table td strong{display:block}.tw-table td small{color:var(--muted)}.tw-badge{display:inline-block;border-radius:999px;padding:4px 7px;font-size:9px;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.tw-badge.good{background:#dff6ee;color:#08745c}.tw-badge.warn{background:#fff0cf;color:#956313}.tw-badge.bad{background:#fde5e8;color:#a9273c}.tw-badge.neutral{background:#edf1f5;color:#59687b}
        .tw-aging{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.tw-aging div{background:var(--soft);border-radius:10px;padding:13px}.tw-aging strong{display:block;font-size:21px}.tw-aging span{font-size:10px;color:var(--muted)}.tw-mini-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.tw-mini{background:var(--soft);border-radius:10px;padding:13px}.tw-mini strong{display:block;font-size:19px}.tw-mini span{font-size:10px;color:var(--muted)}
        .tw-funnel{display:grid;grid-template-columns:140px repeat(5,1fr);gap:6px;align-items:center;padding:7px 0;border-bottom:1px solid #edf1f5;font-size:11px}.tw-funnel strong{font-size:11px}.tw-funnel span{text-align:center;padding:7px 3px;background:#f0f5f9;border-radius:6px}.tw-funnel-head span{background:transparent;color:var(--muted);font-size:9px;text-transform:uppercase;font-weight:800}
        .tw-issue{display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid #edf1f5;font-size:11px}.tw-empty{text-align:center;color:var(--muted);padding:24px!important}.tw-definition{padding:11px;background:var(--soft);border-radius:9px;margin-bottom:8px}.tw-definition strong{display:block;font-size:11px}.tw-definition p{margin:3px 0 0;font-size:10px;color:var(--muted)}
        .tw-fresh{font-size:10px;color:var(--muted);text-align:right}
        @media(max-width:1200px){.tw-bi-filter{grid-template-columns:repeat(4,1fr)}.tw-stat{grid-column:span 4}.tw-bi-tabs{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:800px){.tw-bi-hero{align-items:flex-start;flex-direction:column}.tw-bi-filter{grid-template-columns:repeat(2,1fr)}.tw-stat{grid-column:span 6}.tw-panel,.tw-panel.wide,.tw-panel.narrow{grid-column:1/-1}.tw-bi-tabs{grid-template-columns:repeat(2,1fr)}.tw-product-row{grid-template-columns:1fr 1fr}.tw-product-row .tw-progress{display:none}.tw-aging{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:520px){.tw-bi-filter{grid-template-columns:1fr}.tw-stat{grid-column:1/-1}.tw-mini-stats{grid-template-columns:1fr}.tw-bi-tabs{grid-template-columns:1fr 1fr}.tw-bi-tab{font-size:10px}.tw-funnel{grid-template-columns:110px repeat(5,55px);overflow:auto}}
    </style>

    <div class="tw-bi">
        <section class="tw-bi-hero">
            <div>
                <div class="tw-bi-eyebrow">TravelWheel intelligence</div>
                <h2>Every product. One operating picture.</h2>
                <p>{{ $dashboard['range'][0]->format('d M Y') }} — {{ $dashboard['range'][1]->format('d M Y') }} · {{ str($this->dateBasis)->headline() }} date basis</p>
            </div>
            <div class="tw-bi-hero-actions">
                @if ($this->canViewFinancials())
                    <a class="tw-btn tw-btn-light" href="{{ $this->exportUrl('transactions', 'csv') }}">Export CSV</a>
                    <a class="tw-btn tw-btn-ghost" href="{{ $this->exportUrl('transactions', 'xlsx') }}">Export XLSX</a>
                @endif
                <button class="tw-btn tw-btn-ghost" wire:click="refreshReportingData" wire:loading.attr="disabled">Refresh data</button>
            </div>
        </section>

        <section class="tw-bi-filter">
            <label class="tw-field"><span>From</span><input type="date" wire:model.live.debounce.400ms="from"></label>
            <label class="tw-field"><span>To</span><input type="date" wire:model.live.debounce.400ms="to"></label>
            <label class="tw-field"><span>Date basis</span>
                <select wire:model.live="dateBasis">
                    <option value="created">Created</option><option value="paid">Paid</option>
                    <option value="service">Service date</option><option value="completed">Completed</option>
                </select>
            </label>
            <label class="tw-field"><span>Products</span>
                <select wire:model.live="products" multiple>
                    @foreach ($this->productOptions() as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="tw-field"><span>Payment</span>
                <select wire:model.live="paymentStatus"><option value="">All statuses</option>
                    @foreach (['paid','pending','processing','failed','refunded','unknown'] as $status)<option value="{{ $status }}">{{ str($status)->headline() }}</option>@endforeach
                </select>
            </label>
            <label class="tw-field"><span>Fulfilment</span>
                <select wire:model.live="fulfillmentStatus"><option value="">All statuses</option>
                    @foreach (['completed','in_progress','pending','failed','unknown'] as $status)<option value="{{ $status }}">{{ str($status)->replace('_',' ')->headline() }}</option>@endforeach
                </select>
            </label>
            <label class="tw-field"><span>Payment method</span>
                <select wire:model.live="paymentMethod"><option value="">All methods</option>
                    @foreach ($this->paymentMethodOptions() as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </label>
        </section>

        <section class="tw-bi-saved">
            <input type="text" wire:model="savedViewName" placeholder="Name this view">
            <button class="tw-btn-primary" wire:click="saveCurrentView">Save view</button>
            @foreach ($this->savedViews() as $view)
                <span class="tw-saved-pill">
                    <button wire:click="applySavedView({{ $view->id }})">{{ $view->name }}</button>
                    @if ($view->user_id === auth()->id())<button title="Delete" wire:click="deleteSavedView({{ $view->id }})">×</button>@endif
                </span>
            @endforeach
        </section>

        <nav class="tw-bi-tabs">
            @foreach ($sections as $key => [$label, $description])
                <button wire:click="$set('section', '{{ $key }}')" class="tw-bi-tab {{ $this->section === $key ? 'active' : '' }}">
                    {{ $label }}<small>{{ $description }}</small>
                </button>
            @endforeach
        </nav>

        @if ($this->section === 'overview')
            <section class="tw-bi-grid">
                <article class="tw-stat" style="--tone:#2563eb"><label>Transactions</label><strong>{{ number_format($summary['transactions']) }}</strong><small>All normalized product records
                    @if ($change('transactions') !== null)<span class="tw-change {{ $change('transactions') >= 0 ? 'up' : 'down' }}">{{ $change('transactions') > 0 ? '↑' : '↓' }} {{ abs($change('transactions')) }}%</span>@endif
                </small></article>
                @if ($this->canViewFinancials())
                    <article class="tw-stat" style="--tone:#0f8a83"><label>Verified collections</label><strong>{{ $money($summary['verified_collections']) }}</strong><small>Confirmed customer payments
                        @if ($change('verified_collections') !== null)<span class="tw-change {{ $change('verified_collections') >= 0 ? 'up' : 'down' }}">{{ $change('verified_collections') > 0 ? '↑' : '↓' }} {{ abs($change('verified_collections')) }}%</span>@endif
                    </small></article>
                    <article class="tw-stat" style="--tone:#d97706"><label>TravelWheel revenue</label><strong>{{ $money($summary['travelwheel_revenue']) }}</strong><small>Markup and attributable fees</small></article>
                @endif
                <article class="tw-stat" style="--tone:#7c3aed"><label>Payment conversion</label><strong>{{ $percent($summary['payment_conversion']) }}</strong><small>{{ number_format($summary['paid_transactions']) }} paid records</small></article>
                <article class="tw-stat" style="--tone:#059669"><label>Fulfilment rate</label><strong>{{ $percent($summary['fulfillment_rate']) }}</strong><small>Completed product requests</small></article>
                <article class="tw-stat" style="--tone:#db2777"><label>Known customers</label><strong>{{ number_format($summary['customers']) }}</strong><small>Privacy-safe unique identities</small></article>
                <article class="tw-stat" style="--tone:#dc2626"><label>Open exceptions</label><strong>{{ number_format($summary['open_exceptions']) }}</strong><small>State or data-quality issues</small></article>
            </section>

            <section class="tw-bi-grid">
                <article class="tw-panel wide">
                    <div class="tw-panel-head"><div><h3>Collections trajectory</h3><p>Daily verified collections for the selected date basis.</p></div></div>
                    <div class="tw-chart">
                        @foreach ($dashboard['trend'] as $point)
                            <div class="tw-bar-col" title="{{ $point['label'] }}: {{ $money($point['collections']) }}"><i class="tw-bar" style="height:{{ $point['height'] }}%"></i></div>
                        @endforeach
                    </div>
                    <div class="tw-chart-labels"><span>{{ data_get($dashboard, 'trend.0.label') }}</span><span>{{ data_get($dashboard, 'trend.'.(count($dashboard['trend']) - 1).'.label') }}</span></div>
                </article>
                <article class="tw-panel narrow">
                    <div class="tw-panel-head"><div><h3>Forward run rate</h3><p>{{ $dashboard['forecast']['method'] }}</p></div></div>
                    <div class="tw-mini-stats" style="grid-template-columns:1fr">
                        <div class="tw-mini"><span>Next 30-day collections</span><strong>{{ $money($dashboard['forecast']['next_30_day_collections']) }}</strong></div>
                        <div class="tw-mini"><span>Next 30-day revenue</span><strong>{{ $money($dashboard['forecast']['next_30_day_revenue']) }}</strong></div>
                        <div class="tw-mini"><span>Daily collection run rate</span><strong>{{ $money($dashboard['forecast']['daily_collection_run_rate']) }}</strong></div>
                    </div>
                </article>
                <article class="tw-panel wide">
                    <div class="tw-panel-head"><div><h3>Portfolio performance</h3><p>Collections share, conversion, and average value by product.</p></div><button class="tw-btn-soft" wire:click="$set('section','products')">Open products</button></div>
                    @forelse ($dashboard['products'] as $product)
                        <div class="tw-product-row">
                            <div class="tw-product-name"><i class="tw-dot" style="background:{{ $product['color'] }}"></i>{{ $product['label'] }}</div>
                            <div class="tw-progress"><i style="width:{{ min(100,$product['share']) }}%;--bar:{{ $product['color'] }}"></i></div>
                            <div class="tw-num"><strong>{{ $money($product['collections']) }}</strong><small>{{ $product['share'] }}% share</small></div>
                            <div class="tw-num"><strong>{{ $product['conversion'] }}%</strong><small>conversion</small></div>
                        </div>
                    @empty <div class="tw-empty">No product activity in this period.</div> @endforelse
                </article>
                <article class="tw-panel narrow">
                    <div class="tw-panel-head"><div><h3>Targets</h3><p>Progress against active goals.</p></div>
                        @if ($this->canManageReporting())<a href="{{ url('/admin/reporting-targets') }}">Manage</a>@endif
                    </div>
                    @forelse ($dashboard['targets'] as $target)
                        <div class="tw-target-row" style="grid-template-columns:1fr 1fr">
                            <div><strong>{{ $target['label'] }}</strong><small style="display:block;color:#68778c">{{ str($target['metric'])->headline() }}</small></div>
                            <div><div class="tw-progress"><i style="width:{{ min(100,$target['attainment']) }}%"></i></div><small>{{ $target['attainment'] }}% · {{ number_format($target['actual']) }} / {{ number_format($target['target']) }}</small></div>
                        </div>
                    @empty <div class="tw-empty">No targets overlap this period.</div> @endforelse
                </article>
            </section>
        @elseif ($this->section === 'finance')
            @if (! $this->canViewFinancials())
                <article class="tw-panel full"><div class="tw-empty">Your role can view operational reporting, but financial reporting is restricted.</div></article>
            @else
                <section class="tw-bi-grid">
                    @foreach ([
                        ['Gross booking value',$summary['gross_value'],'#2563eb','Created value'],
                        ['Verified collections',$summary['verified_collections'],'#059669','Confirmed receipts'],
                        ['TravelWheel revenue',$summary['travelwheel_revenue'],'#d97706','Attributable fees'],
                        ['Known gross profit',$summary['gross_profit'],'#7c3aed',$summary['profit_coverage'].'% cost coverage'],
                    ] as [$label,$value,$tone,$note])
                        <article class="tw-stat" style="--tone:{{ $tone }}"><label>{{ $label }}</label><strong>{{ $money($value) }}</strong><small>{{ $note }}</small></article>
                    @endforeach
                    <article class="tw-panel">
                        <div class="tw-panel-head"><div><h3>Collections by payment method</h3><p>Verified money, not checkout intent.</p></div></div>
                        @forelse ($dashboard['finance']['by_payment_method'] as $row)
                            <div class="tw-breakdown-row"><strong>{{ $row['label'] }}</strong><span>{{ number_format($row['transactions']) }} transactions</span><span class="tw-num">{{ $money($row['collections']) }}</span><span class="tw-num">{{ $money($row['revenue']) }} revenue</span></div>
                        @empty <div class="tw-empty">No payment method data.</div> @endforelse
                    </article>
                    <article class="tw-panel">
                        <div class="tw-panel-head"><div><h3>Collections by gateway</h3><p>Provider reconciliation view.</p></div></div>
                        @forelse ($dashboard['finance']['by_gateway'] as $row)
                            <div class="tw-breakdown-row"><strong>{{ $row['label'] }}</strong><span>{{ number_format($row['transactions']) }} transactions</span><span class="tw-num">{{ $money($row['collections']) }}</span><span class="tw-num">{{ $money($row['gross_value'] - $row['collections']) }} gap</span></div>
                        @empty <div class="tw-empty">No gateway data.</div> @endforelse
                    </article>
                    <article class="tw-panel full">
                        <div class="tw-panel-head"><div><h3>Outstanding collection queue</h3><p>Highest-value records that have not reached a paid state.</p></div><a href="{{ $this->exportUrl('reconciliation') }}">Export</a></div>
                        @include('filament.pages.partials.reporting-fact-table', ['facts' => $dashboard['finance']['uncollected'], 'money' => $money, 'badge' => $badge])
                    </article>
                </section>
            @endif
        @elseif ($this->section === 'products')
            <section class="tw-bi-grid">
                <article class="tw-panel full">
                    <div class="tw-panel-head"><div><h3>Product scorecard</h3><p>Commercial and delivery performance across the entire portfolio.</p></div></div>
                    <div class="tw-table-wrap"><table class="tw-table"><thead><tr><th>Product</th><th>Transactions</th><th>Gross value</th><th>Collections</th><th>Revenue</th><th>Conversion</th><th>Completion</th><th>AOV</th></tr></thead><tbody>
                    @forelse ($dashboard['products'] as $product)<tr>
                        <td><strong><i class="tw-dot" style="display:inline-block;background:{{ $product['color'] }}"></i> {{ $product['label'] }}</strong></td>
                        <td>{{ number_format($product['transactions']) }}</td><td>{{ $this->canViewFinancials() ? $money($product['gross_value']) : 'Restricted' }}</td>
                        <td>{{ $this->canViewFinancials() ? $money($product['collections']) : 'Restricted' }}</td><td>{{ $this->canViewFinancials() ? $money($product['revenue']) : 'Restricted' }}</td>
                        <td>{{ $product['conversion'] }}%</td><td>{{ $product['completion'] }}%</td><td>{{ $this->canViewFinancials() ? $money($product['aov']) : 'Restricted' }}</td>
                    </tr>@empty <tr><td colspan="8" class="tw-empty">No product activity.</td></tr>@endforelse
                    </tbody></table></div>
                </article>
                <article class="tw-panel full">
                    <div class="tw-panel-head"><div><h3>Product funnels</h3><p>Created → paid → in progress → completed, with failures visible.</p></div></div>
                    <div class="tw-funnel tw-funnel-head"><strong>Product</strong><span>Created</span><span>Paid</span><span>In progress</span><span>Completed</span><span>Failed</span></div>
                    @foreach ($dashboard['funnels'] as $funnel)<div class="tw-funnel"><strong>{{ $funnel['product'] }}</strong><span>{{ $funnel['created'] }}</span><span>{{ $funnel['paid'] }}</span><span>{{ $funnel['in_progress'] }}</span><span>{{ $funnel['completed'] }}</span><span>{{ $funnel['failed'] }}</span></div>@endforeach
                </article>
            </section>
        @elseif ($this->section === 'operations')
            <section class="tw-bi-grid">
                <article class="tw-panel full">
                    <div class="tw-panel-head"><div><h3>Open-work aging</h3><p>Age of pending, in-progress, and unclassified fulfilment.</p></div></div>
                    <div class="tw-aging">@foreach ($dashboard['operations']['aging'] as $label => $value)<div><strong>{{ number_format($value) }}</strong><span>{{ $label }}</span></div>@endforeach</div>
                </article>
                <article class="tw-panel narrow">
                    <div class="tw-panel-head"><div><h3>Fulfilment health</h3><p>Current state distribution.</p></div></div>
                    @foreach ($dashboard['operations']['status'] as $status => $count)<div class="tw-issue"><span><i class="tw-badge {{ $badge($status) }}">{{ str($status)->replace('_',' ')->headline() }}</i></span><strong>{{ number_format($count) }}</strong></div>@endforeach
                    <div class="tw-mini" style="margin-top:12px"><span>Average completion time</span><strong>{{ number_format($dashboard['operations']['avg_completion_hours'],1) }}h</strong></div>
                </article>
                <article class="tw-panel wide">
                    <div class="tw-panel-head"><div><h3>Oldest operational backlog</h3><p>Work requiring the earliest attention.</p></div><a href="{{ $this->exportUrl('operations') }}">Export</a></div>
                    @include('filament.pages.partials.reporting-fact-table', ['facts' => $dashboard['operations']['backlog'], 'money' => $money, 'badge' => $badge])
                </article>
                <article class="tw-panel full">
                    <div class="tw-panel-head"><div><h3>Paid, not completed</h3><p>Customer money is verified but fulfilment is still open.</p></div></div>
                    @include('filament.pages.partials.reporting-fact-table', ['facts' => $dashboard['operations']['paid_not_completed'], 'money' => $money, 'badge' => $badge])
                </article>
            </section>
        @elseif ($this->section === 'customers')
            <section class="tw-bi-grid">
                @foreach ([
                    ['Known customers',$dashboard['customers']['unique'],'#2563eb','Privacy-safe identities'],
                    ['Repeat customers',$dashboard['customers']['repeat'],'#059669',$dashboard['customers']['repeat_rate'].'% repeat rate'],
                    ['Cross-product customers',$dashboard['customers']['cross_product'],'#7c3aed',$dashboard['customers']['cross_product_rate'].'% cross-sell rate'],
                    ['Unknown identities',$dashboard['customers']['unknown_identity'],'#d97706','Source data to improve'],
                ] as [$label,$value,$tone,$note])
                    <article class="tw-stat" style="--tone:{{ $tone }}"><label>{{ $label }}</label><strong>{{ number_format($value) }}</strong><small>{{ $note }}</small></article>
                @endforeach
                <article class="tw-panel full">
                    <div class="tw-panel-head"><div><h3>Highest-value customer cohorts</h3><p>Hashed customer keys preserve privacy while revealing retention and cross-sell.</p></div></div>
                    <div class="tw-table-wrap"><table class="tw-table"><thead><tr><th>Customer key</th><th>Transactions</th><th>Products used</th><th>Verified value</th></tr></thead><tbody>
                    @forelse ($dashboard['customers']['top'] as $customer)<tr><td><strong>{{ $customer['customer'] }}</strong></td><td>{{ $customer['transactions'] }}</td><td>{{ implode(', ', $customer['products']) }}</td><td>{{ $this->canViewFinancials() ? $money($customer['value']) : 'Restricted' }}</td></tr>
                    @empty <tr><td colspan="4" class="tw-empty">No customer identities available.</td></tr>@endforelse
                    </tbody></table></div>
                </article>
            </section>
        @else
            <section class="tw-bi-grid">
                <article class="tw-stat" style="--tone:{{ $dashboard['risk']['quality_score'] >= 90 ? '#059669' : '#d97706' }}"><label>Data quality score</label><strong>{{ $dashboard['risk']['quality_score'] }}%</strong><small>Completeness and state consistency</small></article>
                <article class="tw-stat" style="--tone:#dc2626"><label>Live alerts</label><strong>{{ number_format($dashboard['risk']['alerts']->count()) }}</strong><small>Unresolved control signals</small></article>
                <article class="tw-stat" style="--tone:#d97706"><label>Missing cost records</label><strong>{{ number_format($dashboard['risk']['missing_cost_records']) }}</strong><small>Limits profit confidence</small></article>
                <article class="tw-stat" style="--tone:#7c3aed"><label>Duplicate references</label><strong>{{ number_format($dashboard['risk']['duplicate_references']) }}</strong><small>Within the same product</small></article>
                <article class="tw-panel narrow">
                    <div class="tw-panel-head"><div><h3>Issue profile</h3><p>Most frequent data controls.</p></div></div>
                    @forelse ($dashboard['risk']['issue_counts'] as $issue => $count)<div class="tw-issue"><span>{{ str($issue)->replace('_',' ')->headline() }}</span><strong>{{ $count }}</strong></div>@empty <div class="tw-empty">No quality issues.</div>@endforelse
                </article>
                <article class="tw-panel wide">
                    <div class="tw-panel-head"><div><h3>Live alerts</h3><p>Material reconciliation and fulfilment conflicts.</p></div></div>
                    @forelse ($dashboard['risk']['alerts'] as $alert)
                        <div class="tw-issue"><span><i class="tw-badge {{ $alert->severity === 'critical' ? 'bad' : 'warn' }}">{{ $alert->severity }}</i> {{ $alert->message }}</span>
                            @if (! $alert->acknowledged_at)<button class="tw-btn-soft" wire:click="acknowledgeAlert({{ $alert->id }})">Acknowledge</button>@else<small>Acknowledged</small>@endif
                        </div>
                    @empty <div class="tw-empty">No live alerts.</div> @endforelse
                </article>
                <article class="tw-panel wide">
                    <div class="tw-panel-head"><div><h3>Exception work queue</h3><p>Records with payment, fulfilment, or source-data conflicts.</p></div><a href="{{ $this->exportUrl('exceptions') }}">Export</a></div>
                    @include('filament.pages.partials.reporting-fact-table', ['facts' => $dashboard['risk']['exceptions'], 'money' => $money, 'badge' => $badge])
                </article>
                <article class="tw-panel narrow">
                    <div class="tw-panel-head"><div><h3>Metric definitions</h3><p>Shared vocabulary for trustworthy decisions.</p></div></div>
                    @foreach (config('reporting.metrics') as $metric)<div class="tw-definition"><strong>{{ $metric['label'] }}</strong><p>{{ $metric['definition'] }}</p></div>@endforeach
                </article>
            </section>
        @endif

        <div class="tw-fresh">Generated {{ $dashboard['generated_at']->timezone('Africa/Lagos')->format('d M Y, H:i') }} WAT · Source sync freshness target: {{ config('reporting.fresh_for_minutes') }} minutes</div>
    </div>
</x-filament-panels::page>
