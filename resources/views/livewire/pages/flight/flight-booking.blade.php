
<div>{{-- Single Livewire root element --}}
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
        --badgeOut: #267bdc;
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
        --shadow-md:0 4px 16px rgba(0,0,0,.10);
        --font:     'Plus Jakarta Sans', sans-serif;
        --mono:     'DM Mono', monospace;
    }
    body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; line-height: 1.5; margin-top: 120px; }

    /* ── Layout ── */
    .bk-wrap  { max-width: 1160px; margin: 0 auto; padding: 24px 16px 64px; }
    .bk-page  { display: grid; grid-template-columns: 1fr 340px; gap: 22px; align-items: start; }
    .bk-main  { display: flex; flex-direction: column; gap: 12px; }
    .bk-rail  { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 20px; }

    /* ── Breadcrumb ── */
    .bk-crumb { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--gray-400); margin-bottom: 18px; flex-wrap: wrap; }
    .bk-crumb a { color: var(--blue); text-decoration: none; font-weight: 600; }
    .bk-crumb a:hover { text-decoration: underline; }
    .bk-crumb-sep { color: var(--gray-300); }

    /* ── Stepper ── */
    .bk-steps { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 14px 20px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 0; margin-bottom: 4px; }
    .bk-step  { display: flex; align-items: center; gap: 9px; flex-shrink: 0; }
    .bk-step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
    .bk-step-dot.done    { background: var(--green); color: #fff; }
    .bk-step-dot.active  { background: var(--blue);  color: #fff; box-shadow: 0 0 0 4px var(--blue-md); }
    .bk-step-dot.pending { background: var(--gray-100); color: var(--gray-400); border: 2px solid var(--gray-200); }
    .bk-step-label { font-size: 12.5px; font-weight: 700; color: var(--gray-500); }
    .bk-step-label.active { color: var(--gray-900); }
    .bk-step-sub   { font-size: 10.5px; color: var(--gray-400); }
    .bk-connector  { flex: 1; height: 2px; background: var(--gray-200); margin: 0 12px; min-width: 20px; }
    .bk-connector.done { background: var(--green); }

    /* ── Accordion card ── */
    .bk-acc { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
    .bk-acc-head {
        display: flex; align-items: center; gap: 12px; padding: 15px 20px;
        cursor: pointer; user-select: none; transition: background .15s;
        border-bottom: 1px solid transparent;
    }
    .bk-acc-head:hover { background: var(--gray-50); }
    .bk-acc-head.open  { border-bottom-color: var(--gray-100); }
    .bk-acc-icon { width: 36px; height: 36px; border-radius: 9px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-acc-title { font-size: 14.5px; font-weight: 800; color: var(--gray-900); }
    .bk-acc-sub   { font-size: 11.5px; color: var(--gray-400); margin-top: 1px; }
    .bk-acc-chevron { margin-left: auto; color: var(--gray-400); transition: transform .25s; flex-shrink: 0; }
    .bk-acc-chevron.open { transform: rotate(180deg); }
    .bk-acc-body  { padding: 18px 20px; }

    /* ── Notices ── */
    .bk-notice { padding: 11px 14px; border-radius: 9px; font-size: 12.5px; display: flex; align-items: flex-start; gap: 9px; }
    .bk-notice svg { flex-shrink: 0; margin-top: 1px; }
    .bk-notice.info   { background: var(--blue-lt);  color: var(--blue);  border: 1px solid var(--blue-md); }
    .bk-notice.warn   { background: var(--amber-lt); color: var(--amber); border: 1px solid #fed7aa; }
    .bk-notice.danger { background: var(--red-lt);   color: var(--red);   border: 1px solid #fca5a5; }
    .bk-notice.green  { background: var(--green-lt); color: var(--green); border: 1px solid #a7f3d0; }

    /* ── Itinerary (Image 1 style) ── */
    .bk-itin-leg { margin-bottom: 0; }
    .bk-itin-leg + .bk-itin-leg { border-top: 1px solid var(--gray-100); margin-top: 0; }
    .bk-itin-leg-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 13px 20px 11px; background: var(--gray-50);
        cursor: pointer; user-select: none; transition: background .15s;
    }
    .bk-itin-leg-head:hover { background: #eef2f7; }
    .bk-itin-leg-route { font-size: 14px; font-weight: 800; color: var(--gray-900); display: flex; align-items: center; gap: 8px; }
    .bk-itin-leg-meta  { font-size: 11.5px; color: var(--gray-500); margin-top: 3px; display: flex; align-items: center; gap: 8px; }
    .bk-itin-leg-badge { padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; background: var(--amber-lt); color: var(--amber); }
    .bk-itin-leg-badge.direct { background: var(--green-lt); color: var(--green); }
    .bk-itin-leg-body  { padding: 0 20px 16px; }
    .bk-outbound-badge { padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; background: var(--amber-lt); color: var(--badgeOut); }


    /* Flight segment row */
    .bk-seg-group { margin-top: 12px; }
    .bk-seg-airline-bar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 7px 0 8px; font-size: 11.5px; color: var(--gray-500);
        border-bottom: 1px solid var(--gray-100); margin-bottom: 10px;
    }
    .bk-seg-airline-left { display: flex; align-items: center; gap: 8px; }
    .bk-seg-airline-logo { width: 22px; height: 22px; border-radius: 5px; object-fit: contain; background: var(--gray-100); }
    .bk-seg-airline-name { font-weight: 700; color: var(--gray-700); }
    .bk-seg-cabin-tag { font-size: 10.5px; color: var(--gray-400); }

    .bk-seg-timeline { display: flex; gap: 0; align-items: stretch; }
    .bk-seg-spine { display: flex; flex-direction: column; align-items: center; width: 20px; flex-shrink: 0; padding-top: 6px; }
    .bk-seg-dot   { width: 9px; height: 9px; border-radius: 50%; background: var(--blue); flex-shrink: 0; }
    .bk-seg-dot.end { background: var(--navy); }
    .bk-seg-line  { flex: 1; width: 2px; background: var(--gray-200); margin: 3px 0; min-height: 30px; }
    .bk-seg-stops { display: flex; flex-direction: column; flex: 1; gap: 0; }
    .bk-seg-stop  { display: grid; grid-template-columns: 70px 1fr 120px; align-items: start; padding: 4px 0 12px 12px; gap: 10px; }
    .bk-seg-stop:last-child { padding-bottom: 0; }
    .bk-seg-time  { font-size: 14px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); }
    .bk-seg-place { font-size: 12px; color: var(--gray-700); font-weight: 500; }
    .bk-seg-place-sub { font-size: 10.5px; color: var(--gray-400); margin-top: 1px; }
    .bk-seg-bags { display: flex; flex-direction: column; gap: 3px; font-size: 11px; }
    .bk-seg-bags-lbl { font-weight: 700; color: var(--gray-500); font-size: 10px; text-transform: uppercase; letter-spacing: .06em; }
    .bk-seg-bags-val { font-weight: 600; color: var(--gray-700); }

    .bk-layover-strip {
        display: flex; align-items: center; gap: 8px; margin: 6px 12px 8px;
        padding: 7px 12px; background: var(--amber-lt); border: 1px solid #fed7aa;
        border-radius: 8px; font-size: 11.5px; color: var(--amber); font-weight: 600;
    }

    /* ── Extra bags banner ── */
    .bk-bags-banner {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 18px; background: #fff;
        border: 1px solid var(--gray-200); border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .bk-bags-icon { font-size: 28px; flex-shrink: 0; }
    .bk-bags-text { flex: 1; }
    .bk-bags-title { font-size: 13.5px; font-weight: 700; color: var(--gray-900); }
    .bk-bags-sub   { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
    .bk-bags-btn { padding: 7px 18px; border: 1.5px solid var(--blue); border-radius: 8px; background: #fff; color: var(--blue); font-size: 13px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: all .15s; flex-shrink: 0; }
    .bk-bags-btn:hover { background: var(--blue-lt); }

    /* ── Passenger counter ── */
    .bk-pax-counter { display: flex; border: 1.5px solid var(--gray-200); border-radius: 10px; overflow: hidden; }
    .bk-pax-col     { flex: 1; padding: 14px 16px; border-right: 1px solid var(--gray-100); }
    .bk-pax-col:last-child { border-right: none; }
    .bk-pax-col-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
    .bk-pax-col-sub   { font-size: 10.5px; color: var(--gray-400); margin-top: 1px; }
    .bk-pax-ctr       { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .bk-pax-btn { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid var(--gray-200); background: #fff; font-size: 18px; line-height: 1; color: var(--gray-700); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; padding: 0; font-family: var(--font); }
    .bk-pax-btn:hover:not([disabled]) { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .bk-pax-btn[disabled] { opacity: .3; cursor: not-allowed; }
    .bk-pax-num { font-size: 20px; font-weight: 800; color: var(--gray-900); min-width: 24px; text-align: center; }
    .bk-total-bar { display: flex; align-items: center; justify-content: space-between; padding: 11px 16px; background: var(--blue-lt); border-radius: 8px; margin-top: 14px; }
    .bk-total-label { font-size: 13px; font-weight: 700; color: var(--blue); }
    .bk-total-val   { font-size: 15px; font-weight: 800; color: var(--blue); font-family: var(--mono); }

    .bk-pax-info { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .bk-pax-col-label { font-weight: 600; font-size: 13px; }
    .bk-pax-col-sub { font-size: 11px; color: #6b7280;}
    .bk-pax-ctr { display: flex; align-items: center;}

    /* ── Passenger card / form ── */
    .bk-pax-card { border: 1.5px solid var(--gray-200); border-radius: 10px; overflow: hidden; margin-bottom: 12px; }
    .bk-pax-card:last-child { margin-bottom: 0; }
    .bk-pax-card-head {
        display: flex; align-items: center; gap: 10px;
        padding: 11px 15px; background: var(--gray-50);
        border-bottom: 1px solid var(--gray-100);
        cursor: pointer; user-select: none;
    }
    .bk-pax-card-head:hover { background: #eef2f7; }
    .bk-pax-badge { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; flex-shrink: 0; }
    .bk-pax-badge.adt { background: var(--blue-lt);   color: var(--blue); }
    .bk-pax-badge.chd { background: var(--amber-lt);  color: var(--amber); }
    .bk-pax-badge.inf { background: var(--green-lt);  color: var(--green); }
    .bk-pax-num-lbl { font-size: 13px; font-weight: 700; color: var(--gray-700); flex: 1; }
    .bk-pax-progress { font-size: 11px; color: var(--gray-400); font-weight: 600; }
    .bk-pax-complete { font-size: 11px; color: var(--green); font-weight: 700; }
    .bk-primary-chip {
        display: inline-flex;
        align-items: center;
        min-height: 20px;
        margin-left: 6px;
        padding: 2px 7px;
        border-radius: 999px;
        background: #eef2ff;
        color: var(--blue);
        font-size: 10px;
        font-weight: 850;
    }

    /* Important notice in pax card */
    .bk-pax-notice { margin: 0; padding: 9px 15px; background: #fff8e6; border-bottom: 1px solid #fde68a; font-size: 11.5px; color: #92400e; display: flex; align-items: flex-start; gap: 7px; }

    /* Form grid */
    .bk-form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; padding: 16px 15px; }
    .bk-col-2    { grid-column: span 2; }
    .bk-col-full { grid-column: 1 / -3; }
    .bk-col-half { grid-column: span 1; }
    .bk-field { display: flex; flex-direction: column; gap: 5px; }
    .bk-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
    .bk-req   { color: var(--red); margin-left: 2px; }
    .bk-input, .bk-select {
        height: 44px; padding: 0 12px; border: 1.5px solid var(--gray-200); border-radius: 9px;
        font-size: 14px; color: var(--gray-900); background: var(--gray-50); outline: none;
        font-family: var(--font); transition: border-color .15s, box-shadow .15s; width: 100%;
    }
    .bk-input:focus, .bk-select:focus { border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
    .bk-select { appearance: none; cursor: pointer; padding-right: 30px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 11px center; background-color: var(--gray-50); }
    .bk-radio-group { display: flex; gap: 14px; align-items: center; height: 44px; }
    .bk-radio-opt   { display: flex; align-items: center; gap: 7px; cursor: pointer; font-size: 13.5px; font-weight: 600; color: var(--gray-700); }
    .bk-radio-opt input { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }
    .bk-error { font-size: 11px; color: var(--red); margin-top: 2px; }
    .bk-hint  { font-size: 11px; color: var(--gray-400); margin-top: 2px; }

    /* Passport accordion */
    .bk-pp-toggle { display: flex; align-items: center; gap: 9px; cursor: pointer; width: 100%; padding: 10px 15px; background: var(--gray-50); border-top: 1px solid var(--gray-100); border-bottom: none; border-left: none; border-right: none; transition: background .15s; user-select: none; font-family: var(--font); text-align: left; }
    .bk-pp-toggle:hover { background: var(--blue-lt); }
    .bk-pp-icon   { width: 22px; height: 22px; border-radius: 6px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-pp-label  { font-size: 13px; font-weight: 600; color: var(--gray-700); flex: 1; }
    .bk-pp-added  { font-size: 11px; color: var(--green); font-weight: 600; margin-left: 6px; }
    .bk-pp-chevron { color: var(--gray-400); transition: transform .25s; flex-shrink: 0; }
    .bk-pp-chevron.open { transform: rotate(180deg); }
    .bk-pp-body { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 14px 15px; background: #fff; border-top: 1px solid var(--gray-100); }

    /* ── Contact ── */
    .bk-contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .bk-contact-full { grid-column: 3 / 3; }

    /* ── Seat selection (Image 3 style) ── */
    .bk-seat-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--gray-100); }
    .bk-seat-row:last-child { border-bottom: none; }
    .bk-seat-leg  { display: flex; align-items: center; gap: 8px; flex: 1; }
    .bk-seat-leg-icon { width: 28px; height: 28px; border-radius: 6px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-seat-leg-info { flex: 1; }
    .bk-seat-leg-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
    .bk-seat-leg-route { font-size: 13px; font-weight: 700; color: var(--gray-900); }
    .bk-seat-chips { display: flex; gap: 6px; align-items: center; }
    .bk-seat-chip { display: flex; align-items: center; gap: 5px; padding: 5px 10px; border: 1.5px solid var(--gray-200); border-radius: 7px; font-size: 12px; font-weight: 600; color: var(--gray-500); background: #fff; cursor: pointer; transition: all .15s; }
    .bk-seat-chip:hover { border-color: var(--blue-md); color: var(--blue); background: var(--blue-lt); }
    .bk-seat-chip svg { opacity: .6; }
    .bk-seat-choose { padding: 6px 16px; border-radius: 7px; background: var(--blue); color: #fff; font-size: 12.5px; font-weight: 700; border: none; cursor: pointer; font-family: var(--font); transition: background .15s; }
    .bk-seat-choose:hover { background: #1e40af; }

    /* ── T&C bar ── */
    .bk-terms-bar { display: flex; align-items: center; gap: 10px; padding: 12px 0 0; font-size: 12.5px; color: var(--gray-500); }
    .bk-terms-bar a { color: var(--blue); font-weight: 600; }
    .bk-terms-bar input { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }

    /* ── Action buttons ── */
    .bk-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 4px; }
    .bk-btn-ghost { padding: 0 22px; height: 46px; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 10px; font-size: 13.5px; font-weight: 700; color: var(--gray-700); cursor: pointer; font-family: var(--font); transition: all .15s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .bk-btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
    .bk-btn-next { padding: 0 28px; height: 48px; background: #165ef9; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: var(--font); box-shadow: 0 4px 16px rgba(249,115,22,.3); transition: all .2s; display: inline-flex; align-items: center; gap: 8px; }
    .bk-btn-next:hover { background: #ea6c0a; transform: translateY(-1px); }
    .bk-btn-next[disabled] { opacity: .6; cursor: not-allowed; transform: none; }
    .bk-btn-pay { padding: 0 28px; height: 52px; background: #f97316; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 800; cursor: pointer; font-family: var(--font); box-shadow: 0 4px 16px rgba(249,115,22,.35); transition: all .2s; display: inline-flex; align-items: center; gap: 9px; }
    .bk-btn-pay:hover { background: #ea6c0a; transform: translateY(-1px); }

    /* ── RIGHT RAIL: My Cart ── */
    .bk-cart { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
    .bk-cart-head { background: var(--navy); padding: 14px 18px; }
    .bk-cart-title { font-size: 15px; font-weight: 800; color: #fff; }
    .bk-cart-body  { padding: 14px 18px; }
    .bk-cart-section { margin-bottom: 14px; }
    .bk-cart-section-lbl { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); margin-bottom: 8px; }
    .bk-cart-flight-row { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid var(--gray-100); }
    .bk-cart-flight-row:last-child { border-bottom: none; margin-bottom: 0; }
    .bk-cart-plane { color: var(--blue); flex-shrink: 0; margin-top: 2px; }
    .bk-cart-route { font-size: 12.5px; font-weight: 700; color: var(--gray-900); }
    .bk-cart-sub   { font-size: 11px; color: var(--gray-400); margin-top: 1px; }
    .bk-cart-divider { height: 1px; background: var(--gray-100); margin: 12px 0; }

    /* Fare summary */
    .bk-fare-section { padding: 12px 18px; border-top: 1px solid var(--gray-100); }
    .bk-fare-title   { font-size: 13px; font-weight: 800; color: var(--gray-900); margin-bottom: 10px; }
    .bk-fare-row  { display: flex; align-items: center; justify-content: space-between; padding: 4px 0; font-size: 12.5px; }
    .bk-fare-lbl  { color: var(--gray-500); }
    .bk-fare-val  { font-family: var(--mono); font-size: 12px; color: var(--gray-900); font-weight: 600; }
    .bk-fare-disc { color: var(--red); }
    .bk-fare-view { font-size: 11px; color: var(--blue); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px; }
    .bk-fare-view:hover { text-decoration: underline; }
    .bk-fare-total-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 2px solid var(--gray-200); }
    .bk-fare-total-lbl { font-size: 14px; font-weight: 800; color: var(--navy); }
    .bk-fare-total-val { font-size: 22px; font-weight: 800; color: var(--navy); font-family: var(--mono); }

    /* Promo code */
    .bk-promo { padding: 12px 18px; border-top: 1px solid var(--gray-100); }
    .bk-promo-title { font-size: 13px; font-weight: 800; color: var(--gray-900); margin-bottom: 10px; }
    .bk-promo-row { display: flex; gap: 8px; }
    .bk-promo-input { flex: 1; height: 38px; padding: 0 12px; border: 1.5px solid var(--gray-200); border-radius: 8px; font-size: 13px; color: var(--gray-900); background: var(--gray-50); outline: none; font-family: var(--font); transition: border-color .15s; }
    .bk-promo-input:focus { border-color: var(--blue); background: #fff; }
    .bk-promo-input::placeholder { color: var(--gray-300); }
    .bk-promo-btn { padding: 0 14px; height: 38px; background: var(--navy); color: #fff; border: none; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .15s; }
    .bk-promo-btn:hover { background: #0f2460; }

    /* ── Fare detail panel (tax breakdown) ── */
    .bk-tax-detail { padding-left: 10px; border-left: 2px solid var(--blue-md); margin: 4px 0 6px; }
    .bk-tax-row    { display: flex; align-items: center; justify-content: space-between; padding: 2px 0; font-size: 11.5px; }
    .bk-tax-lbl    { color: var(--gray-500); }
    .bk-tax-code   { font-size: 10px; opacity: .5; margin-left: 4px; }
    .bk-tax-val    { font-family: var(--mono); font-size: 11px; color: var(--gray-700); }

    /* ── Review step ── */
    .bk-review-section { margin-bottom: 20px; }
    .bk-review-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); padding-bottom: 8px; border-bottom: 1px solid var(--gray-100); margin-bottom: 10px; }
    .bk-review-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--gray-100); gap: 16px; font-size: 13px; }
    .bk-review-row:last-child { border-bottom: none; }
    .bk-review-label { color: var(--gray-500); font-weight: 500; flex-shrink: 0; }
    .bk-review-val   { color: var(--gray-900); font-weight: 700; text-align: right; }

    /* Phase 1 booking redesign shell */
    :root {
        --navy: var(--tw-brand, #303191);
        --blue: var(--tw-brand, #303191);
        --blue-lt: #f1f1ff;
        --blue-md: #d7d8ff;
        --green: var(--tw-accent, #009933);
        --gray-50: var(--tw-surface-soft, #f8f9fc);
        --gray-100: #f2f4f7;
        --gray-200: #e6e8ee;
        --gray-300: #d0d5dd;
        --gray-400: #98a2b3;
        --gray-500: #667085;
        --gray-700: #344054;
        --gray-900: #111827;
        --radius: 12px;
        --shadow: 0 1px 2px rgba(16,24,40,.05);
        --shadow-md: 0 10px 28px rgba(16,24,40,.08);
        --font: var(--tw-font-sans, 'Open Sans', 'Plus Jakarta Sans', sans-serif);
    }
    body {
        background: linear-gradient(180deg, #fff 0%, var(--gray-50) 42%, #fff 100%);
    }
    .bk-wrap {
        max-width: 1216px;
        padding: 24px 16px 72px;
    }
    .bk-page {
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 18px;
    }
    .bk-main,
    .bk-rail {
        min-width: 0;
    }
    .bk-rail {
        top: 18px;
    }
    .bk-crumb {
        gap: 7px;
        margin-bottom: 16px;
        font-size: 12px;
    }
    .bk-crumb a:hover {
        color: #252675;
        text-decoration: none;
    }
    .bk-steps {
        padding: 12px 16px;
        border-radius: 12px;
        background: rgba(255,255,255,.96);
        box-shadow: var(--shadow-md);
    }
    .bk-step-dot {
        transition: background .18s ease, box-shadow .18s ease, color .18s ease;
    }
    .bk-step-dot.active {
        box-shadow: 0 0 0 4px rgba(48,49,145,.12);
    }
    .bk-step-label {
        font-weight: 800;
    }
    .bk-acc {
        border-radius: 12px;
        border-color: var(--gray-200);
        box-shadow: var(--shadow);
    }
    .bk-acc-head {
        padding: 15px 18px;
    }
    .bk-acc-head:hover {
        background: #fbfcfe;
    }
    .bk-acc-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
    }
    .bk-actions {
        padding-top: 8px;
    }
    .bk-btn-ghost,
    .bk-btn-next,
    .bk-btn-pay {
        border-radius: 10px;
        letter-spacing: 0;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
    }
    .bk-btn-ghost {
        height: 46px;
        border: 1px solid var(--gray-200);
        color: var(--gray-700);
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }
    .bk-btn-ghost:hover {
        border-color: #cfd2da;
        background: #fbfcfe;
        transform: translateY(-1px);
    }
    .bk-btn-next,
    .bk-btn-pay {
        background: var(--blue);
        box-shadow: 0 12px 24px rgba(48,49,145,.18);
    }
    .bk-btn-next:hover,
    .bk-btn-pay:hover {
        background: #252675;
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(48,49,145,.24);
    }
    .bk-cart {
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(16,24,40,.08);
        overflow: hidden;
    }
    .bk-cart-head {
        padding: 16px 18px 13px;
        background: #fff;
        border-bottom: 1px solid var(--gray-200);
    }
    .bk-cart-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-900);
        font-size: 15px;
        font-weight: 850;
    }
    .bk-cart-title::before {
        content: "";
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: var(--blue-lt);
        color: var(--blue);
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 4h-2l-1 2h2l3.6 7.59-1.35 2.45A2 2 0 0 0 10 19h9v-2h-9l1.1-2h7.45a2 2 0 0 0 1.8-1.1L24 7H8.42L7 4Zm3 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm8 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z'/%3E%3C/svg%3E") center / 15px 15px no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 4h-2l-1 2h2l3.6 7.59-1.35 2.45A2 2 0 0 0 10 19h9v-2h-9l1.1-2h7.45a2 2 0 0 0 1.8-1.1L24 7H8.42L7 4Zm3 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm8 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z'/%3E%3C/svg%3E") center / 15px 15px no-repeat;
    }
    .bk-cart-body {
        padding: 16px 18px 10px;
    }
    .bk-cart-section {
        margin-bottom: 10px;
    }
    .bk-cart-section-lbl,
    .bk-fare-title {
        color: var(--gray-500);
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .04em;
    }
    .bk-cart-flight-row {
        gap: 10px;
        padding: 10px 0;
        margin: 0;
        border-bottom: 1px solid var(--gray-100);
    }
    .bk-cart-plane {
        width: 16px;
        height: 16px;
        color: var(--blue);
    }
    .bk-cart-route {
        color: var(--gray-900);
        font-size: 13px;
        line-height: 1.35;
    }
    .bk-cart-sub {
        color: var(--gray-500);
        font-size: 11.5px;
    }
    .bk-fare-section {
        padding: 14px 18px;
        border-top: 1px solid var(--gray-200);
    }
    .bk-fare-title {
        margin-bottom: 12px;
    }
    .bk-fare-row {
        gap: 12px;
        padding: 5px 0;
    }
    .bk-fare-lbl {
        color: var(--gray-500);
        min-width: 0;
    }
    .bk-fare-val {
        color: var(--gray-900);
        white-space: nowrap;
    }
    .bk-fare-total-row {
        padding: 16px 18px;
        border-top: 1px solid var(--gray-200);
        background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
    }
    .bk-fare-total-lbl {
        color: var(--gray-900);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .02em;
    }
    .bk-fare-total-val {
        color: var(--navy);
        font-size: 24px;
        letter-spacing: 0;
    }
    .bk-promo {
        border-top: 1px solid var(--gray-200);
        padding: 0;
    }
    .bk-promo > details {
        padding: 0;
    }
    .bk-promo-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-height: 46px;
        padding: 0 18px;
        color: var(--blue);
        font-size: 13px;
        font-weight: 750;
        cursor: pointer;
        list-style: none;
    }
    .bk-promo-summary::-webkit-details-marker {
        display: none;
    }
    .bk-promo-summary::after {
        content: "";
        width: 14px;
        height: 14px;
        background: currentColor;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") center / contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") center / contain no-repeat;
        transition: transform .18s ease;
    }
    .bk-promo details[open] .bk-promo-summary::after {
        transform: rotate(180deg);
    }
    .bk-promo-row {
        padding: 0 18px 16px;
    }
    .bk-promo-input {
        height: 42px;
        border: 1px solid var(--gray-200);
        border-radius: 9px;
        background: #fbfcfe;
    }
    .bk-promo-input:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(48,49,145,.10);
    }
    .bk-promo-btn {
        height: 42px;
        border-radius: 9px;
        background: var(--blue);
    }
    .bk-promo-btn:hover {
        background: #252675;
    }
    .bk-rail-trust {
        display: grid;
        gap: 8px;
        padding: 14px 18px 16px;
        border-top: 1px solid var(--gray-200);
        background: #fbfcfe;
    }
    .bk-rail-trust-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-600, #475467);
        font-size: 11.5px;
        line-height: 1.35;
    }
    .bk-rail-trust-icon {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        color: var(--green);
    }

    /* Phase 2 itinerary and rules redesign */
    .bk-main > .bk-acc:first-of-type {
        overflow: visible;
    }
    .bk-main > .bk-acc:first-of-type .bk-acc-head {
        background: #fff;
        border-bottom-color: var(--gray-200);
    }
    .bk-main > .bk-acc:first-of-type .bk-acc-icon {
        background: #f1f1ff;
        color: var(--blue);
    }
    .bk-itin-leg {
        background: #fff;
    }
    .bk-itin-leg + .bk-itin-leg {
        border-top: 1px solid var(--gray-200);
    }
    .bk-itin-leg-head {
        padding: 14px 18px;
        background: #fbfcfe;
        border-bottom: 1px solid var(--gray-100);
        transition: background .18s ease;
    }
    .bk-itin-leg-head:hover {
        background: #f7f8fc;
    }
    .bk-itin-leg-route {
        gap: 9px;
        color: var(--gray-900);
        font-size: 15px;
        line-height: 1.2;
        letter-spacing: 0;
    }
    .bk-itin-leg-route svg {
        color: var(--blue);
        flex: 0 0 14px;
    }
    .bk-itin-leg-meta {
        gap: 8px;
        color: var(--gray-500);
        font-size: 11.5px;
        font-weight: 600;
        flex-wrap: wrap;
    }
    .bk-itin-leg-badge,
    .bk-outbound-badge {
        display: inline-flex;
        align-items: center;
        min-height: 20px;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 0;
    }
    .bk-outbound-badge {
        background: #eef2ff;
        color: var(--blue);
    }
    .bk-itin-leg-badge {
        background: #fff7ed;
        color: #d97706;
    }
    .bk-itin-leg-badge.direct {
        background: #eafff0;
        color: var(--green);
    }
    .bk-itin-leg-body {
        padding: 14px 18px 18px;
    }
    .bk-seg-group {
        margin-top: 0;
        padding: 15px 16px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }
    .bk-seg-group + .bk-seg-group {
        margin-top: 12px;
    }
    .bk-seg-airline-bar {
        gap: 12px;
        padding: 0 0 12px;
        margin-bottom: 14px;
        border-bottom: 1px solid var(--gray-100);
    }
    .bk-seg-airline-left {
        min-width: 0;
        flex-wrap: wrap;
    }
    .bk-seg-airline-logo {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: #fff;
        border: 1px solid var(--gray-200);
    }
    .bk-seg-airline-name {
        color: var(--gray-900);
        font-size: 13px;
        font-weight: 850;
    }
    .bk-seg-cabin-tag {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 999px;
        background: var(--gray-50);
        color: var(--gray-500);
        font-size: 10.5px;
        font-weight: 750;
        white-space: nowrap;
    }
    .bk-seg-timeline {
        align-items: stretch;
    }
    .bk-seg-spine {
        width: 22px;
        padding-top: 8px;
    }
    .bk-seg-dot {
        width: 8px;
        height: 8px;
        background: var(--blue);
        box-shadow: 0 0 0 4px rgba(48,49,145,.10);
    }
    .bk-seg-dot.end {
        background: var(--green);
        box-shadow: 0 0 0 4px rgba(0,153,51,.10);
    }
    .bk-seg-line {
        width: 1.5px;
        background: #d8dbe3;
        min-height: 52px;
    }
    .bk-seg-stop {
        grid-template-columns: 82px minmax(0, 1fr) 128px;
        gap: 12px;
        padding: 2px 0 12px 12px;
    }
    .bk-seg-time {
        color: #111827;
        font-size: 17px;
        line-height: 1.05;
        letter-spacing: 0;
    }
    .bk-seg-place {
        color: var(--gray-700);
        font-size: 12.5px;
        font-weight: 750;
        line-height: 1.25;
    }
    .bk-seg-place-sub {
        color: var(--gray-400);
        font-size: 11px;
        line-height: 1.35;
    }
    .bk-seg-bags {
        align-self: start;
        gap: 4px;
        padding: 8px 10px;
        border-radius: 10px;
        background: #fbfcfe;
        border: 1px solid var(--gray-100);
    }
    .bk-seg-bags-lbl {
        color: var(--gray-400);
        font-size: 9.5px;
        letter-spacing: .04em;
    }
    .bk-seg-bags-val {
        color: var(--gray-900);
        font-size: 11px;
        font-weight: 850;
    }
    .bk-layover-strip {
        margin: 12px 0;
        border-color: #fed7aa;
        border-radius: 10px;
    }
    .bk-bags-banner {
        border-radius: 12px;
        border-color: var(--gray-200);
        box-shadow: var(--shadow);
    }
    .bk-bags-icon {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #f1f1ff;
        color: var(--blue);
        font-size: 19px;
    }
    .bk-bags-title {
        font-size: 14px;
        font-weight: 850;
    }
    .bk-bags-sub {
        color: var(--gray-500);
        font-size: 12px;
    }
    .bk-main > .bk-acc:nth-of-type(3) .bk-acc-head {
        background: #fff;
    }
    .bk-main > .bk-acc:nth-of-type(3) .bk-acc-icon {
        background: #fff1f2;
        color: #dc2626;
    }
    .bk-main > .bk-acc:nth-of-type(3) .bk-acc-body {
        background: #fff;
    }
    .bk-main > .bk-acc:nth-of-type(3) table {
        border-radius: 10px;
        overflow: hidden;
    }
    .bk-main > .bk-acc:nth-of-type(3) div[style*="border:1px solid var(--gray-200)"] {
        border-color: var(--gray-200) !important;
        border-radius: 10px !important;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }

    /* Phase 3 traveller and contact form redesign */
    .bk-route-summary {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .bk-route-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 24px;
        padding: 4px 9px;
        border: 1px solid var(--gray-200);
        border-radius: 999px;
        background: #fbfcfe;
        color: var(--gray-600, #475467);
        font-size: 11px;
        font-weight: 750;
        line-height: 1;
        white-space: nowrap;
    }
    .bk-route-main {
        border-color: rgba(48,49,145,.15);
        background: linear-gradient(180deg, #fff 0%, #f7f7ff 100%);
        color: var(--blue);
        font-weight: 850;
    }
    .bk-route-main svg {
        width: 13px;
        height: 13px;
        flex: 0 0 13px;
    }
    .bk-route-cabin {
        border-color: rgba(0,153,51,.18);
        background: #f1fff5;
        color: var(--green);
    }

    /* Phase 4 review, contact and summary refinement */
    .bk-icon-mask,
    .bk-inline-icon,
    .bk-mini-icon {
        display: inline-flex;
        flex: 0 0 auto;
        background: currentColor;
        mask-position: center;
        mask-repeat: no-repeat;
        mask-size: contain;
        -webkit-mask-position: center;
        -webkit-mask-repeat: no-repeat;
        -webkit-mask-size: contain;
    }
    .bk-inline-icon { width: 15px; height: 15px; }
    .bk-mini-icon { width: 13px; height: 13px; }
    .bk-icon-plane {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z'/%3E%3C/svg%3E");
    }
    .bk-icon-bag {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M8 6V5a4 4 0 0 1 8 0v1h2.5A2.5 2.5 0 0 1 21 8.5v10A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-10A2.5 2.5 0 0 1 5.5 6H8Zm2 0h4V5a2 2 0 1 0-4 0v1Zm-3 4v7h2v-7H7Zm8 0v7h2v-7h-2Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M8 6V5a4 4 0 0 1 8 0v1h2.5A2.5 2.5 0 0 1 21 8.5v10A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-10A2.5 2.5 0 0 1 5.5 6H8Zm2 0h4V5a2 2 0 1 0-4 0v1Zm-3 4v7h2v-7H7Zm8 0v7h2v-7h-2Z'/%3E%3C/svg%3E");
    }
    .bk-icon-cabin {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm1 3v12h8V6H8Zm2 2h4v2h-4V8Zm0 4h4v2h-4v-2Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm1 3v12h8V6H8Zm2 2h4v2h-4V8Zm0 4h4v2h-4v-2Z'/%3E%3C/svg%3E");
    }
    .bk-icon-meal {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 2h2v8a3 3 0 0 1-2 2.83V22H5v-9.17A3 3 0 0 1 3 10V2h2v8h2V2Zm10 0c2.21 0 4 2.24 4 5v5h-3v10h-2V2h1Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 2h2v8a3 3 0 0 1-2 2.83V22H5v-9.17A3 3 0 0 1 3 10V2h2v8h2V2Zm10 0c2.21 0 4 2.24 4 5v5h-3v10h-2V2h1Z'/%3E%3C/svg%3E");
    }
    .bk-icon-users {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.31 0-6 1.79-6 4v2h12v-2c0-2.21-2.69-4-6-4Zm7.5-.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0 1.5c-.77 0-1.5.1-2.16.28 1.6.92 2.66 2.23 2.66 3.72v1h5v-1.5c0-1.93-2.46-3.5-5.5-3.5Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.31 0-6 1.79-6 4v2h12v-2c0-2.21-2.69-4-6-4Zm7.5-.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0 1.5c-.77 0-1.5.1-2.16.28 1.6.92 2.66 2.23 2.66 3.72v1h5v-1.5c0-1.93-2.46-3.5-5.5-3.5Z'/%3E%3C/svg%3E");
    }
    .bk-route-summary-card {
        display: grid;
        grid-template-columns: minmax(0, auto) auto;
        gap: 7px 10px;
        align-items: center;
        margin-top: 7px;
    }
    .bk-route-pair {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-900);
        font-size: 14px;
        font-weight: 900;
        line-height: 1.1;
        letter-spacing: 0;
    }
    .bk-route-arrow {
        width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: var(--blue);
        background: #f1f1ff;
    }
    .bk-route-arrow svg {
        width: 12px;
        height: 12px;
    }
    .bk-route-meta {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
        grid-column: 1 / -1;
        color: var(--gray-500);
        font-size: 11.5px;
        font-weight: 650;
    }
    .bk-route-status {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #ecfdf3;
        color: var(--green);
        font-size: 11px;
        font-weight: 850;
    }
    .bk-route-status.stop {
        background: #fff7ed;
        color: var(--amber);
    }
    .bk-phone-grid {
        grid-template-columns: 1fr;
    }
    .bk-phone-input {
        font-size: 15px;
        font-weight: 750;
        letter-spacing: .01em;
    }
    .bk-cart {
        border: 1px solid rgba(48,49,145,.12);
        border-radius: 16px;
        box-shadow: 0 18px 48px rgba(16,24,40,.10);
    }
    .bk-cart-head {
        padding: 18px 18px 14px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    }
    .bk-cart-title::before {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M6 2h12a2 2 0 0 1 2 2v18l-4-2-4 2-4-2-4 2V4a2 2 0 0 1 2-2Zm2 6h8V6H8v2Zm0 4h8v-2H8v2Zm0 4h5v-2H8v2Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M6 2h12a2 2 0 0 1 2 2v18l-4-2-4 2-4-2-4 2V4a2 2 0 0 1 2-2Zm2 6h8V6H8v2Zm0 4h8v-2H8v2Zm0 4h5v-2H8v2Z'/%3E%3C/svg%3E");
    }
    .bk-cart-subtitle {
        margin-top: 4px;
        color: var(--gray-500);
        font-size: 11.5px;
        font-weight: 600;
    }
    .bk-cart-flight-row {
        align-items: center;
        padding: 11px 0;
    }
    .bk-cart-plane {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 999px;
        color: var(--blue);
        background: #f1f1ff;
        padding: 8px;
    }
    .bk-cart-route {
        font-size: 13.5px;
        font-weight: 850;
    }
    .bk-fare-section {
        background: linear-gradient(180deg, #fff 0%, #fcfcff 100%);
    }
    .bk-fare-total-row {
        margin: 0 14px 14px;
        padding: 16px;
        border: 1px solid rgba(48,49,145,.12);
        border-radius: 14px;
        background: #f8f9ff;
    }
    .bk-fare-total-val {
        font-size: clamp(21px, 2.4vw, 26px);
        line-height: 1.1;
    }
    .bk-pax-counter {
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }
    .bk-pax-col {
        padding: 16px;
        border-right-color: var(--gray-200);
    }
    .bk-pax-col-label {
        color: var(--gray-500);
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .04em;
    }
    .bk-pax-col-sub {
        color: var(--gray-400);
        font-size: 11px;
    }
    .bk-pax-num {
        color: var(--gray-900);
        font-size: 21px;
        letter-spacing: 0;
    }
    .bk-total-bar {
        border: 1px solid rgba(48,49,145,.10);
        border-radius: 10px;
        background: linear-gradient(180deg, #f8f8ff 0%, #f1f1ff 100%);
    }
    .bk-pax-card {
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
        overflow: hidden;
    }
    .bk-pax-card-head {
        padding: 13px 15px;
        background: #fbfcfe;
        border-bottom-color: var(--gray-200);
        transition: background .18s ease;
    }
    .bk-pax-card-head:hover {
        background: #f7f8fc;
    }
    .bk-pax-badge {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 850;
    }
    .bk-pax-num-lbl {
        color: var(--gray-900);
        font-size: 13px;
        font-weight: 850;
    }
    .bk-pax-progress {
        padding: 3px 8px;
        border-radius: 999px;
        background: var(--gray-100);
        color: var(--gray-500);
        font-size: 10.5px;
        font-weight: 800;
    }
    .bk-pax-complete {
        padding: 3px 8px;
        border-radius: 999px;
        background: #eafff0;
        color: var(--green);
        font-size: 10.5px;
        font-weight: 850;
    }
    .bk-pax-notice {
        padding: 11px 15px;
        border-bottom: 1px solid #fde68a;
        background: #fff9eb;
        color: #92400e;
        font-size: 11.5px;
        line-height: 1.45;
    }
    .bk-form-grid {
        gap: 14px;
        padding: 17px 15px 18px;
    }
    .bk-field {
        gap: 6px;
        min-width: 0;
    }
    .bk-label {
        color: var(--gray-500);
        font-size: 10.5px;
        font-weight: 850;
        letter-spacing: .035em;
    }
    .bk-input,
    .bk-select {
        height: 46px;
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        background-color: #fbfcfe;
        color: var(--gray-900);
        font-size: 13.5px;
        font-weight: 600;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.55);
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .bk-input::placeholder {
        color: #a6afbf;
        font-weight: 500;
    }
    .bk-input:focus,
    .bk-select:focus {
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(48,49,145,.10);
    }
    .bk-select {
        background-color: #fbfcfe;
        background-position: right 12px center;
    }
    .bk-radio-group {
        gap: 8px;
        height: auto;
        min-height: 46px;
        flex-wrap: wrap;
    }
    .bk-radio-opt {
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid var(--gray-200);
        border-radius: 999px;
        background: #fff;
        color: var(--gray-700);
        font-size: 13px;
        font-weight: 750;
        transition: border-color .18s ease, background .18s ease, color .18s ease;
    }
    .bk-radio-opt:hover {
        border-color: var(--blue-md);
        background: var(--blue-lt);
        color: var(--blue);
    }
    .bk-radio-opt input {
        accent-color: var(--blue);
    }
    .bk-hint {
        color: var(--gray-400);
        font-size: 10.5px;
        line-height: 1.35;
    }
    .bk-error {
        color: var(--red);
        font-size: 11px;
        font-weight: 650;
    }
    .bk-contact-grid {
        gap: 14px;
    }
    .bk-contact-grid + .bk-contact-grid {
        padding-top: 14px;
        border-top: 1px solid var(--gray-100);
    }

    /* ── Responsive ── */
    @media (max-width: 900px) { .bk-page { grid-template-columns: 1fr; } .bk-rail { position: static; } }
    @media (max-width: 580px) {
        .bk-wrap { padding: 10px 10px 48px; }
        .bk-form-grid { grid-template-columns: 1fr 1fr; }
        .bk-form-grid .bk-col-2, .bk-form-grid .bk-col-full { grid-column: 1 / -1; }
        .bk-contact-grid { grid-template-columns: 1fr; }
        .bk-contact-full { grid-column: 1 / -1; }
        .bk-pp-body { grid-template-columns: 1fr; }
        .bk-pax-counter { flex-wrap: wrap; }
        .bk-pax-col { min-width: 50%; }
        .bk-connector { display: none; }
        .bk-btn-next, .bk-btn-pay { flex: 1; justify-content: center; }
        .bk-actions { flex-wrap: wrap; }
        .bk-seg-stop { grid-template-columns: 60px 1fr; }
        .bk-seg-bags { display: none; }
    }
    @media (max-width: 900px) {
        .bk-page {
            gap: 14px;
        }
        .bk-rail {
            order: 2;
        }
        .bk-main {
            order: 1;
        }
        .bk-cart {
            box-shadow: 0 8px 22px rgba(16,24,40,.06);
        }
    }
    @media (max-width: 640px) {
        body {
            margin-top: 0 !important;
        }
        section.navbarmain {
            padding-top: 104px !important;
        }
        main.navbarmain.upper-space {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .bk-wrap {
            padding: 8px 12px 52px;
        }
        .bk-crumb {
            margin-bottom: 12px;
        }
        .bk-steps {
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .bk-steps::-webkit-scrollbar {
            display: none;
        }
        .bk-step {
            min-width: 116px;
            gap: 8px;
        }
        .bk-step-dot {
            width: 28px;
            height: 28px;
        }
        .bk-step-label {
            font-size: 11.5px;
            white-space: nowrap;
        }
        .bk-step-sub {
            display: none;
        }
        .bk-route-summary {
            gap: 6px;
        }
        .bk-route-chip {
            min-height: 23px;
            padding: 4px 8px;
            font-size: 10.5px;
        }
        .bk-connector {
            display: none;
        }
        .bk-acc-head {
            padding: 13px 14px;
        }
        .bk-cart-head,
        .bk-cart-body,
        .bk-fare-section,
        .bk-fare-total-row,
        .bk-rail-trust {
            padding-left: 14px;
            padding-right: 14px;
        }
        .bk-fare-total-val {
            font-size: 21px;
        }
        .bk-promo-summary {
            padding: 0 14px;
        }
        .bk-promo-row {
            padding: 0 14px 14px;
        }
        .bk-actions {
            gap: 10px;
        }
        .bk-btn-ghost,
        .bk-btn-next,
        .bk-btn-pay {
            width: 100%;
            justify-content: center;
        }
        .bk-form-grid,
        .bk-contact-grid,
        .bk-pp-body {
            grid-template-columns: 1fr !important;
        }
        .bk-form-grid .bk-field,
        .bk-form-grid .bk-col-2,
        .bk-form-grid .bk-col-full,
        .bk-form-grid .bk-col-half {
            grid-column: 1 / -1 !important;
        }
        .bk-pax-card-head {
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .bk-pax-progress,
        .bk-pax-complete {
            margin-left: 0;
        }
    }
</style>

@php
    // ── Core session data ──
    $flight        = session('bookingFlight') ?? [];
    $mappedFlight  = $flight['flight'] ?? $flight;
    $sessionId     = session('bookingSessionId') ?? null;
    $searchParams  = session('bookingSearchParams') ?? [];
    $fareRulesData = session('fareRules') ?? [];
    $extraServices = session('extraServices') ?? [];

    // ── Parse revalidate data (raw from API) ──
    $revalidate    = $flight['revalidate'] ?? [];
    $fareItinerary = $revalidate['AirRevalidateResponse']['AirRevalidateResult']['FareItineraries']['FareItinerary'] ?? [];
    $airFareInfo   = $fareItinerary['AirItineraryFareInfo'] ?? [];
    $fareBreakdown = $airFareInfo['FareBreakdown'] ?? [];
    $itinTotals    = $airFareInfo['ItinTotalFares'] ?? [];
    $originDest    = $fareItinerary['OriginDestinationOptions'] ?? [];

    // ── Parse Extra Services ──
    $esResult      = $extraServices['ExtraServicesResponse']['ExtraServicesResult']['ExtraServicesData'] ?? [];
    $dynBaggage    = $esResult['DynamicBaggage'] ?? [];
    $dynMeal       = $esResult['DynamicMeal'] ?? [];
    $dynSeat       = $esResult['DynamicSeat'] ?? [];

    // ── Parse Fare Rules ──
    $fareRulesResult  = $fareRulesData['FareRules1_1Response']['FareRules1_1Result'] ?? [];
    $baggageInfos     = $fareRulesResult['BaggageInfos'] ?? [];
    $fareRulesList    = $fareRulesResult['FareRules'] ?? [];

    // ── Existing mapped fields (keep your existing ones) ──
    $cabinMap = ['Y' => 'Economy', 'S' => 'Premium Economy', 'C' => 'Business', 'F' => 'First Class'];
    $cabin    = $cabinMap[$searchParams['flight_type'] ?? 'Y'] ?? 'Economy';

    $currency = $mappedFlight['currency'] ?? ($itinTotals['TotalFare']['CurrencyCode'] ?? 'NGN');
    $sym      = $currency === 'NGN' ? '₦' : ($currency === 'USD' ? '$' : $currency . ' ');
    $fmt      = fn($v) => $sym . number_format((float) $v, 2);

    $segments  = $flight['segments'] ?? ($mappedFlight['segments'] ?? []);
    $retSegs   = $mappedFlight['returnSegments'] ?? [];
    $multiLegs = $mappedFlight['multiLegs'] ?? [];
    $breakdown = $flight['fareBreakdown'] ?? ($mappedFlight['fareBreakdown'] ?? $fareBreakdown);
    $isReturn  = count($retSegs) > 0;
    $isMulti   = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round-trip' : ($isMulti ? 'Multi-city' : 'One-way');
    $firstSeg  = $segments[0] ?? [];
    $lastSeg   = count($segments) > 0 ? $segments[count($segments) - 1] : [];
    if ($isMulti && !empty($multiLegs)) {
        $firstSeg = $multiLegs[0]['segments'][0] ?? [];
        $lastMultiLeg = $multiLegs[count($multiLegs) - 1]['segments'] ?? [];
        $lastSeg = !empty($lastMultiLeg) ? $lastMultiLeg[count($lastMultiLeg) - 1] : [];
    }
    $stopCount = $mappedFlight['stops'] ?? 0;

    // Totals from revalidate
    $totalPrice = (float)($mappedFlight['price'] ?? ($itinTotals['TotalFare']['Amount'] ?? 0));
    $totalBase  = (float)($mappedFlight['baseFare'] ?? ($itinTotals['BaseFare']['Amount'] ?? 0));
    $totalTax   = (float)($mappedFlight['totalTax'] ?? ($itinTotals['TotalTax']['Amount'] ?? 0));
    $markupAmount = (float)($mappedFlight['markupAmount'] ?? 0);
    $discount   = 0;

    $taxLabels = [
        'QT'  => 'Airport Tax',        'TE5' => 'Ticket Levy',
        'W3'  => 'Security Surcharge', 'W32' => 'Security Surcharge',
        'MA'  => 'Miscellaneous Fee',  'MAC' => 'Miscellaneous Fee',
        'NG3' => 'Nigeria Passenger Levy', 'YQF' => 'Fuel Surcharge',
        'YQI' => 'Fuel Surcharge',     'YRI' => 'Carrier Surcharge',
        'YRF' => 'Carrier Surcharge',  'GB'  => 'Air Passenger Duty',
        'UB'  => 'Passenger Service Charge', 'DE' => 'Departure Tax',
        'BE'  => 'Booking Fee',        'OtherTaxes' => 'Taxes & Fees',
    ];

    $equipMap = [
        '73H' => 'Boeing 737-800', '738' => 'Boeing 737-800',
        '7M8' => 'Boeing 737 MAX 8', '789' => 'Boeing 787-9',
        '788' => 'Boeing 787-8',    '320' => 'Airbus A320',
        '321' => 'Airbus A321',     '332' => 'Airbus A330-200',
        '333' => 'Airbus A330-300', 'E90' => 'Embraer E190',
    ];

    // Build allLegs for seat section
    $allLegs = [];
    if (!empty($segments)) {
        $allLegs[] = ['label' => 'Departure', 'route' => ($firstSeg['from'] ?? '') . ' → ' . (($segments[count($segments)-1]['to'] ?? '')), 'type' => 'outbound', 'logo' => $firstSeg['airlineLogo'] ?? ''];
    }
    if ($isReturn && !empty($retSegs)) {
        $allLegs[] = ['label' => 'Return', 'route' => ($retSegs[0]['from'] ?? '') . ' → ' . ($retSegs[count($retSegs)-1]['to'] ?? ''), 'type' => 'return', 'logo' => $retSegs[0]['airlineLogo'] ?? ''];
    }
    foreach ($multiLegs as $li => $leg) {
        $legSegs = $leg['segments'] ?? [];
        if (!empty($legSegs)) {
            $allLegs[] = ['label' => 'Leg ' . ($li + 2), 'route' => ($legSegs[0]['from'] ?? '') . ' → ' . ($legSegs[count($legSegs)-1]['to'] ?? ''), 'type' => 'multi', 'logo' => $legSegs[0]['airlineLogo'] ?? ''];
        }
    }

    // ── Parse DynamicBaggage for outbound/inbound options ──
    if ($isMulti && !empty($multiLegs)) {
        $allLegs = [];
        foreach ($multiLegs as $li => $leg) {
            $legSegs = $leg['segments'] ?? [];
            if (!empty($legSegs)) {
                $allLegs[] = [
                    'label' => 'Leg ' . ($li + 1),
                    'route' => ($legSegs[0]['from'] ?? '') . ' â†’ ' . ($legSegs[count($legSegs)-1]['to'] ?? ''),
                    'type' => 'multi',
                    'logo' => $legSegs[0]['airlineLogo'] ?? '',
                ];
            }
        }
    }

    $baggageOutbound = [];
    $baggageInbound  = [];
    foreach ($dynBaggage as $bag) {
        $behavior = $bag['Behavior'] ?? '';
        $services = $bag['Services'][0] ?? [];
        if ($behavior === 'PER_PAX_OUTBOUND')  $baggageOutbound = $services;
        if ($behavior === 'PER_PAX_INBOUND')   $baggageInbound  = $services;
    }

    // ── Parse DynamicMeal for outbound/inbound ──
    $mealOutbound = [];
    $mealInbound  = [];
    foreach ($dynMeal as $meal) {
        $behavior = $meal['Behavior'] ?? '';
        $services = $meal['Services'] ?? [];
        if ($behavior === 'PER_PAX_PER_SEGMENT_OUTBOUND') $mealOutbound = $services;
        if ($behavior === 'PER_PAX_PER_SEGMENT_INBOUND')  $mealInbound  = $services;
    }

    // Helper: outbound currency symbol (extra services may use AED or local)
    $esSym = fn($code) => match($code) { 'NGN' => '₦', 'USD' => '$', 'AED' => 'AED ', default => $code . ' ' };
    $esFmt = fn($svc) => ($esSym($svc['ServiceCost']['CurrencyCode'] ?? '') . number_format((float)($svc['ServiceCost']['Amount'] ?? 0), 2));
    $isTravelFlexCheckout = session('bookingIntent', 'booking') === 'travelflex';
@endphp

    <div class="bk-wrap"
        x-data="{
            submitForm() { document.getElementById('bk-form').submit(); },
            taxOpen: {},
            toggleTax(key) { this.taxOpen[key] = !this.taxOpen[key]; }
        }">

        {{-- Breadcrumb --}}
        <div class="bk-crumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bk-crumb-sep">›</span>
            <a href="{{ route('air.flight-s') }}">Flight Results</a>
            <span class="bk-crumb-sep">›</span>
            <span>Complete Booking</span>
        </div>
        @if($errors->any())
        <div class="bk-notice danger" style="margin-bottom:16px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif
        {{-- Global notices --}}
        @if(!empty($flight['isPassportMandatory']))
            <div class="bk-notice danger" style="margin-bottom:12px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                <span><strong>Passport required.</strong> All passengers must carry a valid passport. Names must match exactly.</span>
            </div>
        @endif

        <div class="bk-page">

            {{-- ══════════════ MAIN COLUMN ══════════════ --}}
            <div class="bk-main">

                {{-- Stepper --}}
                <div class="bk-steps">
                    <div class="bk-step">
                        <div class="bk-step-dot {{ $step > 1 ? 'done' : 'active' }}">
                            @if($step > 1)
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            @else 1 @endif
                        </div>
                        <div>
                            <div class="bk-step-label {{ $step === 1 ? 'active' : '' }}">Traveller Info</div>
                            <div class="bk-step-sub">Names &amp; documents</div>
                        </div>
                    </div>
                    <div class="bk-connector {{ $step > 1 ? 'done' : '' }}"></div>
                    <div class="bk-step">
                        <div class="bk-step-dot {{ $step > 2 ? 'done' : ($step === 2 ? 'active' : 'pending') }}">
                            @if($step > 2)
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            @else 2 @endif
                        </div>
                        <div>
                            <div class="bk-step-label {{ $step === 2 ? 'active' : '' }}">Trip Customisation</div>
                            <div class="bk-step-sub">Baggage &amp; meals</div>
                        </div>
                    </div>
                    <div class="bk-connector {{ $step > 2 ? 'done' : '' }}"></div>
                    <div class="bk-step">
                        <div class="bk-step-dot {{ $step === 3 ? 'active' : 'pending' }}">3</div>
                        <div>
                            <div class="bk-step-label {{ $step === 3 ? 'active' : '' }}">Review &amp; Continue</div>
                            <div class="bk-step-sub">{{ $isTravelFlexCheckout ? 'Continue to TravelFlex' : 'Review & pay' }}</div>
                        </div>
                    </div>
                </div>

                {{-- ════════ STEP 1 ════════ --}}
                @if(in_array($step, [1, 2], true))

                    {{-- ── 1. Flight Itinerary (accordion, open by default) ── --}}
                    <div class="bk-acc" x-data="{ open: false }">
                        @php
                            $routeStopCount = $flight['stops'] ?? max(0, count($segments) - 1);
                            $routeDuration = $flight['totalTimeLabel'] ?? $flight['durationLabel'] ?? '';
                            $routeDate = !empty($firstSeg['departDT'])
                                ? \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M')
                                : '';
                        @endphp
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <span class="bk-icon-mask bk-icon-plane" style="width:17px;height:17px;"></span>
                            </div>
                            <div>
                                <div class="bk-acc-title">Flight Itinerary</div>
                                <div class="bk-acc-sub bk-route-summary-card">
                                    <div class="bk-route-pair">
                                        <span>{{ $firstSeg['from'] ?? '' }}</span>
                                        <span class="bk-route-arrow">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h14"/>
                                                <path d="m13 6 6 6-6 6"/>
                                            </svg>
                                        </span>
                                        <span>{{ $lastSeg['to'] ?? '' }}</span>
                                    </div>
                                    <span class="bk-route-chip">{{ $tripLabel }}</span>
                                    <div class="bk-route-meta">
                                        @if($routeDate)
                                            <span>{{ $routeDate }}</span>
                                        @endif
                                        <span class="bk-route-status {{ $routeStopCount > 0 ? 'stop' : '' }}">
                                            {{ $routeStopCount > 0 ? $routeStopCount . ' stop' . ($routeStopCount > 1 ? 's' : '') : 'Non stop' }}
                                        </span>
                                        @if($routeDuration)
                                            <span>&middot; {{ $routeDuration }}</span>
                                        @endif
                                        <span>&middot; {{ $cabin }}</span>
                                    </div>
                                </div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>

                        <div x-show="open" x-transition>
                            @if(!$isMulti)
                                {{-- ── Outbound leg ── --}}
                                @php
                                    $outStopCount = $flight['stops'] ?? max(0, count($segments) - 1);
                                    $outDuration  = $flight['totalTimeLabel'] ?? $flight['durationLabel'] ?? '';
                                @endphp
                                <div class="bk-itin-leg" x-data="{ legOpen: true }">
                                    <div class="bk-itin-leg-head" @click="legOpen = !legOpen">
                                        <div>
                                            <div class="bk-itin-leg-route">
                                                {{ $firstSeg['from'] ?? '' }}
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                                {{ $lastSeg['to'] ?? '' }}
                                                <span class="bk-outbound-badge">
                                                    Outbound
                                                </span>
                                            </div>
                                            <div class="bk-itin-leg-meta">
                                                @if(!empty($flight['departDateLabel'])) <span>{{ $flight['departDateLabel'] }}</span> @endif
                                                <span class="bk-itin-leg-badge {{ $outStopCount === 0 ? 'direct' : '' }}">
                                                    {{ $outStopCount === 0 ? 'Non stop' : $outStopCount . ' stop' . ($outStopCount > 1 ? 's' : '') }}
                                                </span>
                                                @if($outDuration) <span>· {{ $outDuration }}</span> @endif
                                            </div>
                                        </div>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :style="legOpen ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
                                    </div>

                                    <div x-show="legOpen" x-transition>
                                        <div class="bk-itin-leg-body">
                                            @foreach($segments as $si => $seg)
                                                @php
                                                    $equip   = $seg['equipment'] ?? '';
                                                    $equipLbl= $equipMap[$equip] ?? $equip;
                                                    $bagStr  = implode(' / ', array_unique(array_filter((array)($breakdown[0]['baggage'] ?? []), fn($v) => $v !== ''))) ?: '1 × 23kg';
                                                    $cabinBag= implode(' / ', array_unique(array_filter((array)($breakdown[0]['cabinBaggage'] ?? []), fn($v) => $v !== ''))) ?: '1 × 7kg';
                                                @endphp

                                                @if($si > 0 && !empty($flight['layoverDurations'][$si - 1]))
                                                    <div class="bk-layover-strip">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                        Layover in {{ $segments[$si-1]['toCity'] ?? $segments[$si-1]['to'] ?? '' }}
                                                        · {{ $flight['layoverDurations'][$si-1] }}
                                                    </div>
                                                @endif

                                                <div class="bk-seg-group">
                                                    <div class="bk-seg-airline-bar">
                                                        <div class="bk-seg-airline-left">
                                                            @if(!empty($seg['airlineLogo']))
                                                                <img class="bk-seg-airline-logo" src="{{ $seg['airlineLogo'] }}" alt="{{ $seg['airline'] ?? '' }}">
                                                            @endif
                                                            <span class="bk-seg-airline-name">{{ $seg['airline'] ?? '' }}</span>
                                                            @if($equipLbl) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--gray-400);">{{ $equipLbl }}</span> @endif
                                                            @if(!empty($seg['flightNo'])) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--gray-400);">{{ $seg['flightNo'] }}</span> @endif
                                                        </div>
                                                        <div>
                                                            <span class="bk-seg-cabin-tag">{{ $seg['cabin'] ?? $cabin }}</span>
                                                            @if(!empty($seg['resBookCode'])) <span class="bk-seg-cabin-tag" style="margin-left:5px;">Class{{ $seg['resBookCode'] }}</span> @endif
                                                        </div>
                                                    </div>

                                                    <div class="bk-seg-timeline">
                                                        <div class="bk-seg-spine">
                                                            <div class="bk-seg-dot"></div>
                                                            <div class="bk-seg-line"></div>
                                                            <div class="bk-seg-dot end"></div>
                                                        </div>
                                                        <div class="bk-seg-stops">
                                                            <div class="bk-seg-stop">
                                                                <div class="bk-seg-time">{{ $seg['departTime'] }}</div>
                                                                <div>
                                                                    <div class="bk-seg-place">{{ $seg['fromCity'] ?? $seg['from'] ?? '' }}</div>
                                                                    <div class="bk-seg-place-sub">{{ $seg['fromAirport'] ?? '' }}</div>
                                                                </div>
                                                                <div class="bk-seg-bags">
                                                                    <span class="bk-seg-bags-lbl">Baggage</span>
                                                                    <span class="bk-seg-bags-val">{{ $bagStr }}</span>
                                                                    <span class="bk-seg-bags-lbl" style="margin-top:4px;">Check In</span>
                                                                    <span class="bk-seg-bags-val">{{ $bagStr }}</span>
                                                                    <span class="bk-seg-bags-lbl" style="margin-top:4px;">Cabin</span>
                                                                    <span class="bk-seg-bags-val">{{ $cabinBag }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="bk-seg-stop" style="padding-bottom:0;">
                                                                <div class="bk-seg-time" style="color:var(--gray-500);font-size:12px;">
                                                                    {{ floor($seg['duration']/60) }}h {{ $seg['duration']%60 }}m
                                                                </div>
                                                                <div></div>
                                                                <div></div>
                                                            </div>
                                                            <div class="bk-seg-stop" style="padding-top:4px;padding-bottom:0;">
                                                                <div class="bk-seg-time">{{ $seg['arriveTime'] }}</div>
                                                                <div>
                                                                    <div class="bk-seg-place">{{ $seg['toCity'] ?? $seg['to'] ?? '' }}</div>
                                                                    <div class="bk-seg-place-sub">{{ $seg['toAirport'] ?? '' }}</div>
                                                                </div>
                                                                <div></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- ── Return leg ── --}}
                            @if($isReturn && count($retSegs) > 0)
                                @php
                                    $retFirst    = $retSegs[0];
                                    $retLast     = $retSegs[count($retSegs)-1];
                                    $retStops    = $flight['returnStops'] ?? max(0, count($retSegs)-1);
                                    $retDuration = $flight['returnTotalTimeLabel'] ?? $flight['returnDurationLabel'] ?? '';
                                @endphp
                                <div class="bk-itin-leg" x-data="{ legOpen: true }">
                                    <div class="bk-itin-leg-head" @click="legOpen = !legOpen">
                                        <div>
                                            <div class="bk-itin-leg-route">
                                                {{ $retFirst['from'] ?? '' }}
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                                {{ $retLast['to'] ?? '' }}
                                                <span class="bk-outbound-badge">
                                                    Return
                                                </span>
                                            </div>
                                            <div class="bk-itin-leg-meta">
                                                @if(!empty($flight['returnDateLabel'])) <span>{{ $flight['returnDateLabel'] }}</span> @endif
                                                <span class="bk-itin-leg-badge {{ $retStops === 0 ? 'direct' : '' }}">
                                                    {{ $retStops === 0 ? 'Non stop' : $retStops . ' stop' . ($retStops > 1 ? 's' : '') }}
                                                </span>
                                                @if($retDuration) <span>· {{ $retDuration }}</span> @endif
                                            </div>
                                        </div>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :style="legOpen ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
                                    </div>

                                    <div x-show="legOpen" x-transition>
                                        <div class="bk-itin-leg-body">
                                            @foreach($retSegs as $si => $seg)
                                                @php
                                                    $equip    = $seg['equipment'] ?? '';
                                                    $equipLbl = $equipMap[$equip] ?? $equip;
                                                    $bagStr   = implode(' / ', array_unique(array_filter((array)($breakdown[0]['baggage'] ?? []), fn($v) => $v !== ''))) ?: '1 × 23kg';
                                                    $cabinBag = implode(' / ', array_unique(array_filter((array)($breakdown[0]['cabinBaggage'] ?? []), fn($v) => $v !== ''))) ?: '1 × 7kg';
                                                @endphp

                                                @if($si > 0 && !empty($flight['returnLayoverDurations'][$si-1]))
                                                    <div class="bk-layover-strip">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                        Layover in {{ $retSegs[$si-1]['toCity'] ?? $retSegs[$si-1]['to'] ?? '' }}
                                                        · {{ $flight['returnLayoverDurations'][$si-1] }}
                                                    </div>
                                                @endif

                                                <div class="bk-seg-group">
                                                    <div class="bk-seg-airline-bar">
                                                        <div class="bk-seg-airline-left">
                                                            @if(!empty($seg['airlineLogo']))
                                                                <img class="bk-seg-airline-logo" src="{{ $seg['airlineLogo'] }}" alt="{{ $seg['airline'] ?? '' }}">
                                                            @endif
                                                            <span class="bk-seg-airline-name">{{ $seg['airline'] ?? '' }}</span>
                                                            @if($equipLbl) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--gray-400);">{{ $equipLbl }}</span> @endif
                                                            @if(!empty($seg['flightNo'])) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--gray-400);">{{ $seg['flightNo'] }}</span> @endif
                                                        </div>
                                                        <span class="bk-seg-cabin-tag">{{ $seg['cabin'] ?? $cabin }}</span>
                                                    </div>
                                                    <div class="bk-seg-timeline">
                                                        <div class="bk-seg-spine">
                                                            <div class="bk-seg-dot"></div>
                                                            <div class="bk-seg-line"></div>
                                                            <div class="bk-seg-dot end"></div>
                                                        </div>
                                                        <div class="bk-seg-stops">
                                                            <div class="bk-seg-stop">
                                                                <div class="bk-seg-time">{{ $seg['departTime'] }}</div>
                                                                <div>
                                                                    <div class="bk-seg-place">{{ $seg['fromCity'] ?? $seg['from'] ?? '' }}</div>
                                                                    <div class="bk-seg-place-sub">{{ $seg['fromAirport'] ?? '' }}</div>
                                                                </div>
                                                                <div class="bk-seg-bags">
                                                                    <span class="bk-seg-bags-lbl">Baggage</span>
                                                                    <span class="bk-seg-bags-val">{{ $bagStr }}</span>
                                                                    <span class="bk-seg-bags-lbl" style="margin-top:4px;">Check In</span>
                                                                    <span class="bk-seg-bags-val">{{ $bagStr }}</span>
                                                                    <span class="bk-seg-bags-lbl" style="margin-top:4px;">Cabin</span>
                                                                    <span class="bk-seg-bags-val">{{ $cabinBag }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="bk-seg-stop" style="padding-bottom:0;">
                                                                <div class="bk-seg-time" style="color:var(--gray-500);font-size:12px;">{{ floor($seg['duration']/60) }}h {{ $seg['duration']%60 }}m</div>
                                                                <div></div><div></div>
                                                            </div>
                                                            <div class="bk-seg-stop" style="padding-top:4px;padding-bottom:0;">
                                                                <div class="bk-seg-time">{{ $seg['arriveTime'] }}</div>
                                                                <div>
                                                                    <div class="bk-seg-place">{{ $seg['toCity'] ?? $seg['to'] ?? '' }}</div>
                                                                    <div class="bk-seg-place-sub">{{ $seg['toAirport'] ?? '' }}</div>
                                                                </div>
                                                                <div></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Multi-city legs --}}
                            @if($isMulti)
                                @foreach($multiLegs as $li => $leg)
                                    @php
                                        $legSegs  = $leg['segments'] ?? [];
                                        $legFirst = $legSegs[0] ?? [];
                                        $legLast  = count($legSegs) > 0 ? $legSegs[count($legSegs)-1] : [];
                                        $legStops = $leg['stops'] ?? max(0, count($legSegs)-1);
                                    @endphp
                                    @if(!empty($legSegs))
                                        <div class="bk-itin-leg" x-data="{ legOpen: true }">
                                            <div class="bk-itin-leg-head" @click="legOpen = !legOpen">
                                                <div>
                                                    <div class="bk-itin-leg-route">
                                                        {{ $legFirst['from'] ?? '' }}
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                                        {{ $legLast['to'] ?? '' }}
                                                    </div>
                                                    <div class="bk-itin-leg-meta">
                                                        <span>Leg {{ $li + 1 }}</span>
                                                        @if(!empty($leg['departDateLabel'])) <span>· {{ $leg['departDateLabel'] }}</span> @endif
                                                        <span class="bk-itin-leg-badge {{ $legStops === 0 ? 'direct' : '' }}">{{ $legStops === 0 ? 'Non stop' : $legStops . ' stop' }}</span>
                                                        @if(!empty($leg['totalTimeLabel'])) <span>· {{ $leg['totalTimeLabel'] }}</span> @endif
                                                    </div>
                                                </div>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :style="legOpen ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
                                            </div>
                                            <div x-show="legOpen" x-transition>
                                                <div class="bk-itin-leg-body">
                                                    @foreach($legSegs as $si => $seg)
                                                        @if($si > 0 && !empty($leg['layoverDurations'][$si-1]))
                                                            <div class="bk-layover-strip">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                                Layover · {{ $leg['layoverDurations'][$si-1] }}
                                                            </div>
                                                        @endif
                                                        <div class="bk-seg-group">
                                                            <div class="bk-seg-airline-bar">
                                                                <div class="bk-seg-airline-left">
                                                                    @if(!empty($seg['airlineLogo'])) <img class="bk-seg-airline-logo" src="{{ $seg['airlineLogo'] }}" alt=""> @endif
                                                                    <span class="bk-seg-airline-name">{{ $seg['airline'] ?? '' }}</span>
                                                                    @if(!empty($seg['flightNo'])) <span style="font-size:11px;color:var(--gray-400);">· {{ $seg['flightNo'] }}</span> @endif
                                                                </div>
                                                                <span class="bk-seg-cabin-tag">{{ $seg['cabin'] ?? $cabin }}</span>
                                                            </div>
                                                            <div class="bk-seg-timeline">
                                                                <div class="bk-seg-spine">
                                                                    <div class="bk-seg-dot"></div>
                                                                    <div class="bk-seg-line"></div>
                                                                    <div class="bk-seg-dot end"></div>
                                                                </div>
                                                                <div class="bk-seg-stops">
                                                                    <div class="bk-seg-stop">
                                                                        <div class="bk-seg-time">{{ $seg['departTime'] }}</div>
                                                                        <div><div class="bk-seg-place">{{ $seg['fromCity'] ?? $seg['from'] ?? '' }}</div><div class="bk-seg-place-sub">{{ $seg['fromAirport'] ?? '' }}</div></div>
                                                                        <div></div>
                                                                    </div>
                                                                    <div class="bk-seg-stop" style="padding-bottom:0;">
                                                                        <div class="bk-seg-time" style="color:var(--gray-500);font-size:12px;">{{ floor($seg['duration']/60) }}h {{ $seg['duration']%60 }}m</div>
                                                                        <div></div><div></div>
                                                                    </div>
                                                                    <div class="bk-seg-stop" style="padding-top:4px;padding-bottom:0;">
                                                                        <div class="bk-seg-time">{{ $seg['arriveTime'] }}</div>
                                                                        <div><div class="bk-seg-place">{{ $seg['toCity'] ?? $seg['to'] ?? '' }}</div><div class="bk-seg-place-sub">{{ $seg['toAirport'] ?? '' }}</div></div>
                                                                        <div></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- ── 2. Extra Services (Baggage + Meals) ── --}}
                    @if($step === 2)
                    @if(!empty($baggageOutbound) || !empty($baggageInbound) || !empty($mealOutbound) || !empty($mealInbound))
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon" style="background:#f0fdf4;color:#059669;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Extra Services</div>
                                <div class="bk-acc-sub">Add baggage or meals to your booking</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body" style="padding-top:0;">
                        
                                {{-- ── EXTRA BAGGAGE ── --}}
                                @if(!empty($baggageOutbound) || !empty($baggageInbound))
                                <div style="padding:14px 0 10px;border-bottom:1px solid var(--gray-100);">
                        
                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                                        <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span>
                                        Extra Check-in Baggage
                                    </div>
                        
                                    @foreach([['outbound', $baggageOutbound], ['inbound', $baggageInbound]] as [$dir, $bagOpts])
                                    @if(!empty($bagOpts))
                                    <div style="margin-bottom:14px;">
                                        <div style="font-size:11.5px;font-weight:700;color:var(--gray-700);margin-bottom:8px;">
                                            {{ ucfirst($dir) }}
                                        </div>
                        
                                        <div style="display:flex;flex-direction:column;gap:8px;">
                                            @foreach($bagOpts as $svc)
                                            @php
                                                $svcId  = $svc['ServiceId'];
                                                $maxQty = (int) ($svc['MaximumQuantity'] ?? 3);
                                                $price  = (float) ($svc['ServiceCost']['Amount'] ?? 0);
                                                $curr   = $svc['ServiceCost']['CurrencyCode'] ?? 'USD';
                                                $sym    = match($curr) { 'NGN' => '₦', 'USD' => '$', 'AED' => 'AED ', default => $curr . ' ' };
                                                $currentQty = (int) ($selectedBaggage[$dir][$svcId] ?? 0);
                                            @endphp
                        
                                            <div style="display:flex;align-items:center;gap:14px;padding:11px 14px;
                                                        border:1.5px solid {{ $currentQty > 0 ? 'var(--blue)' : 'var(--gray-200)' }};
                                                        border-radius:10px;background:{{ $currentQty > 0 ? 'var(--blue-lt)' : '#fff' }};
                                                        transition:all .2s;">
                        
                                                {{-- Description --}}
                                                <div style="flex:1;">
                                                    <div style="font-size:13px;font-weight:700;color:var(--gray-900);">
                                                        {{ $svc['Description'] }}
                                                    </div>
                                                    <div style="font-size:11px;color:var(--gray-400);margin-top:2px;">
                                                        {{ $svc['FareDescription'] ?? 'per passenger' }}
                                                    </div>
                                                </div>
                        
                                                {{-- Price --}}
                                                <div style="font-size:13px;font-weight:800;color:var(--blue);
                                                            white-space:nowrap;font-family:var(--mono);min-width:70px;text-align:right;">
                                                    +{{ $sym }}{{ number_format($price, 2) }}
                                                    <div style="font-size:10px;color:var(--gray-400);font-weight:500;font-family:var(--font);">
                                                        per bag
                                                    </div>
                                                </div>
                        
                                                {{-- Quantity stepper --}}
                                                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                                    <button type="button"
                                                        style="width:28px;height:28px;border-radius:50%;border:1.5px solid var(--gray-200);
                                                            background:#fff;font-size:16px;color:var(--gray-700);cursor:pointer;
                                                            display:flex;align-items:center;justify-content:center;transition:all .15s;
                                                            {{ $currentQty === 0 ? 'opacity:.35;cursor:not-allowed;' : '' }}"
                                                        wire:click="$set('selectedBaggage.{{ $dir }}.{{ $svcId }}', {{ max(0, $currentQty - 1) }})"
                                                        {{ $currentQty === 0 ? 'disabled' : '' }}>
                                                        −
                                                    </button>
                        
                                                    <span style="font-size:16px;font-weight:800;color:var(--gray-900);
                                                                min-width:20px;text-align:center;">
                                                        {{ $currentQty }}
                                                    </span>
                        
                                                    <button type="button"
                                                        style="width:28px;height:28px;border-radius:50%;border:1.5px solid var(--gray-200);
                                                            background:#fff;font-size:16px;color:var(--gray-700);cursor:pointer;
                                                            display:flex;align-items:center;justify-content:center;transition:all .15s;
                                                            {{ $currentQty >= $maxQty ? 'opacity:.35;cursor:not-allowed;' : '' }}"
                                                        wire:click="$set('selectedBaggage.{{ $dir }}.{{ $svcId }}', {{ min($maxQty, $currentQty + 1) }})"
                                                        {{ $currentQty >= $maxQty ? 'disabled' : '' }}>
                                                        +
                                                    </button>
                                                </div>
                        
                                                {{-- Line total --}}
                                                @if($currentQty > 0)
                                                <div style="font-size:12px;font-weight:800;color:var(--green);
                                                            white-space:nowrap;font-family:var(--mono);min-width:65px;text-align:right;">
                                                    = {{ $sym }}{{ number_format($price * $currentQty, 2) }}
                                                </div>
                                                @endif
                        
                                                {{-- Hidden input for form submission --}}
                                                @if($currentQty > 0)
                                                <input type="hidden"
                                                    name="extra_baggage[{{ $dir }}][{{ $svcId }}]"
                                                    value="{{ $currentQty }}">
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                        
                                {{-- ── MEALS ── --}}
                                @if(!empty($mealOutbound) || !empty($mealInbound))
                                <div style="padding-top:14px;">
                        
                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                                        <span class="bk-mini-icon bk-icon-meal" aria-hidden="true"></span>
                                        Meal Preferences
                                    </div>
                        
                                    @foreach([['outbound', $mealOutbound], ['inbound', $mealInbound]] as [$dir, $mealSegs])
                                    @if(!empty($mealSegs))
                                    <div style="margin-bottom:14px;">
                                        <div style="font-size:11.5px;font-weight:700;color:var(--gray-700);margin-bottom:8px;">
                                            {{ ucfirst($dir) }}
                                        </div>
                        
                                        @foreach($mealSegs as $si => $segMeals)
                                        <div style="margin-bottom:10px;">
                                            <div style="font-size:10.5px;font-weight:700;color:var(--gray-400);
                                                        text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                                                Segment {{ $si + 1 }}
                                            </div>
                        
                                            <div style="display:flex;flex-direction:column;gap:7px;">
                                                @foreach($segMeals as $svc)
                                                @php
                                                    $svcId    = $svc['ServiceId'];
                                                    $price    = (float) ($svc['ServiceCost']['Amount'] ?? 0);
                                                    $curr     = $svc['ServiceCost']['CurrencyCode'] ?? 'AED';
                                                    $sym      = match($curr) { 'NGN' => '₦', 'USD' => '$', 'AED' => 'AED ', default => $curr . ' ' };
                                                    $isChecked = !empty($selectedMeals[$dir][$si][$svcId]);
                                                @endphp
                        
                                                <label style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                                                            border:1.5px solid {{ $isChecked ? 'var(--amber)' : 'var(--gray-200)' }};
                                                            border-radius:10px;cursor:pointer;
                                                            background:{{ $isChecked ? 'var(--amber-lt)' : '#fff' }};
                                                            transition:all .2s;">
                        
                                                    <input type="checkbox"
                                                        wire:model.live="selectedMeals.{{ $dir }}.{{ $si }}.{{ $svcId }}"
                                                        style="width:16px;height:16px;accent-color:var(--amber);
                                                                cursor:pointer;flex-shrink:0;">
                        
                                                    <div style="flex:1;">
                                                        <div style="font-size:12.5px;font-weight:700;color:var(--gray-900);">
                                                            {{ $svc['Description'] }}
                                                        </div>
                                                    </div>
                        
                                                    <div style="font-size:12.5px;font-weight:800;
                                                                color:{{ $isChecked ? 'var(--amber)' : 'var(--gray-500)' }};
                                                                white-space:nowrap;font-family:var(--mono);">
                                                        +{{ $sym }}{{ number_format($price, 2) }}
                                                    </div>
                        
                                                    @if($isChecked)
                                                    <span style="font-size:11px;font-weight:700;color:var(--amber);white-space:nowrap;">Added</span>
                                                    @endif
                        
                                                    {{-- Hidden input for form submission --}}
                                                    @if($isChecked)
                                                    <input type="hidden"
                                                        name="extra_meal[{{ $dir }}][{{ $si }}][]"
                                                        value="{{ $svcId }}">
                                                    @endif
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                        
                                {{-- ── LIVE EXTRAS SUMMARY (shows only when something selected) ── --}}
                                @if($extrasTotal > 0)
                                <div style="margin-top:14px;padding:12px 16px;background:var(--green-lt);
                                            border:1.5px solid #a7f3d0;border-radius:10px;
                                            display:flex;align-items:center;justify-content:space-between;">
                                    <span style="font-size:12.5px;font-weight:700;color:var(--green);">
                                        Extras selected
                                    </span>
                                    <span style="font-size:14px;font-weight:800;color:var(--green);font-family:var(--mono);">
                                        +{{ $esFmt(['ServiceCost' => ['CurrencyCode' => 'NGN', 'Amount' => $extrasTotal]]) }}
                                    </span>
                                </div>
                                @endif
                        
                            </div>
                        </div>
                    </div>

                    @else
                    {{-- Fallback banner if no extra services available --}}
                    <div class="bk-bags-banner">
                        <div class="bk-bags-icon"><span class="bk-icon-mask bk-icon-bag" style="width:18px;height:18px;" aria-hidden="true"></span></div>
                        <div class="bk-bags-text">
                            <div class="bk-bags-title">Add extra check-in bags</div>
                            <div class="bk-bags-sub">No additional baggage options available for this route</div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Fare Rules (from BaggageInfos + FareRules) ── --}}
                    @if(!empty($baggageInfos) || !empty($fareRulesList))
                    <div class="bk-acc" x-data="{ open: false }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon" style="background:#fef2f2;color:#dc2626;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Fare & Baggage Rules</div>
                                <div class="bk-acc-sub">Baggage allowance per segment</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body" style="padding-top:14px;">

                                {{-- Baggage per segment table --}}
                                @if(!empty($baggageInfos))
                                <div style="margin-bottom:16px;">
                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Baggage Allowance by Segment</div>
                                    <div style="border:1px solid var(--gray-200);border-radius:9px;overflow:hidden;">
                                        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                                            <thead>
                                                <tr style="background:var(--gray-50);">
                                                    <th style="padding:9px 14px;text-align:left;font-weight:700;color:var(--gray-500);font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--gray-200);">Flight</th>
                                                    <th style="padding:9px 14px;text-align:left;font-weight:700;color:var(--gray-500);font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--gray-200);">Route</th>
                                                    <th style="padding:9px 14px;text-align:left;font-weight:700;color:var(--gray-500);font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--gray-200);">Allowance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($baggageInfos as $bi => $bagInfo)
                                                    @php $b = $bagInfo['BaggageInfo'] ?? $bagInfo; @endphp
                                                    <tr style="{{ $bi % 2 === 0 ? '' : 'background:var(--gray-50);' }}">
                                                        <td style="padding:10px 14px;font-weight:700;color:var(--blue);font-family:var(--mono);border-bottom:1px solid var(--gray-100);">
                                                            {{ $b['FlightNo'] ?? '—' }}
                                                        </td>
                                                        <td style="padding:10px 14px;color:var(--gray-700);font-weight:600;border-bottom:1px solid var(--gray-100);">
                                                            {{ $b['Departure'] ?? '' }} → {{ $b['Arrival'] ?? '' }}
                                                        </td>
                                                        <td style="padding:10px 14px;border-bottom:1px solid var(--gray-100);">
                                                            <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:var(--green-lt);color:var(--green);border-radius:999px;font-size:11.5px;font-weight:700;">
                                                                <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span> {{ $b['Baggage'] ?? '—' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                                {{-- Fare Rules per city pair --}}
                                @if(!empty($fareRulesList))
                                <div>
                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Fare Rules by Route</div>
                                    @foreach($fareRulesList as $frItem)
                                        @php
                                            $fr = $frItem['FareRule'] ?? $frItem;
                                            $rawRules = (string)($fr['Rules'] ?? '');
                                            $rulesText = trim(strip_tags(preg_replace('/<(br|\/p|\/div|\/li)>/i', "\n", html_entity_decode($rawRules, ENT_QUOTES | ENT_HTML5))));
                                        @endphp
                                        <div style="border:1px solid var(--gray-200);border-radius:9px;margin-bottom:8px;overflow:hidden;">
                                            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 14px;background:var(--gray-50);border-bottom:1px solid var(--gray-100);">
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span style="font-size:11.5px;font-weight:700;color:var(--navy);font-family:var(--mono);">{{ $fr['Airline'] ?? '' }}</span>
                                                    <span style="font-size:12px;font-weight:700;color:var(--gray-700);">
                                                        {{ substr($fr['CityPair'] ?? '', 0, 3) }} → {{ substr($fr['CityPair'] ?? '', 3, 3) }}
                                                    </span>
                                                </div>
                                                <span style="font-size:10.5px;padding:2px 8px;border-radius:999px;background:var(--blue-lt);color:var(--blue);font-weight:700;">
                                                    {{ $fr['Category'] ?? 'General' }}
                                                </span>
                                            </div>
                                            @if($rulesText !== '')
                                            <div style="padding:12px 14px;font-size:12px;color:var(--gray-600);line-height:1.7;white-space:pre-wrap;">{{ $rulesText }}</div>
                                            @else
                                            <div style="padding:12px 14px;font-size:12px;color:var(--gray-400);font-style:italic;">No specific rules text available for this route.</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── 4. Passenger Count ── --}}
                    <div class="bk-actions">
                        <button class="bk-btn-ghost" wire:click="back">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Traveller Info
                        </button>
                        <button class="bk-btn-next" wire:click="proceed" wire:loading.attr="disabled" wire:target="proceed">
                            <span wire:loading.remove wire:target="proceed" style="display:inline-flex;align-items:center;gap:7px;color:#fff;">
                                Review Booking
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
                            <span wire:loading wire:target="proceed">Updating…</span>
                        </button>
                    </div>
                    @endif

                    @if($step === 1)
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Passengers</div>
                                <div class="bk-acc-sub">{{ $this->getTotalPassengers() }} passenger{{ $this->getTotalPassengers() > 1 ? 's' : '' }} · adjust if needed</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">
                                <div>
                                    <div class="bk-pax-counter"> 
                                        <div class="bk-pax-col">
                                            <div class="bk-pax-info">
                                                <div>
                                                    <div class="bk-pax-col-label">Adults</div>
                                                    <div class="bk-pax-col-sub">18+ years</div>
                                                </div>

                                                <div class="bk-pax-ctr">
                                                    <span class="bk-pax-num">{{ $this->adultCount }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bk-pax-col">
                                            <div class="bk-pax-info">
                                                <div>
                                                    <div class="bk-pax-col-label">Children</div>
                                                    <div class="bk-pax-col-sub">2–12 years</div>
                                                </div>

                                                <div class="bk-pax-ctr">
                                                    <span class="bk-pax-num">{{ $this->childCount }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bk-pax-col">
                                            <div class="bk-pax-info">
                                                <div>
                                                    <div class="bk-pax-col-label">Infants</div>
                                                    <div class="bk-pax-col-sub">Under 2</div>
                                                </div>
                                                <div class="bk-pax-ctr">
                                                    <span class="bk-pax-num">{{ $this->infantCount }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bk-total-bar">
                                    <span class="bk-total-label" style="display:inline-flex;align-items:center;gap:7px;"><span class="bk-inline-icon bk-icon-users" aria-hidden="true"></span> Total passengers</span>
                                    <span class="bk-total-val">{{ $this->getTotalPassengers() }} passenger{{ $this->getTotalPassengers() > 1 ? 's' : '' }}</span>
                                </div>
                                @error('passengers') <span class="bk-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── 5. Traveller Details (accordion, one card per passenger) ── --}}
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Traveller Details</div>
                                <div class="bk-acc-sub">Names must match ID or passport exactly</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">

                                @foreach($this->passengers as $i => $pax)
                                    @php
                                        $typeLabel  = match($pax['type']) { 'ADT' => 'Adult (18 yrs+)', 'CHD' => 'Child (2–12 yrs)', 'INF' => 'Infant (under 2)', default => 'Passenger' };
                                        $badgeClass = strtolower($pax['type']);
                                        $showPp     = !empty($pax['show_passport']);
                                        $hasPpData  = !empty($pax['passport_no']);
                                        $isComplete = !empty($pax['first_name']) && !empty($pax['last_name']) && !empty($pax['dob']);
                                        $filledCount= (int)!empty($pax['first_name']) + (int)!empty($pax['last_name']) + (int)!empty($pax['dob']) + (int)!empty($pax['nationality']);
                                        $titleOptions = in_array($pax['type'], ['CHD', 'INF'], true)
                                            ? ['Master', 'Miss']
                                            : ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Master'];
                                    @endphp

                                    <div class="bk-pax-card" wire:key="pax-{{ $i }}-{{ $pax['type'] }}"
                                        x-data="{ cardOpen: {{ $i === 0 ? 'true' : 'false' }} }">

                                        <div class="bk-pax-card-head" @click="cardOpen = !cardOpen">
                                            <span class="bk-pax-badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                                            <span class="bk-pax-num-lbl">
                                                @if($pax['is_primary']) <span class="bk-primary-chip">Primary</span> @endif
                                                Passenger {{ $i + 1 }}
                                            </span>
                                            @if($isComplete)
                                                <span class="bk-pax-complete">{{ strtoupper($pax['first_name']) }} {{ strtoupper($pax['last_name']) }}</span>
                                            @else
                                                <span class="bk-pax-progress">{{ $filledCount }}/4 added</span>
                                            @endif
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :style="cardOpen ? 'transform:rotate(180deg)' : ''" style="color:var(--gray-400);flex-shrink:0;transition:transform .2s;"><polyline points="6 9 12 15 18 9"/></svg>
                                        </div>

                                        <div x-show="cardOpen" x-transition>
                                            <div class="bk-pax-notice">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                Enter your name as it is mentioned on your passport. Passport should be valid for a minimum of 6 months from date of travel.
                                            </div>

                                            <div class="bk-form-grid">
                                                <div class="bk-field">
                                                    <label class="bk-label">Title <span class="bk-req">*</span></label>
                                                    <select class="bk-select" wire:model="passengers.{{ $i }}.title">
                                                        <option value="">–</option>
                                                        @foreach($titleOptions as $t)
                                                            <option value="{{ $t }}">{{ $t }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("passengers.{$i}.title") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="bk-field">
                                                    <label class="bk-label">Last Name <span class="bk-req">*</span></label>
                                                    <input class="bk-input" type="text" placeholder="Last Name"
                                                        wire:model.blur="passengers.{{ $i }}.last_name">
                                                    @error("passengers.{$i}.last_name") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>
                                            
                                                <div class="bk-field">
                                                    <label class="bk-label">First Name <span class="bk-req">*</span></label>
                                                    <input class="bk-input" type="text" placeholder="First Name"
                                                        wire:model.blur="passengers.{{ $i }}.first_name">
                                                    @error("passengers.{$i}.first_name") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="bk-field">
                                                    <label class="bk-label">Middle Name</label>
                                                    <input class="bk-input" type="text" placeholder="Middle Name"
                                                        wire:model.blur="passengers.{{ $i }}.middle_name">
                                                </div>

                                                <div class="bk-field">
                                                    <label class="bk-label">Date of Birth <span class="bk-req">*</span></label>
                                                    <input class="bk-input" type="date"
                                                        wire:model.blur="passengers.{{ $i }}.dob"
                                                        placeholder="yyyy-mm-dd"
                                                        max="{{ now()->subDay()->format('Y-m-d') }}">
                                                    @if($pax['type'] === 'CHD') <span class="bk-hint">Must be 2–12 years old at travel</span>
                                                    @elseif($pax['type'] === 'INF') <span class="bk-hint">Must be under 2 at travel</span> @endif
                                                    @error("passengers.{$i}.dob") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="bk-field">
                                                    <label class="bk-label">Nationality <span class="bk-req">*</span></label>
                                                    <select class="bk-select" wire:model="passengers.{{ $i }}.nationality">
                                                        @foreach($this->nationalities as $code => $name)
                                                            <option value="{{ $code }}">{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("passengers.{$i}.nationality") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="bk-field">
                                                    <label class="bk-label">Gender <span class="bk-req">*</span></label>
                                                    <div class="bk-radio-group">
                                                        <label class="bk-radio-opt">
                                                            <input type="radio" wire:model="passengers.{{ $i }}.gender" value="M"> Male
                                                        </label>
                                                        <label class="bk-radio-opt">
                                                            <input type="radio" wire:model="passengers.{{ $i }}.gender" value="F"> Female
                                                        </label>
                                                    </div>
                                                    @error("passengers.{$i}.gender") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="bk-field">
                                                    <label class="bk-label">Passport Number</label>
                                                    <input class="bk-input" type="text" placeholder="e.g. A12345678"
                                                            wire:model.blur="passengers.{{ $i }}.passport_no">
                                                    @error("passengers.{$i}.passport_no") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="bk-field">
                                                    <label class="bk-label">Expiry Date</label>
                                                    <input class="bk-input" type="date"
                                                            wire:model.blur="passengers.{{ $i }}.passport_exp"
                                                            min="{{ now()->addDay()->format('Y-m-d') }}">
                                                    <span class="bk-hint">Must be valid beyond travel dates</span>
                                                    @error("passengers.{$i}.passport_exp") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>
                                                {{-- Passport Issuing Country (passportIssueCountry in API) --}}
                                                <div class="bk-field">
                                                    <label class="bk-label">Passport Issuing Country</label>
                                                    <select class="bk-select" wire:model="passengers.{{ $i }}.passport_issue_country">
                                                        @foreach($this->nationalities as $code => $name)
                                                            <option value="{{ $code }}">{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("passengers.{$i}.passport_issue_country") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>

                                                {{-- Passport Issue Date (passportIssueDate in API) --}}
                                                <div class="bk-field">
                                                    <label class="bk-label">Passport Issue Date</label>
                                                    <input class="bk-input" type="date"
                                                            wire:model.blur="passengers.{{ $i }}.passport_issue_date"
                                                            max="{{ now()->format('Y-m-d') }}">
                                                    <span class="bk-hint">Date passport was issued</span>
                                                    @error("passengers.{$i}.passport_issue_date") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="bk-field">
                                                    <label class="bk-label">Frequent Flyer Number</label>
                                                    <input class="bk-input" type="text" placeholder="e.g. BA12345678"
                                                        wire:model.blur="passengers.{{ $i }}.frequent_flyer_number">
                                                    <span class="bk-hint">Optional — enter your airline loyalty number</span>
                                                    @error("passengers.{$i}.frequent_flyer_number") <span class="bk-error">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                                
                                                
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>

                    {{-- ── 6. Contact Details (accordion) ── --}}
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Contact Details</div>
                                <div class="bk-acc-sub">E-ticket and confirmation sent here</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">
                                <div class="bk-contact-grid">
                                    <div class="bk-field">
                                        <label class="bk-label">Email Address <span class="bk-req">*</span></label>
                                        <input class="bk-input" type="email" placeholder="you@example.com"
                                            wire:model.blur="contactEmail">
                                        @error('contactEmail') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="bk-field">
                                        <label class="bk-label">Confirm Email <span class="bk-req">*</span></label>
                                        <input class="bk-input" type="email" placeholder="Re-enter email"
                                            wire:model.blur="contactEmailConfirm">
                                        @error('contactEmailConfirm') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>
                                </div> 
                                <div class="bk-contact-grid bk-phone-grid" style="margin-top:15px;">
                                    <div class="bk-field">
                                        <label class="bk-label">Mobile Number <span class="bk-req">*</span></label>
                                        <input class="bk-input bk-phone-input" type="tel" placeholder="+234 800 000 0000"
                                                                                wire:model.blur="contactPhoneFull">
                                        <span class="bk-hint">Include country code. We will format it for ticketing.</span>
                                        @error('contactPhoneFull') <span class="bk-error">{{ $message }}</span> @enderror
                                        @error('contactPhone') <span class="bk-error">{{ $message }}</span> @enderror
                                        @error('contactAreaCode') <span class="bk-error">{{ $message }}</span> @enderror
                                        @error('contactCountryCode') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="bk-field" style="display:none;">
                                        <label class="bk-label">Area Code <span class="bk-req"></span></label>
                                        <input class="bk-input" type="text" placeholder="e.g. 080"
                                                                                wire:model.blur="contactAreaCode">
                                        <span class="bk-hint">Local area code</span>
                                        @error('contactAreaCode') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="bk-field bk-contact-full" style="display:none;">
                                        <label class="bk-label">Mobile No <span class="bk-req"></span></label>
                                        <input class="bk-input" type="tel" placeholder="+234 800 000 0000"
                                                                                wire:model.blur="contactPhone">
                                        <span class="bk-hint">without country code · e.g. 800 000 0000</span>
                                        @error('contactPhone') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>

                
                    {{-- Actions --}}
                    <div class="bk-actions">
                        <a href="{{ route('air.flight-s') }}" class="bk-btn-ghost">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Go Back
                        </a>
                        <button class="bk-btn-next"
                                wire:click="proceed"
                                wire:loading.attr="disabled"
                                wire:target="proceed">
                            <span wire:loading.remove wire:target="proceed" style="display:inline-flex;align-items:center;gap:7px; color:#fff;">
                                Continue
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
                            <span wire:loading wire:target="proceed">Validating…</span>
                        </button>
                    </div>

        

                    @endif {{-- /STEP 1 TRAVELLER DETAILS --}}
                @endif {{-- /STEPS 1 AND 2 --}}

                {{-- ════════ STEP 2 ════════ --}}
                @if($step === 3)

                    <div class="bk-notice info" style="margin-bottom:4px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>
                            {{ $isTravelFlexCheckout
                                ? 'Review all details carefully before continuing to TravelFlex. No payment is due at this step.'
                                : 'Review all details carefully before payment. Name corrections after ticketing may incur fees.' }}
                        </span>
                    </div>

                    {{-- Review accordion --}}
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Booking Summary</div>
                                <div class="bk-acc-sub">
                                    {{ $isTravelFlexCheckout
                                        ? 'Confirm all details before starting your TravelFlex application'
                                        : 'Confirm all details are correct before paying' }}
                                </div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">

                                <div class="bk-review-section">
                                    <div class="bk-review-title">Passengers ({{ $this->getTotalPassengers() }})</div>
                                    @foreach($this->passengers as $i => $pax)
                                        @php
                                            $ptLabel = match($pax['type']) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' };
                                            $dobStr  = !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—';
                                            $natName = $this->nationalities[$pax['nationality']] ?? $pax['nationality'];
                                        @endphp
                                        <div class="bk-review-row">
                                            <span class="bk-review-label">{{ $ptLabel }} {{ $i + 1 }}{{ $pax['is_primary'] ? ' · Primary' : '' }}</span>
                                            <span class="bk-review-val">
                                                {{ $pax['title'] }} {{ strtoupper($pax['first_name']) }} {{ strtoupper($pax['last_name']) }}
                                                <br><span style="font-size:11px;color:var(--gray-500);font-weight:500;">DOB: {{ $dobStr }} · {{ $natName }}@if(!empty($pax['passport_no'])) · Passport: {{ $pax['passport_no'] }} @endif</span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="bk-review-section">
                                    <div class="bk-review-title">Contact</div>
                                    <div class="bk-review-row">
                                        <span class="bk-review-label">Email</span>
                                        <span class="bk-review-val">{{ $contactEmail }}</span>
                                    </div>
                                    <div class="bk-review-row">
                                        <span class="bk-review-label">Phone</span>
                                        <span class="bk-review-val">{{ $contactPhoneFull ?: ('+' . $contactCountryCode . ' ' . $contactPhone) }}</span>
                                    </div>
                                </div>

                                <div class="bk-review-section" style="margin-bottom:0;">
                                    <div class="bk-review-title">Fare Policy</div>
                                    @foreach($breakdown as $fb)
                                        @php
                                            $ptl    = match($fb['passengerType'] ?? '') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                                            $bagStr = implode(' / ', array_unique(array_filter((array)($fb['baggage'] ?? []), fn($v) => $v !== ''))) ?: '—';
                                            $refund = !empty($fb['refundAllowed']);
                                            $change = !empty($fb['changeAllowed']);
                                        @endphp
                                        <div class="bk-review-row">
                                            <span class="bk-review-label">{{ $ptl }} · Baggage</span>
                                            <span class="bk-review-val">{{ $bagStr }}</span>
                                        </div>
                                    @endforeach
                                    <div class="bk-review-row">
                                            <span class="bk-review-label">Refund</span>
                                            <span class="bk-review-val" style="color:{{ !empty($flight['isRefundable']) ? 'var(--green)' : 'var(--red)' }}">{{ !empty($flight['isRefundable']) ? 'Allowed' : 'Not allowed' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="bk-form" method="POST" action="{{ route('flights.book') }}" style="display:none;">
                        @csrf
                        <input type="hidden" name="fare_source_code" value="{{ $flight['flight']['fareSourceCode'] ?? $flight['fareSourceCode'] ?? '' }}">
                        <input type="hidden" name="session_id"            value="{{ $sessionId }}">
                        <input type="hidden" name="intent"                value="{{ session('bookingIntent', 'booking') }}">
                        <input type="hidden" name="contact[email]"        value="{{ $contactEmail }}">
                        <input type="hidden" name="contact[phone]"        value="{{ $contactPhone }}">
                        <input type="hidden" name="contact[area_code]"    value="{{ $contactAreaCode }}">
                        <input type="hidden" name="contact[country_code]" value="{{ $contactCountryCode }}">
                        @foreach($this->passengers as $i => $pax)
                        @foreach([
                        'type','title','first_name','middle_name','last_name',
                        'gender','dob','nationality',
                        'passport_no','passport_issue_country','passport_issue_date','passport_exp', 'frequent_flyer_number'
                        ] as $field)
                        <input type="hidden" name="passengers[{{ $i }}][{{ $field }}]" value="{{ $pax[$field] ?? '' }}">
                        @endforeach
                        @endforeach

                        {{-- ── Extra Services (baggage) ── --}}
                        @foreach($selectedBaggage as $direction => $items)
                            @foreach($items as $svcId => $qty)
                                @if($qty > 0)
                                <input type="hidden" name="extra_baggage[{{ $direction }}][{{ $svcId }}]" value="{{ $qty }}">
                                @endif
                            @endforeach
                        @endforeach

                        {{-- ── Extra Services (meals) ── --}}
                        @foreach($selectedMeals as $direction => $segments)
                            @foreach($segments as $segmentIndex => $items)
                                @foreach($items as $svcId => $checked)
                                    @if($checked)
                                    <input type="hidden" name="extra_meal[{{ $direction }}][{{ $segmentIndex }}][{{ $svcId }}]" value="1">
                                    @endif
                                @endforeach
                            @endforeach
                        @endforeach
                    </form>

                

                    <div class="bk-actions">
                        <button class="bk-btn-ghost" wire:click="back">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Edit Details
                        </button>
                        <button class="bk-btn-pay" @click="submitForm()">
                            @if($isTravelFlexCheckout)
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                Continue to TravelFlex
                            @else
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                Confirm &amp; Pay {{ $fmt($this->getTotalPrice()) }}
                            @endif
                        </button>
                    </div>

                @endif {{-- /step 2 --}}

            </div>{{-- /bk-main --}}


            {{-- ══════════════ RIGHT RAIL: MY CART ══════════════ --}}
            <aside class="bk-rail">

                {{-- My Cart --}}
                <div class="bk-cart">
                    <div class="bk-cart-head">
                        <div class="bk-cart-title">Booking Summary</div>
                        <div class="bk-cart-subtitle">
                            {{ $isTravelFlexCheckout
                                ? 'Fare, route and extras for your TravelFlex application'
                                : 'Fare, route and extras reviewed before payment' }}
                        </div>
                    </div>
                    <div class="bk-cart-body">
                        <div class="bk-cart-section">
                            <div class="bk-cart-section-lbl">Flight</div>

                            @if($isMulti && !empty($allLegs))
                                @foreach($allLegs as $leg)
                                    <div class="bk-cart-flight-row">
                                        <span class="bk-cart-plane"><span class="bk-icon-mask bk-icon-plane" style="width:14px;height:14px;" aria-hidden="true"></span></span>
                                        <div>
                                            <div class="bk-cart-route">{{ $leg['route'] ?? '' }}</div>
                                            <div class="bk-cart-sub">{{ $cabin }} · {{ $leg['label'] ?? 'Multi-city' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                {{-- Outbound --}}
                                <div class="bk-cart-flight-row">
                                    <span class="bk-cart-plane"><span class="bk-icon-mask bk-icon-plane" style="width:14px;height:14px;" aria-hidden="true"></span></span>
                                    <div>
                                        <div class="bk-cart-route">{{ ($firstSeg['from'] ?? '') }} to {{ ($lastSeg['to'] ?? '') }} ({{ strtoupper($firstSeg['from'] ?? '') }})</div>
                                        <div class="bk-cart-sub">{{ $cabin }} · {{ $tripLabel }}</div>
                                    </div>
                                </div>

                                {{-- Return --}}
                                @if($isReturn && !empty($retSegs))
                                    <div class="bk-cart-flight-row">
                                        <span class="bk-cart-plane" style="transform:scaleX(-1);"><span class="bk-icon-mask bk-icon-plane" style="width:14px;height:14px;" aria-hidden="true"></span></span>
                                        <div>
                                            <div class="bk-cart-route">{{ ($retSegs[0]['from'] ?? '') }} to {{ ($retSegs[count($retSegs)-1]['to'] ?? '') }} ({{ strtoupper($retSegs[0]['from'] ?? '') }})</div>
                                            <div class="bk-cart-sub">{{ $cabin }} · Round Trip</div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Fare summary using real revalidate data --}}
                    

                    <div class="bk-fare-section">
                        <div class="bk-fare-title">Flight Fare Summary</div>
                    
                        @foreach($breakdown as $fb)
                        @php
                            $ptCode  = $fb['passengerType'] ?? ($fb['PassengerTypeQuantity']['Code'] ?? 'ADT');
                            $ptQty   = (int)($fb['qty'] ?? ($fb['PassengerTypeQuantity']['Quantity'] ?? 1));
                            $ptLabel = match($ptCode) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                            $paxFare = $fb['PassengerFare'] ?? [];
                            $base    = (float)($fb['baseFare'] ?? ($paxFare['BaseFare']['Amount'] ?? 0));
                            $taxes   = $fb['taxes'] ?? ($paxFare['Taxes'] ?? []);
                            $serviceTax  = (float)($fb['serviceTax']  ?? ($paxFare['ServiceTax']['Amount']  ?? 0));
                            $surcharges  = (float)($fb['surcharges']  ?? ($paxFare['Surcharges']['Amount']  ?? 0));
                    
                            $taxGroups = [];
                            foreach ($taxes as $tax) {
                                $code = $tax['TaxCode'] ?? 'OtherTaxes';
                                $taxGroups[$code] = ($taxGroups[$code] ?? 0) + (float)($tax['Amount'] ?? 0);
                            }
                            $rawTaxBreakdownTotal = array_sum($taxGroups);
                            $rawTotalPaxFare = (float)($fb['totalFare'] ?? ($paxFare['TotalFare']['Amount'] ?? 0));
                            $hasNegativeTaxLine = collect($taxGroups)->contains(fn($amount) => $amount < 0);
                            $preferredTaxTotal = $serviceTax > 0
                                ? $serviceTax + $surcharges
                                : ($hasNegativeTaxLine ? 0.0 : max($rawTaxBreakdownTotal + $surcharges, 0));
                    
                            if ($rawTotalPaxFare <= 0) { $totalPaxFare = $base + $preferredTaxTotal; }
                            elseif ($rawTotalPaxFare < $base) { $totalPaxFare = $base + $rawTotalPaxFare; }
                            else { $totalPaxFare = $rawTotalPaxFare; }
                    
                            $derivedTaxAmt = max($totalPaxFare - $base, 0);
                            if ($hasNegativeTaxLine || $rawTaxBreakdownTotal <= 0 || abs($preferredTaxTotal - $derivedTaxAmt) > 1) {
                                $taxGroups   = $derivedTaxAmt > 0 ? ['OtherTaxes' => $derivedTaxAmt] : [];
                                $totalTaxAmt = $derivedTaxAmt;
                            } else {
                                $totalTaxAmt = $preferredTaxTotal;
                            }
                            $baseTotal = $base * $ptQty;
                            $taxTotal  = $totalTaxAmt * $ptQty;
                            $bagArr = (array)($fb['baggage']      ?? ($fb['Baggage']      ?? []));
                            $cabArr = (array)($fb['cabinBaggage'] ?? ($fb['CabinBaggage'] ?? []));
                            $bagStr = implode(', ', array_unique(array_filter($bagArr))) ?: '—';
                            $cabStr = implode(', ', array_unique(array_filter($cabArr))) ?: '—';
                        @endphp
                    
                        <div style="padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid var(--gray-100);"
                            x-data="{ showTax: false }">
                    
                            <div class="bk-fare-row" style="padding-bottom:4px;">
                                <span class="bk-fare-lbl" style="font-weight:700;color:var(--gray-700);">{{ $ptLabel }} × {{ $ptQty }}</span>
                                <span class="bk-fare-val" style="font-weight:800;">{{ $fmt($totalPaxFare * $ptQty) }}</span>
                            </div>
                            <div class="bk-fare-row">
                                <span class="bk-fare-lbl">Base Fare</span>
                                <span class="bk-fare-val">{{ $fmt($baseTotal) }}</span>
                            </div>
                            <div class="bk-fare-row">
                                <span class="bk-fare-lbl">
                                    Taxes & Fees
                                    <button type="button" @click="showTax = !showTax"
                                        style="background:none;border:none;color:var(--blue);cursor:pointer;
                                            font-size:11px;font-family:var(--font);padding:0;margin-left:4px;"
                                        x-text="showTax ? '▲ hide' : '▼ breakdown'">
                                    </button>
                                </span>
                                <span class="bk-fare-val">{{ $fmt($taxTotal) }}</span>
                            </div>
                            <div x-show="showTax" x-transition style="margin:4px 0 2px;">
                                <div class="bk-tax-detail">
                                    @foreach($taxGroups as $code => $amount)
                                    <div class="bk-tax-row">
                                        <span class="bk-tax-lbl">
                                            {{ $taxLabels[$code] ?? $code }}
                                            <span class="bk-tax-code">({{ $code }})</span>
                                        </span>
                                        <span class="bk-tax-val">{{ $fmt($amount * $ptQty) }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--green);font-weight:600;background:var(--green-lt);padding:2px 8px;border-radius:999px;">
                                    <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span> {{ $bagStr }}
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--blue);font-weight:600;background:var(--blue-lt);padding:2px 8px;border-radius:999px;">
                                    <span class="bk-mini-icon bk-icon-cabin" aria-hidden="true"></span> {{ $cabStr }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    
                        {{-- ── EXTRAS LINE — only shows when something selected ── --}}
                        @if($markupAmount > 0)
                        <div style="padding:10px 0;margin-bottom:10px;border-bottom:1px solid var(--gray-100);">
                            <div class="bk-fare-row">
                                <span class="bk-fare-lbl" style="font-weight:700;color:var(--gray-700);">Service charge</span>
                                <span class="bk-fare-val" style="font-weight:800;">{{ $fmt($markupAmount) }}</span>
                            </div>
                        </div>
                        @endif

                        @if($extrasTotal > 0)
                        <div style="padding:10px 0;border-bottom:1px solid var(--gray-100);">
                            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;
                                        color:var(--gray-400);margin-bottom:8px;">Extras Added</div>
                    
                            {{-- Baggage lines --}}
                            @foreach($selectedBaggage as $dir => $items)
                                @foreach($items as $svcId => $qty)
                                    @if($qty > 0)
                                    @php
                                        // Find the service from parsed extras
                                        $matchedSvc = null;
                                        foreach ($dynBaggage as $bag) {
                                            $bagDir = str_contains(strtoupper($bag['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                                            if ($bagDir === $dir) {
                                                foreach (($bag['Services'][0] ?? []) as $s) {
                                                    if ((string)$s['ServiceId'] === (string)$svcId) { $matchedSvc = $s; break 2; }
                                                }
                                            }
                                        }
                                        $bagPrice = (float) ($matchedSvc['ServiceCost']['Amount'] ?? 0);
                                        $bagCurr  = $matchedSvc['ServiceCost']['CurrencyCode'] ?? 'USD';
                                        $bagSym   = match($bagCurr) { 'NGN' => '₦', 'USD' => '$', 'AED' => 'AED ', default => $bagCurr . ' ' };
                                        $lineTotal = $bagPrice * (int)$qty;
                                    @endphp
                                    <div class="bk-fare-row" style="padding:3px 0;">
                                        <span class="bk-fare-lbl" style="font-size:11.5px;">
                                            <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span> {{ $matchedSvc['Description'] ?? 'Baggage' }}
                                            <span style="color:var(--gray-400);font-size:10.5px;">× {{ $qty }} ({{ ucfirst($dir) }})</span>
                                        </span>
                                        <span class="bk-fare-val" style="color:var(--green);">
                                            +{{ $bagSym }}{{ number_format($lineTotal, 2) }}
                                        </span>
                                    </div>
                                    @endif
                                @endforeach
                            @endforeach
                    
                            {{-- Meal lines --}}
                            @foreach($selectedMeals as $dir => $segments)
                                @foreach($segments as $si => $items)
                                    @foreach($items as $svcId => $checked)
                                        @if($checked)
                                        @php
                                            $matchedMeal = null;
                                            foreach ($dynMeal as $meal) {
                                                $mDir = str_contains(strtoupper($meal['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                                                if ($mDir === $dir) {
                                                    foreach (($meal['Services'][$si] ?? []) as $s) {
                                                        if ((string)$s['ServiceId'] === (string)$svcId) { $matchedMeal = $s; break 2; }
                                                    }
                                                }
                                            }
                                            $mealPrice = (float) ($matchedMeal['ServiceCost']['Amount'] ?? 0);
                                            $mealCurr  = $matchedMeal['ServiceCost']['CurrencyCode'] ?? 'AED';
                                            $mealSym   = match($mealCurr) { 'NGN' => '₦', 'USD' => '$', 'AED' => 'AED ', default => $mealCurr . ' ' };
                                        @endphp
                                        <div class="bk-fare-row" style="padding:3px 0;">
                                            <span class="bk-fare-lbl" style="font-size:11.5px;">
                                                <span class="bk-mini-icon bk-icon-meal" aria-hidden="true"></span> {{ $matchedMeal['Description'] ?? 'Meal' }}
                                                <span style="color:var(--gray-400);font-size:10.5px;">(Seg {{ $si + 1 }}, {{ ucfirst($dir) }})</span>
                                            </span>
                                            <span class="bk-fare-val" style="color:var(--amber);">
                                                +{{ $mealSym }}{{ number_format($mealPrice, 2) }}
                                            </span>
                                        </div>
                                        @endif
                                    @endforeach
                                @endforeach
                            @endforeach
                    
                            {{-- Extras subtotal --}}
                            <div class="bk-fare-row" style="padding-top:6px;border-top:1px dashed var(--gray-200);margin-top:4px;">
                                <span class="bk-fare-lbl" style="font-weight:700;color:var(--gray-700);">Extras Subtotal</span>
                                <span class="bk-fare-val" style="font-weight:800;color:var(--green);">
                                    +{{ $esFmt(['ServiceCost' => ['CurrencyCode' => 'USD', 'Amount' => $extrasTotal]]) }}
                                </span>
                            </div>
                        </div>
                        @endif
                    
                        @if($discount > 0)
                        <div class="bk-fare-row">
                            <span class="bk-fare-lbl bk-fare-disc">Discount</span>
                            <span class="bk-fare-val bk-fare-disc">−{{ $fmt($discount) }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="bk-fare-total-row">
                        <div>
                            <span class="bk-fare-total-lbl">Trip Total</span>
                            @if($extrasTotal > 0)
                            <div style="font-size:10.5px;color:var(--gray-400);margin-top:2px;">
                                Flight + Extras
                            </div>
                            @endif
                        </div>
                        <div style="text-align:right;">
                            <span class="bk-fare-total-val">{{ $fmt($this->getTotalPrice()) }}</span>
                            @if($extrasTotal > 0)
                            <div style="font-size:10.5px;color:var(--gray-400);margin-top:1px;">
                                incl. {{ $esFmt(['ServiceCost' => ['CurrencyCode' => 'USD', 'Amount' => $extrasTotal]]) }} extras
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Promo codes --}}
                    <div class="bk-promo">
                        <details>
                            <summary class="bk-promo-summary">Have a promo code?</summary>
                            <div class="bk-promo-row">
                                <input class="bk-promo-input" type="text" placeholder="Enter promo code">
                                <button class="bk-promo-btn" type="button">Apply</button>
                            </div>
                        </details>
                    </div>

                    <div class="bk-rail-trust">
                        <div class="bk-rail-trust-item">
                            <svg class="bk-rail-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 13c0 5-3.5 7.5-7.5 8.8a1.5 1.5 0 0 1-1 0C7.5 20.5 4 18 4 13V6.5a1.5 1.5 0 0 1 1-1.4l6.5-2.4a1.5 1.5 0 0 1 1 0L19 5.1a1.5 1.5 0 0 1 1 1.4V13Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                            <span>Secure checkout. Your fare is reviewed before ticketing.</span>
                        </div>
                        <div class="bk-rail-trust-item">
                            <svg class="bk-rail-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/>
                                <path d="M8 9h8"/>
                                <path d="M8 13h5"/>
                            </svg>
                            <span>Need help? TravelWheel support is available for booking questions.</span>
                        </div>
                    </div>
                </div>

            </aside>

        </div>{{-- /bk-page --}}
    </div>{{-- /bk-wrap --}}
</div>{{-- /Livewire root --}}
