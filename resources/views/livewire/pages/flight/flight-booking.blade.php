<div>{{-- Single Livewire root element — wire:click requires this --}}
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --navy:     #0a1940;
    --blue:     #1d4ed8;
    --blue-lt:  #eff6ff;
    --blue-md:  #bfdbfe;
    --green:    #059669;
    --green-lt: #f0fdf4;
    --amber:    #d97706;
    --amber-lt: #fff7ed;
    --red:      #dc2626;
    --red-lt:   #fef2f2;
    --gray-50:  #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-700: #334155;
    --gray-900: #0f172a;
    --radius:   10px;
    --shadow:   0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --font:     'Plus Jakarta Sans', sans-serif;
    --mono:     'DM Mono', monospace;
}
body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; line-height: 1.5; margin-top:120px;}

/* ── Page layout ── */
.bk-wrap { max-width: 1160px; margin: 0 auto; padding: 24px 16px 64px; }
.bk-page { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
.bk-main { display: flex; flex-direction: column; gap: 18px; }
.bk-rail { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px; }

/* ── Breadcrumb ── */
.bk-crumb { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--gray-400); margin-bottom: 16px; flex-wrap: wrap; }
.bk-crumb a { color: var(--blue); text-decoration: none; font-weight: 600; }
.bk-crumb a:hover { text-decoration: underline; }
.bk-crumb-sep { color: var(--gray-300); }

/* ── Notices ── */
.bk-notice { padding: 12px 15px; border-radius: 9px; font-size: 12.5px; display: flex; align-items: flex-start; gap: 9px; margin-bottom: 14px; }
.bk-notice svg { flex-shrink: 0; margin-top: 1px; }
.bk-notice.info     { background: var(--blue-lt);  color: var(--blue);  border: 1px solid var(--blue-md); }
.bk-notice.warn     { background: var(--amber-lt); color: var(--amber); border: 1px solid #fed7aa; }
.bk-notice.danger   { background: var(--red-lt);   color: var(--red);   border: 1px solid #fca5a5; }
.bk-notice.stub     { background: #fef9c3; color: #713f12; border: 1px solid #fde68a; }
.bk-notice.security { background: var(--green-lt); color: var(--green); border: 1px solid #a7f3d0; }

/* ── Stepper ── */
.bk-steps { display: flex; align-items: center; background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 16px 22px; box-shadow: var(--shadow); }
.bk-step  { display: flex; align-items: center; gap: 10px; }
.bk-step-dot { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0; transition: all .25s; }
.bk-step-dot.done    { background: var(--green); color: #fff; }
.bk-step-dot.active  { background: var(--blue);  color: #fff; box-shadow: 0 0 0 4px var(--blue-md); }
.bk-step-dot.pending { background: var(--gray-100); color: var(--gray-400); border: 2px solid var(--gray-200); }
.bk-step-label { font-size: 13px; font-weight: 700; color: var(--gray-500); }
.bk-step-label.active { color: var(--gray-900); }
.bk-step-sub   { font-size: 11px; color: var(--gray-400); margin-top: 1px; }
.bk-connector  { flex: 1; height: 2px; background: var(--gray-200); margin: 0 16px; border-radius: 2px; }
.bk-connector.done { background: var(--green); }

/* ── Panel ── */
.bk-panel { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.bk-panel-head  { padding: 16px 22px 13px; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; gap: 12px; }
.bk-panel-icon  { width: 36px; height: 36px; border-radius: 9px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.bk-panel-title { font-size: 15px; font-weight: 800; color: var(--gray-900); }
.bk-panel-sub   { font-size: 12px; color: var(--gray-400); margin-top: 2px; }
.bk-panel-body  { padding: 20px 22px; }

/* ── Passenger counter ── */
.bk-pax-counter { display: flex; border: 1.5px solid var(--gray-200); border-radius: 10px; overflow: hidden; }
.bk-pax-col     { flex: 1; padding: 14px 16px; border-right: 1px solid var(--gray-100); }
.bk-pax-col:last-child { border-right: none; }
.bk-pax-col-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
.bk-pax-col-sub   { font-size: 10.5px; color: var(--gray-400); margin-top: 1px; }
.bk-pax-ctr       { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
.bk-pax-btn {
    width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid var(--gray-200);
    background: #fff; font-size: 18px; line-height: 1; color: var(--gray-700);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all .15s; padding: 0; font-family: var(--font);
}
.bk-pax-btn:hover:not([disabled]) { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
.bk-pax-btn[disabled] { opacity: .3; cursor: not-allowed; }
.bk-pax-num { font-size: 20px; font-weight: 800; color: var(--gray-900); min-width: 24px; text-align: center; }
.bk-total-bar { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--blue-lt); border-radius: 8px; margin-top: 14px; }
.bk-total-label { font-size: 13px; font-weight: 700; color: var(--blue); }
.bk-total-val   { font-size: 15px; font-weight: 800; color: var(--blue); font-family: var(--mono); }

/* ── Passenger card ── */
.bk-pax-card { border: 1.5px solid var(--gray-200); border-radius: var(--radius); overflow: hidden; margin-bottom: 14px; }
.bk-pax-card:last-child { margin-bottom: 0; }
.bk-pax-card:focus-within { border-color: var(--blue-md); box-shadow: 0 0 0 3px rgba(59,130,246,.08); }
.bk-pax-head { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); }
.bk-pax-badge { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.bk-pax-badge.adt { background: var(--blue-lt); color: var(--blue); }
.bk-pax-badge.chd { background: var(--amber-lt); color: var(--amber); }
.bk-pax-badge.inf { background: var(--green-lt); color: var(--green); }
.bk-pax-num-lbl { font-size: 13px; font-weight: 700; color: var(--gray-700); }
.bk-pax-lead    { margin-left: auto; font-size: 11px; color: var(--blue); font-weight: 600; background: var(--blue-lt); padding: 2px 9px; border-radius: 999px; }

/* ── Form grid inside passenger card ── */
.bk-form-grid { display: grid; grid-template-columns: 90px 1fr 1fr; gap: 12px; padding: 16px; }
.bk-col-2 { grid-column: span 2; }
.bk-col-full { grid-column: 1 / -1; }
.bk-field { display: flex; flex-direction: column; gap: 5px; }
.bk-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
.bk-req   { color: var(--red); margin-left: 2px; }
.bk-input,
.bk-select {
    height: 44px; padding: 0 12px; border: 1.5px solid var(--gray-200); border-radius: 9px;
    font-size: 14px; color: var(--gray-900); background: var(--gray-50); outline: none;
    font-family: var(--font); transition: border-color .15s, box-shadow .15s, background .15s; width: 100%;
}
.bk-input:focus,
.bk-select:focus { border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.bk-select {
    appearance: none; cursor: pointer; padding-right: 30px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 11px center; background-color: var(--gray-50);
}
.bk-error { font-size: 11px; color: var(--red); margin-top: 2px; }
.bk-hint  { font-size: 11px; color: var(--gray-400); margin-top: 2px; }

/* ── Passport accordion ── */
.bk-pp-toggle {
    display: flex; align-items: center; gap: 9px; cursor: pointer; width: 100%;
    padding: 11px 16px; background: var(--gray-50); border-top: 1px solid var(--gray-100);
    border-bottom: none; border-left: none; border-right: none;
    transition: background .15s; user-select: none; font-family: var(--font); text-align: left;
}
.bk-pp-toggle:hover { background: var(--blue-lt); }
.bk-pp-icon    { width: 22px; height: 22px; border-radius: 6px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.bk-pp-label   { font-size: 13px; font-weight: 600; color: var(--gray-700); flex: 1; }
.bk-pp-added   { font-size: 11px; color: var(--green); font-weight: 600; margin-left: 6px; }
.bk-pp-chevron { color: var(--gray-400); transition: transform .25s; flex-shrink: 0; }
.bk-pp-chevron.open { transform: rotate(180deg); }
.bk-pp-body    { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 14px 16px; background: #fff; border-top: 1px solid var(--gray-100); }

/* ── Contact grid ── */
.bk-contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.bk-contact-grid .bk-full { grid-column: 1 / -1; }

/* ── Review ── */
.bk-review-section { margin-bottom: 20px; }
.bk-review-section:last-child { margin-bottom: 0; }
.bk-review-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); padding-bottom: 8px; border-bottom: 1px solid var(--gray-100); margin-bottom: 10px; }
.bk-review-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--gray-100); gap: 16px; font-size: 13px; }
.bk-review-row:last-child { border-bottom: none; }
.bk-review-label { color: var(--gray-500); font-weight: 500; flex-shrink: 0; }
.bk-review-val   { color: var(--gray-900); font-weight: 700; text-align: right; }

/* ── Action buttons ── */
.bk-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.bk-btn-ghost {
    padding: 0 22px; height: 46px; background: #fff; border: 1.5px solid var(--gray-200);
    border-radius: 10px; font-size: 14px; font-weight: 700; color: var(--gray-700);
    cursor: pointer; font-family: var(--font); transition: all .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
}
.bk-btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
.bk-btn-next {
    padding: 0 30px; height: 48px; background: linear-gradient(135deg,#1d4ed8,#2563eb);
    color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 800;
    cursor: pointer; font-family: var(--font); box-shadow: 0 4px 16px rgba(29,78,216,.3);
    transition: all .2s; display: inline-flex; align-items: center; gap: 8px;
}
.bk-btn-next:hover { background: linear-gradient(135deg,#1e40af,#1d4ed8); transform: translateY(-1px); }
.bk-btn-next[disabled] { opacity: .6; cursor: not-allowed; transform: none; }
.bk-btn-pay {
    padding: 0 30px; height: 52px; background: linear-gradient(135deg,#059669,#10b981);
    color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 800;
    cursor: pointer; font-family: var(--font); box-shadow: 0 4px 16px rgba(5,150,105,.3);
    transition: all .2s; display: inline-flex; align-items: center; gap: 9px;
}
.bk-btn-pay:hover { background: linear-gradient(135deg,#047857,#059669); transform: translateY(-1px); }

/* ── Rail: flight card ── */
.bk-fc { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.bk-fc-head  { background: var(--navy); padding: 16px 18px; }
.bk-fc-route { font-size: 18px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px; }
.bk-fc-tags  { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.bk-fc-tag   { background: rgba(255,255,255,.12); border-radius: 999px; padding: 3px 10px; font-size: 11px; font-weight: 600; color: rgba(255,255,255,.8); }
.bk-fc-tag.success { background: rgba(5,150,105,.3);  color: #6ee7b7; }
.bk-fc-tag.danger  { background: rgba(220,38,38,.3);  color: #fca5a5; }
.bk-validating-bar { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); font-size: 12px; color: var(--gray-500); }
.bk-validating-bar img { width: 20px; height: 20px; border-radius: 4px; object-fit: contain; background: #fff; }

/* ── Segment timeline ── */
.bk-timeline   { padding: 16px 18px; }
.bk-tl-section { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.bk-tl-section::after { content: ''; flex: 1; height: 1px; background: var(--gray-100); }
.bk-tl-divider { height: 1px; background: var(--gray-100); margin: 12px 0; }
.bk-tl-seg     { display: flex; gap: 10px; }
.bk-tl-spine   { display: flex; flex-direction: column; align-items: center; width: 32px; flex-shrink: 0; }
.bk-tl-dot     { width: 10px; height: 10px; border-radius: 50%; background: var(--blue); flex-shrink: 0; margin-top: 5px; }
.bk-tl-dot.last{ background: var(--navy); }
.bk-tl-line    { flex: 1; width: 2px; background: var(--gray-200); margin: 3px 0; min-height: 16px; }
.bk-tl-logo    { width: 28px; height: 28px; border-radius: 6px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; margin-top: 1px; }
.bk-tl-logo img  { width: 100%; height: 100%; object-fit: contain; }
.bk-tl-logo span { font-size: 8px; font-weight: 800; color: var(--gray-500); }
.bk-tl-body    { flex: 1; padding-bottom: 12px; }
.bk-tl-times   { font-size: 15px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); }
.bk-tl-cities  { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
.bk-tl-airport { font-size: 11px; color: var(--gray-400); }
.bk-tl-country { font-size: 10.5px; color: var(--gray-400); }
.bk-layover    { display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: var(--amber-lt); border: 1px solid #fed7aa; border-radius: 8px; margin: 4px 0 8px 42px; font-size: 11.5px; color: var(--amber); font-weight: 600; }

/* ── Chips ── */
.bk-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
.bk-chip  { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 600; }
.bk-chip.blue  { background: var(--blue-lt);  color: var(--blue); }
.bk-chip.gray  { background: var(--gray-100); color: var(--gray-500); }
.bk-chip.green { background: var(--green-lt); color: var(--green); }
.bk-chip.amber { background: var(--amber-lt); color: var(--amber); }
.bk-chip.red   { background: var(--red-lt);   color: var(--red); }
.bk-chip.navy  { background: #e8edf7; color: var(--navy); }
.bk-codeshare  { display: flex; align-items: center; gap: 6px; padding: 5px 8px; background: var(--amber-lt); border: 1px solid #fed7aa; border-radius: 7px; margin-top: 6px; font-size: 11px; color: var(--amber); font-weight: 600; }
.bk-codeshare img { width: 18px; height: 18px; border-radius: 4px; object-fit: contain; background: #fff; }

/* ── Fare breakdown card ── */
.bk-fare-card  { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.bk-fare-head  { padding: 13px 18px; border-bottom: 1px solid var(--gray-100); font-size: 13px; font-weight: 800; color: var(--gray-900); display: flex; align-items: center; justify-content: space-between; }
.bk-fare-curr  { font-size: 11px; color: var(--gray-400); font-weight: 500; }
.bk-fare-body  { padding: 14px 18px; }
.bk-fare-pax-lbl { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); display: block; padding-top: 10px; margin-top: 10px; border-top: 1px solid var(--gray-100); }
.bk-fare-pax-lbl:first-child { padding-top: 0; margin-top: 0; border-top: none; }
.bk-fare-row   { display: flex; align-items: center; justify-content: space-between; padding: 4px 0; font-size: 12.5px; }
.bk-fare-row.subtotal { border-top: 1px solid var(--gray-100); padding-top: 6px; margin-top: 2px; font-weight: 700; }
.bk-fare-lbl   { color: var(--gray-500); }
.bk-fare-lbl.bold { font-weight: 700; color: var(--gray-700); }
.bk-fare-val   { font-family: var(--mono); font-size: 12px; color: var(--gray-900); }
.bk-tax-btn    { font-size: 11px; color: var(--blue); cursor: pointer; font-weight: 600; margin-left: 5px; }
.bk-tax-btn:hover { text-decoration: underline; }
.bk-tax-detail { padding-left: 10px; border-left: 2px solid var(--blue-md); margin: 4px 0 6px; }
.bk-tax-row    { display: flex; align-items: center; justify-content: space-between; padding: 2px 0; font-size: 11.5px; }
.bk-tax-lbl    { color: var(--gray-500); }
.bk-tax-code   { font-size: 10px; opacity: .5; margin-left: 4px; }
.bk-tax-val    { font-family: var(--mono); font-size: 11px; color: var(--gray-700); }
.bk-fare-total { display: flex; align-items: center; justify-content: space-between; padding: 13px 18px; background: var(--blue-lt); border-top: 2px solid var(--blue-md); }
.bk-fare-total-label { font-size: 13px; font-weight: 800; color: var(--navy); }
.bk-fare-total-val   { font-size: 22px; font-weight: 800; color: var(--blue); font-family: var(--mono); }

/* ── Fare rules card ── */
.bk-rules-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
.bk-rules-head { padding: 13px 18px; border-bottom: 1px solid var(--gray-100); font-size: 13px; font-weight: 800; color: var(--gray-900); }
.bk-rules-body { padding: 4px 18px 12px; }
.bk-rule-pax   { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); padding: 10px 0 4px; }
.bk-rule-row   { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--gray-100); font-size: 12.5px; }
.bk-rule-row:last-child { border-bottom: none; }
.bk-rule-icon  { font-size: 15px; flex-shrink: 0; width: 22px; text-align: center; }
.bk-rule-lbl   { color: var(--gray-500); flex: 1; }
.bk-rule-val   { font-weight: 700; color: var(--gray-900); text-align: right; }
.bk-rule-val.good { color: var(--green); }
.bk-rule-val.bad  { color: var(--red); }

/* ── Responsive ── */
@media (max-width: 900px) {
    .bk-page { grid-template-columns: 1fr; }
    .bk-rail { position: static; }
}
@media (max-width: 580px) {
    .bk-wrap  { padding: 12px 10px 48px; }
    .bk-form-grid { grid-template-columns: 1fr 1fr; }
    .bk-form-grid .bk-col-2,
    .bk-form-grid .bk-col-full { grid-column: 1 / -1; }
    .bk-contact-grid { grid-template-columns: 1fr; }
    .bk-pp-body { grid-template-columns: 1fr; }
    .bk-pax-counter { flex-wrap: wrap; }
    .bk-pax-col { min-width: 50%; }
    .bk-connector { display: none; }
    .bk-btn-next, .bk-btn-pay { flex: 1; justify-content: center; }
    .bk-actions { flex-wrap: wrap; }
}
</style>

@php
    // ── Always read flight fresh from session — never from a Livewire public
    //    property, which gets truncated on re-renders due to payload size.
    $flight       = session('bookingFlight', []);
    $sessionId    = session('bookingSessionId', '');
    $searchParams = session('bookingSearchParams', []);

    // ── Cabin label ───────────────────────────────────────────────────────────
    $cabinMap = ['Y' => 'Economy', 'S' => 'Premium Economy', 'C' => 'Business', 'F' => 'First Class'];
    $cabin    = $cabinMap[$searchParams['flight_type'] ?? 'Y'] ?? 'Economy';

    // ── Currency formatter ────────────────────────────────────────────────────
    $currency = $flight['currency'] ?? 'NGN';
    $sym      = $currency === 'NGN' ? '₦' : ($currency === 'USD' ? '$' : $currency . ' ');
    $fmt      = fn($v) => $sym . number_format((float) $v, 2);

    // ── Flight slices ─────────────────────────────────────────────────────────
    $segments  = $flight['segments']       ?? [];
    $retSegs   = $flight['returnSegments'] ?? [];
    $multiLegs = $flight['multiLegs']      ?? [];
    $breakdown = $flight['fareBreakdown']  ?? [];
    $isReturn  = count($retSegs) > 0;
    $isMulti   = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round Trip' : ($isMulti ? 'Multi-city' : 'One Way');
    $firstSeg  = $segments[0] ?? [];
    $lastSeg   = count($segments) > 0 ? $segments[count($segments) - 1] : [];
    $stopCount = $flight['stops'] ?? 0;

    // ── Tax label map ─────────────────────────────────────────────────────────
    $taxLabels = [
        'QT'  => 'Airport Tax',              'TE5' => 'Ticket Levy',
        'W3'  => 'Security Surcharge',       'W32' => 'Security Surcharge',
        'MA'  => 'Miscellaneous Fee',        'MAC' => 'Miscellaneous Fee',
        'NG3' => 'Nigeria Passenger Levy',   'YQF' => 'Fuel Surcharge',
        'YQI' => 'Fuel Surcharge',           'YRI' => 'Carrier Surcharge',
        'YRF' => 'Carrier Surcharge',        'GB'  => 'Air Passenger Duty',
        'UB'  => 'Passenger Service Charge', 'DE'  => 'Departure Tax',
        'BE'  => 'Booking Fee',              'RA2' => 'Regulatory Fee',
        'W42' => 'Passenger Facility Charge','CH'  => 'Customs Fee',
        'CJ'  => 'Customs Fee',              'EQ'  => 'Equipment Fee',
        'G4'  => 'Government Tax',           'L3'  => 'Landing Fee',
        'O2'  => 'Airport Operations Fee',   'O9'  => 'Other Charge',
        'QA'  => 'Passenger Service Fee',    'QX'  => 'Airport Development Fee',
        'R9'  => 'Revenue Tax',              'RN'  => 'Regional Tax',
        'S2'  => 'Safety Charge',            'S4'  => 'Security Fee',
        'S42' => 'Security Fee',             'TR'  => 'Ticket Tax',
        'ZR2' => 'Zone Charge',              'OtherTaxes' => 'Taxes & Fees',
    ];

    // ── Equipment → aircraft name ─────────────────────────────────────────────
    $equipMap = [
        '73H' => 'Boeing 737-800',   '738' => 'Boeing 737-800',
        '7M8' => 'Boeing 737 MAX 8', '7M9' => 'Boeing 737 MAX 9',
        '789' => 'Boeing 787-9',     '788' => 'Boeing 787-8',
        '77W' => 'Boeing 777-300ER', '773' => 'Boeing 777-300',
        '772' => 'Boeing 777-200',   '320' => 'Airbus A320',
        '321' => 'Airbus A321',      '319' => 'Airbus A319',
        '332' => 'Airbus A330-200',  '333' => 'Airbus A330-300',
        '359' => 'Airbus A350-900',  '388' => 'Airbus A380-800',
        '223' => 'Airbus A220-300',  'E90' => 'Embraer E190',
        'E75' => 'Embraer E175',     '772' => 'Boeing 777-200',
    ];

    // ── Meal code → label ─────────────────────────────────────────────────────
    $mealMap = [
        'M' => 'Meal',    'B' => 'Breakfast', 'L' => 'Lunch',
        'D' => 'Dinner',  'S' => 'Snack',     'V' => 'Vegetarian',
        'K' => 'Kosher',  'H' => 'Halal',     'R' => 'Refreshment',
    ];
@endphp
<div class="bk-wrap"
     x-data="{
         init() {
             window.addEventListener('scrollTop', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
         },
         submitForm() {
             document.getElementById('bk-form').submit();
         }
     }">

    {{-- ── Breadcrumb ── --}}
    <div class="bk-crumb">
        <a href="{{ route('home') }}">Home</a>
        <span class="bk-crumb-sep">›</span>
        <a href="{{ route('air.flight-s') }}">Flight Results</a>
        <span class="bk-crumb-sep">›</span>
        <span>Complete Booking</span>
    </div>


    {{-- ── Global notices ── --}}
    @if(session('stub_notice'))
        <div class="bk-notice stub">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>⚠️ Dev stub: {{ session('stub_notice') }}</span>
        </div>
    @endif

    @if(!empty($flight['isPassportMandatory']))
        <div class="bk-notice danger">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            <span><strong>Passport required.</strong> All passengers must carry a valid passport. Ensure details match exactly and the document is valid beyond travel dates.</span>
        </div>
    @endif

    @if(!empty($flight['ticketAdvisory']))
        <div class="bk-notice warn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ $flight['ticketAdvisory'] }}</span>
        </div>
    @endif

    <div class="bk-page">

        {{-- ══════════════ MAIN COLUMN ══════════════ --}}
        <div class="bk-main">

            {{-- ── Stepper — driven purely by PHP $step, no Alpine ── --}}
            <div class="bk-steps">
                <div class="bk-step">
                    <div class="bk-step-dot {{ $step > 1 ? 'done' : 'active' }}">
                        @if($step > 1)
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        @else
                            <span>1</span>
                        @endif
                    </div>
                    <div>
                        <div class="bk-step-label {{ $step === 1 ? 'active' : '' }}">Passenger Details</div>
                        <div class="bk-step-sub">Names, DOB, documents</div>
                    </div>
                </div>
                <div class="bk-connector {{ $step > 1 ? 'done' : '' }}"></div>
                <div class="bk-step">
                    <div class="bk-step-dot {{ $step >= 2 ? 'active' : 'pending' }}">2</div>
                    <div>
                        <div class="bk-step-label {{ $step === 2 ? 'active' : '' }}">Review &amp; Pay</div>
                        <div class="bk-step-sub">Confirm and complete</div>
                    </div>
                </div>
            </div>

            {{-- ══ STEP 1 ══ --}}
            @if($step === 1)

                {{-- ── Passenger count controls ── --}}
                <div class="bk-panel">
                    <div class="bk-panel-head">
                        <div class="bk-panel-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <div class="bk-panel-title">Passengers</div>
                            <div class="bk-panel-sub">Adjust count · maximum 9 total</div>
                        </div>
                    </div>
                    <div class="bk-panel-body">
                        <div class="bk-pax-counter">

                            {{-- Adults --}}
                            <div class="bk-pax-col">
                                <div class="bk-pax-col-label">Adults</div>
                                <div class="bk-pax-col-sub">12+ years</div>
                                <div class="bk-pax-ctr">
                                    <button type="button" class="bk-pax-btn"
                                            wire:click="decrementPassenger('ADT')"
                                            @if($this->adultCount <= 1) disabled @endif>−</button>
                                    <span class="bk-pax-num">{{ $this->adultCount }}</span>
                                    <button type="button" class="bk-pax-btn"
                                            wire:click="incrementPassenger('ADT')"
                                            @if($this->getTotalPassengers() >= 9) disabled @endif>+</button>
                                </div>
                            </div>

                            {{-- Children --}}
                            <div class="bk-pax-col">
                                <div class="bk-pax-col-label">Children</div>
                                <div class="bk-pax-col-sub">2–11 years</div>
                                <div class="bk-pax-ctr">
                                    <button type="button" class="bk-pax-btn"
                                            wire:click="decrementPassenger('CHD')"
                                            @if($this->childCount <= 0) disabled @endif>−</button>
                                    <span class="bk-pax-num">{{ $this->childCount }}</span>
                                    <button type="button" class="bk-pax-btn"
                                            wire:click="incrementPassenger('CHD')"
                                            @if($this->getTotalPassengers() >= 9) disabled @endif>+</button>
                                </div>
                            </div>

                            {{-- Infants --}}
                            <div class="bk-pax-col">
                                <div class="bk-pax-col-label">Infants</div>
                                <div class="bk-pax-col-sub">Under 2</div>
                                <div class="bk-pax-ctr">
                                    <button type="button" class="bk-pax-btn"
                                            wire:click="decrementPassenger('INF')"
                                            @if($this->infantCount <= 0) disabled @endif>−</button>
                                    <span class="bk-pax-num">{{ $this->infantCount }}</span>
                                    <button type="button" class="bk-pax-btn"
                                            wire:click="incrementPassenger('INF')"
                                            @if($this->getTotalPassengers() >= 9) disabled @endif>+</button>
                                </div>
                            </div>

                        </div>
                        <div class="bk-total-bar">
                            <span class="bk-total-label">✈ Total passengers</span>
                            <span class="bk-total-val">
                                {{ $this->getTotalPassengers() }} passenger{{ $this->getTotalPassengers() > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ── Passenger detail forms ── --}}
                <div class="bk-panel">
                    <div class="bk-panel-head">
                        <div class="bk-panel-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        </div>
                        <div>
                            <div class="bk-panel-title">Passenger Details</div>
                            <div class="bk-panel-sub">Names must match passport or government-issued ID exactly</div>
                        </div>
                    </div>
                    <div class="bk-panel-body">

                        @foreach($this->passengers as $i => $pax)
                            @php
                                $typeLabel  = match($pax['type']) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                                $badgeClass = strtolower($pax['type']);
                                $showPp     = !empty($pax['show_passport']);
                                $hasPpData  = !empty($pax['passport_no']);
                            @endphp

                            {{-- FIX: wire:key required on looped elements so Livewire tracks DOM nodes correctly --}}
                            <div class="bk-pax-card" wire:key="pax-{{ $i }}-{{ $pax['type'] }}">

                                <div class="bk-pax-head">
                                    <span class="bk-pax-badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                                    <span class="bk-pax-num-lbl">Passenger {{ $i + 1 }}</span>
                                    @if($pax['is_primary'])
                                        <span class="bk-pax-lead">★ Lead passenger</span>
                                    @endif
                                </div>

                                <div class="bk-form-grid">

                                    <div class="bk-field">
                                        <label class="bk-label">Title <span class="bk-req">*</span></label>
                                        <select class="bk-select" wire:model="passengers.{{ $i }}.title">
                                            <option value="">–</option>
                                            @foreach(['Mr','Mrs','Ms','Miss','Dr'] as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                        @error("passengers.{$i}.title")
                                            <span class="bk-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="bk-field">
                                        <label class="bk-label">First Name <span class="bk-req">*</span></label>
                                        <input class="bk-input" type="text" placeholder="As on ID/passport"
                                               wire:model.blur="passengers.{{ $i }}.first_name">
                                        @error("passengers.{$i}.first_name")
                                            <span class="bk-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="bk-field">
                                        <label class="bk-label">Last Name <span class="bk-req">*</span></label>
                                        <input class="bk-input" type="text" placeholder="As on ID/passport"
                                               wire:model.blur="passengers.{{ $i }}.last_name">
                                        @error("passengers.{$i}.last_name")
                                            <span class="bk-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="bk-field bk-col-2">
                                        <label class="bk-label">Date of Birth <span class="bk-req">*</span></label>
                                        <input class="bk-input" type="date"
                                               wire:model.blur="passengers.{{ $i }}.dob"
                                               max="{{ now()->subDay()->format('Y-m-d') }}">
                                        @if($pax['type'] === 'CHD')
                                            <span class="bk-hint">Must be 2–11 years old at time of travel</span>
                                        @elseif($pax['type'] === 'INF')
                                            <span class="bk-hint">Must be under 2 years at time of travel</span>
                                        @endif
                                        @error("passengers.{$i}.dob")
                                            <span class="bk-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="bk-field">
                                        <label class="bk-label">Nationality <span class="bk-req">*</span></label>
                                        <select class="bk-select" wire:model="passengers.{{ $i }}.nationality">
                                            @foreach($this->nationalities as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error("passengers.{$i}.nationality")
                                            <span class="bk-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>{{-- /bk-form-grid --}}

                                {{--
                                    PASSPORT ACCORDION
                                    FIX: wire:click calls togglePassport($i) which reassigns the full
                                    $this->passengers array — the only way to trigger Livewire reactivity
                                    on a nested array key. @if($showPp) reads the re-rendered PHP value.
                                --}}
                                <button type="button" class="bk-pp-toggle" wire:click="togglePassport({{ $i }})">
                                    <div class="bk-pp-icon">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                                    </div>
                                    <span class="bk-pp-label">
                                        Passport / Travel Document
                                        @if($hasPpData)
                                            <span class="bk-pp-added">✓ Added</span>
                                        @endif
                                    </span>
                                    <svg class="bk-pp-chevron {{ $showPp ? 'open' : '' }}"
                                         width="14" height="14" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2.5">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>

                                @if($showPp)
                                    <div class="bk-pp-body">
                                        <div class="bk-field">
                                            <label class="bk-label">Passport Number</label>
                                            <input class="bk-input" type="text" placeholder="e.g. A12345678"
                                                   wire:model.blur="passengers.{{ $i }}.passport_no">
                                            @error("passengers.{$i}.passport_no")
                                                <span class="bk-error">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="bk-field">
                                            <label class="bk-label">Expiry Date</label>
                                            <input class="bk-input" type="date"
                                                   wire:model.blur="passengers.{{ $i }}.passport_exp"
                                                   min="{{ now()->addDay()->format('Y-m-d') }}">
                                            <span class="bk-hint">Must be valid beyond travel dates</span>
                                            @error("passengers.{$i}.passport_exp")
                                                <span class="bk-error">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                            </div>{{-- /bk-pax-card --}}
                        @endforeach

                    </div>
                </div>

                {{-- ── Contact details ── --}}
                <div class="bk-panel">
                    <div class="bk-panel-head">
                        <div class="bk-panel-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <div class="bk-panel-title">Contact Details</div>
                            <div class="bk-panel-sub">Confirmation and e-ticket will be sent here</div>
                        </div>
                    </div>
                    <div class="bk-panel-body">
                        {{-- FIX: phone uses class bk-full which is defined on .bk-contact-grid --}}
                        <div class="bk-contact-grid">
                            <div class="bk-field">
                                <label class="bk-label">Email Address <span class="bk-req">*</span></label>
                                <input class="bk-input" type="email" placeholder="you@example.com"
                                       wire:model.blur="contactEmail">
                                @error('contactEmail')
                                    <span class="bk-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="bk-field">
                                <label class="bk-label">Confirm Email <span class="bk-req">*</span></label>
                                <input class="bk-input" type="email" placeholder="Re-enter email"
                                       wire:model.blur="contactEmailConfirm">
                                @error('contactEmailConfirm')
                                    <span class="bk-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="bk-field bk-full">
                                <label class="bk-label">Phone Number <span class="bk-req">*</span></label>
                                <input class="bk-input" type="tel" placeholder="+234 800 000 0000"
                                       wire:model.blur="contactPhone">
                                <span class="bk-hint">Include country code · e.g. +234 for Nigeria</span>
                                @error('contactPhone')
                                    <span class="bk-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bk-actions">
                    <a href="{{ route('air.flight-s') }}" class="bk-btn-ghost">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back to results
                    </a>
                    <button class="bk-btn-next"
                            wire:click="proceed"
                            wire:loading.attr="disabled"
                            wire:target="proceed">
                       <span 
                            wire:loading.remove 
                            wire:target="proceed" 
                            style="color:white; display:inline-flex; align-items:center; gap:6px;"
                        >
                            Review Booking
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </span>
                        <span wire:loading wire:target="proceed">Validating…</span>
                    </button>
                </div>

            @endif {{-- /step 1 --}}

            {{-- ══ STEP 2 ══ --}}
            @if($step === 2)

                <div class="bk-notice info">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Review all details carefully before payment. Name corrections after ticketing may incur fees or may not be possible.</span>
                </div>

                <div class="bk-panel">
                    <div class="bk-panel-head">
                        <div class="bk-panel-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div>
                            <div class="bk-panel-title">Booking Summary</div>
                            <div class="bk-panel-sub">Confirm everything is correct before paying</div>
                        </div>
                    </div>
                    <div class="bk-panel-body">

                        {{-- Passengers --}}
                        <div class="bk-review-section">
                            <div class="bk-review-title">Passengers ({{ $this->getTotalPassengers() }})</div>
                            @foreach($this->passengers as $i => $pax)
                                @php
                                    $ptLabel = match($pax['type']) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' };
                                    $dobStr  = !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—';
                                    $natName = $this->nationalities[$pax['nationality']] ?? $pax['nationality'];
                                @endphp
                                <div class="bk-review-row">
                                    <span class="bk-review-label">{{ $ptLabel }} {{ $i + 1 }}{{ $pax['is_primary'] ? ' ★' : '' }}</span>
                                    <span class="bk-review-val">
                                        {{ $pax['title'] }} {{ strtoupper($pax['first_name']) }} {{ strtoupper($pax['last_name']) }}
                                        <br>
                                        <span style="font-size:11px;color:var(--gray-500);font-weight:500;">
                                            DOB: {{ $dobStr }} · {{ $natName }}
                                            @if(!empty($pax['passport_no'])) · Passport: {{ $pax['passport_no'] }} @endif
                                        </span>
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Contact --}}
                        <div class="bk-review-section">
                            <div class="bk-review-title">Contact</div>
                            <div class="bk-review-row">
                                <span class="bk-review-label">Email</span>
                                <span class="bk-review-val">{{ $contactEmail }}</span>
                            </div>
                            <div class="bk-review-row">
                                <span class="bk-review-label">Phone</span>
                                <span class="bk-review-val">{{ $contactPhone }}</span>
                            </div>
                        </div>

                        {{-- Fare policy summary --}}
                        <div class="bk-review-section" style="margin-bottom:0">
                            <div class="bk-review-title">Fare Policy</div>
                            @foreach($breakdown as $fb)
                                @php
                                    $ptl     = match($fb['passengerType'] ?? '') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                                    $bagStr  = implode(' / ', array_unique(array_filter((array) ($fb['baggage'] ?? []), fn($v) => $v !== ''))) ?: '—';
                                    $refund  = !empty($fb['refundAllowed']);
                                    $change  = !empty($fb['changeAllowed']);
                                    $chgFee  = $fmt($fb['changePenalty'] ?? 0);
                                @endphp
                                <div class="bk-review-row">
                                    <span class="bk-review-label">{{ $ptl }} · Checked Baggage</span>
                                    <span class="bk-review-val">{{ $bagStr }}</span>
                                </div>
                                <div class="bk-review-row">
                                    <span class="bk-review-label">{{ $ptl }} · Refund</span>
                                    <span class="bk-review-val" style="color:{{ $refund ? 'var(--green)' : 'var(--red)' }}">
                                        {{ $refund ? '✓ Allowed' : '✗ Not allowed' }}
                                    </span>
                                </div>
                                <div class="bk-review-row">
                                    <span class="bk-review-label">{{ $ptl }} · Changes</span>
                                    <span class="bk-review-val">
                                        @if($change)
                                            <span style="color:var(--green)">✓ Allowed</span> · Fee {{ $chgFee }}
                                        @else
                                            <span style="color:var(--red)">✗ Not allowed</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>

                {{-- Hidden submission form --}}
                <form id="bk-form" method="POST" action="{{ route('flights.book') }}" style="display:none;">
                    @csrf
                    <input type="hidden" name="fare_source_code" value="{{ $flight['fareSourceCode'] ?? '' }}">
                    <input type="hidden" name="session_id"       value="{{ $sessionId }}">
                    <input type="hidden" name="contact[email]"   value="{{ $contactEmail }}">
                    <input type="hidden" name="contact[phone]"   value="{{ $contactPhone }}">
                    @foreach($this->passengers as $i => $pax)
                        @foreach(['type','title','first_name','last_name','dob','nationality','passport_no','passport_exp'] as $field)
                            <input type="hidden" name="passengers[{{ $i }}][{{ $field }}]" value="{{ $pax[$field] ?? '' }}">
                        @endforeach
                    @endforeach
                </form>

                <div class="bk-notice warn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Payment gateway will be connected here. <strong>Confirm &amp; Pay</strong> currently submits to a test endpoint.</span>
                </div>

                <div class="bk-actions">
                    <button class="bk-btn-ghost" wire:click="back">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Edit Details
                    </button>
                    <button class="bk-btn-pay" @click="submitForm()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Confirm &amp; Pay {{ $fmt($this->getTotalPrice()) }}
                    </button>
                </div>

            @endif {{-- /step 2 --}}

        </div>{{-- /bk-main --}}


        {{-- ══════════════ RIGHT RAIL ══════════════ --}}
        <aside class="bk-rail">

            {{-- ── Flight summary card ── --}}
            <div class="bk-fc">
                <div class="bk-fc-head">
                    <div class="bk-fc-route">
                        <span style="color:whitesmoke;">{{ $firstSeg['from'] ?? '—' }}</span>
                        @if($isReturn)
                            <svg color="whitesmoke" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                        @else
                            <svg color="whitesmoke" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        @endif
                        <span style="color:whitesmoke;">{{ $lastSeg['to'] ?? '—' }}</span>
                    </div>
                    <div class="bk-fc-tags">
                        <span class="bk-fc-tag">{{ $tripLabel }}</span>
                        <span class="bk-fc-tag">{{ $cabin }}</span>
                        @if(!empty($flight['departDateLabel']))
                            <span class="bk-fc-tag">{{ $flight['departDateLabel'] }}</span>
                        @endif
                        <span class="bk-fc-tag">{{ $stopCount === 0 ? 'Non-stop' : $stopCount . ' stop' . ($stopCount > 1 ? 's' : '') }}</span>
                        @if(!empty($flight['ticketType']))
                            <span class="bk-fc-tag success">{{ $flight['ticketType'] }}</span>
                        @endif
                        @if(!empty($flight['isPassportMandatory']))
                            <span class="bk-fc-tag danger">Passport required</span>
                        @endif
                    </div>
                </div>

                {{-- Validating airline bar (shown when different from marketing carrier) --}}
                @if(!empty($flight['validatingCode']) && $flight['validatingCode'] !== ($firstSeg['airlineCode'] ?? ''))
                    <div class="bk-validating-bar">
                        <img src="{{ $flight['validatingLogo'] }}" alt="{{ $flight['validatingAirline'] ?? '' }}">
                        <span>Ticketed by <strong>{{ $flight['validatingAirline'] ?? $flight['validatingCode'] }}</strong></span>
                    </div>
                @endif

                {{-- Segment timeline --}}
                <div class="bk-timeline">

                    {{-- ── OUTBOUND / single leg ── --}}
                    <div class="bk-tl-section">
                        @if($isReturn) Outbound @elseif($isMulti) Leg 1 @else Flight @endif
                        · {{ $flight['durationLabel'] ?? '' }}
                    </div>

                    @foreach($segments as $si => $seg)
                        @php
                            $isLastSeg = $si === count($segments) - 1;
                            $needsLine = !$isLastSeg || $isReturn || $isMulti;
                            $equip     = $seg['equipment'] ?? '';
                            $equipLbl  = $equipMap[$equip] ?? $equip;
                            $mealCode  = $seg['mealCode'] ?? '';
                            $mealLbl   = $mealMap[$mealCode] ?? '';
                            $seats     = (int) ($seg['seatsLeft'] ?? 9);
                        @endphp

                        @if($si > 0 && !empty($flight['layoverDurations'][$si - 1]))
                            <div class="bk-layover">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Layover in {{ $segments[$si - 1]['toCity'] ?? $segments[$si - 1]['to'] }}
                                · {{ $flight['layoverDurations'][$si - 1] }}
                            </div>
                        @endif

                        <div class="bk-tl-seg">
                            <div class="bk-tl-spine">
                                <div class="bk-tl-dot {{ $isLastSeg && !$isReturn && !$isMulti ? 'last' : '' }}"></div>
                                @if($needsLine) <div class="bk-tl-line"></div> @endif
                            </div>
                            <div class="bk-tl-logo">
                                @if(!empty($seg['airlineLogo']))
                                    <img src="{{ $seg['airlineLogo'] }}" alt="{{ $seg['airline'] ?? '' }}">
                                @else
                                    <span>{{ $seg['airlineCode'] ?? '' }}</span>
                                @endif
                            </div>
                            <div class="bk-tl-body">
                                <div class="bk-tl-times">{{ $seg['departTime'] }} → {{ $seg['arriveTime'] }}</div>
                                <div class="bk-tl-cities">{{ $seg['fromCity'] ?? $seg['from'] }} → {{ $seg['toCity'] ?? $seg['to'] }}</div>
                                <div class="bk-tl-airport">{{ $seg['fromAirport'] ?? '' }}</div>
                                @if(!empty($seg['fromCountry']))
                                    <div class="bk-tl-country">{{ $seg['fromCountry'] }}</div>
                                @endif
                                <div class="bk-chips">
                                    <span class="bk-chip blue">{{ $seg['flightNo'] ?? '' }}</span>
                                    <span class="bk-chip gray">{{ floor($seg['duration'] / 60) }}h {{ $seg['duration'] % 60 }}m</span>
                                    @if($equipLbl)
                                        <span class="bk-chip gray">{{ $equipLbl }}</span>
                                    @endif
                                    @if(!empty($seg['cabin']))
                                        <span class="bk-chip navy">{{ $seg['cabin'] }}</span>
                                    @endif
                                    @if(!empty($seg['resBookCode']))
                                        <span class="bk-chip gray">Class {{ $seg['resBookCode'] }}</span>
                                    @endif
                                    @if($mealLbl)
                                        <span class="bk-chip green">🍽 {{ $mealLbl }}</span>
                                    @endif
                                    @if($seats <= 4)
                                        <span class="bk-chip red">⚠ {{ $seats }} seats left</span>
                                    @elseif($seats < 9)
                                        <span class="bk-chip green">{{ $seats }} seats</span>
                                    @endif
                                    @if(!empty($seg['eticket']))
                                        <span class="bk-chip green">e-Ticket</span>
                                    @endif
                                </div>
                                @if(!empty($seg['isCodeshare']))
                                    <div class="bk-codeshare">
                                        @if(!empty($seg['operatingLogo']))
                                            <img src="{{ $seg['operatingLogo'] }}" alt="{{ $seg['operatingAirline'] ?? '' }}">
                                        @endif
                                        Operated by {{ $seg['operatingAirline'] ?? '' }} ({{ $seg['operatingFlightNo'] ?? '' }})
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- ── RETURN inbound ── --}}
                    @if($isReturn && count($retSegs) > 0)
                        <div class="bk-tl-divider"></div>
                        <div class="bk-tl-section">
                            Return · {{ $flight['returnDurationLabel'] ?? '' }}
                            @if(!empty($flight['returnDateLabel'])) · {{ $flight['returnDateLabel'] }} @endif
                        </div>

                        @foreach($retSegs as $si => $seg)
                            @php
                                $isLastSeg = $si === count($retSegs) - 1;
                                $needsLine = !$isLastSeg;
                                $equip     = $seg['equipment'] ?? '';
                                $equipLbl  = $equipMap[$equip] ?? $equip;
                                $seats     = (int) ($seg['seatsLeft'] ?? 9);
                            @endphp

                            @if($si > 0 && !empty($flight['returnLayoverDurations'][$si - 1]))
                                <div class="bk-layover">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Layover in {{ $retSegs[$si - 1]['toCity'] ?? $retSegs[$si - 1]['to'] }}
                                    · {{ $flight['returnLayoverDurations'][$si - 1] }}
                                </div>
                            @endif

                            <div class="bk-tl-seg">
                                <div class="bk-tl-spine">
                                    <div class="bk-tl-dot {{ $isLastSeg ? 'last' : '' }}"></div>
                                    @if($needsLine) <div class="bk-tl-line"></div> @endif
                                </div>
                                <div class="bk-tl-logo">
                                    @if(!empty($seg['airlineLogo']))
                                        <img src="{{ $seg['airlineLogo'] }}" alt="">
                                    @else
                                        <span>{{ $seg['airlineCode'] ?? '' }}</span>
                                    @endif
                                </div>
                                <div class="bk-tl-body">
                                    <div class="bk-tl-times">{{ $seg['departTime'] }} → {{ $seg['arriveTime'] }}</div>
                                    <div class="bk-tl-cities">{{ $seg['fromCity'] ?? $seg['from'] }} → {{ $seg['toCity'] ?? $seg['to'] }}</div>
                                    <div class="bk-tl-airport">{{ $seg['fromAirport'] ?? '' }}</div>
                                    <div class="bk-chips">
                                        <span class="bk-chip blue">{{ $seg['flightNo'] ?? '' }}</span>
                                        <span class="bk-chip gray">{{ floor($seg['duration'] / 60) }}h {{ $seg['duration'] % 60 }}m</span>
                                        @if($equipLbl) <span class="bk-chip gray">{{ $equipLbl }}</span> @endif
                                        @if(!empty($seg['cabin'])) <span class="bk-chip navy">{{ $seg['cabin'] }}</span> @endif
                                        @if($seats <= 4)
                                            <span class="bk-chip red">⚠ {{ $seats }} seats left</span>
                                        @elseif($seats < 9)
                                            <span class="bk-chip green">{{ $seats }} seats</span>
                                        @endif
                                    </div>
                                    @if(!empty($seg['isCodeshare']))
                                        <div class="bk-codeshare">
                                            @if(!empty($seg['operatingLogo'])) <img src="{{ $seg['operatingLogo'] }}" alt=""> @endif
                                            Operated by {{ $seg['operatingAirline'] ?? '' }} ({{ $seg['operatingFlightNo'] ?? '' }})
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- ── MULTI-CITY extra legs ── --}}
                    @if($isMulti)
                        @foreach($multiLegs as $li => $leg)
                            @php $legSegs = $leg['segments'] ?? []; @endphp
                            <div class="bk-tl-divider"></div>
                            <div class="bk-tl-section">
                                Leg {{ $li + 2 }} · {{ $leg['durationLabel'] ?? '' }}
                                @if(!empty($leg['departDateLabel'])) · {{ $leg['departDateLabel'] }} @endif
                            </div>

                            @foreach($legSegs as $si => $seg)
                                @php
                                    $isLastSeg = $si === count($legSegs) - 1;
                                    $isLastLeg = $li === count($multiLegs) - 1;
                                    $needsLine = !($isLastSeg && $isLastLeg);
                                    $equip     = $seg['equipment'] ?? '';
                                    $equipLbl  = $equipMap[$equip] ?? $equip;
                                    $seats     = (int) ($seg['seatsLeft'] ?? 9);
                                @endphp

                                @if($si > 0 && !empty($leg['layoverDurations'][$si - 1]))
                                    <div class="bk-layover">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        Layover · {{ $leg['layoverDurations'][$si - 1] }}
                                    </div>
                                @endif

                                <div class="bk-tl-seg">
                                    <div class="bk-tl-spine">
                                        <div class="bk-tl-dot {{ $isLastSeg && $isLastLeg ? 'last' : '' }}"></div>
                                        @if($needsLine) <div class="bk-tl-line"></div> @endif
                                    </div>
                                    <div class="bk-tl-logo">
                                        @if(!empty($seg['airlineLogo']))
                                            <img src="{{ $seg['airlineLogo'] }}" alt="">
                                        @else
                                            <span>{{ $seg['airlineCode'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="bk-tl-body">
                                        <div class="bk-tl-times">{{ $seg['departTime'] }} → {{ $seg['arriveTime'] }}</div>
                                        <div class="bk-tl-cities">{{ $seg['fromCity'] ?? $seg['from'] }} → {{ $seg['toCity'] ?? $seg['to'] }}</div>
                                        <div class="bk-tl-airport">{{ $seg['fromAirport'] ?? '' }}</div>
                                        <div class="bk-chips">
                                            <span class="bk-chip blue">{{ $seg['flightNo'] ?? '' }}</span>
                                            <span class="bk-chip gray">{{ floor($seg['duration'] / 60) }}h {{ $seg['duration'] % 60 }}m</span>
                                            @if($equipLbl) <span class="bk-chip gray">{{ $equipLbl }}</span> @endif
                                            @if(!empty($seg['cabin'])) <span class="bk-chip navy">{{ $seg['cabin'] }}</span> @endif
                                            @if($seats <= 4)
                                                <span class="bk-chip red">⚠ {{ $seats }} seats left</span>
                                            @elseif($seats < 9)
                                                <span class="bk-chip green">{{ $seats }} seats</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @endif

                </div>{{-- /bk-timeline --}}
            </div>{{-- /bk-fc --}}

            {{-- ── Fare breakdown card ── --}}
            <div class="bk-fare-card">
                <div class="bk-fare-head">
                    Price Breakdown
                    <span class="bk-fare-curr">{{ $currency }}</span>
                </div>
                <div class="bk-fare-body">
                    @php $computed = $this->getComputedFare(); @endphp

                    @foreach($computed['rows'] as $row)
                        @php
                            $fbQty   = $row['qty'];
                            $fbBase  = $row['perBase'];
                            $fbTax   = $row['perTax'];
                            $fbTotal = $row['perTotal'];
                            $fbTaxes = $row['taxes'];
                        @endphp

                        <span class="bk-fare-pax-lbl">{{ $row['label'] }} × {{ $fbQty }}</span>

                        <div class="bk-fare-row">
                            <span class="bk-fare-lbl">
                                Base fare
                                <span style="color:var(--gray-400);font-size:11px;">({{ $fmt($fbBase) }} × {{ $fbQty }})</span>
                            </span>
                            <span class="bk-fare-val">{{ $fmt($row['subtotalBase']) }}</span>
                        </div>

                        <div x-data="{ showTaxes: false }">
                            <div class="bk-fare-row">
                                <span class="bk-fare-lbl">
                                    Taxes &amp; fees
                                    <span style="color:var(--gray-400);font-size:11px;">({{ $fmt($fbTax) }} × {{ $fbQty }})</span>
                                    <span class="bk-tax-btn"
                                          @click="showTaxes = !showTaxes"
                                          x-text="showTaxes ? '▲ hide' : '▼ detail'"></span>
                                </span>
                                <span class="bk-fare-val">{{ $fmt($row['subtotalTax']) }}</span>
                            </div>
                            <div x-show="showTaxes" x-transition class="bk-tax-detail">
                                @forelse($fbTaxes as $tax)
                                    @php
                                        $tCode = $tax['TaxCode'] ?? '';
                                        $tAmt  = (float) ($tax['Amount'] ?? 0);
                                    @endphp
                                    <div class="bk-tax-row">
                                        <span class="bk-tax-lbl">
                                            {{ $taxLabels[$tCode] ?? $tCode }}
                                            <span class="bk-tax-code">({{ $tCode }})</span>
                                        </span>
                                        <span class="bk-tax-val">{{ $fmt($tAmt * $fbQty) }}</span>
                                    </div>
                                @empty
                                    <div class="bk-tax-row">
                                        <span class="bk-tax-lbl">Taxes &amp; levies</span>
                                        <span class="bk-tax-val">{{ $fmt($row['subtotalTax']) }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="bk-fare-row subtotal">
                            <span class="bk-fare-lbl bold">Subtotal</span>
                            <span class="bk-fare-val">{{ $fmt($row['subtotal']) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bk-fare-total">
                    <span class="bk-fare-total-label">Total to Pay</span>
                    <span class="bk-fare-total-val">{{ $fmt($computed['total']) }}</span>
                </div>
            </div>

            {{-- ── Fare rules & baggage card ── --}}
            <div class="bk-rules-card">
                <div class="bk-rules-head">Fare Rules &amp; Baggage</div>
                <div class="bk-rules-body">
                    @foreach($breakdown as $fb)
                        @php
                            $rPtl    = match($fb['passengerType'] ?? '') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' };
                            $rBag    = implode(' / ', array_unique(array_filter((array) ($fb['baggage'] ?? []), fn($v) => $v !== ''))) ?: '—';
                            $rCabin  = implode(' / ', array_unique(array_filter((array) ($fb['cabinBaggage'] ?? []), fn($v) => $v !== ''))) ?: '—';
                            $rRefund = !empty($fb['refundAllowed']);
                            $rChange = !empty($fb['changeAllowed']);
                            $rChgFee = $fmt($fb['changePenalty'] ?? 0);
                            $rRfdFee = (float) ($fb['refundPenalty'] ?? 0);
                        @endphp
                        @if(count($breakdown) > 1)
                            <div class="bk-rule-pax">{{ $rPtl }}</div>
                        @endif
                        <div class="bk-rule-row">
                            <span class="bk-rule-icon">🧳</span>
                            <span class="bk-rule-lbl">Checked baggage</span>
                            <span class="bk-rule-val">{{ $rBag }}</span>
                        </div>
                        <div class="bk-rule-row">
                            <span class="bk-rule-icon">💼</span>
                            <span class="bk-rule-lbl">Cabin baggage</span>
                            <span class="bk-rule-val">{{ $rCabin }}</span>
                        </div>
                        <div class="bk-rule-row">
                            <span class="bk-rule-icon">{{ $rRefund ? '✅' : '❌' }}</span>
                            <span class="bk-rule-lbl">Refundable</span>
                            <span class="bk-rule-val {{ $rRefund ? 'good' : 'bad' }}">
                                @if($rRefund)
                                    Yes{{ $rRfdFee > 0 ? ' · Penalty ' . $fmt($rRfdFee) : '' }}
                                @else
                                    No
                                @endif
                            </span>
                        </div>
                        <div class="bk-rule-row">
                            <span class="bk-rule-icon">{{ $rChange ? '✅' : '❌' }}</span>
                            <span class="bk-rule-lbl">Date / route changes</span>
                            <span class="bk-rule-val {{ $rChange ? 'good' : 'bad' }}">
                                @if($rChange) Yes · Fee {{ $rChgFee }} @else No @endif
                            </span>
                        </div>
                        <div class="bk-rule-row">
                            <span class="bk-rule-icon">🎫</span>
                            <span class="bk-rule-lbl">Ticket type</span>
                            <span class="bk-rule-val good">{{ $flight['ticketType'] ?? 'eTicket' }}</span>
                        </div>
                        <div class="bk-rule-row">
                            <span class="bk-rule-icon">📋</span>
                            <span class="bk-rule-lbl">Fare type</span>
                            <span class="bk-rule-val">{{ $flight['fareType'] ?? 'Public' }}</span>
                        </div>
                        @if(!empty($flight['directionInd']))
                            <div class="bk-rule-row">
                                <span class="bk-rule-icon">🗺</span>
                                <span class="bk-rule-lbl">Journey type</span>
                                <span class="bk-rule-val">{{ $flight['directionInd'] }}</span>
                            </div>
                        @endif
                        @if(!empty($flight['validatingCode']) && $flight['validatingCode'] !== ($firstSeg['airlineCode'] ?? ''))
                            <div class="bk-rule-row">
                                <span class="bk-rule-icon">✈</span>
                                <span class="bk-rule-lbl">Ticketing carrier</span>
                                <span class="bk-rule-val">{{ $flight['validatingAirline'] ?? $flight['validatingCode'] }}</span>
                            </div>
                        @endif
                        @if(!empty($flight['isPassportMandatory']))
                            <div class="bk-rule-row">
                                <span class="bk-rule-icon">🛂</span>
                                <span class="bk-rule-lbl">Passport required</span>
                                <span class="bk-rule-val bad">Yes — mandatory</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

     
        </aside>

    </div>{{-- /bk-page --}}
</div>{{-- /bk-wrap --}}
</div>{{-- /single Livewire root --}}