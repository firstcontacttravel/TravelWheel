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

    {{--
        The inline <style> that lived here declared this page's own palette
        (--ink:#10233e, --brand:#1261a6, --teal:#0f8a83), its own radii and its
        own badge colours — a fifth design system, in a Blade file, that no
        token could reach. It now draws from resources/css/admin/reports.css,
        so "paid" is the same green here as on the dashboard and in the queue.
    --}}

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
                <article class="tw-stat" style="--tone:var(--tc-status-info-dot)"><label>Transactions</label><strong>{{ number_format($summary['transactions']) }}</strong><small>All normalized product records
                    @if ($change('transactions') !== null)<span class="tw-change {{ $change('transactions') >= 0 ? 'up' : 'down' }}">{{ $change('transactions') > 0 ? '↑' : '↓' }} {{ abs($change('transactions')) }}%</span>@endif
                </small></article>
                @if ($this->canViewFinancials())
                    <article class="tw-stat" style="--tone:var(--tc-status-positive-dot)"><label>Verified collections</label><strong>{{ $money($summary['verified_collections']) }}</strong><small>Confirmed customer payments
                        @if ($change('verified_collections') !== null)<span class="tw-change {{ $change('verified_collections') >= 0 ? 'up' : 'down' }}">{{ $change('verified_collections') > 0 ? '↑' : '↓' }} {{ abs($change('verified_collections')) }}%</span>@endif
                    </small></article>
                    <article class="tw-stat" style="--tone:var(--tc-status-warning-dot)"><label>TravelWheel revenue</label><strong>{{ $money($summary['travelwheel_revenue']) }}</strong><small>Markup and attributable fees</small></article>
                @endif
                <article class="tw-stat" style="--tone:var(--tc-brand)"><label>Payment conversion</label><strong>{{ $percent($summary['payment_conversion']) }}</strong><small>{{ number_format($summary['paid_transactions']) }} paid records</small></article>
                <article class="tw-stat" style="--tone:var(--tc-status-positive-dot)"><label>Fulfilment rate</label><strong>{{ $percent($summary['fulfillment_rate']) }}</strong><small>Completed product requests</small></article>
                <article class="tw-stat" style="--tone:var(--tc-brand)"><label>Known customers</label><strong>{{ number_format($summary['customers']) }}</strong><small>Privacy-safe unique identities</small></article>
                <article class="tw-stat" style="--tone:var(--tc-status-critical-dot)"><label>Open exceptions</label><strong>{{ number_format($summary['open_exceptions']) }}</strong><small>State or data-quality issues</small></article>
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
                            <div><strong>{{ $target['label'] }}</strong><small style="display:block;color:var(--tc-text-secondary)">{{ str($target['metric'])->headline() }}</small></div>
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
                        ['Gross booking value',$summary['gross_value'],'var(--tc-status-info-dot)','Created value'],
                        ['Verified collections',$summary['verified_collections'],'var(--tc-status-positive-dot)','Confirmed receipts'],
                        ['TravelWheel revenue',$summary['travelwheel_revenue'],'var(--tc-status-warning-dot)','Attributable fees'],
                        ['Known gross profit',$summary['gross_profit'],'var(--tc-brand)',$summary['profit_coverage'].'% cost coverage'],
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
                    ['Known customers',$dashboard['customers']['unique'],'var(--tc-status-info-dot)','Privacy-safe identities'],
                    ['Repeat customers',$dashboard['customers']['repeat'],'var(--tc-status-positive-dot)',$dashboard['customers']['repeat_rate'].'% repeat rate'],
                    ['Cross-product customers',$dashboard['customers']['cross_product'],'var(--tc-brand)',$dashboard['customers']['cross_product_rate'].'% cross-sell rate'],
                    ['Unknown identities',$dashboard['customers']['unknown_identity'],'var(--tc-status-warning-dot)','Source data to improve'],
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
                <article class="tw-stat" style="--tone:{{ $dashboard['risk']['quality_score'] >= 90 ? 'var(--tc-status-positive-dot)' : 'var(--tc-status-warning-dot)' }}"><label>Data quality score</label><strong>{{ $dashboard['risk']['quality_score'] }}%</strong><small>Completeness and state consistency</small></article>
                <article class="tw-stat" style="--tone:var(--tc-status-critical-dot)"><label>Live alerts</label><strong>{{ number_format($dashboard['risk']['alerts']->count()) }}</strong><small>Unresolved control signals</small></article>
                <article class="tw-stat" style="--tone:var(--tc-status-warning-dot)"><label>Missing cost records</label><strong>{{ number_format($dashboard['risk']['missing_cost_records']) }}</strong><small>Limits profit confidence</small></article>
                <article class="tw-stat" style="--tone:var(--tc-brand)"><label>Duplicate references</label><strong>{{ number_format($dashboard['risk']['duplicate_references']) }}</strong><small>Within the same product</small></article>
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
