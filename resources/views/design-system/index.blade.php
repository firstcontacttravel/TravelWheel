@extends('design-system.layout')

@section('title', 'Design system')

@push('styles')
<style>
    /* Specimen-only layout. None of this is part of the system — it is the
       scaffolding the system is displayed in. */
    .sp-shell { display: grid; grid-template-columns: 200px minmax(0, 1fr); min-height: 100vh; }
    .sp-nav { position: sticky; top: 0; align-self: start; height: 100vh; overflow-y: auto;
              padding: var(--tc-space-6) var(--tc-space-5); border-inline-end: 1px solid var(--tc-border-subtle);
              background: var(--tc-bg-surface); }
    .sp-nav a { display: block; padding: var(--tc-space-2) 0; color: var(--tc-text-secondary);
                text-decoration: none; font-size: var(--tc-text-body); }
    .sp-nav a:hover { color: var(--tc-text-primary); }
    .sp-main { min-width: 0; padding: var(--tc-space-7) var(--tc-space-8) var(--tc-space-12); }
    .sp-head { display: flex; align-items: flex-start; justify-content: space-between;
               gap: var(--tc-space-5); flex-wrap: wrap; margin-bottom: var(--tc-space-8); }
    .sp-section { margin-block: var(--tc-space-10) var(--tc-space-7); scroll-margin-top: var(--tc-space-5); }
    .sp-section > p { max-width: 68ch; margin: var(--tc-space-2) 0 var(--tc-space-5); }
    .sp-grid { display: grid; gap: var(--tc-space-4); grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
    .sp-row { display: flex; align-items: center; gap: var(--tc-space-5); flex-wrap: wrap; }
    .sp-stack { display: grid; gap: var(--tc-space-4); }

    .sp-swatch { border: 1px solid var(--tc-border-subtle); border-radius: var(--tc-radius-sm); overflow: hidden; }
    .sp-swatch i { display: block; height: 52px; }
    .sp-swatch div { padding: var(--tc-space-2) var(--tc-space-3); border-top: 1px solid var(--tc-border-subtle); }

    .sp-spec { display: flex; align-items: baseline; gap: var(--tc-space-5);
               padding-block: var(--tc-space-3); border-bottom: 1px dashed var(--tc-border-subtle); }
    .sp-spec > :first-child { flex: none; width: 94px; }

    /* The before/after density comparison. "Before" reproduces the measured
       geometry of the current panel — 126px rows, 16px type — so the
       comparison is honest rather than flattering. */
    .sp-compare { display: grid; gap: var(--tc-space-7); grid-template-columns: minmax(0, 1fr); }
    .sp-before-row { display: grid; grid-template-columns: 110px 1fr auto; gap: 16px; align-items: center;
                     height: 126px; padding-inline: 16px; border-bottom: 1px solid #e5e7eb;
                     font-family: Inter, system-ui, sans-serif; font-size: 16px; color: #111827; background: #fff; }
    .sp-before-pill { display: inline-block; padding: 4px 10px; border-radius: 999px;
                      background: rgba(0,168,90,.12); color: #006b3a; font-size: 12px; font-weight: 700; }

    /* The new row. Geometry comes from the tokens, not from this file. */
    .sp-table { border: 1px solid var(--tc-border-subtle); border-radius: var(--tc-radius-md);
                background: var(--tc-bg-surface); overflow: hidden; }
    .sp-thead, .sp-trow { display: grid;
                          grid-template-columns: 132px 150px minmax(150px, 230px) minmax(0, 1fr) 128px;
                          align-items: center; gap: var(--tc-space-5); padding-inline: var(--tc-space-5); }
    .sp-thead { height: 32px; border-bottom: 1px solid var(--tc-border-subtle); background: var(--tc-bg-sunken); }
    .sp-trow { height: var(--tc-row-height); border-bottom: 1px solid var(--tc-border-subtle);
               transition: background-color var(--tc-duration-fast) var(--tc-ease-standard); }
    .sp-trow:last-child { border-bottom: 0; }
    .sp-trow:hover { background: var(--tc-bg-hover); }
    .sp-end { text-align: end; }

    /* Rail sketch, at its real 56px. */
    .sp-rail { width: var(--tc-rail-width); border-radius: var(--tc-radius-md);
               background: var(--tc-bg-rail); padding-block: var(--tc-space-3); display: grid;
               gap: var(--tc-space-1); justify-items: center; align-content: start; }
    .sp-rail b { position: relative; display: grid; place-items: center; width: 36px; height: 36px;
                 border-radius: var(--tc-radius-sm); color: var(--tc-text-on-rail); font-weight: 400; font-size: 15px; }
    .sp-rail b:hover { background: var(--tc-bg-rail-hover); color: var(--tc-text-on-rail-active); }
    .sp-rail b.is-active { background: var(--tc-bg-rail-active); color: var(--tc-text-on-rail-active); }
    .sp-rail b.is-active::before { content: ""; position: absolute; inset-inline-start: -10px; top: 8px; bottom: 8px;
                                   width: 2px; border-radius: 0 2px 2px 0; background: var(--tc-accent-500); }

    .sp-note { border-inline-start: 2px solid var(--tc-border-strong); padding-inline-start: var(--tc-space-4);
               color: var(--tc-text-secondary); }

    /* Caught by the responsive harness on its first run: below ~900px the
       200px sticky nav left the content about 190px wide and set the prose
       one word per line. The nav becomes a horizontal strip and the density
       tables scroll rather than crush their columns. */
    @media (max-width: 900px) {
        .sp-shell { grid-template-columns: minmax(0, 1fr); }
        .sp-nav { position: static; height: auto; display: flex; gap: var(--tc-space-4);
                  overflow-x: auto; align-items: center; padding: var(--tc-space-3) var(--tc-space-5);
                  border-inline-end: 0; border-bottom: 1px solid var(--tc-border-subtle); }
        .sp-nav p { display: none; }
        .sp-nav a { padding: 0; white-space: nowrap; }
        .sp-main { padding: var(--tc-space-6) var(--tc-space-5) var(--tc-space-10); }
        .sp-head { flex-direction: column; align-items: stretch; }
    }

    @media (max-width: 760px) {
        .sp-table, .sp-before-wrap { overflow-x: auto; }
        .sp-thead, .sp-trow { min-width: 720px; }
        .sp-before-row { min-width: 560px; }
        .sp-row { gap: var(--tc-space-4); }
        .sp-section { margin-block: var(--tc-space-8) var(--tc-space-6); }
    }
</style>
@endpush

@section('body')
<div class="sp-shell">
    <nav class="sp-nav">
        <p class="tc-t-label" style="margin-bottom:var(--tc-space-3)">Specimen</p>
        <a href="#density">Density</a>
        <a href="#type">Type scale</a>
        <a href="#numerals">Numerals</a>
        <a href="#colour">Colour roles</a>
        <a href="#status">Status</a>
        <a href="#controls">Controls</a>
        <a href="#surfaces">Surfaces</a>
        <a href="#rail">Navigation rail</a>
        <a href="#scale">Space &amp; radius</a>
        <a href="#motion">Motion</a>
    </nav>

    <main class="sp-main">
        <header class="sp-head">
            <div>
                <p class="tc-t-label">TravelWheel Console · Phase 1</p>
                <h1 class="tc-t-hero" style="margin-top:var(--tc-space-2)">Design system</h1>
                <p class="tc-t-base" style="max-width:62ch;margin-top:var(--tc-space-3)">
                    The foundation only — tokens, type and the primitives the shell, tables and
                    forms will be built from. Nothing in the admin panel has changed yet.
                    Everything below uses {{ $isLive ? 'live booking data from this database' : 'representative booking data' }},
                    because the question this page exists to answer is whether the density reads
                    as professional or as cramped with your records in it.
                </p>
            </div>
            <div class="sp-row" style="gap:var(--tc-space-3)">
                <button type="button" class="tc-btn" data-tc-theme>Toggle dark</button>
                <a class="tc-btn tc-btn-quiet" href="{{ route('design-system.responsive') }}">Responsive harness</a>
            </div>
        </header>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="density">
            <h2 class="tc-t-heading">Density</h2>
            <p class="tc-t-base">
                The decision that defines this direction. Today a booking row is 126px tall and a
                1517px screen shows four of them; at a 40px row the same screen shows eighteen.
                Above is the current panel reproduced at its measured geometry, below is the system.
            </p>

            <div class="sp-compare">
                <div>
                    <p class="tc-t-label" style="margin-bottom:var(--tc-space-3)">Now · 126px rows · 4 visible · shown in the current panel's own colours</p>
                    <div class="sp-before-wrap" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff">
                        @foreach ($bookings->take(3) as $b)
                            <div class="sp-before-row">
                                <span class="sp-before-pill">{{ $b['queue'] }}</span>
                                <span>
                                    <strong style="display:block;font-weight:700">{{ $b['ref'] }}</strong>
                                    <span style="color:#667085;font-size:14px">{{ $b['from'] }} -&gt; {{ $b['to'] }} · {{ $b['airline'] }}</span>
                                </span>
                                <span style="white-space:nowrap">NGN {{ $b['amount'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="tc-t-label" style="margin-bottom:var(--tc-space-3)">System · 40px rows · 18 visible</p>
                    <div class="sp-table">
                        <div class="sp-thead">
                            <span class="tc-t-label">Booking</span>
                            <span class="tc-t-label">Status</span>
                            <span class="tc-t-label">Journey</span>
                            <span class="tc-t-label">Airline</span>
                            <span class="tc-t-label sp-end">Total</span>
                        </div>
                        @foreach ($bookings as $b)
                            <div class="sp-trow">
                                <span class="tc-mono tc-t-body">{{ $b['ref'] }}</span>
                                <span class="tc-status tc-status-{{ $b['tone'] }} {{ $b['shape'] }} tc-t-small">{{ $b['queue'] }}</span>
                                <span class="tc-route tc-t-body">
                                    <span class="tc-mono">{{ $b['from'] }}</span>
                                    <span class="tc-route-line"></span>
                                    <span class="tc-mono">{{ $b['to'] }}</span>
                                </span>
                                <span class="tc-t-small tc-truncate">{{ $b['airline'] }}</span>
                                <span class="tc-money tc-t-body sp-end">&#8358;{{ $b['amount'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="sp-note tc-t-small" style="margin-top:var(--tc-space-5)">
                Status is a dot, not a pill. Eleven coloured pills in a column is a fruit salad;
                eleven dots in a fixed position is a stripe you can scan without reading.
                Shape carries the meaning too — filled is settled, open is not started,
                half is in progress — so the system never depends on colour alone.
            </p>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="type">
            <h2 class="tc-t-heading">Type scale</h2>
            <p class="tc-t-base">
                Inter, self-hosted. Eight steps, 13px body. Tracking tightens as size grows;
                untracked large text is the clearest tell of an undesigned panel.
            </p>
            <div>
                @foreach ([
                    ['hero', '32 / 36', 'Hero', 'NGN 370,009.40'],
                    ['display', '24 / 28', 'Display', '121 bookings'],
                    ['heading', '18 / 24', 'Heading', 'Flight Bookings'],
                    ['title', '15 / 20', 'Title', 'Operations overview'],
                    ['base', '14 / 22', 'Base', 'Operational queue for payment verification and ticketing.'],
                    ['body', '13 / 20', 'Body — default', 'Paid, not ticketed · ready for ticketing review'],
                    ['small', '12 / 16', 'Small', 'Service charge NGN 30,000.00 · 1 adult'],
                    ['micro', '11 / 16', 'Micro', 'Last seen 3 minutes ago'],
                ] as [$class, $metrics, $name, $sample])
                    <div class="sp-spec">
                        <span class="tc-t-micro tc-mono">{{ $metrics }}</span>
                        <span class="tc-t-micro" style="flex:none;width:110px">{{ $name }}</span>
                        <span class="tc-t-{{ $class }}">{{ $sample }}</span>
                    </div>
                @endforeach
                <div class="sp-spec">
                    <span class="tc-t-micro tc-mono">11 / 16</span>
                    <span class="tc-t-micro" style="flex:none;width:110px">Label</span>
                    <span class="tc-t-label">Queue · Booking · Journey</span>
                </div>
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="numerals">
            <h2 class="tc-t-heading">Numerals &amp; identifiers</h2>
            <p class="tc-t-base">
                JetBrains Mono carries anything read aloud down a phone line or retyped into
                another system. It removes the 0/O and 1/l/I ambiguity that costs a support call,
                and tabular figures make a money column align on the decimal.
            </p>
            <div class="sp-row" style="align-items:flex-start;gap:var(--tc-space-10)">
                <div>
                    <p class="tc-t-label" style="margin-bottom:var(--tc-space-3)">Proportional — drifts</p>
                    <div class="sp-stack" style="gap:var(--tc-space-1)">
                        @foreach ($bookings->take(5) as $b)
                            <span class="tc-t-body">&#8358;{{ $b['amount'] }}</span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="tc-t-label" style="margin-bottom:var(--tc-space-3)">Tabular mono — aligns</p>
                    <div class="sp-stack" style="gap:var(--tc-space-1)">
                        @foreach ($bookings->take(5) as $b)
                            <span class="tc-money tc-t-body">&#8358;{{ $b['amount'] }}</span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="tc-t-label" style="margin-bottom:var(--tc-space-3)">Identifiers</p>
                    <div class="sp-stack" style="gap:var(--tc-space-1)">
                        <span class="tc-mono tc-t-body">{{ $bookings->first()['ref'] }}</span>
                        <span class="tc-mono tc-t-body">KL7X2MQ</span>
                        <span class="tc-mono tc-t-body">074-2411583967</span>
                        <span class="tc-mono tc-t-body">0O1lI · 0O1lI</span>
                    </div>
                </div>
            </div>
            <p class="sp-note tc-t-small" style="margin-top:var(--tc-space-5)">
                Both faces ship latin <em>and</em> latin-ext. The naira sign &#8358; is U+20A6, which
                lives in latin-ext — ship latin alone and every price falls back to a system font
                mid-string. The arrow &rarr; (U+2192) is in no Inter subset at all, which is why
                route connectors above are drawn rather than typed.
            </p>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="colour">
            <h2 class="tc-t-heading">Colour roles</h2>
            <p class="tc-t-base">
                Components reference roles, never palette values. Dark mode remaps the roles;
                no component needs a dark variant. Toggle the theme to see every swatch move.
            </p>

            <p class="tc-t-label" style="margin:var(--tc-space-5) 0 var(--tc-space-3)">Surfaces</p>
            <div class="sp-grid">
                @foreach (['bg-canvas','bg-surface','bg-raised','bg-sunken','bg-rail','bg-hover','bg-selected','bg-input'] as $role)
                    <div class="sp-swatch">
                        <i style="background:var(--tc-{{ $role }})"></i>
                        <div><span class="tc-t-small tc-mono">--tc-{{ $role }}</span></div>
                    </div>
                @endforeach
            </div>

            <p class="tc-t-label" style="margin:var(--tc-space-6) 0 var(--tc-space-3)">Text &amp; border</p>
            <div class="sp-grid">
                @foreach (['text-primary','text-secondary','text-tertiary','text-disabled','text-brand','border-subtle','border-default','border-strong'] as $role)
                    <div class="sp-swatch">
                        <i style="background:var(--tc-{{ $role }})"></i>
                        <div><span class="tc-t-small tc-mono">--tc-{{ $role }}</span></div>
                    </div>
                @endforeach
            </div>

            <p class="tc-t-label" style="margin:var(--tc-space-6) 0 var(--tc-space-3)">Brand ramp · 600 is #303191</p>
            <div class="sp-row" style="gap:0">
                @foreach ([50,100,200,300,400,500,600,700,800,900,950] as $step)
                    <div style="flex:1;text-align:center">
                        <i style="display:block;height:44px;background:var(--tc-brand-{{ $step }})"></i>
                        <span class="tc-t-micro tc-mono">{{ $step }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="status">
            <h2 class="tc-t-heading">Status</h2>
            <p class="tc-t-base">
                Five meanings, named for what they mean rather than what colour they are, so
                "blocked" can stop being red without touching a component.
            </p>
            <div class="sp-row" style="gap:var(--tc-space-7)">
                <span class="tc-status tc-status-critical">Ticketing failed</span>
                <span class="tc-status tc-status-warning">Awaiting transfer</span>
                <span class="tc-status tc-status-positive">Ticketed</span>
                <span class="tc-status tc-status-info">Ready to ticket</span>
                <span class="tc-status tc-status-idle">Pending payment</span>
            </div>
            <div class="sp-row" style="gap:var(--tc-space-7);margin-top:var(--tc-space-5)">
                <span class="tc-status tc-status-positive">Settled</span>
                <span class="tc-status tc-status-info tc-status-progress">In progress</span>
                <span class="tc-status tc-status-idle tc-status-pending">Not started</span>
            </div>
            <div class="sp-row" style="gap:var(--tc-space-3);margin-top:var(--tc-space-6)">
                <span class="tc-tag">Oneway</span>
                <span class="tc-tag">WebFare</span>
                <span class="tc-tag tc-tag-count">121</span>
                <span class="tc-tag tc-tag-critical">4 failed</span>
                <span class="tc-tag tc-tag-warning">9 awaiting</span>
                <span class="tc-tag tc-tag-positive">48 ticketed</span>
                <span class="tc-tag tc-tag-info">12 ready</span>
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="controls">
            <h2 class="tc-t-heading">Controls</h2>
            <p class="tc-t-base">
                30px default height, which is what lets an action sit inside a 40px table row.
                No shadows — buttons sit on the surface rather than floating above it.
            </p>
            <div class="sp-row">
                <button class="tc-btn tc-btn-primary">Verify payment</button>
                <button class="tc-btn">Issue ticket</button>
                <button class="tc-btn tc-btn-danger">Mark failed</button>
                <button class="tc-btn tc-btn-quiet">Cancel</button>
                <button class="tc-btn" disabled>Unavailable</button>
                <button class="tc-btn tc-btn-sm">Small</button>
                <button class="tc-btn tc-btn-primary tc-btn-lg">Large</button>
            </div>
            <div class="sp-row" style="margin-top:var(--tc-space-6);align-items:flex-start">
                <label class="tc-field" style="width:240px">
                    <span class="tc-field-label">Booking reference</span>
                    <input class="tc-input tc-mono" value="{{ $bookings->first()['ref'] }}">
                </label>
                <label class="tc-field" style="width:240px">
                    <span class="tc-field-label">Amount received</span>
                    <input class="tc-input tc-money" value="925023.49">
                    <span class="tc-field-hint">Naira, as credited</span>
                </label>
                <label class="tc-field" style="width:240px">
                    <span class="tc-field-label">Payment reference</span>
                    <input class="tc-input" aria-invalid="true" value="">
                    <span class="tc-field-hint" style="color:var(--tc-status-critical)">Required before verifying</span>
                </label>
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="surfaces">
            <h2 class="tc-t-heading">Surfaces</h2>
            <p class="tc-t-base">
                Separated by hairline borders, not shadows. Shadows are reserved for things that
                genuinely float — popovers and modals — which is why there are only two.
            </p>
            <div class="sp-row" style="align-items:stretch">
                <div class="tc-surface" style="width:300px">
                    <div class="tc-surface-header">
                        <span class="tc-t-title">Payment</span>
                        <span class="tc-status tc-status-positive tc-t-small">Paid</span>
                    </div>
                    <div class="tc-surface-body sp-stack">
                        <div class="sp-row" style="justify-content:space-between">
                            <span class="tc-t-small">Charged</span>
                            <span class="tc-money tc-t-body">&#8358;925,023.49</span>
                        </div>
                        <div class="sp-row" style="justify-content:space-between">
                            <span class="tc-t-small">Service charge</span>
                            <span class="tc-money tc-t-body">&#8358;30,000.00</span>
                        </div>
                    </div>
                </div>
                <div class="tc-surface" style="width:300px;box-shadow:var(--tc-shadow-popover)">
                    <div class="tc-surface-body"><span class="tc-t-body">Popover elevation</span></div>
                </div>
                <div class="tc-surface" style="width:300px;box-shadow:var(--tc-shadow-modal)">
                    <div class="tc-surface-body"><span class="tc-t-body">Modal elevation</span></div>
                </div>
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="rail">
            <h2 class="tc-t-heading">Navigation rail</h2>
            <p class="tc-t-base">
                56px, shown at real size. Icons stand in for the final set; the point here is the
                width, the active marker and the contrast of the rail against the canvas.
            </p>
            <div class="sp-row" style="align-items:flex-start">
                <div class="sp-rail">
                    <b class="is-active">&#9635;</b>
                    <b>&#9703;</b>
                    <b>&#9707;</b>
                    <b>&#9704;</b>
                    <b>&#9636;</b>
                    <b>&#9637;</b>
                    <b>&#9639;</b>
                </div>
                <p class="sp-note tc-t-small" style="max-width:52ch">
                    The accent green marks the current section on the rail edge. It is the only
                    place the accent appears as a solid — everywhere else green means "positive
                    status", and a colour that means two things means neither.
                </p>
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="scale">
            <h2 class="tc-t-heading">Space &amp; radius</h2>
            <p class="tc-t-base">4px base. Radii are tighter than most admin themes; a console should read as precise.</p>
            <div class="sp-row" style="align-items:flex-end">
                @foreach ([1,2,3,4,5,6,7,8,9,10,12] as $step)
                    <div style="text-align:center">
                        <i style="display:block;width:var(--tc-space-{{ $step }});height:var(--tc-space-{{ $step }});background:var(--tc-brand-500)"></i>
                        <span class="tc-t-micro tc-mono">{{ $step }}</span>
                    </div>
                @endforeach
            </div>
            <div class="sp-row" style="margin-top:var(--tc-space-6)">
                @foreach (['xs','sm','md','lg','xl','full'] as $r)
                    <div style="text-align:center">
                        <i style="display:block;width:56px;height:40px;border:1px solid var(--tc-border-default);border-radius:var(--tc-radius-{{ $r }});background:var(--tc-bg-sunken)"></i>
                        <span class="tc-t-micro tc-mono">{{ $r }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ───────────────────────────────────────────────────────────── --}}
        <section class="sp-section" id="motion">
            <h2 class="tc-t-heading">Motion</h2>
            <p class="tc-t-base">
                Four durations, all short. Nothing in an ops tool should make someone wait for a
                transition to finish. Hover the blocks; they honour prefers-reduced-motion.
            </p>
            <div class="sp-row">
                @foreach (['instant' => '60ms', 'fast' => '100ms', 'base' => '150ms', 'slow' => '240ms'] as $name => $ms)
                    <div style="text-align:center">
                        <i style="display:block;width:96px;height:44px;border-radius:var(--tc-radius-sm);background:var(--tc-bg-sunken);
                                  transition:background-color var(--tc-duration-{{ $name }}) var(--tc-ease-standard)"
                           onmouseover="this.style.background='var(--tc-brand-500)'"
                           onmouseout="this.style.background='var(--tc-bg-sunken)'"></i>
                        <span class="tc-t-micro tc-mono">{{ $name }} · {{ $ms }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</div>
@endsection
