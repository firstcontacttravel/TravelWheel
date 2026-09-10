{{-- Plus Jakarta Sans used to be loaded here too, at 5 weights, but --font
     resolves to the real site-wide body font (--tw-font-sans, Open Sans —
     loaded once in layouts/app.blade.php) via its fallback chain below, not
     to Plus Jakarta Sans, which was never actually applied anywhere on this
     page. Only DM Mono (used for times/prices) is real. --}}
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    /* ── Reset & Base ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy:    var(--tw-brand, #303191);
        --blue:    var(--tw-brand, #303191);
        --blue-lt: #f1f1ff;
        --blue-md: #d7d8ff;
        --green:   var(--tw-accent, #00a859);
        --amber:   #d97706;
        --red:     #dc2626;
        --gray-50: var(--tw-surface-soft, #f8f9fc);
        --gray-100:var(--tw-surface-muted, #f2f4f7);
        --gray-200:var(--tw-line, #e6e8ee);
        --gray-300:#d5d9e2;
        --gray-400:var(--tw-subtle, #98a2b3);
        --gray-500:var(--tw-muted, #667085);
        --gray-600:#516079;
        --gray-700:var(--tw-text, #1f2937);
        --gray-900:var(--tw-ink, #111827);
        --radius:  var(--tw-radius-lg, 12px);
        --shadow:  var(--tw-shadow-sm, 0 1px 2px rgba(16,24,40,.06));
        --shadow-md: var(--tw-shadow-md, 0 8px 24px rgba(16,24,40,.07));
        --font: var(--tw-font-sans, 'Open Sans', sans-serif);
        --mono: 'DM Mono', monospace;
    }
    body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; line-height: 1.5; }
    body.sr-filter-open { overflow: hidden; }
    [x-cloak] { display: none !important; }

    .sr-container { width:100%; max-width:100vw; overflow-x:hidden; box-sizing:border-box; }
    .tw-flight-results-page,
    .sr-results-shell {
        width: 100%;
        background: linear-gradient(180deg, #ffffff 0%, var(--gray-50) 58%, #ffffff 100%);
        color: var(--gray-900);
        font-family: var(--font);
    }

    /* ── Top Search Bar ── */
    .sr-topbar { background: transparent; padding: 22px 16px 0; position: relative; z-index: 60; box-shadow: none; }
    .sr-topbar-inner { max-width: 960px; min-height: 76px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 17px 22px; overflow: hidden; scrollbar-width: none; position: relative; width: 100%; border: 1px solid rgba(255,255,255,.26); border-radius: 12px; background: radial-gradient(circle at 88% 16%, rgba(0,153,51,.26), transparent 30%), linear-gradient(105deg, #303191 0%, #254277 56%, #0c6b64 100%); box-shadow: 0 18px 36px rgba(48,49,145,.16); }
    .sr-topbar-inner::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(255,255,255,.08), transparent 32%, rgba(255,255,255,.05)); pointer-events: none; }
    .sr-topbar-inner::-webkit-scrollbar { display: none; }
    .sr-tb-copy { position: relative; z-index: 1; min-width: 0; display: flex; flex-direction: column; gap: 9px; }
    .sr-tb-route { display: flex; align-items: center; gap: 10px; color: #fff; font-size: var(--tw-text-xl, 20px); font-weight: 800; line-height: 1.2; min-width: 0; }
    .sr-tb-route-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sr-tb-route-arrow { color: rgba(255,255,255,.72); font-size: 18px; font-weight: 700; flex-shrink: 0; }
    .sr-tb-pin { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; color: #10b981; flex: 0 0 18px; }
    .sr-tb-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; color: rgba(255,255,255,.82); font-size: var(--tw-text-xs, 12px); font-weight: 600; }
    .sr-tb-meta-item { display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
    .sr-tb-meta-item svg { width: 13px; height: 13px; flex: 0 0 13px; color: rgba(255,255,255,.86); }
    .sr-tb-date-pill { padding: 3px 10px; border-radius: 999px; background: rgba(0,153,51,.22); color: #eafff1; }
    .sr-topbar-inner > .sr-tb-pill,
    .sr-topbar-inner > .sr-tb-sep { display: none; }
    .sr-tb-pill { display: flex; flex-direction: column; gap: 1px; padding: 6px 12px; border-radius: 8px; border: 1.5px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: all .15s; position: relative; }
    .sr-tb-pill:hover  { background: rgba(255,255,255,.13); border-color: rgba(255,255,255,.22); }
    .sr-tb-pill.tb-active { background: rgba(255,255,255,.15); border-color: rgba(37,99,235,.7); box-shadow: 0 0 0 2px rgba(37,99,235,.35); }
    .sr-tb-pill-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: rgba(255,255,255,.45); }
    .sr-tb-pill-value { font-size: 13px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 5px; }
    .sr-tb-edit-hint  { font-size: 9px; color: rgba(255,255,255,.3); font-weight: 400; }
    .sr-tb-sep  { width: 1px; height: 28px; background: rgba(255,255,255,.12); flex-shrink: 0; margin: 0 2px; }
    .sr-tb-arrow { color: rgba(255,255,255,.35); font-size: 15px; flex-shrink: 0; }
    .sr-tb-swap { width: 26px; height: 26px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,.2); background: rgba(255,255,255,.08); color: rgba(255,255,255,.7); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all .2s; padding: 0; }
    .sr-tb-swap:hover { background: rgba(255,255,255,.18); transform: rotate(180deg); }
    .sr-tb-search { margin-left: auto; flex-shrink: 0; padding: 0 22px; height: 38px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: var(--font); display: flex; align-items: center; gap: 7px; transition: background .15s; }
    .sr-tb-search:hover { background: #1d4ed8; }

    /* ── Edit Dropdown Panel ── */
    #tb-dropdown { background: #fff; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,.22), 0 4px 16px rgba(0,0,0,.1); min-width: 280px; max-width: 340px; overflow: hidden; animation: panelIn .18s ease both; }
    @keyframes panelIn { from { opacity: 0; transform: translateY(-6px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    .sr-edit-panel-head { padding: 12px 16px 10px; border-bottom: 1px solid var(--gray-100); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); }
    .sr-edit-panel-body { padding: 14px 16px 16px; }
    .sr-trip-opts { display: flex; gap: 8px; }
    .sr-trip-opt { flex: 1; padding: 8px 6px; border-radius: 9px; border: 1.5px solid var(--gray-200); background: #fff; text-align: center; font-size: 12px; font-weight: 600; color: var(--gray-500); cursor: pointer; transition: all .14s; }
    .sr-trip-opt:hover  { border-color: var(--blue-md); color: var(--blue); }
    .sr-trip-opt.active { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .sr-ac-wrap { position: relative; }
    .sr-ac-input { width: 100%; height: 44px; padding: 0 12px; border: 1.5px solid var(--gray-200); border-radius: 9px; font-size: 14px; color: var(--gray-900); background: var(--gray-50); outline: none; font-family: var(--font); transition: border-color .15s, box-shadow .15s; }
    .sr-ac-input:focus { border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
    .sr-ac-drop { display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 11px; box-shadow: 0 12px 36px rgba(0,0,0,.13); z-index: 400; overflow: hidden; max-height: 220px; overflow-y: auto; }
    .sr-ac-drop.open { display: block; }
    .sr-ac-item { display: flex; align-items: center; gap: 10px; padding: 9px 13px; cursor: pointer; border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    .sr-ac-item:last-child { border-bottom: none; }
    .sr-ac-item:hover { background: var(--blue-lt); }
    .sr-ac-iata { font-size: 12px; font-weight: 700; color: var(--blue); min-width: 30px; font-family: var(--mono); }
    .sr-ac-name { font-size: 12.5px; font-weight: 500; color: var(--gray-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sr-ac-city { font-size: 11px; color: var(--gray-400); }
    .sr-ac-empty { padding: 12px; text-align: center; font-size: 12.5px; color: var(--gray-400); }
    .sr-cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .sr-cal-title  { font-size: 13.5px; font-weight: 700; color: var(--gray-900); }
    .sr-cal-nav    { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--gray-200); background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-500); padding: 0; font-size: 16px; line-height: 1; transition: all .15s; }
    .sr-cal-nav:hover { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .sr-cal-grid   { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
    .sr-cal-dow    { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--gray-400); text-align: center; padding: 3px 0; }
    .sr-cal-day    { height: 32px; border-radius: 7px; border: none; background: none; font-size: 12.5px; color: var(--gray-900); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .13s; font-family: var(--font); width: 100%; padding: 0; }
    .sr-cal-day:hover:not(.dis):not(.emp) { background: var(--blue-lt); color: var(--blue); }
    .sr-cal-day.today    { font-weight: 700; color: var(--blue); }
    .sr-cal-day.selected { background: var(--blue) !important; color: #fff !important; font-weight: 700; }
    .sr-cal-day.dis  { color: var(--gray-300); cursor: not-allowed; }
    .sr-cal-day.emp  { visibility: hidden; pointer-events: none; }
    .sr-cal-done { display: block; width: 100%; margin-top: 12px; padding: 9px; background: var(--blue); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .15s; }
    .sr-cal-done:hover { background: #1e40af; }
    .sr-pax-row  { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
    .sr-pax-row:last-of-type { border-bottom: none; }
    .sr-pax-lbl  { font-size: 13px; font-weight: 600; color: var(--gray-900); }
    .sr-pax-sub  { font-size: 11px; color: var(--gray-400); margin-top: 1px; }
    .sr-pax-ctr  { display: flex; align-items: center; gap: 10px; }
    .sr-pax-btn  { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid var(--gray-200); background: #fff; font-size: 18px; color: var(--gray-700); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .14s; padding: 0; font-family: var(--font); line-height: 1; }
    .sr-pax-btn:hover { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .sr-pax-num  { font-size: 14px; font-weight: 700; color: var(--gray-900); min-width: 20px; text-align: center; }
    .sr-cabin-row   { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--gray-100); }
    .sr-cabin-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); margin-bottom: 8px; }
    .sr-cabin-opts  { display: flex; flex-wrap: wrap; gap: 6px; }
    .sr-cabin-opt   { padding: 5px 12px; border-radius: 999px; border: 1.5px solid var(--gray-200); font-size: 12px; font-weight: 600; color: var(--gray-500); cursor: pointer; transition: all .13s; }
    .sr-cabin-opt:hover  { border-color: var(--blue-md); color: var(--blue); }
    .sr-cabin-opt.active { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .sr-pax-done { display: block; width: 100%; margin-top: 14px; padding: 9px; background: var(--blue); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .15s; }
    .sr-pax-done:hover { background: #1e40af; }

    /* ── Page Layout ── */
    .sr-page {
        max-width: 1394px; margin: 0 auto; padding: 20px 16px 48px;
        display: grid; grid-template-columns: 270px minmax(0, 835px) 220px;
        gap: 18px; align-items: start; justify-content: center;
    }

    /* ── Filters (left rail) ── */
    .sr-sidebar {
        position: sticky; top: 18px;
        display: flex; flex-direction: column;
        background: #fff; border: 1px solid var(--gray-200);
        border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden;
    }
    .sr-filters-head { display: flex; align-items: center; gap: 9px; padding: 13px 16px; border-bottom: 1px solid var(--gray-200); }
    .sr-filters-head .sr-ic { color: var(--gray-500); }
    .sr-filters-title { font-size: 13px; font-weight: 700; color: var(--gray-900); }
    .sr-filters-count { font-size: 11px; font-weight: 600; color: var(--blue); background: var(--blue-lt); border-radius: 999px; padding: 2px 8px; }
    .sr-filters-reset { margin-left: auto; font-size: 11.5px; font-weight: 600; color: var(--blue); cursor: pointer; text-decoration: none; }
    .sr-filters-reset:hover { text-decoration: underline; }
    .sr-filters-reset[aria-disabled="true"] { color: var(--gray-400); cursor: default; text-decoration: none; }

    .sr-panel { background: transparent; border: 0; border-radius: 0; box-shadow: none; }
    .sr-panel ~ .sr-panel { border-top: 1px solid var(--gray-100); }
    .sr-panel-head { display: flex; align-items: center; gap: 8px; padding: 14px 16px 0; border: 0; }
    .sr-panel-title { font-size: 12px; font-weight: 700; color: var(--gray-900); }
    .sr-panel-sub { font-size: 11.5px; color: var(--gray-500); font-weight: 500; }
    .sr-panel-reset { margin-left: auto; font-size: 11.5px; color: var(--blue); cursor: pointer; font-weight: 600; text-decoration: none; }
    .sr-panel-reset:hover { text-decoration: underline; }
    .sr-panel-body { padding: 8px 10px 14px; display: flex; flex-direction: column; gap: 1px; max-height: 268px; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: var(--gray-300) transparent; }
    .sr-panel-body::-webkit-scrollbar { width: 8px; }
    .sr-panel-body::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 999px; }

    .sr-check-row { display: flex; align-items: center; gap: 10px; min-height: 34px; padding: 5px 6px; border-radius: 8px; cursor: pointer; transition: background .14s ease; }
    .sr-check-row:hover { background: var(--gray-50); }
    .sr-check-left { display: flex; align-items: center; gap: 9px; min-width: 0; }
    /* Square, not round: airlines are a multi-select, and a circle reads as a
       radio button — i.e. as though picking one would clear the others. */
    .sr-check-box { width: 16px; height: 16px; border-radius: 5px; border: 1.5px solid var(--gray-300); background: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .14s ease, border-color .14s ease; }
    .sr-check-row:hover .sr-check-box { border-color: var(--gray-400); }
    .sr-check-box.checked { background: var(--blue); border-color: var(--blue); }
    .sr-check-box.checked::after {
        content: ""; width: 11px; height: 11px; background: #fff;
        mask: url("{{ asset('images/flight-icons/check.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/flight-icons/check.svg') }}") center / contain no-repeat;
    }
    .sr-check-name { font-size: 12.5px; color: var(--gray-700); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sr-check-row:hover .sr-check-name { color: var(--gray-900); }
    .sr-check-price { margin-left: auto; flex-shrink: 0; font-size: 11.5px; color: var(--gray-500); font-weight: 500; font-family: var(--mono); }

    .sr-stop-pills { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 7px; padding: 10px 16px 16px; }
    .sr-time-pills { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 7px; padding: 10px 16px 16px; }
    .sr-stop-pill, .sr-time-pill {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
        min-width: 0; min-height: 52px; padding: 8px 6px; border-radius: 9px;
        border: 1px solid var(--gray-200); background: #fff;
        font-size: 11.5px; font-weight: 600; color: var(--gray-700);
        text-align: center; cursor: pointer;
        transition: border-color .14s ease, background .14s ease, color .14s ease;
    }
    .sr-stop-pill:hover, .sr-time-pill:hover { border-color: var(--blue-md); background: #fcfcff; }
    .sr-stop-pill.active, .sr-time-pill.active { border-color: var(--blue); background: var(--blue-lt); color: var(--blue); box-shadow: inset 0 0 0 1px var(--blue); }
    .sr-pill-sub { font-size: 10.5px; font-weight: 500; color: var(--gray-500); font-family: var(--mono); }
    .sr-stop-pill.active .sr-pill-sub, .sr-time-pill.active .sr-pill-sub { color: var(--blue); opacity: .75; }

    /* ── Main Content ── */
    .sr-main { display: flex; flex-direction: column; gap: 14px; }

    /*
     * ── Fare Matrix ──
     * A grid for spotting the cheapest airline/stops combination, not a
     * spreadsheet. Vertical rules are gone — columns are separated by
     * whitespace and the sticky label column's single border — because every
     * cell already reads as a cell without being boxed in. Each populated
     * cell filters the list below, so it gets a real hit target and hover
     * state; before, a click did something but nothing said it would.
     */
    .sr-matrix { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
    .sr-matrix-head { display: flex; align-items: center; gap: 9px; padding: 13px 16px; border-bottom: 1px solid var(--gray-200); }
    .sr-matrix-head .sr-ic { color: var(--gray-500); }
    .sr-matrix-title { font-size: 13px; font-weight: 700; color: var(--gray-900); }
    .sr-matrix-hint { margin-left: auto; font-size: 11.5px; color: var(--gray-500); }
    .sr-matrix-scroll { overflow-x: auto; overscroll-behavior-x: contain; -webkit-overflow-scrolling: touch; scrollbar-width: thin; scrollbar-color: var(--gray-300) transparent; }
    .sr-matrix-scroll::-webkit-scrollbar { height: 8px; }
    .sr-matrix-scroll::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 999px; }
    .sr-matrix-scroll::-webkit-scrollbar-track { background: transparent; }
    /* separate, not collapse: collapsed borders drop out from under a
       position:sticky cell in several engines. */
    .sr-matrix table { width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
    .sr-matrix th, .sr-matrix td { padding: 0; text-align: center; vertical-align: middle; white-space: nowrap; }
    .sr-matrix thead th { height: 60px; background: #fff; border-bottom: 1px solid var(--gray-200); }
    .sr-matrix tbody td, .sr-matrix tbody th { height: 52px; border-bottom: 1px solid var(--gray-100); }
    .sr-matrix tbody tr:last-child td, .sr-matrix tbody tr:last-child th { border-bottom: 0; }
    .sr-matrix th:first-child, .sr-matrix td:first-child {
        position: sticky; left: 0; z-index: 2; background: #fff;
        width: 116px; min-width: 116px; max-width: 116px;
        padding-left: 16px; text-align: left;
        border-right: 1px solid var(--gray-200);
    }
    .sr-matrix th:not(:first-child), .sr-matrix td:not(:first-child) { width: 124px; min-width: 124px; }
    .sr-matrix-corner { font-size: 11px; font-weight: 600; color: var(--gray-500); line-height: 1.35; }
    .sr-matrix-corner span { display: block; }
    .sr-matrix-row-label { font-size: 12.5px; font-weight: 600; color: var(--gray-900); }
    .sr-matrix .airline-logo1 { display: flex; align-items: center; justify-content: center; height: 60px; padding: 0 12px; }
    .sr-mat-img {
        display: block; width: auto; max-width: 92px; max-height: 30px; object-fit: contain;
        /* airline logos arrive from the data provider as small (70×30) GIFs —
           crisp-edges keeps them defined rather than smeared when scaled up. */
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
    .sr-matrix-price {
        display: flex; align-items: center; justify-content: center;
        width: calc(100% - 20px); height: 36px; margin: 0 auto;
        border: 0; border-radius: 8px; background: transparent;
        font-family: var(--mono); font-size: 12.5px; font-weight: 500; color: var(--gray-900);
        cursor: pointer; transition: background .14s ease, color .14s ease, box-shadow .14s ease;
    }
    .sr-matrix-price:hover { background: var(--blue-lt); color: var(--blue); box-shadow: inset 0 0 0 1px var(--blue-md); }
    .sr-matrix-price:focus-visible { outline: none; box-shadow: 0 0 0 2px var(--blue); }
    .sr-matrix-price.cheapest { background: #e9f9f0; color: #04713f; font-weight: 600; }
    .sr-matrix-price.cheapest:hover { box-shadow: inset 0 0 0 1px #8fdcb4; }
    .sr-matrix-empty { display: block; color: var(--gray-300); font-size: 13px; }
    .sr-matrix .airline-name, .sr-next-btn { display: none; }

    /*
     * ── Best / Cheapest / Fastest ──
     * Three peer choices, so they get one shape and are ordered in the markup
     * rather than shuffled by `order` on :nth-child, which made the rendered
     * order impossible to read off the template.
     */
    .sr-fare-options { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; }
    .sr-fare-option {
        display: flex; align-items: flex-start; gap: 11px;
        padding: 13px 14px; text-align: left;
        border: 1px solid var(--gray-200); border-radius: var(--radius); background: #fff;
        box-shadow: var(--shadow); cursor: pointer;
        transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .sr-fare-option:hover { border-color: var(--blue-md); box-shadow: var(--shadow-md); }
    .sr-fare-option.active { border-color: var(--blue); background: var(--blue-lt); box-shadow: inset 0 0 0 1px var(--blue); }
    .sr-fare-option-ic { flex-shrink: 0; width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); color: var(--gray-500); }
    .sr-fare-option.active .sr-fare-option-ic { background: #fff; color: var(--blue); }
    .sr-fare-option-txt { min-width: 0; }
    .sr-fare-option-label { display: block; font-size: 11.5px; font-weight: 600; color: var(--gray-500); line-height: 1.35; }
    .sr-fare-option.active .sr-fare-option-label { color: var(--blue); }
    .sr-fare-option-price { display: block; font-size: 19px; font-weight: 500; font-family: var(--mono); color: var(--gray-900); line-height: 1.25; letter-spacing: -.01em; }
    .sr-fare-option.active .sr-fare-option-price { color: var(--blue); }
    .sr-fare-option-note { display: block; font-size: 11px; color: var(--gray-500); line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sr-fare-option.active .sr-fare-option-note { color: var(--blue); opacity: .8; }

    /* Supplemental-supplier loading indicator */
    .sr-supplement-status { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--gray-500); padding: 2px 0; }
    .sr-supplement-status--done { color: var(--green); }
    .sr-supplement-spinner { width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--gray-200); border-top-color: var(--blue); animation: sr-spin .7s linear infinite; }
    @keyframes sr-spin { to { transform: rotate(360deg); } }
    @keyframes sr-card-highlight {
        0% { box-shadow: 0 0 0 2px var(--blue-md); background-color: var(--blue-lt); }
        100% { box-shadow: none; background-color: transparent; }
    }
    .sr-card-new { animation: sr-card-highlight 2.4s ease-out; }

    /* Sort bar */
    .sr-sort-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; min-height: 34px; }
    .sr-result-pill { display: inline-flex; align-items: center; gap: 7px; height: 30px; padding: 0 12px; border-radius: 999px; background: var(--blue-lt); color: var(--blue); font-size: 11.5px; font-weight: 600; }
    .sr-sort-select { display: inline-flex; align-items: center; gap: 8px; margin-left: auto; font-size: 12px; font-weight: 600; color: var(--gray-600); }
    .sr-sort-select select {
        height: 32px; min-width: 152px; padding: 0 32px 0 11px;
        border: 1px solid var(--gray-200); border-radius: 8px; color: var(--gray-900);
        font: inherit; font-size: 12px; font-weight: 600; outline: none; cursor: pointer;
        appearance: none; -webkit-appearance: none;
        background: #fff url("{{ asset('images/flight-icons/chevron-down.svg') }}") no-repeat right 10px center / 14px 14px;
        transition: border-color .14s ease, box-shadow .14s ease;
    }
    .sr-sort-select select:hover { border-color: var(--blue-md); }
    .sr-sort-select select:focus-visible { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(48,49,145,.12); }

    /* ── Flight Card (rebuilt — see comment block below) ── */
    /*
     * This card had accumulated four full, unconditional redefinitions of
     * the same classes over successive redesign passes (this original one,
     * "Phase 2/3", and a "Figma replica" pass — none removed the one before
     * it), each with its own hardcoded, non-token colors and, in the last
     * pass, heavy absolute positioning pinned to fixed pixel widths. This is
     * the single canonical implementation replacing all of them — one card
     * shape/shadow/radius, real brand tokens throughout, and an actual CSS
     * grid instead of absolute positioning, so the card reflows correctly
     * instead of relying on a fixed min-height and magic-number offsets.
     */
    @keyframes cardIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /*
     * Icon set. One family of 24px line glyphs (stroke 1.75, round joins,
     * public/images/flight-icons) drawn as CSS masks, so every icon inherits
     * `color` from its context instead of shipping a fixed-colour asset and
     * every icon on the card shares the same optical weight. Replaces the
     * emoji that used to stand in for icons in the fare-rules panel.
     */
    .sr-ic { display: inline-block; width: 16px; height: 16px; flex: 0 0 16px; background: currentColor;
             mask: var(--i) center / contain no-repeat; -webkit-mask: var(--i) center / contain no-repeat; }
    .sr-ic-sm { width: 14px; height: 14px; flex-basis: 14px; }
    .sr-ic-lg { width: 18px; height: 18px; flex-basis: 18px; }
    .sr-ic-luggage  { --i: url("{{ asset('images/flight-icons/luggage.svg') }}"); }
    .sr-ic-cabin    { --i: url("{{ asset('images/flight-icons/cabin-bag.svg') }}"); }
    .sr-ic-seat     { --i: url("{{ asset('images/flight-icons/seat.svg') }}"); }
    .sr-ic-refund   { --i: url("{{ asset('images/flight-icons/refund.svg') }}"); }
    .sr-ic-change   { --i: url("{{ asset('images/flight-icons/change.svg') }}"); }
    .sr-ic-clock    { --i: url("{{ asset('images/flight-icons/clock.svg') }}"); }
    .sr-ic-chevron  { --i: url("{{ asset('images/flight-icons/chevron-down.svg') }}"); }
    .sr-ic-check    { --i: url("{{ asset('images/flight-icons/check.svg') }}"); }
    .sr-ic-cross    { --i: url("{{ asset('images/flight-icons/cross.svg') }}"); }
    .sr-ic-info     { --i: url("{{ asset('images/flight-icons/info.svg') }}"); }
    .sr-ic-aircraft { --i: url("{{ asset('images/flight-icons/aircraft.svg') }}"); }
    .sr-ic-ticket   { --i: url("{{ asset('images/flight-icons/ticket.svg') }}"); }
    .sr-ic-card     { --i: url("{{ asset('images/flight-icons/card.svg') }}"); }
    .sr-ic-tag      { --i: url("{{ asset('images/flight-icons/tag.svg') }}"); }
    .sr-ic-alert    { --i: url("{{ asset('images/flight-icons/alert.svg') }}"); }
    .sr-ic-search   { --i: url("{{ asset('images/flight-icons/search.svg') }}"); }
    .sr-ic-route    { --i: url("{{ asset('images/flight-icons/route.svg') }}"); }
    .sr-ic-award    { --i: url("{{ asset('images/flight-icons/award.svg') }}"); }
    .sr-ic-sliders  { --i: url("{{ asset('images/flight-icons/sliders.svg') }}"); }
    .sr-ic-grid     { --i: url("{{ asset('images/flight-icons/grid.svg') }}"); }
    .sr-ic-list     { --i: url("{{ asset('images/flight-icons/list.svg') }}"); }

    /*
     * Card anatomy: an itinerary panel and a price rail, split by a hairline.
     * The rail carries a faint brand tint so the commercial half of the card
     * (what it costs, how to buy it) reads as a distinct zone from the factual
     * half (where and when you fly) — and so a column of prices scans as a
     * single vertical strip down a long result list.
     */
    .sr-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 218px;
        grid-template-areas: "main rail" "details details";
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: box-shadow .18s ease, border-color .18s ease;
        animation: cardIn .3s ease both;
    }
    .sr-card:hover { border-color: #c9cce4; box-shadow: var(--shadow-md); }
    .sr-card:focus-within { border-color: var(--blue); }
    .sr-card-expanded { border-color: #c9cce4; box-shadow: var(--shadow-md); }

    .sr-card-main { grid-area: main; min-width: 0; display: flex; flex-direction: column; padding: 16px 20px 0; }

    /* Head — airline identity, kept deliberately quiet so the route can lead */
    .sr-card-head { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .sr-airline-logo-wrap { width: 34px; height: 34px; border-radius: 8px; background: #fff; border: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 9px; font-weight: 800; color: var(--gray-500); overflow: hidden; padding: 3px; }
    .sr-airline-logo-wrap img { width: 100%; height: 100%; object-fit: contain; }
    .sr-card-airline { font-size: 13.5px; font-weight: 700; color: var(--gray-900); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sr-card-class { font-size: 11.5px; color: var(--gray-500); font-weight: 500; }
    .sr-head-tags { display: flex; align-items: center; gap: 6px; margin-left: auto; flex-shrink: 0; }
    .sr-tag { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; line-height: 1; padding: 5px 9px; border-radius: 6px; white-space: nowrap; }
    .sr-tag-neutral { background: var(--gray-50); color: var(--gray-600); border: 1px solid var(--gray-200); }
    .sr-tag-brand   { background: var(--blue-lt); color: var(--blue); }
    .sr-tag-good    { background: #e9f9f0; color: #04713f; }
    .sr-tag-warn    { background: #fef3f2; color: #b42318; }

    /* Route — the one loud element on the card */
    .sr-depart-return { display: flex; flex-wrap: wrap; gap: 0 24px; padding: 16px 0 4px; }
    .sr-dr-col { flex: 1 1 240px; min-width: 0; }
    /* Alpine's x-if leaves its <template> anchor in the DOM between the two
       columns, so the adjacent-sibling combinator never matches here — the
       general-sibling one does, and there are only ever two columns. */
    .sr-dr-col ~ .sr-dr-col { border-left: 1px solid var(--gray-100); padding-left: 24px; }
    .sr-dr-label { display: flex; align-items: baseline; gap: 6px; font-size: 11.5px; color: var(--gray-500); font-weight: 600; margin-bottom: 8px; }
    .sr-segments { display: flex; align-items: flex-start; gap: 0; }
    .sr-seg { display: flex; flex-direction: column; gap: 3px; min-width: 62px; }
    .sr-seg:last-child { align-items: flex-end; text-align: right; }
    .sr-seg-time { font-size: 23px; font-weight: 500; color: var(--gray-900); font-family: var(--mono); line-height: 1.05; letter-spacing: -.01em; }
    .sr-seg-place { font-size: 12px; color: var(--gray-500); font-weight: 500; max-width: 130px; }
    .sr-seg-line { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 3px 14px 0; min-width: 92px; }
    .sr-seg-duration { font-size: 11.5px; color: var(--gray-600); font-weight: 600; font-family: var(--mono); }
    .sr-seg-track { position: relative; width: 100%; display: flex; align-items: center; height: 14px; }
    .sr-seg-dash { flex: 1; height: 1px; background: var(--gray-200); }
    .sr-seg-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gray-300); flex-shrink: 0; }
    .sr-seg-track::after {
        content: ""; position: absolute; left: 50%; top: 50%; width: 15px; height: 15px;
        transform: translate(-50%, -50%); background: var(--blue);
        mask: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center / contain no-repeat;
    }
    .sr-seg-stop { font-size: 11px; font-weight: 600; color: var(--green); }
    .sr-seg-stop.hasstop { color: var(--gray-500); font-weight: 500; }

    /* Meta strip — every remaining fact plus the expand control, on one line */
    .sr-card-meta-clean {
        display: flex; align-items: center; gap: 8px 18px; flex-wrap: wrap;
        margin-top: auto; padding: 12px 0 13px;
        border-top: 1px solid var(--gray-100);
        font-size: 12px; color: var(--gray-500);
    }
    .sr-card-meta-item { display: inline-flex; align-items: center; gap: 7px; min-width: 0; }
    .sr-card-meta-item .sr-ic { color: var(--gray-400); }
    .sr-card-meta-item strong { color: var(--gray-700); font-weight: 600; }
    .sr-card-meta-item.low strong { color: #b42318; }
    .sr-card-meta-item.low .sr-ic { color: #b42318; }
    .sr-card-meta-sep { display: none; }

    .sr-view-details {
        display: inline-flex; align-items: center; gap: 5px; margin-left: auto;
        font-size: 12.5px; font-weight: 600; color: var(--blue);
        cursor: pointer; text-decoration: none; white-space: nowrap;
        border-radius: 6px; padding: 3px 2px;
    }
    .sr-view-details:hover { color: var(--tw-brand-hover, #252675); text-decoration: underline; }
    .sr-view-details .sr-ic { transition: transform .18s ease; }
    .sr-card-expanded .sr-view-details .sr-ic { transform: rotate(180deg); }

    /* Price rail */
    .sr-card-price-wrap {
        grid-area: rail; display: flex; flex-direction: column; justify-content: center;
        gap: 3px; padding: 18px 20px; text-align: right;
        background: linear-gradient(180deg, #fbfbff 0%, #f7f7fd 100%);
        border-left: 1px solid var(--gray-100);
    }
    .sr-card-price-label { font-size: 11.5px; font-weight: 500; color: var(--gray-500); }
    .sr-card-price { font-size: 24px; font-weight: 500; color: var(--gray-900); font-family: var(--mono); line-height: 1.15; letter-spacing: -.02em; }
    .sr-card-price-note { font-size: 11px; color: var(--gray-400); }
    .sr-card-price-sub { font-size: 11px; color: var(--blue); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 3px; justify-content: flex-end; }
    .sr-card-price-sub:hover { text-decoration: underline; }
    .sr-card-actions { display: flex; flex-direction: column; align-items: stretch; gap: 7px; margin-top: 13px; }
    .sr-book-btn { width: 100%; padding: 0 18px; height: 42px; background: var(--blue); color: #fff; border: none; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .15s ease, box-shadow .15s ease; }
    .sr-book-btn:hover { background: var(--tw-brand-hover, #252675); box-shadow: 0 4px 12px rgba(48,49,145,.24); }
    .sr-book-btn:active { transform: translateY(1px); }
    .sr-installment-btn { width: 100%; padding: 7px 10px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-500); font-family: var(--font); cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 1px; transition: border-color .15s ease, color .15s ease; }
    .sr-installment-btn:hover:not(:disabled) { border-color: var(--blue-md); color: var(--blue); }
    .sr-installment-btn:disabled { opacity: .55; cursor: not-allowed; }
    .sr-installment-btn-price { font-size: 12px; font-weight: 600; color: var(--gray-700); font-family: var(--mono); }
    .sr-installment-btn-label { font-size: 10.5px; color: inherit; font-weight: 600; }

    /* Multi-city legs — same route grammar as above, at leg scale */
    .mc-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; padding: 14px 0 4px; }
    .mc-leg { min-width: 0; padding: 12px 14px; border: 1px solid var(--gray-100); border-radius: 10px; background: #fcfcfe; }
    .mc-leg.mc-span { grid-column: 1 / -1; }
    .mc-leg-lbl { font-size: 11.5px; font-weight: 600; color: var(--gray-600); margin-bottom: 2px; display: flex; align-items: baseline; gap: 6px; }
    .mc-leg-airline { font-size: 11px; color: var(--gray-500); font-weight: 500; margin-bottom: 9px; display: flex; align-items: center; gap: 5px; }
    .mc-leg-airline img { width: 15px; height: 15px; object-fit: contain; border-radius: 3px; background: #fff; }
    .mc-row { display: flex; align-items: center; gap: 0; }
    .mc-pt { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .mc-time { font-size: 19px; font-weight: 500; color: var(--gray-900); font-family: var(--mono); line-height: 1.1; }
    .mc-city { font-size: 11px; color: var(--gray-500); font-weight: 500; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px; }
    .mc-mid { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 0 10px; min-width: 60px; }
    .mc-dur { font-size: 10.5px; color: var(--gray-600); font-weight: 600; font-family: var(--mono); }
    .mc-track { position: relative; width: 100%; display: flex; align-items: center; height: 12px; }
    .mc-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--gray-300); flex-shrink: 0; }
    .mc-dash { flex: 1; height: 1px; background: var(--gray-200); }
    .mc-track::after {
        content: ""; position: absolute; left: 50%; top: 50%; width: 12px; height: 12px;
        transform: translate(-50%, -50%); background: var(--blue);
        mask: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center / contain no-repeat;
    }
    .mc-stop { font-size: 10.5px; font-weight: 600; }
    .mc-stop.direct { color: var(--green); }
    .mc-stop.hasstop { color: var(--gray-500); font-weight: 500; }

    /* Responsive — the rail unstacks under the itinerary and goes horizontal */
    @media (max-width: 860px) {
        .sr-card { grid-template-columns: 1fr; grid-template-areas: "main" "rail" "details"; }
        .sr-card-main { padding: 15px 16px 0; }
        .sr-card-price-wrap {
            flex-direction: row; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; text-align: left; padding: 13px 16px;
            border-left: none; border-top: 1px solid var(--gray-100);
        }
        .sr-card-price-wrap > .sr-rail-figures { display: flex; flex-direction: column; }
        .sr-card-actions { flex-direction: row; align-items: center; gap: 9px; margin-top: 0; margin-left: auto; }
        .sr-book-btn { width: auto; min-width: 132px; }
        .sr-installment-btn { width: auto; min-width: 96px; }
        .sr-dr-col { flex: 1 1 100%; }
        .sr-dr-col ~ .sr-dr-col { border-left: none; border-top: 1px solid var(--gray-100); padding-left: 0; padding-top: 14px; margin-top: 14px; }
        .mc-grid { grid-template-columns: 1fr; }
        .mc-leg.mc-span { grid-column: 1; }
    }
    @media (max-width: 520px) {
        .sr-seg-time { font-size: 20px; }
        .sr-seg-line { min-width: 70px; padding: 3px 10px 0; }
        .sr-card-price { font-size: 21px; }
        .sr-card-actions { width: 100%; margin-left: 0; }
        .sr-book-btn, .sr-installment-btn { flex: 1; min-width: 0; }
        .sr-head-tags { margin-left: 0; width: 100%; }
        .sr-card-head { flex-wrap: wrap; }
    }

    /*
     * ── Expanded Detail Panel ──
     * Each leg is drawn as a vertical timeline: a hairline spine with a node at
     * every airport, layovers sitting on the spine between segments. That is
     * how a journey actually reads — one continuous line of stations — and it
     * replaces a stack of bordered boxes that gave a two-stop connection the
     * same visual weight as three unrelated flights.
     */
    .sr-detail-panel { grid-area: details; border-top: 1px solid var(--gray-200); background: #fff; }
    .sr-detail-tabs { display: flex; gap: 4px; padding: 14px 20px 0; }
    .sr-detail-tab {
        padding: 7px 14px; font-size: 12.5px; font-weight: 600; color: var(--gray-500);
        cursor: pointer; border-radius: 7px; border: 1px solid transparent;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .sr-detail-tab:hover { color: var(--gray-900); background: var(--gray-50); }
    .sr-detail-tab.active { color: var(--blue); background: var(--blue-lt); border-color: #e2e2fb; }

    .sr-detail-body { padding: 16px 20px 4px; }
    .sr-detail-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .sr-detail-cols > .sr-detail-col ~ .sr-detail-col { border-left: 1px solid var(--gray-100); padding-left: 20px; }
    .sr-detail-col { min-width: 0; }
    .sr-multi-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px 24px; }
    .sr-multi-detail-leg { min-width: 0; }

    .sr-detail-leg-head { display: flex; align-items: center; gap: 9px; margin-bottom: 14px; }
    .sr-detail-leg-title { font-size: 13px; font-weight: 700; color: var(--gray-900); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sr-detail-leg-badge { flex-shrink: 0; font-size: 10.5px; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: var(--blue-lt); color: var(--blue); }
    .sr-detail-leg-badge.inbound { background: #e9f9f0; color: #04713f; }
    .sr-detail-leg-badge.connecting { background: var(--gray-50); color: var(--gray-600); border: 1px solid var(--gray-200); }
    .sr-detail-leg-date { flex-shrink: 0; margin-left: auto; font-size: 11.5px; color: var(--gray-500); white-space: nowrap; }

    /* The spine. Nodes and layovers are positioned against this left gutter. */
    .sr-timeline { position: relative; padding-left: 26px; }
    .sr-timeline::before {
        content: ""; position: absolute; left: 5px; top: 7px; bottom: 7px;
        width: 1px; background: var(--gray-200);
    }
    .sr-tl-node { position: relative; padding: 0 0 2px; }
    .sr-tl-node::before {
        content: ""; position: absolute; left: -26px; top: 6px;
        width: 11px; height: 11px; border-radius: 50%;
        background: #fff; border: 2px solid var(--blue); box-sizing: border-box;
    }
    .sr-tl-node.mid::before { border-color: var(--gray-300); width: 9px; height: 9px; left: -25px; top: 7px; }
    .sr-tl-row { display: flex; align-items: baseline; gap: 10px; min-width: 0; }
    .sr-tl-time { flex: 0 0 46px; font-size: 15px; font-weight: 500; font-family: var(--mono); color: var(--gray-900); line-height: 1.45; }
    .sr-tl-station { min-width: 0; }
    .sr-tl-place { display: block; font-size: 12.5px; font-weight: 600; color: var(--gray-900); line-height: 1.45; }
    .sr-tl-airport { display: block; font-size: 11.5px; color: var(--gray-500); line-height: 1.4; }

    /* The flight between two nodes: carrier, duration and cabin facts */
    .sr-tl-flight { padding: 9px 0 11px 56px; }
    .sr-tl-carrier { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-size: 12px; color: var(--gray-700); font-weight: 600; }
    .sr-tl-carrier img { width: 18px; height: 18px; object-fit: contain; border-radius: 4px; background: #fff; border: 1px solid var(--gray-100); flex-shrink: 0; }
    .sr-tl-carrier-code { font-size: 11.5px; color: var(--gray-500); font-weight: 500; }
    .sr-tl-carrier-dur { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--gray-500); font-weight: 500; font-family: var(--mono); }
    .sr-tl-carrier-dur::before { content: ""; width: 3px; height: 3px; border-radius: 50%; background: var(--gray-300); }
    .sr-tl-facts { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 9px; }
    .sr-fact {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; color: var(--gray-600); font-weight: 500;
        background: var(--gray-50); border: 1px solid var(--gray-100);
        border-radius: 6px; padding: 4px 9px; line-height: 1.4;
    }
    .sr-fact .sr-ic { color: var(--gray-400); }
    .sr-fact strong { color: var(--gray-900); font-weight: 600; }
    .sr-fact.low { background: #fef3f2; border-color: #fee4e2; color: #b42318; }
    .sr-fact.low .sr-ic, .sr-fact.low strong { color: #b42318; }

    .sr-detail-layover {
        position: relative; display: inline-flex; align-items: center; gap: 7px;
        margin: 4px 0 10px; padding: 5px 11px;
        background: #fffaf0; border: 1px solid #fde8c8; border-radius: 7px;
        font-size: 11.5px; color: #92400e; font-weight: 600;
    }
    .sr-detail-layover::before {
        content: ""; position: absolute; left: -22px; top: 50%; transform: translateY(-50%);
        width: 5px; height: 5px; border-radius: 50%; background: var(--gray-300);
    }
    .sr-detail-layover .sr-ic { color: #b45309; }

    /* ── Fare Rules ── policy cards per passenger type, then the money row */
    .sr-fare-rules-body { padding: 16px 20px 4px; }
    .sr-fare-group ~ .sr-fare-group { margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--gray-200); }
    .sr-fare-group-head { display: flex; align-items: center; gap: 8px; margin-bottom: 11px; }
    .sr-fare-group-title { font-size: 13px; font-weight: 700; color: var(--gray-900); }
    .sr-fare-group-qty { font-size: 11.5px; font-weight: 600; color: var(--gray-600); background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 6px; padding: 2px 8px; }
    .sr-policy-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 9px; }
    .sr-policy {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 11px 13px; border: 1px solid var(--gray-200); border-radius: 9px; background: #fff;
    }
    .sr-policy-ic {
        flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gray-50); color: var(--gray-500);
    }
    .sr-policy.good .sr-policy-ic { background: #e9f9f0; color: #04713f; }
    .sr-policy.bad  .sr-policy-ic { background: #fef3f2; color: #b42318; }
    .sr-policy-txt { min-width: 0; }
    .sr-policy-label { display: block; font-size: 11.5px; color: var(--gray-500); font-weight: 500; line-height: 1.4; }
    .sr-policy-val { display: block; font-size: 12.5px; color: var(--gray-900); font-weight: 600; line-height: 1.45; margin-top: 1px; }
    .sr-policy.good .sr-policy-val { color: #04713f; }
    .sr-policy.bad  .sr-policy-val { color: #b42318; }
    .sr-policy-note { display: block; font-size: 11.5px; color: var(--gray-500); font-weight: 400; line-height: 1.45; margin-top: 3px; }
    .sr-rules-empty { display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px; border: 1px solid var(--gray-200); border-radius: 9px; background: var(--gray-50); font-size: 12.5px; color: var(--gray-600); line-height: 1.55; max-width: 62ch; }
    .sr-rules-empty .sr-ic { color: var(--gray-400); margin-top: 1px; }

    .sr-fare-money { display: flex; flex-direction: column; gap: 7px; margin-top: 11px; padding: 12px 14px; border-radius: 9px; background: var(--gray-50); border: 1px solid var(--gray-100); }
    .sr-fare-money-row { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; font-size: 12.5px; color: var(--gray-600); }
    .sr-fare-money-row .v { font-family: var(--mono); color: var(--gray-700); }
    .sr-fare-money-row.total { padding-top: 7px; border-top: 1px solid var(--gray-200); font-weight: 700; color: var(--gray-900); }
    .sr-fare-money-row.total .v { font-size: 14px; color: var(--gray-900); }

    .sr-detail-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 20px 16px; margin-top: 14px; border-top: 1px solid var(--gray-100); }
    .sr-detail-footer-note { font-size: 11.5px; color: var(--gray-500); }
    .sr-detail-footer .sr-book-btn { width: auto; min-width: 168px; }

    /* ── Right Rail ── */
    .sr-rail { position: sticky; top: 18px; display: flex; flex-direction: column; gap: 12px; }

    /*
     * The promo rotates on a timer. Its dot control existed in the stylesheet
     * and in the Alpine state but had no markup, so the card silently changed
     * under the reader with nothing to say why or how to go back. The dots are
     * now rendered, and the rotation stops for anyone who asked for reduced
     * motion.
     */
    .sr-promo {
        position: relative; border-radius: var(--radius); overflow: hidden;
        background: linear-gradient(152deg, var(--blue) 0%, #2b3f88 54%, #0f6b62 100%);
        box-shadow: 0 10px 26px rgba(48,49,145,.22);
    }
    .sr-promo::after {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: radial-gradient(circle at 86% 12%, rgba(0,168,89,.28), transparent 46%);
    }
    .sr-promo-slides { position: relative; min-height: 182px; }
    .sr-promo-slide {
        position: absolute; inset: 0; z-index: 1;
        display: flex; flex-direction: column; align-items: flex-start;
        padding: 15px 16px 42px;
        opacity: 0; transition: opacity .5s ease; pointer-events: none;
    }
    .sr-promo-slide.active { opacity: 1; pointer-events: auto; }
    .sr-promo-chip {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 10.5px; font-weight: 600; letter-spacing: .02em;
        background: rgba(255,255,255,.14); color: #fff;
        padding: 4px 10px; border-radius: 999px; margin-bottom: 11px;
    }
    .sr-promo-chip-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--green); flex-shrink: 0; }
    .sr-promo-title { font-size: 14px; font-weight: 700; color: #fff; line-height: 1.35; }
    .sr-promo-body { font-size: 11.5px; color: rgba(255,255,255,.74); line-height: 1.55; margin-top: 6px; }
    .sr-promo-btn {
        display: inline-flex; align-items: center; gap: 6px; margin-top: auto;
        padding: 8px 15px; background: #fff; color: var(--blue);
        border-radius: 8px; font-size: 12px; font-weight: 700;
        text-decoration: none; border: none; cursor: pointer;
        transition: background .15s ease, transform .15s ease;
    }
    .sr-promo-btn:hover { background: #f2f2ff; transform: translateY(-1px); }
    .sr-promo-dots { position: absolute; z-index: 2; left: 16px; bottom: 15px; display: flex; gap: 5px; align-items: center; }
    .sr-promo-dot {
        width: 5px; height: 5px; border-radius: 999px; padding: 0; border: 0;
        background: rgba(255,255,255,.34); cursor: pointer;
        transition: width .28s ease, background .28s ease;
    }
    .sr-promo-dot:hover { background: rgba(255,255,255,.6); }
    .sr-promo-dot.active { width: 15px; background: #fff; }

    .sr-tip-card { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); padding: 14px; }
    .sr-tip-title { display: flex; align-items: center; gap: 9px; font-size: 12.5px; font-weight: 700; color: var(--gray-900); margin-bottom: 9px; }
    .sr-tip-icon { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; flex-shrink: 0; border-radius: 8px; background: var(--blue-lt); color: var(--blue); }
    .sr-tip-body { font-size: 11.5px; color: var(--gray-500); line-height: 1.6; }
    /* Was brand blue, which reads as a link in a paragraph that has none. */
    .sr-tip-highlight { color: var(--gray-900); font-weight: 600; }

    /* ── Modify Button ── */
    .sr-tb-modify-btn {
        position: relative; z-index: 1; display: flex; align-items: center; gap: 8px; padding: 0 18px; height: 42px;
        background: var(--green); color: #fff; border: 1px solid rgba(255,255,255,.28);
        border-radius: 8px; font-size: 13px; font-weight: 800; cursor: pointer;
        font-family: var(--font); flex-shrink: 0; margin-left: auto; transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        box-shadow: 0 10px 22px rgba(0,153,51,.22);
    }
    .sr-tb-modify-btn:hover { background: #00852d; transform: translateY(-1px); box-shadow: 0 14px 26px rgba(0,153,51,.28); }
    .sr-tb-modify-btn.active { background: #087f32; box-shadow: 0 0 0 3px rgba(255,255,255,.16), 0 10px 22px rgba(0,153,51,.18); }

    /* ── Backdrop ── */
    .sr-modify-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,.45);
        z-index: 300; backdrop-filter: blur(2px);
    }

    /* ── Drop Modal ── */
    .sr-modify-modal {
        position: fixed; top: 60px; left: 0; right: 0; z-index: 301;
        background: var(--navy); border-bottom: 1px solid rgba(255,255,255,.1);
        box-shadow: 0 16px 48px rgba(0,0,0,.35);
        max-height: calc(100vh - 60px); overflow-y: auto;
    }
    .sr-modify-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 32px; border-bottom: 1px solid rgba(255,255,255,.1);
        background: rgba(255,255,255,.04); max-width: 1280px;
        margin: 0 auto; width: 100%; box-sizing: border-box;
    }
    .sr-modify-close {
        width: 32px; height: 32px; border-radius: 50%;
        border: 1.5px solid rgba(255,255,255,.2); background: rgba(255,255,255,.08);
        color: rgba(255,255,255,.7); display: flex; align-items: center;
        justify-content: center; cursor: pointer; transition: all .15s; padding: 0;
    }

    .sr-modify-close:hover { background: rgba(255,255,255,.18); color: #fff; }
    .sr-modify-body { max-width: 1280px; margin: 0 auto; padding: 24px 32px 32px; box-sizing: border-box; }
    .sr-modify-body .fw-card-outer { background: none !important; padding: 0 !important; min-height: unset !important; }
    .sr-modify-body .fw-card-outer::before { display: none !important; }
    .sr-modify-body .fw-card { box-shadow: 0 4px 24px rgba(0,0,0,.2); }
    .sr-modify-inline {
        max-width: 1394px;
        margin: 14px auto 0;
        padding: 0 16px;
        position: relative;
        z-index: 45;
        transform-origin: top center;
    }
    .sr-modify-transition {
        transition: opacity .22s ease, transform .24s ease, filter .24s ease;
    }
    .sr-modify-hidden {
        opacity: 0;
        transform: translateY(-10px) scale(.985);
        filter: blur(2px);
    }
    .sr-modify-shown {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
    .sr-modify-card {
        overflow: visible;
        border: 1px solid #e6e8ee;
        border-radius: 12px;
        background: rgba(255,255,255,.96);
        box-shadow: 0 18px 34px rgba(16,24,40,.08);
    }
    .sr-modify-inline .sr-modify-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 46px;
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0 16px;
        border-bottom: 1px solid #eef0f5;
        background: #fff;
    }
    .sr-modify-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #111827;
        font-size: 13px;
        font-weight: 800;
    }
    .sr-modify-title-icon {
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #f1f1ff;
        color: #303191;
    }
    .sr-modify-inline .sr-modify-close {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #667085;
        box-shadow: none;
    }
    .sr-modify-inline .sr-modify-close:hover {
        background: #f8f9fc;
        color: #303191;
    }
    .sr-modify-inline .sr-modify-body {
        max-width: none;
        margin: 0;
        padding: 14px 16px 16px;
        background: #fff;
        border-radius: 0 0 12px 12px;
        overflow: visible !important;
    }
    .sr-modify-inline .fw-card-outer {
        background: transparent !important;
        min-height: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    .sr-modify-inline .fw-card-outer::before {
        display: none !important;
    }
    .sr-modify-inline .fw-card {
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    .sr-modify-inline .tw-landing-hero {
        min-height: 0 !important;
        display: block !important;
        padding: 0 !important;
        background: transparent !important;
        overflow: visible !important;
    }
    .sr-modify-inline .tw-landing-hero::before,
    .sr-modify-inline .tw-hero-title,
    .sr-modify-inline .tw-hero-subtitle,
    .sr-modify-inline .tw-product-tabs {
        display: none !important;
    }
    .sr-modify-inline .tw-hero-inner {
        max-width: none !important;
        width: 100% !important;
        text-align: left !important;
        overflow: visible !important;
    }
    .sr-modify-inline .fw-card {
        max-width: none !important;
        padding: 0 !important;
    }
    .sr-modify-inline .fw-card > div:first-child {
        margin-bottom: 14px !important;
    }
    .sr-modify-inline .fw-tabs {
        padding-bottom: 0 !important;
        border-bottom: 0 !important;
        gap: 8px !important;
    }
    .sr-modify-inline .fw-tab {
        min-height: 32px;
        padding: 8px 12px;
        font-size: 12px;
    }
    .sr-modify-inline .fw-row {
        gap: 12px;
    }
    .sr-modify-inline .fw-field,
    .sr-modify-inline .fw-field-2x,
    .sr-modify-inline .fw-field-15x,
    .sr-modify-inline .fw-field-12x,
    .sr-modify-inline .fw-input-wrap,
    .sr-modify-inline .fw-cabin-field {
        position: relative;
        overflow: visible !important;
    }
    .sr-modify-inline .fw-field:focus-within,
    .sr-modify-inline .fw-field-2x:focus-within,
    .sr-modify-inline .fw-field-15x:focus-within,
    .sr-modify-inline .fw-field-12x:focus-within,
    .sr-modify-inline .fw-cabin-field:focus-within {
        z-index: 120;
    }
    .sr-modify-inline .fw-ac-dropdown,
    .sr-modify-inline .fw-cal,
    .sr-modify-inline .fw-pax-dropdown,
    .sr-modify-inline .fw-cabin-dropdown {
        z-index: 1400 !important;
    }
    .sr-modify-inline .fw-input {
        height: 48px;
        background: #fbfcfe;
        border-color: #e1e4eb;
        border-radius: 8px;
        font-size: 13px;
    }
    .sr-modify-inline .fw-label {
        margin-bottom: 7px;
        color: #30364a;
        font-size: 11px;
        font-weight: 800;
    }
    .sr-modify-inline .fw-search-row {
        margin-top: 14px;
    }
    .sr-modify-inline .fw-search-btn {
        width: 196px;
        height: 48px;
        border-radius: 10px;
        font-size: 14px;
        box-shadow: 0 10px 20px rgba(48,49,145,.18);
    }

    /* ── Tooltip ── */
    .sr-tooltip { position: relative; display: inline-block; cursor: pointer; }
    .sr-tooltip-text {
        position: absolute; bottom: 150%; left: 20%; transform: translateX(-5%);
        background: #111827; color: #fff; font-size: 11px; padding: 6px 8px;
        border-radius: 6px; white-space: nowrap; opacity: 0; pointer-events: none;
        transition: opacity 0.2s ease; z-index: 50;
    }
    .sr-tooltip:hover .sr-tooltip-text { opacity: 1; }

    /*
     * ── Results shell & topbar sizing ──
     * What used to sit here were three more full, unconditional redefinitions
     * of the sidebar, fare matrix, fare cards, sort bar and right rail
     * ("Phase 2 result controls", "Requested result-page refinements" and a
     * "Focused Figma price matrix replica"), stacked on top of the originals
     * in exactly the pattern the flight card had. Each carried its own
     * hardcoded greys, its own fixed pixel sizes, and UI faked in CSS
     * ::before/::after — including a pair of matrix arrows that looked
     * clickable but were pointer-events:none, and a "Filters" heading drawn
     * with three gradient bars. All of it is replaced by one canonical
     * implementation per component, further up this stylesheet.
     */
    .sr-results-shell { background: #fff; }
    .sr-topbar { padding-top: 24px; }
    .sr-topbar-inner {
        max-width: 960px; min-height: 82px; border-radius: 8px; box-shadow: none; border: 0;
        background: linear-gradient(96deg, #303191 0%, #24467a 61%, #146b6b 100%);
    }
    .sr-tb-route { font-size: 20px; }
    .sr-tb-modify-btn { height: 42px; border-radius: 8px; background: var(--green); box-shadow: none; border: 0; }

    /* ── Responsive ── */
    .sr-mobile-filter-bar,
    .sr-filter-backdrop,
    .sr-filter-sheet {
        display: none;
    }
    .sr-mobile-filter-bar {
        align-items: center;
        gap: 8px;
    }
    .sr-mobile-filter-btn,
    .sr-mobile-filter-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        border-radius: 999px;
        border: 1px solid #e1e5ee;
        background: rgba(255,255,255,.94);
        color: #30364a;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 8px 22px rgba(16,24,40,.06);
        transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
    }
    .sr-mobile-filter-btn {
        padding: 0 13px;
        color: #303191;
    }
    .sr-mobile-filter-chip {
        padding: 0 12px;
    }
    .sr-mobile-filter-btn:active,
    .sr-mobile-filter-chip:active {
        transform: scale(.98);
    }
    .sr-mobile-filter-chip.active,
    .sr-mobile-filter-btn.has-filters {
        border-color: #d7d8ff;
        background: #f7f7ff;
        color: #303191;
    }
    .sr-mobile-filter-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: #009933;
        color: #fff;
        font-size: 10px;
        font-weight: 900;
    }
    .sr-filter-sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 920;
        max-height: min(88vh, 720px);
        flex-direction: column;
        border-radius: 22px 22px 0 0;
        background: #fff;
        box-shadow: 0 -24px 54px rgba(17,24,39,.22);
        overflow: hidden;
    }
    .sr-filter-sheet-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px 12px;
        border-bottom: 1px solid #eef0f4;
    }
    .sr-filter-sheet-title {
        display: flex;
        flex-direction: column;
        gap: 2px;
        color: #111827;
        font-size: 17px;
        font-weight: 900;
        line-height: 1.2;
    }
    .sr-filter-sheet-title span:last-child {
        color: #667085;
        font-size: 11px;
        font-weight: 700;
    }
    .sr-filter-sheet-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 1px solid #e1e5ee;
        border-radius: 999px;
        background: #fff;
        color: #303191;
    }
    .sr-filter-sheet-body {
        overflow-y: auto;
        padding: 12px 14px 92px;
        background: linear-gradient(180deg, #fff 0%, #fbfcfe 100%);
    }
    .sr-filter-sheet .sr-panel {
        border-radius: 14px;
        border-color: #e8ebf2;
        background: #fff;
        box-shadow: none;
    }
    .sr-filter-sheet .sr-panel + .sr-panel {
        margin-top: 10px;
    }
    .sr-filter-sheet-footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        display: grid;
        grid-template-columns: 1fr 1.35fr;
        gap: 10px;
        padding: 12px 14px 14px;
        border-top: 1px solid #eef0f4;
        background: rgba(255,255,255,.96);
        backdrop-filter: blur(14px);
    }
    .sr-filter-sheet-clear,
    .sr-filter-sheet-apply {
        height: 44px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 900;
    }
    .sr-filter-sheet-clear {
        border: 1px solid #e1e5ee;
        background: #fff;
        color: #30364a;
    }
    .sr-filter-sheet-apply {
        border: 1px solid #303191;
        background: #303191;
        color: #fff;
        box-shadow: 0 12px 24px rgba(48,49,145,.18);
    }
    .sr-filter-backdrop {
        position: fixed;
        inset: 0;
        z-index: 910;
        background: rgba(17,24,39,.44);
        backdrop-filter: blur(3px);
    }
    .translate-y-full { transform: translateY(100%); }
    .translate-y-0 { transform: translateY(0); }
    .transition { transition-property: transform, opacity; }
    .ease-out { transition-timing-function: cubic-bezier(.16,1,.3,1); }
    .ease-in { transition-timing-function: cubic-bezier(.4,0,1,1); }
    .duration-200 { transition-duration: 200ms; }
    .duration-160 { transition-duration: 160ms; }
    .sr-time-pill {
        background: #fff;
        border-color: #e1e5ee;
        color: #303191;
    }
    .sr-time-pill.active {
        background: #f7f7ff;
        border-color: #d7d8ff;
        box-shadow: 0 0 0 3px rgba(48,49,145,.08);
    }

    @media (max-width: 1100px) {
        .sr-page { grid-template-columns: 220px 1fr; gap: 14px; }
        .sr-rail { display: none; }
        .sr-fare-options { gap: 10px; }
        .sr-fare-option { padding: 12px; gap: 9px; }
        .sr-fare-option-price { font-size: 18px; }
    }
    @media (max-width: 860px) {
        .sr-page { grid-template-columns: 1fr; padding: 12px 10px 32px; gap: 12px; }
        .sr-sidebar { display: none; }
        .sr-sidebar::-webkit-scrollbar { display: none; }
        .sr-main { order: 1; }
        .sr-mobile-filter-bar {
            position: sticky;
            top: 0;
            z-index: 85;
            display: flex;
            width: calc(100vw - 20px);
            margin: -2px 0 2px;
            padding: 8px 0;
            overflow-x: auto;
            scrollbar-width: none;
            background: linear-gradient(180deg, rgba(248,249,252,.98) 0%, rgba(248,249,252,.9) 100%);
            backdrop-filter: blur(14px);
        }
        .sr-mobile-filter-bar::-webkit-scrollbar { display: none; }
        .sr-filter-backdrop { display: block; }
        .sr-filter-sheet { display: flex; }
        .sr-stop-pills, .sr-time-pills { padding: 8px 12px 12px; gap: 6px; }
        .sr-stop-pill, .sr-time-pill { min-height: 46px; padding: 6px 5px; font-size: 11px; }
        .sr-fare-options { grid-template-columns: 1fr; gap: 9px; }
        .sr-sort-bar { gap: 8px; }
        .sr-detail-tabs { padding: 12px 16px 0; }
        .sr-detail-body { min-height: 0; padding: 14px 16px 2px; }
        .sr-detail-cols,
        .sr-multi-detail-grid { grid-template-columns: 1fr; gap: 18px; }
        .sr-detail-cols > .sr-detail-col ~ .sr-detail-col { border-left: none; padding-left: 0; border-top: 1px solid var(--gray-100); padding-top: 18px; }
        .sr-fare-rules-body { min-height: 0; padding: 14px 16px 2px; }
        .sr-detail-footer { padding: 13px 16px 15px; }
    }
    @media (max-width: 600px) {
        .sr-topbar { padding: 16px 12px 0; }
        .sr-topbar-inner { align-items: stretch; flex-direction: column; gap: 14px; min-height: 0; padding: 16px; }
        .sr-tb-route { flex-wrap: wrap; gap: 8px; font-size: var(--tw-text-lg, 17px); }
        .sr-tb-route-text { max-width: calc(100vw - 116px); }
        .sr-tb-meta { gap: 8px; font-size: 11.5px; }
        .sr-tb-modify-btn { width: 100%; justify-content: center; margin-left: 0; height: 40px; }
        .sr-tb-pill { padding: 5px 9px; }
        .sr-tb-pill-value { font-size: 12px; }
        .sr-tb-search { padding: 0 14px; font-size: 12px; height: 34px; }
        .sr-matrix-head { padding: 11px 14px; }
        .sr-matrix-hint { display: none; }
        .sr-fare-options { grid-template-columns: 1fr; }
        .sr-fare-option-price { font-size: 18px; }
        .sr-sort-bar { gap: 8px; }
        .sr-sort-select { margin-left: 0; width: 100%; }
        .sr-sort-select select { flex: 1; min-width: 0; }
        .sr-detail-footer { flex-direction: column; align-items: stretch; gap: 10px; }
        .sr-detail-footer .sr-book-btn { width: 100%; }
        .sr-policy-grid { grid-template-columns: 1fr; }
        .sr-modify-head { padding: 12px 16px; }
        .sr-modify-body { padding: 16px; }
    }
    @media (max-width: 380px) {
        .sr-tb-pill { padding: 4px 7px; }
        .sr-tb-pill-value { font-size: 11px; }
    }


    .tw-toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .tw-toast {  min-width: 280px; max-width: 350px; padding: 14px 16px; border-radius: 8px; color: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; justify-content: space-between; align-items: center; }
    .tw-toast-content { display: flex; align-items: center; gap: 10px; color: #fff; }
    .tw-toast-icon { display: inline-flex; flex-shrink: 0; }
    .tw-toast-icon .sr-ic { width: 18px; height: 18px; flex-basis: 18px; }
    .tw-toast-message { font-size: 14px;}
    .tw-toast-close { background: transparent; border: none; color: white; font-size: 18px; cursor: pointer; }

    /* Types */
    .tw-toast.error { background: #e3342f; }
    .tw-toast.success { background: #38c172; }

    /* Animations */
    .tw-toast-enter { transition: all 0.3s ease; transform: translateY(-10px); opacity: 0; }
    .tw-toast-leave { transition: all 0.3s ease; transform: translateY(-10px); opacity: 0; }

</style>


@php
    $searchParams = $searchParams ?? [];
    $trip        = $searchParams['trip'] ?? 'oneway';
    $from        = $searchParams['from_city'] ?? $searchParams['from'] ?? 'Lagos';
    $to          = $searchParams['to_city'] ?? $searchParams['to'] ?? 'Abuja';
    $depart      = $searchParams['depart'] ?? null;
    $return      = $searchParams['returning'] ?? null;

    if ($trip === 'multi' && !empty($searchParams['multi_legs'])) {
        $legs = collect($searchParams['multi_legs'] ?? []);

        $from = $legs->first()['from'] ?? 'N/A';
        $to   = $legs->last()['to'] ?? 'N/A';
        $depart = $legs->first()['depart'] ?? null;
    }
    
@endphp

@php
    $searchParams = $searchParams ?? [];

    $trip   = $searchParams['trip'] ?? 'oneway';
    $adults = $searchParams['adults'] ?? 1;
    $childs = $searchParams['childs'] ?? 0;
    $kids   = $searchParams['kids'] ?? 0;

    $totalPassengers = $adults + $childs + $kids;

    $cabinMap = [
        'Y' => 'Economy',
        'S' => 'Premium Economy',
        'C' => 'Business',
        'F' => 'First Class'
    ];

    $cabin = $cabinMap[$searchParams['flight_type'] ?? 'Y'] ?? 'Economy';

    // ── ROUTE HANDLING ─────────────────────────────

    $routes = [];

    if ($trip === 'multi' && !empty($searchParams['multi_legs'])) {

        foreach ($searchParams['multi_legs'] as $leg) {
            $routes[] = [
                'from'   => $leg['from'] ?? 'N/A',
                'to'     => $leg['to'] ?? 'N/A',
                'depart' => $leg['depart'] ?? null,
            ];
        }

    } else {

        $routes[] = [
            'from'   => $searchParams['from_city'] ?? $searchParams['from'] ?? 'Lagos',
            'to'     => $searchParams['to_city'] ?? $searchParams['to'] ?? 'Abuja',
            'depart' => $searchParams['depart'] ?? null,
        ];

        // Add return leg if round trip
        if ($trip === 'return') {
            $routes[] = [
                'from'   => $searchParams['to_city'] ?? $searchParams['to'] ?? 'Abuja',
                'to'     => $searchParams['from_city'] ?? $searchParams['from'] ?? 'Lagos',
                'depart' => $searchParams['returning'] ?? null,
            ];
        }
    }
@endphp

<div
    x-data="toast()"
    x-init="init()"
    x-on:flight-toast.window="showToast($event.detail.message, $event.detail.type)"
    class="tw-toast-container"
>
    <div 
        x-show="show"
        x-transition:enter="tw-toast-enter"
        x-transition:leave="tw-toast-leave"
        :class="type"
        class="tw-toast"
    >
        <div class="tw-toast-content">
            <span class="tw-toast-icon"><span class="sr-ic" :class="icon" aria-hidden="true"></span></span>
            <span class="tw-toast-message" x-text="message" style="color: #fff;"></span>
        </div>

        <button class="tw-toast-close" @click="show = false">×</button>
    </div>
</div>





{{-- ══ SINGLE ALPINE SCOPE wraps EVERYTHING ══ --}}
<div x-data="flightResults()" x-init="init()" x-effect="document.body.classList.toggle('sr-filter-open', filterSheetOpen)" x-on:skylink-results-ready.window="onSkylinkResults($event.detail.flights)" class="sr-results-shell">

    {{-- ══ TOPBAR ══ --}}
    <div class="sr-topbar">
        <div class="sr-topbar-inner">
            <div class="sr-tb-copy">
                <div class="sr-tb-route">
                    <span class="sr-tb-pin" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21s7-4.7 7-11a7 7 0 1 0-14 0c0 6.3 7 11 7 11Z"/>
                            <circle cx="12" cy="10" r="2.4"/>
                        </svg>
                    </span>
                    <span class="sr-tb-route-text">{{ $from }}</span>
                    <span class="sr-tb-route-arrow">&rarr;</span>
                    <span class="sr-tb-pin" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21s7-4.7 7-11a7 7 0 1 0-14 0c0 6.3 7 11 7 11Z"/>
                            <circle cx="12" cy="10" r="2.4"/>
                        </svg>
                    </span>
                    <span class="sr-tb-route-text">{{ $to }}</span>
                </div>
                <div class="sr-tb-meta">
                    <span class="sr-tb-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-8 0v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        {{ $totalPassengers }} passenger{{ $totalPassengers > 1 ? 's' : '' }}
                    </span>
                    <span class="sr-tb-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 9h16"/>
                            <path d="M5 9v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9"/>
                            <path d="M8 9V6a4 4 0 0 1 8 0v3"/>
                        </svg>
                        {{ $cabin }}
                    </span>
                    @if($depart)
                        <span class="sr-tb-meta-item sr-tb-date-pill">
                            {{ \Carbon\Carbon::createFromFormat('d/m/Y',$depart)->format('Y-m-d') }}
                            @if($trip === 'return' && $return)
                                &rarr; {{ \Carbon\Carbon::createFromFormat('d/m/Y',$return)->format('Y-m-d') }}
                            @endif
                        </span>
                    @endif
                </div>
            </div>

            {{-- Route pill --}}
            <div class="sr-tb-pill">
                <span class="sr-tb-pill-label">Route</span>
                <span class="sr-tb-pill-value">{{ $from }} → {{ $to }}</span>
            </div>

            <span class="sr-tb-sep"></span>

            {{-- Date pill --}}
            @if($depart)
            <div class="sr-tb-pill">
                <span class="sr-tb-pill-label">Depart</span>
                <span class="sr-tb-pill-value">{{ \Carbon\Carbon::createFromFormat('d/m/Y',$depart)->format('d M') }}</span>
            </div>
            @endif

            @if($trip === 'return' && $return)
            <div class="sr-tb-pill">
                <span class="sr-tb-pill-label">Return</span>
                <span class="sr-tb-pill-value">{{ \Carbon\Carbon::createFromFormat('d/m/Y',$return)->format('d M') }}</span>
            </div>
            @endif

            <span class="sr-tb-sep"></span>

            {{-- Passengers pill --}}
            <div class="sr-tb-pill">
                <span class="sr-tb-pill-label">Passengers</span>
                <span class="sr-tb-pill-value">{{ $totalPassengers }} Pax · {{ $cabin }}</span>
            </div>

            {{-- Modify button — INSIDE the same x-data scope --}}
            <button class="sr-tb-modify-btn" :class="{ active: modifyOpen }" @click="modifyOpen = !modifyOpen">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span x-text="modifyOpen ? 'Close' : 'Modify Search'"></span>
            </button>

        </div>
    </div>

    {{-- ══ Modify Search Backdrop ══ --}}
    {{-- ══ Modify Search Drop Modal ══ --}}
    <section
        class="sr-modify-inline"
        x-show="modifyOpen"
        x-transition:enter="sr-modify-transition"
        x-transition:enter-start="sr-modify-hidden"
        x-transition:enter-end="sr-modify-shown"
        x-transition:leave="sr-modify-transition"
        x-transition:leave-start="sr-modify-shown"
        x-transition:leave-end="sr-modify-hidden"
        style="display:none;">
        <div class="sr-modify-card">

        {{-- Modal Header --}}
        <div class="sr-modify-head">
            <div class="sr-modify-title">
                <span class="sr-modify-title-icon" aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 21l-4.3-4.3"/>
                        <circle cx="11" cy="11" r="7"/>
                    </svg>
                </span>
                <span>Modify search</span>
            </div>
            <button class="sr-modify-close" @click="modifyOpen = false" type="button" aria-label="Close modify search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Embedded Widget --}}
        <div class="sr-modify-body">
            @php
                $modifyWidgetDefaults = [
                    'trip' => $trip === 'oneway' ? 'OneWay' : ($trip === 'multi' ? 'multi' : 'Return'),
                    'from' => $from,
                    'to' => $to,
                    'depart' => $depart,
                    'returning' => $return,
                    'adults' => $adults,
                    'childs' => $childs,
                    'kids' => $kids,
                    'flightType' => $searchParams['flight_type'] ?? 'Y',
                    'multiLegs' => collect($routes)->map(fn ($route) => [
                        'from' => $route['from'] ?? '',
                        'to' => $route['to'] ?? '',
                        'depart' => $route['depart'] ?? '',
                        'cabin' => $searchParams['flight_type'] ?? 'Y',
                    ])->values()->all(),
                ];
            @endphp
            <script>
                window.travelwheelFlightWidgetDefaults = @json($modifyWidgetDefaults);
            </script>
            @include('livewire.pages.flight.flight-search')
        </div>

        </div>
    </section>

    {{-- ══ MAIN PAGE ══ --}}
    <div class="sr-page">

        {{-- ══ LEFT SIDEBAR ══ --}}
        <aside class="sr-sidebar">
            {{-- Real heading. This used to be drawn by .sr-sidebar::before —
                 a content string plus three gradient bars faking an icon. --}}
            <div class="sr-filters-head">
                <span class="sr-ic sr-ic-sliders" aria-hidden="true"></span>
                <span class="sr-filters-title">Filters</span>
                <span class="sr-filters-count" x-show="activeFilterCount > 0" x-cloak x-text="activeFilterCount"></span>
                <a class="sr-filters-reset"
                   href="#"
                   :aria-disabled="activeFilterCount === 0 ? 'true' : 'false'"
                   @click.prevent="activeFilterCount && resetAll()">Clear all</a>
            </div>

            {{-- Airlines --}}
            <div class="sr-panel">
                <div class="sr-panel-head">
                    <span class="sr-panel-title">Airlines</span>
                    <a class="sr-panel-reset" href="#" x-show="selectedAirlines.length > 0" x-cloak @click.prevent="resetAirlines()">Reset</a>
                </div>
                <div class="sr-panel-body">
                    <template x-for="airline in airlines" :key="airline.code">
                        <label class="sr-check-row" @click.prevent="toggleAirline(airline.code)">
                            <span class="sr-check-left">
                                <span class="sr-check-box" :class="{ checked: selectedAirlines.includes(airline.code) }"></span>
                                <span class="sr-check-name" x-text="airline.name" :title="airline.name"></span>
                            </span>
                            <span class="sr-check-price" x-text="airline.fromPrice"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Stops --}}
            <div class="sr-panel">
                <div class="sr-panel-head">
                    <span class="sr-panel-title">Stops</span>
                    <span class="sr-panel-sub">from origin</span>
                </div>
                <div class="sr-stop-pills">
                    <template x-for="stop in stopOptions" :key="stop.value">
                        <div class="sr-stop-pill" :class="{ active: selectedStop === stop.value }" @click="selectedStop = (selectedStop === stop.value ? null : stop.value)">
                            <span x-text="stop.label"></span>
                            <span class="sr-pill-sub" x-text="stop.sub"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Departure Time --}}
            <div class="sr-panel">
                <div class="sr-panel-head">
                    <span class="sr-panel-title">Departure</span>
                    <span class="sr-panel-sub">from origin</span>
                </div>
                <div class="sr-time-pills">
                    <template x-for="t in timeSlots" :key="t.value">
                        <div class="sr-time-pill" :class="{ active: selectedDepartTime === t.value }" @click="selectedDepartTime = (selectedDepartTime === t.value ? null : t.value)">
                            <span x-text="t.label"></span>
                            <span class="sr-pill-sub" x-text="t.range"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Arrival Time --}}
            <div class="sr-panel">
                <div class="sr-panel-head">
                    <span class="sr-panel-title">Arrival</span>
                    <span class="sr-panel-sub">at destination</span>
                </div>
                <div class="sr-time-pills">
                    <template x-for="t in timeSlots" :key="t.value">
                        <div class="sr-time-pill" :class="{ active: selectedArrivalTime === t.value }" @click="selectedArrivalTime = (selectedArrivalTime === t.value ? null : t.value)">
                            <span x-text="t.label"></span>
                            <span class="sr-pill-sub" x-text="t.range"></span>
                        </div>
                    </template>
                </div>
            </div>
        </aside>

        {{-- ══ MAIN CONTENT ══ --}}
        <div
            class="sr-filter-backdrop"
            x-cloak
            x-show="filterSheetOpen"
            x-transition.opacity
            @click="filterSheetOpen = false"
            aria-hidden="true"></div>

        <section
            class="sr-filter-sheet"
            x-cloak
            x-show="filterSheetOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-160"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            @keydown.escape.window="filterSheetOpen = false"
            role="dialog"
            aria-modal="true"
            aria-label="Flight filters">
            <div class="sr-filter-sheet-head">
                <div class="sr-filter-sheet-title">
                    <span>Filters</span>
                    <span x-text="activeFilterCount ? activeFilterCount + ' active filter' + (activeFilterCount > 1 ? 's' : '') : 'Refine flights by airline, stops, and time'"></span>
                </div>
                <button class="sr-filter-sheet-close" type="button" @click="filterSheetOpen = false" aria-label="Close filters">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="sr-filter-sheet-body">
                <div class="sr-panel">
                    <div class="sr-panel-head">
                        <span class="sr-panel-title">Airlines</span>
                        <a class="sr-panel-reset" @click.prevent="resetAirlines()">Reset</a>
                    </div>
                    <div class="sr-panel-body">
                        <template x-for="airline in airlines" :key="'mobile-airline-'+airline.code">
                            <label class="sr-check-row" @click.prevent="toggleAirline(airline.code)">
                                <span class="sr-check-left">
                                    <span class="sr-check-box" :class="{ checked: selectedAirlines.includes(airline.code) }"></span>
                                    <span class="sr-check-name" x-text="airline.name"></span>
                                </span>
                                <span class="sr-check-price" x-text="airline.fromPrice"></span>
                            </label>
                        </template>
                    </div>
                </div>
                <div class="sr-panel">
                    <div class="sr-panel-head">
                        <span class="sr-panel-title">Stops</span>
                        <span class="sr-panel-sub">from origin</span>
                    </div>
                    <div class="sr-stop-pills">
                        <template x-for="stop in stopOptions" :key="'mobile-stop-'+stop.value">
                            <div class="sr-stop-pill" :class="{ active: selectedStop === stop.value }" @click="selectedStop = (selectedStop === stop.value ? null : stop.value)">
                                <span x-text="stop.label"></span>
                                <span class="sr-pill-sub" x-text="stop.sub"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="sr-panel">
                    <div class="sr-panel-head">
                        <span class="sr-panel-title">Departure</span>
                        <span class="sr-panel-sub">from origin</span>
                    </div>
                    <div class="sr-time-pills">
                        <template x-for="t in timeSlots" :key="'mobile-depart-'+t.value">
                            <div class="sr-time-pill" :class="{ active: selectedDepartTime === t.value }" @click="selectedDepartTime = (selectedDepartTime === t.value ? null : t.value)">
                                <span x-text="t.label"></span>
                                <span class="sr-pill-sub" x-text="t.range"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="sr-panel">
                    <div class="sr-panel-head">
                        <span class="sr-panel-title">Arrival</span>
                        <span class="sr-panel-sub">at destination</span>
                    </div>
                    <div class="sr-time-pills">
                        <template x-for="t in timeSlots" :key="'mobile-arrival-'+t.value">
                            <div class="sr-time-pill" :class="{ active: selectedArrivalTime === t.value }" @click="selectedArrivalTime = (selectedArrivalTime === t.value ? null : t.value)">
                                <span x-text="t.label"></span>
                                <span class="sr-pill-sub" x-text="t.range"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="sr-filter-sheet-footer">
                <button class="sr-filter-sheet-clear" type="button" @click="resetAll()">Clear all</button>
                <button class="sr-filter-sheet-apply" type="button" @click="filterSheetOpen = false">
                    Show <span x-text="filteredFlights.length"></span> flights
                </button>
            </div>
        </section>

        <main class="sr-main">

            <div class="sr-mobile-filter-bar">
                <button
                    class="sr-mobile-filter-btn"
                    :class="{ 'has-filters': activeFilterCount > 0 }"
                    type="button"
                    @click="filterSheetOpen = true"
                    aria-label="Open flight filters">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 6h16"/>
                        <path d="M7 12h10"/>
                        <path d="M10 18h4"/>
                    </svg>
                    <span>Filters</span>
                    <span class="sr-mobile-filter-count" x-show="activeFilterCount > 0" x-text="activeFilterCount"></span>
                </button>
                <button
                    class="sr-mobile-filter-chip"
                    :class="{ active: sortBy === 'price' }"
                    type="button"
                    @click="sortBy = sortBy === 'price' ? 'recommended' : 'price'; activeFare = sortBy === 'price' ? 'cheapest' : 'recommended'">
                    Cheapest
                </button>
                <button
                    class="sr-mobile-filter-chip"
                    :class="{ active: selectedStop === 0 }"
                    type="button"
                    @click="selectedStop = selectedStop === 0 ? null : 0">
                    Non-stop
                </button>
            </div>

            {{-- Fare Matrix --}}
            <div class="sr-matrix" x-show="matrixAirlines.length > 0" x-cloak>
                <div class="sr-matrix-head">
                    <span class="sr-ic sr-ic-grid" aria-hidden="true"></span>
                    <span class="sr-matrix-title">Lowest fare by airline and stops</span>
                    <span class="sr-matrix-hint">Pick a fare to filter the results</span>
                </div>
                <div class="sr-matrix-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th class="sr-matrix-corner" scope="col">
                                    <span>Airline</span>
                                    <span>Stops</span>
                                </th>
                                <template x-for="col in matrixAirlines" :key="col.code">
                                    <th scope="col">
                                        <div class="airline-logo1">
                                            <img class="sr-mat-img" :src="col.logo" :alt="col.name" :title="col.name">
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in matrixRows" :key="row.label">
                                <tr>
                                    <th class="sr-matrix-row-label" scope="row" x-text="row.label"></th>
                                    <template x-for="col in matrixAirlines" :key="col.code">
                                        <td>
                                            <button type="button"
                                                    x-show="row.prices[col.code]"
                                                    class="sr-matrix-price"
                                                    :class="{ cheapest: row.prices[col.code] === cheapestPrice }"
                                                    :title="'Show ' + col.name + ' · ' + row.label + ' from ' + row.prices[col.code]"
                                                    @click="selectMatrixCell(col.code, row.stops)"
                                                    x-text="row.prices[col.code]"></button>
                                            <span x-show="!row.prices[col.code]" class="sr-matrix-empty" aria-label="No fare">—</span>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Best / Cheapest / Fastest. Ordered here rather than shuffled
                 with `order` on :nth-child, and each shows the fact that makes
                 it that pick — Best and Cheapest used to print the identical
                 number with nothing to tell them apart. --}}
            <div class="sr-fare-options" x-show="allFlights.length > 0" x-cloak>
                <button type="button" class="sr-fare-option" :class="{ active: activeFare === 'recommended' }"
                        @click="activeFare = 'recommended'; sortBy = 'recommended'">
                    <span class="sr-fare-option-ic"><span class="sr-ic sr-ic-lg sr-ic-award" aria-hidden="true"></span></span>
                    <span class="sr-fare-option-txt">
                        <span class="sr-fare-option-label">Best overall</span>
                        <span class="sr-fare-option-price" x-text="_bestPick.price || '—'"></span>
                        <span class="sr-fare-option-note" x-text="_bestPick.note"></span>
                    </span>
                </button>
                <button type="button" class="sr-fare-option" :class="{ active: activeFare === 'cheapest' }"
                        @click="activeFare = 'cheapest'; sortBy = 'price'">
                    <span class="sr-fare-option-ic"><span class="sr-ic sr-ic-lg sr-ic-tag" aria-hidden="true"></span></span>
                    <span class="sr-fare-option-txt">
                        <span class="sr-fare-option-label">Cheapest</span>
                        <span class="sr-fare-option-price" x-text="_cheapestPick.price || '—'"></span>
                        <span class="sr-fare-option-note" x-text="_cheapestPick.note"></span>
                    </span>
                </button>
                <button type="button" class="sr-fare-option" :class="{ active: activeFare === 'fastest' }"
                        @click="activeFare = 'fastest'; sortBy = 'duration'">
                    <span class="sr-fare-option-ic"><span class="sr-ic sr-ic-lg sr-ic-clock" aria-hidden="true"></span></span>
                    <span class="sr-fare-option-txt">
                        <span class="sr-fare-option-label">Fastest</span>
                        <span class="sr-fare-option-price" x-text="_fastestPick.price || '—'"></span>
                        <span class="sr-fare-option-note" x-text="_fastestPick.note"></span>
                    </span>
                </button>
            </div>

            {{-- Sort Bar --}}
            <div class="sr-sort-bar">
                <span class="sr-result-pill">
                    <span class="sr-ic sr-ic-sm sr-ic-list" aria-hidden="true"></span>
                    <span x-text="filteredFlights.length + ' flight' + (filteredFlights.length !== 1 ? 's' : '') + ' found'"></span>
                </span>
                <label class="sr-sort-select">
                    <span>Sort by</span>
                    <select x-model="sortBy">
                        <option value="recommended">Recommended</option>
                        <option value="price">Price: low to high</option>
                        <option value="duration">Duration: shortest</option>
                        <option value="depart">Departure time</option>
                    </select>
                </label>
            </div>

            <div class="sr-supplement-status" x-show="searchingMore" x-cloak>
                <span class="sr-supplement-spinner" aria-hidden="true"></span>
                <span>Searching more airlines…</span>
            </div>
            <div class="sr-supplement-status sr-supplement-status--done" x-show="!searchingMore && newlyAddedIds.length > 0" x-transition x-cloak
                 x-text="'+' + newlyAddedIds.length + ' more offer' + (newlyAddedIds.length !== 1 ? 's' : '') + ' found'"></div>

            {{-- ══ Flight Cards ══ --}}
            <template x-for="(flight, fi) in paginatedFlights" :key="flight.id">
                <div class="sr-card" :class="{
                    'sr-card-expanded': expandedId === flight.id,
                    'sr-card-multi': flight.multiLegs && flight.multiLegs.length > 0,
                    'sr-card-round': flight.returnSegments && flight.returnSegments.length > 0,
                    'sr-card-new': newlyAddedIds.includes(flight.id)
                }" :style="'animation-delay:' + (fi * 60) + 'ms'">

                    {{-- Itinerary panel: everything factual about the journey --}}
                    <div class="sr-card-main">

                    {{-- Head — carrier, cabin, and the two tags worth seeing before expanding --}}
                    <div class="sr-card-head">
                        <div class="sr-airline-logo-wrap">
                            <template x-if="flight.airlineLogo">
                                <img :src="flight.airlineLogo" :alt="flight.airline">
                            </template>
                            <template x-if="!flight.airlineLogo">
                                <span x-text="flight.airlineCode" style="font-size:8px;font-weight:800;color:var(--gray-600);text-align:center;line-height:1.2;"></span>
                            </template>
                        </div>
                        <div style="min-width:0;">
                            <div class="sr-card-airline" x-text="flight.airline"></div>
                            <div class="sr-card-class" x-text="flight.cabin"></div>
                        </div>
                        <div class="sr-head-tags">
                            <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                                <span class="sr-tag sr-tag-brand">
                                    <span class="sr-ic sr-ic-sm sr-ic-route" aria-hidden="true"></span>
                                    <span x-text="flight.multiLegs.length + ' legs'"></span>
                                </span>
                            </template>
                            <span class="sr-tag" :class="flight.isRefundable ? 'sr-tag-good' : 'sr-tag-warn'">
                                <span class="sr-ic sr-ic-sm" :class="flight.isRefundable ? 'sr-ic-refund' : 'sr-ic-cross'" aria-hidden="true"></span>
                                <span x-text="flight.isRefundable ? 'Refundable' : 'Non-refundable'"></span>
                            </span>
                        </div>
                    </div>

                    {{-- ── ONE WAY / RETURN ── --}}
                    <template x-if="!flight.multiLegs || flight.multiLegs.length === 0">
                        <div class="sr-depart-return">
                            {{-- Outbound --}}
                            <div class="sr-dr-col">
                                <div class="sr-dr-label">
                                    <span style="font-weight:700;color:var(--gray-900);">Depart</span>
                                    <span x-show="flight.departDateLabel" x-text="flight.departDateLabel"></span>
                                </div>
                                <div class="sr-segments">
                                    <div class="sr-seg">
                                        <div class="sr-seg-time" x-text="_time(flight.departTime)"></div>
                                        <div class="sr-seg-place" x-text="flight.segments[0]?.fromCity"></div>
                                    </div>
                                    <div class="sr-seg-line">
                                        <div class="sr-seg-duration" x-text="flight.totalTimeLabel"></div>
                                        <div class="sr-seg-track">
                                            <div class="sr-seg-dot"></div><div class="sr-seg-dash"></div><div class="sr-seg-dot"></div>
                                        </div>
                                        <div class="sr-seg-stop" :class="{ hasstop: flight.stops > 0 }"
                                            x-text="_stopLabel(flight.stops)"></div>
                                    </div>
                                    <div class="sr-seg">
                                        <div class="sr-seg-time" x-text="_time(flight.arriveTime)"></div>
                                        <div class="sr-seg-place" x-text="flight.segments[flight.segments.length-1]?.toCity"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- Return inbound --}}
                            <template x-if="flight.returnSegments && flight.returnSegments.length > 0">
                                <div class="sr-dr-col">
                                    <div class="sr-dr-label">
                                        <span style="font-weight:700;color:var(--gray-900);">Return</span>
                                        <span x-show="flight.returnDateLabel" x-text="flight.returnDateLabel"></span>
                                    </div>
                                    <div class="sr-segments">
                                        <div class="sr-seg">
                                            <div class="sr-seg-time" x-text="_time(flight.returnSegments[0]?.departTime)"></div>
                                            <div class="sr-seg-place" x-text="flight.returnSegments[0]?.fromCity"></div>
                                        </div>
                                        <div class="sr-seg-line">
                                            <div class="sr-seg-duration" x-text="flight.returnTotalTimeLabel || ''"></div>
                                            <div class="sr-seg-track">
                                                <div class="sr-seg-dot"></div><div class="sr-seg-dash"></div><div class="sr-seg-dot"></div>
                                            </div>
                                            <div class="sr-seg-stop" :class="{ hasstop: (flight.returnStops||0) > 0 }"
                                                x-text="_stopLabel(flight.returnStops||0)"></div>
                                        </div>
                                        <div class="sr-seg">
                                            <div class="sr-seg-time" x-text="_time(flight.returnSegments[flight.returnSegments.length-1]?.arriveTime)"></div>
                                            <div class="sr-seg-place" x-text="flight.returnSegments[flight.returnSegments.length-1]?.toCity"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                
                    {{-- ── MULTI-CITY grid ── --}}
                    <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                        <div class="mc-grid">
                            <template x-for="(leg, li) in flight.multiLegs" :key="'mcl-'+li">
                                <div class="mc-leg"
                                    :class="(flight.multiLegs.length % 2 !== 0 && li === flight.multiLegs.length - 1) ? 'mc-span' : ''">
                
                                    {{-- Label: first leg = Depart, rest = Connecting --}}
                                    <div class="mc-leg-lbl">
                                        <span style="font-weight:700;color:var(--gray-900);"
                                              x-text="li === 0 ? 'Depart' : 'Connecting'"></span>
                                        <span x-show="leg.departDateLabel"
                                            style="font-weight:500;color:var(--gray-500);"
                                            x-text="leg.departDateLabel"></span>
                                    </div>

                                    {{-- Airline row --}}
                                    <div class="mc-leg-airline">
                                        <template x-if="leg.segments[0]?.airlineLogo">
                                            <img :src="leg.segments[0].airlineLogo" :alt="leg.segments[0].airline">
                                        </template>
                                        <span x-text="leg.segments[0]?.airline || flight.validatingAirline"></span>
                                        <span style="color:var(--gray-400);" x-text="leg.segments[0]?.flightNo || ''"></span>
                                    </div>
                
                                    {{-- Times / route row --}}
                                    <div class="mc-row">
                                        <div class="mc-pt">
                                            <div class="mc-time" x-text="_time(leg.departTime)"></div>
                                            <div class="mc-city" x-text="leg.fromCity"></div>
                                        </div>
                                        <div class="mc-mid">
                                            <div class="mc-dur" x-text="leg.totalTimeLabel"></div>
                                            <div class="mc-track">
                                                <div class="mc-dot"></div>
                                                <div class="mc-dash"></div>
                                                <div class="mc-dot"></div>
                                            </div>
                                            <div class="mc-stop" :class="leg.stops === 0 ? 'direct' : 'hasstop'"
                                                x-text="_stopLabel(leg.stops)"></div>
                                        </div>
                                        <div class="mc-pt" style="text-align:right;align-items:flex-end;">
                                            <div class="mc-time" x-text="_time(leg.arriveTime)"></div>
                                            <div class="mc-city" x-text="leg.toCity"></div>
                                        </div>
                                    </div>
                
                                </div>
                            </template>
                        </div>
                    </template>
                
                    {{-- Allowances, seat scarcity and the expand control on one line --}}
                    <div class="sr-card-meta-clean">
                        <div class="sr-card-meta-item">
                            <span class="sr-ic sr-ic-sm sr-ic-cabin" aria-hidden="true"></span>
                            <strong x-text="_cabinBagLabel(flight)"></strong>
                            <span>cabin</span>
                        </div>
                        <div class="sr-card-meta-item">
                            <span class="sr-ic sr-ic-sm sr-ic-luggage" aria-hidden="true"></span>
                            <strong x-text="_luggageLabel(flight)"></strong>
                            <span>checked</span>
                        </div>
                        <div class="sr-card-meta-item" :class="{ low: _seatsLeft(flight) !== null && _seatsLeft(flight) <= 5 }">
                            <span class="sr-ic sr-ic-sm sr-ic-seat" aria-hidden="true"></span>
                            <strong x-text="_seatsLeft(flight) === null ? '—' : _seatsLeft(flight)"></strong>
                            <span>seats left</span>
                        </div>

                        <a class="sr-view-details"
                           href="#"
                           :aria-expanded="expandedId === flight.id ? 'true' : 'false'"
                           @click.prevent="toggleDetails(flight.id)">
                            <span x-text="expandedId === flight.id ? 'Hide details' : 'Flight details'"></span>
                            <span class="sr-ic sr-ic-sm sr-ic-chevron" aria-hidden="true"></span>
                        </a>
                    </div>

                </div>{{-- /sr-card-main --}}

                    {{-- Price rail: what it costs and how to buy it --}}
                    <div class="sr-card-price-wrap">
                        <div class="sr-rail-figures">
                            <div class="sr-card-price-label">Total fare</div>
                            <div class="sr-card-price" x-text="_fmtPrice(flight.price, flight.currency)"></div>
                            <div class="sr-card-price-note"
                                 x-text="_passengerCount(flight) > 1 ? ('for ' + _passengerCount(flight) + ' travellers') : 'all taxes included'"></div>
                        </div>
                        <div class="sr-card-actions">
                            <button class="sr-book-btn" @click="selectFlight(flight)">Book now</button>
                            <button
                                class="sr-installment-btn"
                                type="button"
                                :disabled="!canUseTravelFlex(flight)"
                                :title="canUseTravelFlex(flight) ? 'Continue with TravelFlex' : travelFlexUnavailableReason(flight)"
                                @click="selectTravelFlex(flight)">
                                <span class="sr-installment-btn-price" x-show="canUseTravelFlex(flight)" x-cloak x-text="_travelFlexInstallmentPrice(flight)"></span>
                                <span class="sr-installment-btn-label">TravelFlex</span>
                            </button>
                        </div>
                    </div>

                    {{-- ══ Expandable Detail Panel ══ --}}
                    <div class="sr-detail-panel" x-show="expandedId === flight.id" x-transition>

                        <div class="sr-detail-tabs">
                            <div class="sr-detail-tab" :class="{ active: (activeTab[flight.id]||'details') === 'details' }" @click="setTab(flight.id,'details')">Flight Details</div>
                            <div class="sr-detail-tab" :class="{ active: activeTab[flight.id] === 'rules' }" @click="setTab(flight.id,'rules')">Fare Rules</div>
                        </div>

                        <template x-if="(activeTab[flight.id]||'details') === 'details'">
                            <div class="sr-detail-body">

                                {{-- ── ONE WAY / RETURN ── --}}
                                <template x-if="!flight.multiLegs || flight.multiLegs.length === 0">
                                    <div :class="(flight.returnSegments && flight.returnSegments.length > 0) ? 'sr-detail-cols' : ''">

                                        {{-- Outbound --}}
                                        <div class="sr-detail-col">
                                            <div class="sr-detail-leg-head">
                                                <span class="sr-detail-leg-title"
                                                    x-text="(flight.segments[0]?.fromCity||'') + ' to ' + (flight.segments[flight.segments.length-1]?.toCity||'')">
                                                </span>
                                                <span class="sr-detail-leg-badge">Outbound</span>
                                                <span class="sr-detail-leg-date" x-show="flight.departDateLabel" x-text="flight.departDateLabel"></span>
                                            </div>
                                            @include('livewire.pages.flight.partials.flight-timeline', [
                                                'segments' => 'flight.segments',
                                                'layovers' => 'flight.layoverDurations',
                                                'key' => 'd-out-',
                                                'legIndex' => '0',
                                            ])
                                        </div>

                                        {{-- Return inbound --}}
                                        <template x-if="flight.returnSegments && flight.returnSegments.length > 0">
                                            <div class="sr-detail-col">
                                                <div class="sr-detail-leg-head">
                                                    <span class="sr-detail-leg-title"
                                                        x-text="(flight.returnSegments[0]?.fromCity||'') + ' to ' + (flight.returnSegments[flight.returnSegments.length-1]?.toCity||'')">
                                                    </span>
                                                    <span class="sr-detail-leg-badge inbound">Inbound</span>
                                                    <span class="sr-detail-leg-date" x-show="flight.returnDateLabel" x-text="flight.returnDateLabel"></span>
                                                </div>
                                                @include('livewire.pages.flight.partials.flight-timeline', [
                                                    'segments' => 'flight.returnSegments',
                                                    'layovers' => 'flight.returnLayoverDurations',
                                                    'key' => 'd-ret-',
                                                    'legIndex' => '0',
                                                ])
                                            </div>
                                        </template>

                                    </div>
                                </template>

                                {{-- ── MULTI-CITY — one timeline per leg ── --}}
                                <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                                    <div class="sr-multi-detail-grid">
                                        <template x-for="(leg, li) in flight.multiLegs" :key="'det-leg-'+li">
                                            <div class="sr-multi-detail-leg">
                                                <div class="sr-detail-leg-head">
                                                    <span class="sr-detail-leg-title"
                                                        x-text="(leg.fromCity||'') + ' to ' + (leg.toCity||'')">
                                                    </span>
                                                    <span class="sr-detail-leg-badge"
                                                        :class="li === 0 ? '' : 'connecting'"
                                                        x-text="li === 0 ? 'Depart' : 'Leg ' + (li+1)">
                                                    </span>
                                                    <span class="sr-detail-leg-date" x-show="leg.departDateLabel" x-text="leg.departDateLabel"></span>
                                                </div>
                                                @include('livewire.pages.flight.partials.flight-timeline', [
                                                    'segments' => 'leg.segments',
                                                    'layovers' => 'leg.layoverDurations',
                                                    'key' => 'det-l-',
                                                    'legIndex' => 'li',
                                                ])
                                            </div>
                                        </template>
                                    </div>
                                </template>

                            </div>
                        </template>

                        {{-- Fare Rules Tab --}}
                        <template x-if="activeTab[flight.id] === 'rules'">
                            <div class="sr-fare-rules-body">

                                <template x-for="fb in flight.fareBreakdown" :key="fb.passengerType">
                                    <div class="sr-fare-group">
                                        <div class="sr-fare-group-head">
                                            <span class="sr-fare-group-title"
                                                  x-text="fb.passengerType==='ADT'?'Adult':fb.passengerType==='CHD'?'Child':'Infant'"></span>
                                            <span class="sr-fare-group-qty" x-show="fb.qty > 1" x-text="'× ' + fb.qty"></span>
                                        </div>

                                        <div class="sr-policy-grid">
                                            <div class="sr-policy">
                                                <span class="sr-policy-ic"><span class="sr-ic sr-ic-lg sr-ic-luggage" aria-hidden="true"></span></span>
                                                <span class="sr-policy-txt">
                                                    <span class="sr-policy-label">Checked baggage</span>
                                                    <span class="sr-policy-val" x-text="fb.baggage?.[0] || '—'"></span>
                                                </span>
                                            </div>

                                            <div class="sr-policy">
                                                <span class="sr-policy-ic"><span class="sr-ic sr-ic-lg sr-ic-cabin" aria-hidden="true"></span></span>
                                                <span class="sr-policy-txt">
                                                    <span class="sr-policy-label">Cabin baggage</span>
                                                    <span class="sr-policy-val" x-text="cabinBagLabel(fb.cabinBaggage?.[0])"></span>
                                                </span>
                                            </div>

                                            <div class="sr-policy" :class="fb.refundAllowed ? 'good' : 'bad'">
                                                <span class="sr-policy-ic"><span class="sr-ic sr-ic-lg sr-ic-refund" aria-hidden="true"></span></span>
                                                <span class="sr-policy-txt">
                                                    <span class="sr-policy-label">Refunds</span>
                                                    <span class="sr-policy-val" x-text="fb.refundAllowed ? 'Allowed' : 'Not allowed'"></span>
                                                </span>
                                            </div>

                                            {{-- fb.changeAllowed is null for suppliers (SkyLink) that don't expose a
                                                 change policy at all — showing a hard "Not allowed" there would be a
                                                 guess, not a fact, so it gets neutral styling and a plain note rather
                                                 than the red/green treatment the other policies carry. --}}
                                            <div class="sr-policy" :class="fb.changeAllowed === null ? '' : (fb.changeAllowed ? 'good' : 'bad')">
                                                <span class="sr-policy-ic">
                                                    <span class="sr-ic sr-ic-lg" :class="fb.changeAllowed === null ? 'sr-ic-info' : 'sr-ic-change'" aria-hidden="true"></span>
                                                </span>
                                                <span class="sr-policy-txt">
                                                    <span class="sr-policy-label">Date changes</span>
                                                    <span class="sr-policy-val"
                                                          x-text="fb.changeAllowed === null ? 'Not specified' : (fb.changeAllowed ? 'Allowed' : 'Not allowed')"></span>
                                                    <span class="sr-policy-note" x-show="fb.changeAllowed === null">
                                                        This airline didn't publish a change policy. Ask us before booking if you may need to move your dates.
                                                    </span>
                                                    <span class="sr-policy-note" x-show="fb.changeAllowed === true"
                                                          x-text="'Penalty ' + _fmtPrice(fb.changePenalty, flight.currency)"></span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="sr-fare-money">
                                            <div class="sr-fare-money-row">
                                                <span x-text="'Base fare' + (fb.qty > 1 ? ' × ' + fb.qty : '')"></span>
                                                <span class="v" x-text="_fmtPrice(fb.baseFare * fb.qty, flight.currency)"></span>
                                            </div>
                                            <div class="sr-fare-money-row total">
                                                <span>Total for this traveller type</span>
                                                <span class="v" x-text="_fmtPrice(fb.totalFare * fb.qty, flight.currency)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- SkyLink returns no per-passenger fare breakdown at all, so this
                                     tab would otherwise render as a blank panel. --}}
                                <template x-if="!flight.fareBreakdown || flight.fareBreakdown.length === 0">
                                    <div class="sr-rules-empty">
                                        <span class="sr-ic sr-ic-lg sr-ic-info" aria-hidden="true"></span>
                                        <span>
                                            Detailed fare rules aren't published for this fare. The allowances shown on
                                            the card apply — contact us if you need the full conditions before booking.
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="sr-detail-footer">
                            <span class="sr-detail-footer-note"
                                  x-text="'Total fare ' + _fmtPrice(flight.price, flight.currency) + (_passengerCount(flight) > 1 ? ' for ' + _passengerCount(flight) + ' travellers' : '')"></span>
                            <button class="sr-book-btn" @click="selectFlight(flight)">Book now</button>
                        </div>

                    </div>{{-- /sr-detail-panel --}}

                </div>{{-- /sr-card --}}
            </template>

            {{-- Load More --}}
            <template x-if="filteredFlights.length > pageSize">
                <div class="sr-load-more">
                    <button class="sr-book-btn" style="background:var(--gray-100);color:var(--gray-700);box-shadow:none;border:1.5px solid var(--gray-200);" @click="pageSize += 5" x-text="'Show more (' + (filteredFlights.length - pageSize) + ' remaining)'"></button>
                </div>
            </template>

            {{-- No results --}}
            <template x-if="filteredFlights.length === 0">
                <div class="sr-empty-results" style="text-align:center;padding:48px 24px;background:#fff;border-radius:var(--radius);border:1px solid var(--gray-200);">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:12px;background:var(--blue-lt);color:var(--blue);margin-bottom:14px;">
                        <span class="sr-ic sr-ic-search" style="width:22px;height:22px;flex-basis:22px;" aria-hidden="true"></span>
                    </span>
                    <div style="font-size:16px;font-weight:700;color:var(--gray-900);margin-bottom:6px;">No flights match these filters</div>
                    <div style="font-size:13px;color:var(--gray-500);">Widen a filter — stops, times or airlines — to see more of this route.</div>
                    <button class="sr-book-btn" style="width:auto;margin-top:18px;" @click="resetAll()">Clear all filters</button>
                </div>
            </template>

        </main>

        {{-- ══ RIGHT RAIL ══ --}}
        <aside class="sr-rail">
            <div class="sr-promo" 
                x-data="{
                    current: 0,
                    timer: null,
                    items: [
                        { label:'Hotel Booking',    title:'Find the best hotel deals',        body:'Book top-rated hotels at better prices worldwide.',         cta:'Explore Hotels', link:'#' },
                        { label:'Airport Protocol', title:'VIP airport assistance',           body:'Skip queues and enjoy fast-track services at the airport.', cta:'Book Protocol',  link:'#' },
                        { label:'Airport Lounge',   title:'Relax before your flight',         body:'Access premium airport lounges around the world.',          cta:'View Lounges',   link:'#' },
                        { label:'Travel Insurance', title:'Travel with peace of mind',        body:'Coverage for delays, medical emergencies, and more.',       cta:'Get Covered',    link:'#' },
                        { label:'Visa Assistance',  title:'Hassle-free visa processing',      body:'Let us handle your visa application start to finish.',      cta:'Apply Now',      link:'#' },
                        { label:'Air Cargo',        title:'Fast & Reliable cargo delivery', body:'Ship goods locally and internationally with ease.',         cta:'Send Cargo',     link:'#' }
                    ],
                    goTo(idx) {
                        this.current = (idx + this.items.length) % this.items.length;
                    },
                    next() { this.goTo(this.current + 1); },
                    prev() { this.goTo(this.current - 1); },
                    start() {
                        this.pause();
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        this.timer = setInterval(() => this.next(), 5000);
                    },
                    pause() { clearInterval(this.timer); }
                }"
                x-init="start()"
                @mouseenter="pause()"
                @mouseleave="start()">

                <div class="sr-promo-slides">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="sr-promo-slide" :class="{ active: current === i }" :aria-hidden="current === i ? 'false' : 'true'">
                            <span class="sr-promo-chip">
                                <span class="sr-promo-chip-dot"></span>
                                <span x-text="item.label"></span>
                            </span>
                            <div class="sr-promo-title" x-text="item.title"></div>
                            <div class="sr-promo-body" x-text="item.body"></div>
                            <a class="sr-promo-btn" :href="item.link" :tabindex="current === i ? 0 : -1">
                                <span x-text="item.cta"></span>
                                <span class="sr-ic sr-ic-sm sr-ic-chevron" style="transform:rotate(-90deg);" aria-hidden="true"></span>
                            </a>
                        </div>
                    </template>
                </div>

                {{-- The card rotates on a timer; without these it changed under
                     the reader with no way to see where they were or go back. --}}
                <div class="sr-promo-dots">
                    <template x-for="(item, i) in items" :key="'dot-'+i">
                        <button type="button"
                                class="sr-promo-dot"
                                :class="{ active: current === i }"
                                :aria-label="'Show ' + item.label"
                                :aria-current="current === i ? 'true' : 'false'"
                                @click="goTo(i)"></button>
                    </template>
                </div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon"><span class="sr-ic sr-ic-refund" aria-hidden="true"></span></span> Flexible Booking</div>
                <div class="sr-tip-body">Look for <span class="sr-tip-highlight">refundable</span> fares if your plans may change. Most ValueJet routes offer free cancellation within 24hrs.</div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon"><span class="sr-ic sr-ic-card" aria-hidden="true"></span></span> Travel Flex</div>
                <div class="sr-tip-body">Apply for <span class="sr-tip-highlight">TravelFlex financing</span> through Fast Credit and spread eligible travel costs over an approved repayment plan — available at checkout.</div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon"><span class="sr-ic sr-ic-clock" aria-hidden="true"></span></span> Best Time to Fly</div>
                <div class="sr-tip-body">Morning departures (6–9AM) typically have the <span class="sr-tip-highlight">lowest delay rates</span> on the LOS–PHC route.</div>
            </div>
        </aside>

    </div>{{-- /sr-page --}}

</div>{{-- /x-data wrapper --}}

<script>
    function flightResults() {
        return {

            // ── KEY FIX: modifyOpen added here ──
            modifyOpen: false,
            filterSheetOpen: false,

            selectedAirlines: [],
            selectedStop: null,
            selectedDepartTime: null,
            selectedArrivalTime: null,
            sortBy: 'recommended',
            activeFare: 'recommended',
            pageSize: 5,

            // ── SkyLink live supplement (Phase 2) ──────────────────────────
            // True until the loadSkylinkResults() event fires (success or
            // failure) — see FlightPage::loadSkylinkResults().
            searchingMore: true,
            // Ids of flights merged in from the supplement, for the brief
            // highlight animation and the "+N more offers found" message.
            newlyAddedIds: [],
            // Phase 3's gateway-only booking flow (passenger details → pay →
            // SkylinkFlightService::reserve() only fires after payment is
            // verified) is live — see FlightBookingController::book()/
            // _completeSkylinkReservation(). A duplicate offer now keeps
            // whichever supplier is genuinely cheaper, same as any other.
            skylinkBookable: true,

            expandedId: null,
            activeTab:  {},

            cabinBagLabel(value) {
                const label = String(value || '').trim();
                return label.toUpperCase() === 'SB' ? '7KG' : (label || '—');
            },

            // TravelNext's fareBreakdown carries baggage per passenger type
            // (optionally per leg, via legIndex); SkyLink has no such
            // breakdown (always []) but does carry real baggage info per
            // segment — fall back to that (the specific segment in scope,
            // when given one) instead of always showing "-" for SkyLink.
            // A multi-city itinerary carries no top-level `segments` array — its
            // flights live under multiLegs[n].segments — so falling back to
            // flight.segments[0] alone left every multi-city card showing a
            // dash for both allowances.
            _firstSegment(flight) {
                return flight.segments?.[0] || flight.multiLegs?.[0]?.segments?.[0] || null;
            },

            _luggageLabel(flight, seg = null, legIndex = 0) {
                const breakdown = flight.fareBreakdown?.[0]?.baggage;
                const fromBreakdown = breakdown?.[legIndex] || breakdown?.[0];
                if (fromBreakdown) return fromBreakdown;

                return (seg || this._firstSegment(flight))?.baggage || '—';
            },

            _cabinBagLabel(flight, seg = null, legIndex = 0) {
                const breakdown = flight.fareBreakdown?.[0]?.cabinBaggage;
                const fromBreakdown = breakdown?.[legIndex] || breakdown?.[0];
                if (fromBreakdown) return this.cabinBagLabel(fromBreakdown);

                return this.cabinBagLabel((seg || this._firstSegment(flight))?.cabinBaggage);
            },

            // SkyLink reports 12-hour clock times ("07:15 pm"); TravelNext
            // reports 24-hour ("19:15"). Both suppliers' results sit in the
            // same list, so two cards for the same departure could print the
            // time two different ways. Normalise at render time only — the
            // underlying value still goes to the booking payload untouched.
            _time(value) {
                const raw = String(value ?? '').trim();
                const parts = raw.match(/^(\d{1,2}):(\d{2})\s*([ap])\.?m\.?$/i);
                if (! parts) return raw;

                const hour = (Number(parts[1]) % 12) + (parts[3].toLowerCase() === 'p' ? 12 : 0);

                return String(hour).padStart(2, '0') + ':' + parts[2];
            },

            _stopLabel(stops) {
                const n = Number(stops) || 0;
                return n === 0 ? 'Non-stop' : (n === 1 ? '1 stop' : n + ' stops');
            },

            // Seats remaining on the first segment — the binding constraint on
            // the whole itinerary. null when the supplier didn't report it, so
            // the card can show an em dash rather than the string "undefined".
            _seatsLeft(flight) {
                const seats = this._firstSegment(flight)?.seatsLeft;

                return (seats === null || seats === undefined || seats === '') ? null : Number(seats);
            },

            _passengerCount(flight) {
                const counted = (flight.fareBreakdown || [])
                    .reduce((total, fb) => total + (Number(fb.qty) || 0), 0);

                return counted > 0 ? counted : (Number(flight.markupPassengerCount) || 1);
            },

            // 24-hour ranges, matching how every other time on this page is
            // printed now (see _time()) — the pills used to be the only
            // 12-hour clock left on the results page.
            timeSlots: [
                { value: 'morning',   label: 'Morning',   range: '00:00–11:59' },
                { value: 'afternoon', label: 'Afternoon', range: '12:00–17:59' },
                { value: 'evening',   label: 'Evening',   range: '18:00–23:59' },
            ],

            stopOptions: [
                { value: 0, label: 'Non-stop', sub: '' },
                { value: 1, label: '1 stop',   sub: '' },
                { value: 2, label: '2+ stops', sub: '' },
            ],

            allFlights: @js($flightResults),

            airlines: [],
            matrixAirlines: [],
            matrixRows: [],
            cheapestPrice: '',

            get activeFilterCount() {
                return this.selectedAirlines.length
                    + (this.selectedStop !== null ? 1 : 0)
                    + (this.selectedDepartTime ? 1 : 0)
                    + (this.selectedArrivalTime ? 1 : 0);
            },

            // The three shortcut cards above the list. "Best" used to print
            // cheapestPrice — the same number as "Cheapest", with nothing to
            // distinguish them — so it now reports the flight the recommended
            // ranking actually puts first. Each card also carries the fact
            // that earned it the slot, since the price alone doesn't say why.
            _pick(flight, note) {
                if (! flight) return { price: '', note: '' };

                return { price: this._fmtPrice(flight.price, flight.currency), note };
            },

            get _bestPick() {
                const ranked = this._sortByRecommended([...this.allFlights]);
                const best = ranked[0];
                if (! best) return { price: '', note: '' };

                return this._pick(best, best.airline + ' · ' + this._stopLabel(best.stops).toLowerCase());
            },

            get _cheapestPick() {
                const cheapest = [...this.allFlights].sort((a, b) => a.price - b.price)[0];
                if (! cheapest) return { price: '', note: '' };

                return this._pick(cheapest, cheapest.airline);
            },

            get _fastestPick() {
                const fastest = [...this.allFlights].sort((a, b) => a.totalDuration - b.totalDuration)[0];
                if (! fastest) return { price: '', note: '' };

                return this._pick(fastest, fastest.totalTimeLabel || fastest.airline);
            },

            get filteredFlights() {
                let flights = [...this.allFlights];
                if (this.selectedAirlines.length > 0)
                    flights = flights.filter(f => this.selectedAirlines.includes(f.airlineCode));
                if (this.selectedStop !== null)
                    flights = flights.filter(f => this.selectedStop >= 2 ? f.stops >= 2 : f.stops === this.selectedStop);
                if (this.selectedDepartTime)
                    flights = flights.filter(f => f.departSlot === this.selectedDepartTime);
                if (this.selectedArrivalTime)
                    flights = flights.filter(f => f.arrivalSlot === this.selectedArrivalTime);
                if (this.sortBy === 'price')
                    flights.sort((a, b) => a.price - b.price);
                else if (this.sortBy === 'duration')
                    flights.sort((a, b) => a.totalDuration - b.totalDuration);
                else if (this.sortBy === 'depart')
                    flights.sort((a, b) => a.departTime.localeCompare(b.departTime));
                else if (this.sortBy === 'recommended')
                    this._sortByRecommended(flights);
                return flights;
            },

            get paginatedFlights() {
                return this.filteredFlights.slice(0, this.pageSize);
            },

            init() {
                this._buildDerivedData();
            },

            // ── SkyLink live supplement ─────────────────────────────────────
            // Called from the x-on:skylink-results-ready.window listener once
            // FlightPage::loadSkylinkResults() (fired via wire:init) resolves
            // — with a real list on success, or an empty one on any error/
            // timeout, so this always runs exactly once per page load.
            onSkylinkResults(flights) {
                this.searchingMore = false;

                if (!Array.isArray(flights) || flights.length === 0) return;

                const { merged, addedIds } = this._dedupeMerge(this.allFlights, flights);
                this.allFlights = merged;
                this.newlyAddedIds = addedIds;
                this._buildDerivedData();
            },

            // Matches the same physical flight across suppliers: identical
            // airline + flight number for every leg (outbound and return) at
            // the same departure instant and cabin. Exact-match on purpose —
            // a false "not a duplicate" just shows two cards instead of one
            // (harmless), whereas a fuzzy match risks wrongly hiding a
            // genuinely different, cheaper flight.
            _flightSignature(flight) {
                const legs = [...(flight.segments || []), ...(flight.returnSegments || [])];
                const chain = legs.map(s => `${s.airlineCode}${s.flightNo}@${s.departDT}`).join('|');

                return `${chain}|${String(flight.cabinCode || '').toUpperCase()}`;
            },

            // Merges incoming (SkyLink) flights into the existing list.
            // Duplicates of an existing flight are resolved by price — the
            // cheaper supplier wins, since both are bookable (skylinkBookable
            // gates this: while false, a duplicate would keep its bookable
            // TravelNext copy instead, so a customer is never shown a lower
            // price they can't actually select). Non-duplicate SkyLink
            // flights are always added — the real inventory expansion.
            _dedupeMerge(existing, incoming) {
                const bySignature = new Map(existing.map(f => [this._flightSignature(f), f]));
                const merged = [...existing];
                const addedIds = [];

                incoming.forEach((flight, index) => {
                    flight.id = 'sky-' + index;
                    const signature = this._flightSignature(flight);
                    const duplicate = bySignature.get(signature);

                    if (!duplicate) {
                        bySignature.set(signature, flight);
                        merged.push(flight);
                        addedIds.push(flight.id);

                        return;
                    }

                    if (this.skylinkBookable && flight.price < duplicate.price) {
                        const idx = merged.indexOf(duplicate);
                        if (idx !== -1) merged[idx] = flight;
                        bySignature.set(signature, flight);
                        addedIds.push(flight.id);
                    }
                });

                return { merged, addedIds };
            },

            _hasCheckedBaggage(flight) {
                const raw = String(flight.segments?.[0]?.baggage || '').trim().toUpperCase();

                return raw !== '' && !/^0\s*(KG|PC)?$/.test(raw);
            },

            // "Recommended" — unlike Cheapest/Fastest/Departure, which are
            // literal single-factor sorts — blends price, flight time, stop
            // count, and whether checked baggage is included, normalized
            // against this result set. Price dominates; the rest break ties
            // between similarly-priced options. Mutates in place, matching
            // the other branches in filteredFlights().
            _sortByRecommended(flights) {
                const prices = flights.map(f => f.price);
                const durations = flights.map(f => f.totalDuration);
                const minPrice = Math.min(...prices), maxPrice = Math.max(...prices);
                const minDuration = Math.min(...durations), maxDuration = Math.max(...durations);

                const score = (f) => {
                    const priceScore = maxPrice > minPrice ? 1 - (f.price - minPrice) / (maxPrice - minPrice) : 1;
                    const durationScore = maxDuration > minDuration ? 1 - (f.totalDuration - minDuration) / (maxDuration - minDuration) : 1;
                    const stopsScore = f.stops === 0 ? 1 : (f.stops === 1 ? 0.5 : 0);
                    const baggageScore = this._hasCheckedBaggage(f) ? 1 : 0;

                    return (priceScore * 0.55) + (durationScore * 0.20) + (stopsScore * 0.15) + (baggageScore * 0.10);
                };

                // Sorts in place (matching the other branches in
                // filteredFlights) and hands the array back, so callers that
                // only want the top-ranked flight can read it off directly.
                return flights.sort((a, b) => score(b) - score(a));
            },

            _buildDerivedData() {
                const airlineMap = {};
                let cheapest = Infinity;

                this.allFlights.forEach(f => {
                    if (!airlineMap[f.airlineCode]) {
                        airlineMap[f.airlineCode] = {
                            name: f.airline,
                            logo: f.airlineLogo || '/assets/img/airlines/default.png',
                            minPrice: f.price
                        };
                    } else if (f.price < airlineMap[f.airlineCode].minPrice) {
                        airlineMap[f.airlineCode].minPrice = f.price;
                    }
                    if (f.price < cheapest) cheapest = f.price;
                });

                this.airlines = Object.entries(airlineMap)
                    .map(([code, d]) => ({
                        code,
                        name: d.name,
                        airlineLogo: d.logo,
                        fromPrice: this._fmtPrice(d.minPrice, this.allFlights[0]?.currency),
                    }))
                    .sort((a, b) => airlineMap[a.code].minPrice - airlineMap[b.code].minPrice);

                this.cheapestPrice = cheapest < Infinity
                    ? this._fmtPrice(cheapest, this.allFlights[0]?.currency) : '';

                [0, 1, 2].forEach(sv => {
                    const group = this.allFlights.filter(f => sv >= 2 ? f.stops >= 2 : f.stops === sv);
                    const min   = group.length ? Math.min(...group.map(f => f.price)) : null;
                    this.stopOptions[sv].sub = min !== null
                        ? this._fmtPrice(min, this.allFlights[0]?.currency) : '';
                });

                this.matrixAirlines = Object.entries(airlineMap)
                    .map(([code, d]) => ({ code, name: d.name, logo: d.logo }));

                // Labels are the ones rendered, so they read the same here as
                // in the stops filter. The template used to remap two of the
                // three on the way out, which meant the matrix and the filter
                // called the same thing by different names.
                this.matrixRows = [
                    { label: 'Non-stop', stops: 0, prices: {} },
                    { label: '1 stop',   stops: 1, prices: {} },
                    { label: '2+ stops', stops: 2, prices: {} },
                ];

                this.allFlights.forEach(f => {
                    const rowIdx = f.stops === 0 ? 0 : f.stops === 1 ? 1 : 2;
                    const cur    = this.matrixRows[rowIdx].prices[f.airlineCode];
                    if (!cur || f.price < parseFloat(cur.replace(/[^0-9.]/g, ''))) {
                        this.matrixRows[rowIdx].prices[f.airlineCode] =
                            this._fmtPrice(f.price, f.currency);
                    }
                });
            },

            _fmtPrice(amount, currency) {
                if (!amount && amount !== 0) return '';
                const sym = currency === 'NGN' ? '₦' : currency === 'USD' ? '$' : (currency || '');
                const value = parseFloat(amount);
                // Air fares in Naira land on whole numbers far more often than
                // not, and a column of ".00" endings is pure noise in a list
                // whose whole job is price comparison. Kobo still show when a
                // fare actually has them.
                const decimals = Number.isInteger(value) ? 0 : 2;

                return sym + value.toLocaleString('en-NG', {
                    minimumFractionDigits: decimals, maximumFractionDigits: decimals
                });
            },

            _travelFlexInstallmentPrice(flight) {
                const total = parseFloat(flight?.price);
                if (!Number.isFinite(total)) return '';

                return this._fmtPrice((total * 1.04) / 4, flight?.currency);
            },

            toggleDetails(id) {
                this.expandedId = (this.expandedId === id) ? null : id;
                if (!this.activeTab[id]) this.activeTab[id] = 'details';
            },

            setTab(id, tab) {
                this.activeTab[id] = tab;
            },

            toggleAirline(code) {
                if (this.selectedAirlines.includes(code))
                    this.selectedAirlines = this.selectedAirlines.filter(c => c !== code);
                else
                    this.selectedAirlines.push(code);
            },

            selectMatrixCell(code, stops) {
                this.selectedAirlines = [code];
                this.selectedStop = stops;
                this.pageSize = 5;
            },

            resetAirlines() { this.selectedAirlines = []; },

            resetAll() {
                this.selectedAirlines    = [];
                this.selectedStop        = null;
                this.selectedDepartTime  = null;
                this.selectedArrivalTime = null;
                this.sortBy = 'recommended';
            },

            _travelFlexDepartDate(flight) {
                const candidates = [
                    flight.departDate,
                    flight.departureDate,
                    flight.departDateRaw,
                    flight.departDT,
                    flight.departureDateTime,
                    flight.segments?.[0]?.departDate,
                    flight.segments?.[0]?.departureDate,
                    flight.segments?.[0]?.departDT,
                    flight.multiLegs?.[0]?.departDate,
                    flight.multiLegs?.[0]?.segments?.[0]?.departDate,
                    flight.multiLegs?.[0]?.segments?.[0]?.departDT,
                ].filter(Boolean);

                for (const value of candidates) {
                    const date = new Date(value);
                    if (!Number.isNaN(date.getTime())) return date;
                }

                return null;
            },

            canUseTravelFlex(flight) {
                if (!flight?.isRefundable) return false;

                const departDate = this._travelFlexDepartDate(flight);
                if (!departDate) return true;

                const dayMs = 24 * 60 * 60 * 1000;
                return Math.floor((departDate.getTime() - Date.now()) / dayMs) >= 14;
            },

            travelFlexUnavailableReason(flight) {
                if (!flight?.isRefundable) return 'TravelFlex is only available for refundable fares.';

                const departDate = this._travelFlexDepartDate(flight);
                if (departDate) {
                    const dayMs = 24 * 60 * 60 * 1000;
                    if (Math.floor((departDate.getTime() - Date.now()) / dayMs) < 14) {
                        return 'TravelFlex is available when departure is at least 14 days away.';
                    }
                }

                return 'TravelFlex is not available for this fare.';
            },

            selectTravelFlex(flight) {
                if (!this.canUseTravelFlex(flight)) return;
                this.selectFlight(flight, 'travelflex');
            },

            selectFlight(flight, intent = 'booking') {
                // Kept as a live kill switch, not dead code: if skylinkBookable
                // is ever flipped back to false (e.g. rolling back live SkyLink
                // testing), this re-activates automatically with no other
                // change needed. Never expose *why* a fare can't be booked —
                // that would leak which flights came from which supplier —
                // just a generic, could-happen-to-any-fare message.
                if (flight.source === 'skylink' && !this.skylinkBookable) {
                    window.dispatchEvent(new CustomEvent('flight-toast', {
                        detail: { message: 'This fare could not be confirmed right now. Please select another option.', type: 'error' },
                    }));

                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("flights.select") }}';
                form.style.display = 'none';
                const csrf = document.createElement('input');
                csrf.type = 'hidden'; csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                const fsc = document.createElement('input');
                fsc.type = 'hidden'; fsc.name = 'fare_source_code';
                fsc.value = flight.fareSourceCode;
                const sid = document.createElement('input');
                sid.type = 'hidden'; sid.name = 'session_id';
                sid.value = '{{ session("searchSessionId", "") }}';
                const checkoutIntent = document.createElement('input');
                checkoutIntent.type = 'hidden'; checkoutIntent.name = 'intent';
                checkoutIntent.value = intent;
                // Internal routing hint only — never rendered, never a visible
                // badge. FlightBookingController::select() uses this (falling
                // back to 'travelnext') to decide whether to revalidate against
                // TravelNext or price() against SkyLink.
                const source = document.createElement('input');
                source.type = 'hidden'; source.name = 'source';
                source.value = flight.source || 'travelnext';
                form.appendChild(csrf);
                form.appendChild(fsc);
                form.appendChild(sid);
                form.appendChild(checkoutIntent);
                form.appendChild(source);
                document.body.appendChild(form);
                form.submit();
            },

            toggleMore(id) {
                console.log('Load more for flight group:', id);
            }

        };
    }
</script>

<script>
    function toast() {
        return {
            show: false,
            message: '',
            type: 'error',
            icon: 'sr-ic-alert',

            init() {
                let error = @json(session('error'));
                let success = @json(session('success'));

                if (error) this.showToast(error, 'error');
                if (success) this.showToast(success, 'success');
            },

            showToast(msg, type = 'error') {
                this.message = msg;
                this.type = type;
                this.icon = type === 'success' ? 'sr-ic-check' : 'sr-ic-alert';
                this.show = true;

                setTimeout(() => this.show = false, 9000);
            }
        }
    }
</script>
