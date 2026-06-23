<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    /* ── Reset & Base ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy:    var(--tw-brand, #303191);
        --blue:    var(--tw-brand, #303191);
        --blue-lt: #f1f1ff;
        --blue-md: #d7d8ff;
        --green:   var(--tw-accent, #009933);
        --amber:   #d97706;
        --red:     #dc2626;
        --gray-50: var(--tw-surface-soft, #f8f9fc);
        --gray-100:var(--tw-surface-muted, #f2f4f7);
        --gray-200:var(--tw-line, #e6e8ee);
        --gray-400:var(--tw-subtle, #98a2b3);
        --gray-500:var(--tw-muted, #667085);
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
    .sr-page { max-width: 1280px; margin: 0 auto; padding: 20px 16px 48px; display: grid; grid-template-columns: 252px 1fr 220px; gap: 18px; align-items: start; }

    /* ── Sidebar ── */
    .sr-sidebar { display: flex; flex-direction: column; gap: 12px; position: sticky; top: 78px; }
    .sr-panel { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
    .sr-panel-head { display: flex; align-items: center; justify-content: space-between; padding: 13px 16px 10px; border-bottom: 1px solid var(--gray-100); }
    .sr-panel-title { font-size: 13px; font-weight: 700; color: var(--gray-900); }
    .sr-panel-reset { font-size: 11px; color: var(--blue); cursor: pointer; font-weight: 600; text-decoration: none; }
    .sr-panel-reset:hover { text-decoration: underline; }
    .sr-panel-body { padding: 10px 16px 14px; display: flex; flex-direction: column; gap: 6px; }
    .sr-check-row { display: flex; align-items: center; justify-content: space-between; padding: 4px 0; cursor: pointer; gap: 8px; }
    .sr-check-row:hover .sr-check-name { color: var(--blue); }
    .sr-check-left { display: flex; align-items: center; gap: 8px; }
    .sr-check-box { width: 16px; height: 16px; border-radius: 4px; border: 1.5px solid var(--gray-400); background: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .15s; }
    .sr-check-box.checked { background: var(--blue); border-color: var(--blue); }
    .sr-check-box.checked::after { content: '✓'; color: #fff; font-size: 10px; font-weight: 700; }
    .sr-check-name { font-size: 12.5px; color: var(--gray-700); font-weight: 500; }
    .sr-check-price { font-size: 11.5px; color: var(--gray-500); font-family: var(--mono); }
    .sr-mat-img { width: 50px; height: 50px; object-fit: contain; border-radius: 4px; background: #fff; padding: 2px; display: inline-flex; align-items: center; justify-content: center; margin-right: 6px; }

    .sr-stop-pills { display: flex; gap: 6px; padding: 10px 16px 14px; }
    .sr-stop-pill { flex: 1; text-align: center; padding: 7px 4px; border-radius: 8px; border: 1.5px solid var(--gray-200); cursor: pointer; transition: all .15s; font-size: 11.5px; font-weight: 600; color: var(--gray-500); background: #fff; }
    .sr-stop-pill:hover { border-color: var(--blue-md); color: var(--blue); }
    .sr-stop-pill.active { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .sr-stop-pill-sub { font-size: 10px; font-weight: 500; color: var(--gray-400); margin-top: 1px; }
    .sr-stop-pill.active .sr-stop-pill-sub { color: var(--blue); opacity: .7; }
    .sr-time-pills { display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 16px 14px; }
    .sr-time-pill { padding: 6px 10px; border-radius: 8px; border: 1.5px solid var(--gray-200); cursor: pointer; transition: all .15s; font-size: 11px; font-weight: 600; color: var(--gray-500); background: #fff; white-space: nowrap; }
    .sr-time-pill:hover { border-color: var(--blue-md); color: var(--blue); }
    .sr-time-pill.active { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }

    /* ── Main Content ── */
    .sr-main { display: flex; flex-direction: column; gap: 14px; }

    /* Header */
    .sr-header { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .sr-header-title { font-size: 17px; font-weight: 800; color: var(--gray-900); }
    .sr-header-sub { font-size: 13px; color: var(--gray-500); margin-top: 3px; }
    .sr-fare-cal-btn { display: flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 8px; border: 1.5px solid var(--blue-md); background: var(--blue-lt); color: var(--blue); font-size: 12.5px; font-weight: 700; cursor: pointer; white-space: nowrap; font-family: var(--font); transition: all .15s; }
    .sr-fare-cal-btn:hover { background: #dbeafe; border-color: var(--blue); }

    /* ── Fare Matrix ── */
    .sr-matrix { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
    .sr-matrix-scroll { width: 750px; max-width:100vw; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:auto; scrollbar-color:var(--navy) transparent; }
    .sr-matrix-scroll::-webkit-scrollbar { height: 4px; }
    .sr-matrix-scroll::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 2px; }
    .sr-matrix table { width: max-content; min-width: 100%; border-collapse: collapse; table-layout: fixed; }
    .sr-matrix th, .sr-matrix td { padding: 10px 14px; text-align: center; border-bottom: 1px solid var(--gray-100); border-right: 1px solid var(--gray-100); font-size: 12px; white-space: nowrap; }
    .sr-matrix th:last-child, .sr-matrix td:last-child { border-right: none; }
    .sr-matrix tr:last-child td { border-bottom: none; }
    .sr-matrix th:first-child, .sr-matrix td:first-child { position: sticky; left: 0; z-index: 2; background: #fff; min-width: 110px; text-align: left; border-right: 2px solid var(--gray-200); }
    .sr-matrix thead th:first-child { background: var(--gray-50); }
    .sr-matrix thead th { background: var(--gray-50); font-weight: 700; color: var(--gray-500); font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; }
    .sr-matrix th:not(:first-child):not(:last-child), .sr-matrix td:not(:first-child):not(:last-child) { min-width: 100px; max-width: 130px; }
    .sr-matrix tbody td:first-child { font-weight: 700; color: var(--gray-700); font-size: 12.5px; }
    .sr-matrix-price { display: block; font-family: var(--mono); font-weight: 500; color: var(--gray-900); font-size: 12px; cursor: pointer; transition: color .12s; white-space: nowrap; }
    .sr-matrix-price:hover { color: var(--blue); text-decoration: underline; }
    .sr-matrix-price.cheapest { color: var(--green); font-weight: 700; }
    .sr-matrix-empty { color: var(--gray-300); }
    .sr-matrix .airline-logo { width: 50px; height: 50px; border-radius: 8px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; margin: 0 auto 4px; font-size: 9px; font-weight: 700; color: var(--gray-500); }
    .sr-matrix .airline-name { font-size: 10.5px; font-weight: 600; color: var(--gray-700); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90px; }
    .sr-next-btn { background: none; border: none; cursor: pointer; color: var(--blue); padding: 8px; display: flex; align-items: center; }

    /* Fare Summary Bar */
    .sr-fare-bar { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; }
    .sr-fare-bar-head { display: flex; align-items: center; justify-content: space-between; padding: 11px 18px; background: var(--navy); color: #fff; }
    .sr-fare-bar-title { font-size: 13px; font-weight: 700; }
    .sr-fare-bar-cta { font-size: 11.5px; color: var(--blue-md); font-weight: 500; }
    .sr-fare-options { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid var(--gray-100); }
    .sr-fare-option { padding: 14px 16px; text-align: center; border-right: 1px solid var(--gray-100); cursor: pointer; transition: background .15s; }
    .sr-fare-option:last-child { border-right: none; }
    .sr-fare-option:hover { background: var(--gray-50); }
    .sr-fare-option.active { background: var(--blue-lt); }
    .sr-fare-option-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--gray-500); margin-bottom: 4px; }
    .sr-fare-option.active .sr-fare-option-label { color: var(--blue); }
    .sr-fare-option-price { font-size: 17px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); }
    .sr-fare-option.active .sr-fare-option-price { color: var(--blue); }

    /* Sort bar */
    .sr-sort-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sr-sort-label { font-size: 12px; color: var(--gray-500); font-weight: 600; }
    .sr-sort-btn { padding: 6px 14px; border-radius: 999px; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-600); font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; font-family: var(--font); }
    .sr-sort-btn:hover { border-color: var(--blue-md); color: var(--blue); }
    .sr-sort-btn.active { background: var(--blue-lt); border-color: var(--blue); color: var(--blue); }
    .sr-result-count { margin-left: auto; font-size: 12px; color: var(--gray-500); font-weight: 500; }

    /* ── Flight Card ── */
    .sr-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); overflow: hidden; animation: cardIn .3s ease both; transition: box-shadow .2s; }
    .sr-card:hover { box-shadow: var(--shadow-md); }
    @keyframes cardIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .sr-card-head { display: flex; align-items: center; gap: 14px; padding: 14px 18px 12px; }
    .sr-airline-logo-wrap { width: 40px; height: 40px; border-radius: 8px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 9px; font-weight: 800; color: var(--gray-500); overflow: hidden; }
    .sr-airline-logo-wrap img { width: 100%; height: 100%; object-fit: contain; }
    .sr-card-airline { font-size: 14px; font-weight: 700; color: var(--gray-900); }
    .sr-card-class { font-size: 11px; color: var(--gray-400); font-weight: 500; }
    .sr-card-price-wrap { margin-left: auto; text-align: right; }
    .sr-card-price-label { font-size: 10px; color: var(--gray-400); font-weight: 600; text-transform: uppercase; }
    .sr-card-price { font-size: 22px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); line-height: 1.1; }
    .sr-card-price-sub { font-size: 11px; color: var(--blue); font-weight: 600; cursor: pointer; display:flex; align-items:center; gap:3px; justify-content:flex-end; }
    .sr-card-price-sub:hover { text-decoration: underline; }
    .sr-card-body { padding: 0 18px 14px; }
    .sr-segments { display: flex; align-items: center; gap: 0; }
    .sr-seg { display: flex; flex-direction: column; align-items: center; gap: 3px; }
    .sr-seg-time { font-size: 22px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); line-height: 1; }
    .sr-seg-place { font-size: 12px; color: var(--gray-500); font-weight: 600; }
    .sr-seg-line { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 0 14px; min-width: 80px; }
    .sr-seg-duration { font-size: 11.5px; color: var(--gray-500); font-weight: 600; }
    .sr-seg-track { width: 100%; display: flex; align-items: center; gap: 0; }
    .sr-seg-dash { flex: 1; height: 1.5px; background: var(--gray-300); }
    .sr-seg-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--gray-400); flex-shrink: 0; }
    .sr-seg-stop { font-size: 10.5px; color: var(--green); font-weight: 700; }
    .sr-seg-stop.hasstop { color: var(--amber); }
    .sr-depart-return { display: flex; gap: 0; }
    .sr-dr-col { flex: 1; }
    .sr-dr-col + .sr-dr-col { border-left: 1px dashed var(--gray-200); padding-left: 18px; margin-left: 18px; }
    .sr-dr-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); margin-bottom: 8px; }
    .sr-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 10px 18px 14px; gap: 12px; flex-wrap: wrap; border-top: 1px solid var(--gray-100); }
    .sr-refund-badge { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 999px; }
    .sr-refund-badge.no { background: #fef2f2; color: var(--red); }
    .sr-refund-badge.yes { background: #f0fdf4; color: var(--green); }
    .sr-view-details { font-size: 12px; color: var(--blue); font-weight: 600; cursor: pointer; text-decoration: none; }
    .sr-view-details:hover { text-decoration: underline; }
    .sr-book-btn { padding: 0 24px; height: 40px; background: linear-gradient(135deg, #1d4ed8, #2563eb); color: #fff; border: none; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: all .2s; box-shadow: 0 3px 12px rgba(29,78,216,.3); }
    .sr-book-btn:hover { background: linear-gradient(135deg, #1e40af, #1d4ed8); transform: translateY(-1px); box-shadow: 0 5px 18px rgba(29,78,216,.4); }

    /* ── Flight Detail Panel ── */
    .sr-detail-panel { border-top: 1px solid var(--gray-200); background: var(--gray-50); }
    .sr-detail-tabs { display: flex; border-bottom: 1px solid var(--gray-200); background: #fff; padding: 0 18px; }
    .sr-detail-tab { padding: 10px 18px; font-size: 12.5px; font-weight: 700; color: var(--gray-500); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all .15s; }
    .sr-detail-tab:hover { color: var(--blue); }
    .sr-detail-tab.active { color: var(--blue); border-bottom-color: var(--blue); }
    .sr-detail-body { padding: 18px; }
    .sr-detail-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .sr-detail-col { display: flex; flex-direction: column; gap: 12px; }
    .sr-detail-leg-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .sr-detail-leg-title { font-size: 12px; font-weight: 700; color: var(--gray-900); }
    .sr-detail-leg-badge { font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: var(--blue-lt); color: var(--blue); }
    .sr-detail-leg-badge.inbound { background: #f0fdf4; color: var(--green); }
    .sr-detail-leg-badge.connecting { background: #fff7ed; color: var(--amber); }
    .sr-detail-seg { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 14px; }
    .sr-detail-seg-airline { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .sr-detail-seg-logo { width: 28px; height: 28px; border-radius: 6px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; font-size: 8px; font-weight: 800; color: var(--gray-500); flex-shrink: 0; overflow: hidden; }
    .sr-detail-seg-logo img { width: 100%; height: 100%; object-fit: contain; }
    .sr-detail-seg-airline-name { font-size: 12.5px; font-weight: 700; color: var(--gray-900); }
    .sr-detail-seg-route { display: flex; align-items: flex-start; gap: 0; margin-bottom: 12px; }
    .sr-detail-seg-point { flex-shrink: 0; }
    .sr-detail-seg-time { font-size: 20px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); line-height: 1.1; }
    .sr-detail-seg-iata { font-size: 11px; font-weight: 700; color: var(--gray-500); margin-top: 2px; }
    .sr-detail-seg-airport { font-size: 10.5px; color: var(--gray-400); margin-top: 1px; max-width: 130px; line-height: 1.3; }
    .sr-detail-seg-mid { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 6px 12px 0; gap: 3px; }
    .sr-detail-seg-dur { font-size: 11px; font-weight: 700; color: var(--gray-500); }
    .sr-detail-seg-track { width: 100%; display: flex; align-items: center; }
    .sr-detail-seg-line { flex: 1; height: 1.5px; background: var(--gray-300); }
    .sr-detail-seg-dot2 { width: 6px; height: 6px; border-radius: 50%; background: var(--gray-400); flex-shrink: 0; }
    .sr-detail-seg-stops { font-size: 10.5px; color: var(--green); font-weight: 700; }
    .sr-detail-seg-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; padding-top: 10px; border-top: 1px solid var(--gray-100); font-size: 11.5px; color: var(--gray-500); }
    .sr-detail-meta-item { display: flex; align-items: center; gap: 5px; }
    .sr-detail-meta-label { color: var(--gray-400); font-weight: 600; }
    .sr-detail-meta-val { color: var(--gray-700); font-weight: 600; }
    .sr-detail-layover { display: flex; align-items: center; gap: 8px; padding: 7px 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; font-size: 11.5px; color: var(--amber); font-weight: 600; margin-bottom: 10px; }
    .sr-fare-rules-body { padding: 18px; }
    .sr-fare-rule-row { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--gray-100); font-size: 12.5px; }
    .sr-fare-rule-row:last-child { border-bottom: none; }
    .sr-fare-rule-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
    .sr-fare-rule-label { font-weight: 700; color: var(--gray-700); min-width: 110px; flex-shrink: 0; }
    .sr-fare-rule-val { color: var(--gray-500); }
    .sr-fare-rule-val.allowed { color: var(--green); font-weight: 600; }
    .sr-fare-rule-val.not-allowed { color: var(--red); font-weight: 600; }
    .sr-detail-footer { display: flex; align-items: center; justify-content: flex-end; padding: 12px 18px 16px; border-top: 1px solid var(--gray-100); background: #fff; }

    /* ── Right Rail ── */
    .sr-rail { display: flex; flex-direction: column; gap: 12px; position: sticky; top: 78px; }

    .sr-promo {
    position: relative;
    background: linear-gradient(135deg, var(--navy) 0%, #1e3a8a 100%);
    border-radius: 14px;
    overflow: hidden;
    min-height: 164px;
    }
    .sr-promo-slides { position: relative; width: 100%; min-height: 164px; }
    .sr-promo-slide {
    position: absolute; inset: 0;
    padding: 15px 15px 52px;
    opacity: 0;
    transition: opacity 0.55s ease;
    pointer-events: none;
    }
    .sr-promo-slide.active { opacity: 1; pointer-events: auto; }
    .sr-promo-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase;
    background: rgba(255,255,255,.12); color: rgba(255, 255, 255, 0.75);
    padding: 3px 10px; border-radius: 20px; margin-bottom: 10px;
    }
    .sr-promo-chip-dot { width: 5px; height: 5px; border-radius: 50%; background: #38bdf8; }
    .sr-promo-title { font-size: 12px; font-weight: 700; color: #fff; line-height: 1.3; margin-bottom: 7px; }
    .sr-promo-body  { font-size: 10px; color: rgba(255,255,255,.6); line-height: 1.55; margin-bottom: 14px; }
    .sr-promo-btn {
    display: inline-block; padding: 7px 16px;
    background: #fff; color: var(--navy);
    border-radius: 8px; font-size: 12px; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer;
    transition: opacity .15s;
    }
    .sr-promo-btn:hover { opacity: .88; }
    .sr-promo-footer {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 10px 20px;
    display: flex; align-items: center; justify-content: space-between;
    }
    .sr-promo-dots { display: flex; gap: 5px; align-items: center; }
    .sr-promo-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: rgba(255,255,255,.28);
    cursor: pointer; border: none; padding: 0;
    transition: background .3s, transform .3s;
    }
    .sr-promo-dot.active { background: #fff; transform: scale(1.3); }
    .sr-promo-arrows { display: flex; gap: 6px; }
    .sr-promo-arrow {
    width: 26px; height: 26px; border-radius: 50%;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s;
    }
    .sr-promo-arrow:hover { background: rgba(255,255,255,.22); }

    .sr-tip-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); padding: 16px; }
    .sr-tip-title { font-size: 12.5px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .sr-tip-icon { font-size: 16px; }
    .sr-tip-body { font-size: 12px; color: var(--gray-500); line-height: 1.6; }
    .sr-tip-highlight { color: var(--blue); font-weight: 700; }

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

    /* Phase 2 result controls */
    .sr-page { grid-template-columns: 270px minmax(0,1fr) 220px; }
    .sr-sidebar { top: 18px; gap: 0; background: #fbf7f8; border: 1px solid var(--gray-200); border-radius: 12px; padding: 10px 12px; box-shadow: 0 10px 28px rgba(16,24,40,.045); }
    .sr-panel { background: transparent; border: 0; border-radius: 0; box-shadow: none; overflow: visible; transition: none; }
    .sr-panel + .sr-panel { border-top: 2px solid rgba(17,24,39,.48); padding-top: 14px; margin-top: 14px; }
    .sr-panel:hover { border-color: transparent; box-shadow: none; }
    .sr-panel-head { padding: 10px 4px 11px; border-bottom: 0; background: transparent; }
    .sr-panel-title { font-size: 13px; font-weight: 800; }
    .sr-panel-reset { padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; transition: background .16s ease, color .16s ease; }
    .sr-panel-reset:hover { background: var(--blue-lt); text-decoration: none; }
    .sr-panel-body { padding: 8px 4px 14px; gap: 6px; }
    .sr-check-row { padding: 8px 7px; gap: 10px; border-radius: 10px; transition: background .16s ease, color .16s ease; }
    .sr-check-row:hover { background: var(--gray-50); }
    .sr-check-box { width: 17px; height: 17px; border-radius: 6px; }
    .sr-check-box.checked::after { content: ''; width: 7px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg) translateY(-1px); }
    .sr-check-name { font-size: 12.5px; font-weight: 650; line-height: 1.25; }
    .sr-check-price { white-space: nowrap; }
    .sr-stop-pills { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 7px; padding: 10px 4px 15px; }
    .sr-stop-pill { min-width: 0; padding: 9px 5px; border-radius: 8px; font-weight: 800; color: var(--blue); background: #fff; border-color: #cfd2da; transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease; }
    .sr-stop-pill:hover { transform: translateY(-1px); }
    .sr-stop-pill.active { background: #f7f7ff; box-shadow: 0 0 0 3px rgba(48,49,145,.08); }
    .sr-time-pills { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 7px; padding: 10px 4px 15px; }
    .sr-time-pill { min-width: 0; padding: 8px 9px; border-radius: 8px; text-align: center; font-weight: 800; color: var(--blue); background: #e0e0e0; border-color: transparent; transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease; }
    .sr-time-pill:hover { transform: translateY(-1px); }
    .sr-time-pill.active { background: #f7f7ff; box-shadow: 0 0 0 3px rgba(48,49,145,.08); }
    .sr-main { gap: 13px; }
    .sr-main,
    .sr-header,
    .sr-fare-bar,
    .sr-matrix,
    .sr-sort-bar,
    .sr-card { min-width: 0; max-width: 100%; }
    .sr-header { order: 0; border-radius: 14px; box-shadow: 0 10px 28px rgba(16,24,40,.045); padding: 15px 18px; }
    .sr-header-title { font-size: var(--tw-text-lg, 17px); letter-spacing: 0; }
    .sr-header-sub { font-size: var(--tw-text-sm, 13px); }
    .sr-header-title:not(.sr-header-title-clean),
    .sr-header-sub:not(.sr-header-sub-clean) { display: none; }
    .sr-header-sub-clean { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sr-header-sub-clean span { display: inline-flex; align-items: center; }
    .sr-header-sub-clean span + span::before { content: ''; width: 4px; height: 4px; margin-right: 8px; border-radius: 999px; background: var(--gray-400); }
    .sr-fare-bar { order: 2; border-radius: 14px; box-shadow: 0 10px 28px rgba(16,24,40,.045); background: transparent; border: none; overflow: visible; }
    .sr-fare-bar-head { display: none; }
    .sr-fare-options { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; border: 0; }
    .sr-fare-option { position: relative; min-height: 60px; padding: 12px 44px 12px 14px; text-align: left; border: 1.5px solid #cfcfd7; border-radius: 8px; background: #fff; box-shadow: 0 3px 8px rgba(16,24,40,.12); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .sr-fare-option:nth-child(3) { order: 1; }
    .sr-fare-option:nth-child(1) { order: 2; }
    .sr-fare-option:nth-child(2) { order: 3; }
    .sr-fare-option::after { content: ""; position: absolute; top: 13px; right: 16px; width: 20px; height: 20px; border-radius: 999px; background-color: currentColor; opacity: .68; mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='8' r='6'/%3E%3Cpath d='M15.5 13.5 17 22l-5-3-5 3 1.5-8.5'/%3E%3C/svg%3E") center/contain no-repeat; -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='8' r='6'/%3E%3Cpath d='M15.5 13.5 17 22l-5-3-5 3 1.5-8.5'/%3E%3C/svg%3E") center/contain no-repeat; }
    .sr-fare-option:nth-child(1)::after { mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 12h8'/%3E%3Cpath d='M10 8h8a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-3'/%3E%3Cpath d='M8 8H6a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h8'/%3E%3Cpath d='m7 19 3-3-3-3'/%3E%3C/svg%3E"); -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 12h8'/%3E%3Cpath d='M10 8h8a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-3'/%3E%3Cpath d='M8 8H6a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h8'/%3E%3Cpath d='m7 19 3-3-3-3'/%3E%3C/svg%3E"); }
    .sr-fare-option:nth-child(2)::after { mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='13' r='8'/%3E%3Cpath d='M12 9v5l3 2'/%3E%3Cpath d='M9 2h6'/%3E%3C/svg%3E"); -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='13' r='8'/%3E%3Cpath d='M12 9v5l3 2'/%3E%3Cpath d='M9 2h6'/%3E%3C/svg%3E"); }
    .sr-fare-option:last-child { border-right: 1px solid var(--gray-200); }
    .sr-fare-option:hover { background: #fff; border-color: var(--blue-md); transform: translateY(-1px); box-shadow: 0 8px 16px rgba(16,24,40,.14); }
    .sr-fare-option.active { background: #dedcff; border-color: rgba(48,49,145,.22); box-shadow: 0 4px 10px rgba(48,49,145,.16); }
    .sr-fare-option-label { margin-bottom: 4px; font-size: 11px; letter-spacing: 0; text-transform: none; color: var(--blue); font-weight: 800; }
    .sr-fare-option-price { font-size: 22px; line-height: 1.05; color: var(--gray-900); }
    .sr-fare-option.active .sr-fare-option-price { color: var(--blue); }
    .sr-matrix { order: 1; border-radius: 8px; box-shadow: 0 4px 10px rgba(16,24,40,.14); border-color: #d7d7de; }
    .sr-matrix::before { content: "Lowest fares by Airlines and Stops"; display: flex; align-items: center; height: 28px; padding: 0 16px; background: var(--blue); color: #fff; font-size: 12px; font-weight: 700; }
    .sr-matrix-scroll { width: 100%; max-width: 100%; scrollbar-color: var(--blue) transparent; }
    .sr-matrix-scroll::-webkit-scrollbar { height: 6px; }
    .sr-matrix-scroll::-webkit-scrollbar-thumb { background: var(--blue-md); border-radius: 999px; }
    .sr-matrix table { min-width: 720px; }
    .sr-matrix th, .sr-matrix td { padding: 10px 14px; font-size: 12px; border-color: #dcdde6; }
    .sr-matrix thead th { background: #fff; color: var(--gray-500); letter-spacing: 0; text-transform: none; }
    .sr-matrix th:first-child, .sr-matrix td:first-child { background: #fff; border-right: 1px solid var(--gray-200); }
    .sr-matrix thead th:first-child { background: #fff; }
    .sr-matrix-price { font-weight: 800; }
    .sr-matrix-price:hover { text-decoration: none; color: var(--blue); }
    .sr-matrix-price.cheapest { color: var(--green); }
    .sr-mat-img { width: 40px; height: 28px; object-fit: contain; border-radius: 4px; background: #fff; padding: 2px; display: block; margin: 0 auto 3px; border: 0; }
    .sr-sort-bar { order: 3; align-items: center; padding: 10px 0 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
    .sr-sort-label { padding-left: 4px; font-size: 12px; font-weight: 700; }
    .sr-sort-btn { border-width: 1px; padding: 7px 12px; background: transparent; color: var(--gray-700); font-weight: 750; transition: background .16s ease, border-color .16s ease, color .16s ease, transform .16s ease; }
    .sr-sort-btn:hover { background: #fff; transform: translateY(-1px); }
    .sr-sort-btn.active { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 8px 18px rgba(48,49,145,.16); }
    .sr-result-count { padding-right: 4px; font-weight: 700; color: var(--gray-500); }
    .sr-main > .sr-card { order: 4; }
    .sr-load-more { order: 5; text-align: center; padding: 8px 0; }
    .sr-empty-results { order: 4; }

    /* Phase 3 flight cards */
    .sr-card { border-radius: 8px; border-color: #d8dbe3; box-shadow: 0 4px 12px rgba(16,24,40,.12); transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .sr-card:hover { transform: translateY(-1px); border-color: #c8cce0; box-shadow: 0 10px 22px rgba(16,24,40,.14); }
    .sr-card-head { display: grid; grid-template-columns: 40px minmax(0,1fr) 174px 120px; align-items: center; gap: 12px; padding: 14px 18px 12px; }
    .sr-airline-logo-wrap { width: 38px; height: 38px; border-radius: 8px; background: #f4f5f8; border: 1px solid var(--gray-100); }
    .sr-card-airline { font-size: 15px; font-weight: 800; line-height: 1.2; }
    .sr-card-class { margin-top: 2px; font-size: 11px; color: #8c95a8; font-weight: 700; text-transform: uppercase; letter-spacing: 0; }
    .sr-card-price-wrap { align-self: stretch; margin-left: 0; padding-left: 18px; border-left: 1px solid #d8dbe3; display: flex; flex-direction: column; justify-content: center; text-align: right; }
    .sr-card-price-label { font-size: 10px; color: #7a8193; font-weight: 800; letter-spacing: .02em; }
    .sr-card-price { font-size: 22px; color: var(--gray-900); letter-spacing: 0; }
    .sr-card-price-sub { color: var(--blue); font-size: 11px; font-weight: 750; text-decoration: none; }
    .sr-card-head .sr-book-btn { justify-self: stretch; width: 100%; height: 40px; border-radius: 8px; background: var(--blue); box-shadow: 0 8px 18px rgba(48,49,145,.18); }
    .sr-card-head .sr-book-btn:hover { background: var(--tw-brand-hover, #252675); box-shadow: 0 12px 24px rgba(48,49,145,.24); }
    .sr-card-body { padding: 0 18px 0; }
    .sr-card-body > div:first-child { margin-bottom: 8px !important; }
    .sr-refund-badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .sr-refund-badge.yes { background: #eafff0; color: var(--green); }
    .sr-refund-badge.no { background: #fff1f2; color: var(--red); }
    .sr-depart-return { padding: 4px 0 10px; }
    .sr-dr-label { margin-bottom: 8px; font-size: 10px; color: #99a1b2; font-weight: 800; text-transform: uppercase; letter-spacing: 0; }
    .sr-segments { align-items: flex-start; }
    .sr-seg { min-width: 86px; align-items: flex-start; }
    .sr-seg:last-child { align-items: flex-end; text-align: right; }
    .sr-seg-time { font-family: var(--font); font-size: 21px; font-weight: 850; letter-spacing: 0; }
    .sr-seg-place { max-width: 126px; color: #687083; font-size: 12px; line-height: 1.25; white-space: normal; }
    .sr-seg-line { padding: 4px 12px 0; min-width: 130px; }
    .sr-seg-duration { color: var(--gray-700); font-size: 11px; font-weight: 800; }
    .sr-seg-track { gap: 0; }
    .sr-seg-dash { height: 1px; background: #cfd3dc; }
    .sr-seg-dot { width: 7px; height: 7px; background: #9da7b9; }
    .sr-seg-stop { margin-top: 1px; color: var(--green); font-size: 10.5px; font-weight: 800; }
    .sr-seg-stop.hasstop { color: #d97706; }
    .sr-card-meta-clean { display: flex; align-items: center; gap: 14px; padding: 10px 0 12px; border-top: 1px solid #eceef3; color: #6b7280; font-size: 12px; flex-wrap: wrap; }
    .sr-card-meta-clean + div[style*="display:flex"] { display: none !important; }
    .sr-card-meta-item { display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
    .sr-card-meta-item svg { width: 14px; height: 14px; color: #9aa3b4; flex: 0 0 14px; }
    .sr-card-meta-item strong { color: var(--gray-900); font-weight: 850; }
    .sr-card-meta-sep { width: 1px; height: 15px; background: #e5e7eb; }
    .sr-card-footer { padding: 9px 18px 12px; justify-content: flex-end; border-top: 1px solid #eceef3; }
    .sr-view-details { display: inline-flex; align-items: center; gap: 6px; color: var(--blue); font-size: 12px; font-weight: 800; }
    .sr-view-details::after { content: ""; width: 14px; height: 14px; border: 1.5px solid currentColor; border-radius: 999px; background: linear-gradient(currentColor,currentColor) center/6px 1.5px no-repeat; opacity: .8; }
    .mc-grid { border-top-color: #eceef3; }
    .mc-leg { background: #fff; }

    /* Figma result-page alignment */
    .sr-results-shell { background: #fff; }
    .sr-topbar { padding-top: 24px; }
    .sr-topbar-inner {
        max-width: 960px; min-height: 82px; border-radius: 8px; box-shadow: none; border: 0;
        background: linear-gradient(96deg, #303191 0%, #24467a 61%, #146b6b 100%);
    }
    .sr-tb-route { font-size: 20px; }
    .sr-tb-modify-btn { height: 42px; border-radius: 8px; background: var(--green); box-shadow: none; border: 0; }
    .sr-page {
        max-width: 1394px;
        grid-template-columns: 270px minmax(0, 835px) 220px;
        gap: 18px;
        padding-top: 20px;
        justify-content: center;
    }
    .sr-header { display: none; }
    .sr-sidebar {
        position: sticky; top: 18px; min-height: 725px; padding: 14px 16px 18px; border: 1px solid #eceef4;
        border-radius: 8px; background: rgba(255,255,255,.92); box-shadow: none;
    }
    .sr-sidebar::before {
        content: "Filters"; display: flex; align-items: center; min-height: 36px; padding: 0 4px 12px 28px;
        border-bottom: 1px solid rgba(103,103,103,.58); color: #111827; font-size: 13px; font-weight: 700;
        background:
            linear-gradient(#111827,#111827) 4px 10px / 16px 2px no-repeat,
            linear-gradient(#111827,#111827) 7px 16px / 10px 2px no-repeat,
            linear-gradient(#111827,#111827) 10px 22px / 4px 2px no-repeat;
    }
    .sr-sidebar .sr-panel:first-child .sr-panel-head { padding-top: 24px; }
    .sr-panel + .sr-panel { border-top: 1px solid rgba(103,103,103,.68); padding-top: 24px; margin-top: 22px; }
    .sr-panel-title { color: #676767; font-size: 13px; text-transform: uppercase; }
    .sr-panel:first-child .sr-panel-title { font-size: 0; }
    .sr-panel:first-child .sr-panel-title::after { content: "AIRLINES"; font-size: 13px; }
    .sr-check-row { padding: 5px 4px; border-radius: 6px; }
    .sr-check-name, .sr-check-price { font-size: 11.5px; font-weight: 700; }
    .sr-stop-pill { min-height: 60px; border-radius: 7px; background: #fff; box-shadow: none; }
    .sr-time-pill { min-height: 48px; background: #d9d9d9; color: var(--blue); box-shadow: none; }
    .sr-main { gap: 14px; }
    .sr-fare-bar { order: 2; margin-bottom: 0; }
    .sr-fare-options { gap: 12px; }
    .sr-fare-option {
        min-height: 74px; border-radius: 8px; padding: 13px 42px 12px 14px;
        box-shadow: 0 3px 6px rgba(0,0,0,.18);
    }
    .sr-fare-option-label { font-size: 11px; line-height: 1.2; }
    .sr-fare-option-price { font-family: var(--font); font-size: 22px; font-weight: 850; }
    .sr-matrix { order: 1; border: 0; border-radius: 8px; box-shadow: 0 4px 13px rgba(0,0,0,.16); }
    .sr-matrix::before { height: 30px; border-radius: 8px 8px 0 0; font-size: 11px; font-weight: 600; }
    .sr-matrix th, .sr-matrix td { padding: 8px 12px; font-size: 11px; }
    .sr-matrix thead th { font-size: 10.5px; }
    .sr-matrix tbody td:first-child { font-size: 12px; }
    .sr-sort-bar { order: 3; justify-content: space-between; padding-top: 0; min-height: 30px; }
    .sr-result-pill {
        display: inline-flex; align-items: center; gap: 7px; min-height: 30px; padding: 0 11px;
        border-radius: 999px; background: #dbdcff; color: #303191; font-size: 11px; font-weight: 700;
    }
    .sr-result-pill svg { width: 13px; height: 13px; }
    .sr-sort-select {
        display: inline-flex; align-items: center; gap: 6px; margin-left: auto;
        color: #111827; font-size: 11px; font-weight: 700;
    }
    .sr-sort-select select {
        height: 28px; min-width: 138px; padding: 0 26px 0 9px; border: 1px solid var(--blue);
        border-radius: 6px; background: #fff; color: #111827; font: inherit; font-size: 10.5px; outline: none;
    }
    .sr-sort-label, .sr-sort-btn, .sr-result-count { display: none; }
    .sr-card { position: relative; border: 1px solid #d8d8d8; box-shadow: 0 4px 10px rgba(0,0,0,.12); }
    .sr-card-head { grid-template-columns: 34px minmax(0,1fr); align-items: start; padding: 16px 224px 4px 24px; }
    .sr-airline-logo-wrap { width: 30px; height: 30px; background: transparent; border: 0; }
    .sr-card-airline { font-size: 14px; }
    .sr-card-class { color: #676767; font-size: 11px; font-weight: 700; }
    .sr-card-price-wrap {
        position: absolute; top: 15px; right: 24px; width: 199px; min-height: 159px; padding-left: 18px;
        border-left: 1px solid #d6d6d6; align-items: stretch; justify-content: center;
    }
    .sr-card-price-label { font-size: 10px; color: #111827; text-align: right; }
    .sr-card-price { font-family: var(--font); font-size: 22px; font-weight: 850; text-align: right; }
    .sr-card-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
    .sr-card-actions .sr-book-btn, .sr-installment-btn {
        width: 100%; height: 35px; border-radius: 8px; font-family: var(--font); font-size: 13px; font-weight: 850; cursor: pointer;
    }
    .sr-card-actions .sr-book-btn { background: var(--blue); box-shadow: none; }
    .sr-installment-btn { border: 1px dashed var(--blue); background: #fff; color: #111827; }
    .sr-card-body { padding: 0 224px 0 24px !important; }
    .sr-card-body > div:first-child { justify-content: center; margin-top: -4px; }
    .sr-refund-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; padding: 3px 10px; }
    .sr-figma-icon { width: 15px; height: 15px; object-fit: contain; display: inline-block; flex: 0 0 15px; }
    .sr-depart-return { flex-direction: column; gap: 8px; padding-top: 20px; }
    .sr-dr-label { display: none; }
    .sr-seg-time { font-size: 20px; }
    .sr-seg-place { font-size: 11px; }
    .sr-card-meta-clean { margin-top: 8px; padding: 9px 0 0; font-size: 11px; }
    .sr-card-footer { margin-top: -34px; padding: 0 18px 12px; border-top: 0; }
    .sr-view-details { font-weight: 500; }
    .sr-view-details::after {
        border: 0;
        width: 15px;
        height: 15px;
        background: url("{{ asset('images/figma-icons/flight-card-dropdown.svg') }}") center/contain no-repeat;
    }
    .sr-rail { top: 18px; gap: 14px; }

    /* Requested result-page refinements */
    .sr-sidebar {
        position: sticky;
        overflow: visible;
        padding: 14px 16px 18px;
        background: rgba(255,255,255,.92);
    }
    .sr-sidebar .sr-panel:first-child .sr-panel-head {
        position: static;
        padding: 25px 4px 16px;
    }
    .sr-sidebar .sr-panel:first-child .sr-panel-reset {
        position: absolute;
        top: 19px;
        right: 18px;
        padding: 0;
        background: transparent;
        color: var(--blue);
        font-size: 11px;
    }
    .sr-sidebar::before {
        min-height: 36px;
        padding-bottom: 13px;
    }
    .sr-panel + .sr-panel {
        padding-top: 28px;
        margin-top: 24px;
        border-top-color: rgba(103,103,103,.62);
    }
    .sr-panel-title {
        letter-spacing: 0;
        font-weight: 850;
    }
    .sr-panel-body {
        padding-left: 4px;
        padding-right: 4px;
    }
    .sr-check-row {
        min-height: 32px;
        padding: 4px;
        border-radius: 6px;
    }
    .sr-check-box {
        width: 17px;
        height: 17px;
        border-radius: 50%;
        border-color: #8b95a7;
    }
    .sr-check-name {
        max-width: 122px;
        line-height: 1.18;
    }
    .sr-check-price {
        margin-left: auto;
        color: #30364a;
        font-size: 10.5px;
        font-weight: 850;
    }
    .sr-stop-pill {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 60px;
        padding: 8px 4px;
        border: 1px solid #a6a6a6;
    }
    .sr-time-pill {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 48px;
        padding: 7px 6px;
    }
    .sr-card {
        min-height: 227px;
        overflow: visible;
    }
    .sr-card-head {
        min-height: 50px;
        padding: 16px 224px 4px 24px;
    }
    .sr-airline-logo-wrap {
        width: 34px;
        height: 34px;
    }
    .sr-card-price-wrap {
        top: 16px;
        right: 24px;
        width: 199px;
        min-height: 159px;
        padding-left: 18px;
    }
    .sr-card-price-label {
        font-size: 10px;
        font-weight: 850;
        line-height: 1.1;
    }
    .sr-card-price {
        font-size: 20px;
        line-height: 1.15;
    }
    .sr-card-actions {
        gap: 9px;
    }
    .sr-card-actions .sr-book-btn,
    .sr-installment-btn {
        height: 35px;
        border-radius: 8px;
    }
    .sr-card-body {
        padding: 0 224px 0 24px !important;
    }
    .sr-card-body > div:first-child {
        justify-content: flex-end;
        padding-right: 31px;
        margin: -28px 0 16px !important;
        min-height: 18px;
    }
    .sr-depart-return {
        flex-direction: column;
        gap: 8px;
        padding-top: 0;
    }
    .sr-dr-col {
        min-width: 0;
    }
    .sr-dr-col + .sr-dr-col {
        padding-left: 0 !important;
        margin-left: 0 !important;
        border-left: 0;
        border-top: 0;
    }
    .sr-segments {
        align-items: flex-start;
    }
    .sr-seg {
        min-width: 93px;
    }
    .sr-seg-line {
        min-width: 359px;
        padding: 2px 10px 0;
    }
    .sr-seg-track {
        position: relative;
        height: 18px;
    }
    .sr-seg-track .sr-seg-dot {
        display: none;
    }
    .sr-seg-track .sr-seg-dash {
        width: 100%;
    }
    .sr-seg-track::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: 17px;
        height: 17px;
        transform: translate(-50%, -50%);
        background: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center/contain no-repeat;
    }
    .sr-seg-time {
        font-size: 20px;
        line-height: 1.05;
    }
    .sr-seg-place {
        max-width: 93px;
        font-size: 12px;
    }
    .sr-card-meta-clean {
        margin-top: 9px;
        padding: 8px 0 0;
    }
    .sr-card-footer {
        position: absolute;
        right: 24px;
        bottom: 12px;
        margin-top: 0;
        padding: 0;
    }

    /* Focused Figma price matrix replica */
    .sr-matrix {
        position: relative;
        width: 835px;
        max-width: 100%;
        height: 217px;
        border: 0;
        border-radius: 10px;
        background: #303191;
        box-shadow: 0 4px 13.2px -2px rgba(0,0,0,.25);
        overflow: hidden;
    }
    .sr-matrix::before {
        content: "Lowest fares by Airlines and Stops";
        display: flex;
        align-items: center;
        height: 30px;
        padding: 0 19px;
        border-radius: 10px 10px 0 0;
        background: #303191;
        color: #fff;
        font-size: 12px;
        font-weight: 400;
        line-height: 16px;
    }
    .sr-matrix::after {
        content: "";
        position: absolute;
        top: 3px;
        right: 28px;
        width: 51px;
        height: 20px;
        background:
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m15 18-6-6 6-6'/%3E%3C/svg%3E") left center / 20px 20px no-repeat,
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m9 18 6-6-6-6'/%3E%3C/svg%3E") right center / 20px 20px no-repeat;
        pointer-events: none;
    }
    .sr-matrix-scroll {
        width: 100%;
        height: 187px;
        overflow-x: auto;
        overflow-y: hidden;
        border-radius: 10px;
        background: #fff;
        scrollbar-color: #303191 transparent;
    }
    .sr-matrix table {
        width: 835px;
        min-width: 835px;
        height: 187px;
        border-collapse: collapse;
        table-layout: fixed;
        background: #fff;
    }
    .sr-matrix th,
    .sr-matrix td {
        height: 47px;
        padding: 0;
        border-right: 1px solid #b3b3b3;
        border-bottom: 1px solid #b3b3b3;
        background: #fff;
        color: #000;
        text-align: center;
        vertical-align: middle;
        letter-spacing: 0;
        text-transform: none;
        white-space: nowrap;
    }
    .sr-matrix thead th {
        height: 50px;
        background: #fff;
    }
    .sr-matrix tbody tr:last-child td {
        height: 43px;
        border-bottom: 0;
    }
    .sr-matrix th:first-child,
    .sr-matrix td:first-child {
        position: static;
        z-index: auto;
        width: 83px;
        min-width: 83px;
        max-width: 83px;
        border-right: 1px solid #b3b3b3;
        background: #fff;
    }
    .sr-matrix th:not(:first-child):not(:last-child),
    .sr-matrix td:not(:first-child):not(:last-child) {
        width: 120px;
        min-width: 120px;
        max-width: 120px;
    }
    .sr-matrix th:last-child,
    .sr-matrix td:last-child {
        width: 32px;
        min-width: 32px;
        max-width: 32px;
        border-right: 0;
    }
    .sr-matrix-corner {
        text-align: left !important;
    }
    .sr-matrix-corner span {
        display: block;
        margin-left: 22px;
        color: #676767;
        font-size: 11px;
        font-weight: 700;
        line-height: 10px;
        text-align: left;
    }
    .sr-matrix-row-label {
        padding-left: 19px !important;
        color: #676767 !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        line-height: 20px;
        text-align: left !important;
    }
    .sr-matrix .airline-logo1 {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 50px;
    }
    .sr-mat-img {
        display: block;
        width: 32px;
        height: 32px;
        margin: 0 auto;
        padding: 0;
        border-radius: 0;
        background: transparent;
        object-fit: contain;
    }
    .sr-matrix .airline-name,
    .sr-matrix .sr-next-btn {
        display: none !important;
    }
    .sr-matrix-price,
    .sr-matrix-empty {
        display: block;
        padding-right: 10px;
        color: #000 !important;
        font-family: var(--font);
        font-size: 15px;
        font-weight: 700;
        line-height: 20px;
        text-align: right;
        text-decoration: none;
    }
    .sr-matrix-price:hover {
        color: #303191 !important;
        text-decoration: none;
    }

    /* Focused Figma flight card replica */
    .sr-card {
        position: relative;
        min-height: 227px;
        border: 1px solid #b3b3b3;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 4px 10px 1px rgba(0,0,0,.09);
        overflow: visible;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .sr-card:hover {
        transform: translateY(-1px);
        border-color: #aeb1bf;
        box-shadow: 0 8px 18px rgba(0,0,0,.12);
    }
    .sr-card-head {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        min-height: 58px;
        padding: 16px 224px 0 24px;
        gap: 14px;
        align-items: start;
    }
    .sr-airline-logo-wrap {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 0;
        background: transparent;
        overflow: hidden;
    }
    .sr-airline-logo-wrap img {
        width: 34px;
        height: 34px;
        object-fit: contain;
    }
    .sr-card-airline {
        max-width: 340px;
        color: #000;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.12;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sr-card-class {
        max-width: 206px;
        margin-top: 0;
        color: #676767;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-transform: none;
    }
    .sr-card-price-wrap {
        position: absolute;
        top: 15px;
        right: 24px;
        width: 199px;
        min-height: 159px;
        margin: 0;
        padding-left: 18px;
        border-left: 1px solid #d6d6d6;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: center;
        text-align: right;
    }
    .sr-card-price-label {
        color: #000;
        font-size: 11px;
        font-weight: 700;
        line-height: 18px;
        letter-spacing: 0;
        text-transform: uppercase;
    }
    .sr-card-price {
        color: #000;
        font-family: var(--font);
        font-size: 24px;
        font-weight: 850;
        line-height: 1.08;
        letter-spacing: 0;
        white-space: nowrap;
    }
    .sr-card-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 10px;
    }
    .sr-card-actions .sr-book-btn,
    .sr-installment-btn {
        width: 147px;
        height: 35px;
        margin-left: auto;
        border-radius: 10px;
        font-family: var(--font);
        cursor: pointer;
    }
    .sr-card-actions .sr-book-btn {
        background: #303191;
        box-shadow: none;
        color: #fff;
        font-size: 16px;
        font-weight: 850;
        text-transform: uppercase;
        transition: transform .16s ease, background .16s ease;
    }
    .sr-card-actions .sr-book-btn:hover {
        background: #272878;
        transform: translateY(-1px);
    }
    .sr-installment-btn {
        border: 1px dashed #303191;
        background: #fff;
        color: #000;
        font-size: 13px;
        font-weight: 700;
        transition: background .16s ease, border-color .16s ease, transform .16s ease;
    }
    .sr-installment-btn:hover {
        background: #f7f7ff;
        transform: translateY(-1px);
    }
    .sr-pay-small-small {
        width: 147px;
        margin-left: auto;
        color: #303191;
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1.25;
        text-align: right;
    }
    .sr-pay-small-small span {
        display: block;
        color: #111827;
        font-size: 13px;
        font-weight: 850;
    }
    .sr-installment-btn:disabled,
    .sr-installment-btn:disabled:hover {
        border-color: #d8dbe3;
        background: #f8f9fc;
        color: #98a2b3;
        cursor: not-allowed;
        opacity: .82;
        transform: none;
    }
    .sr-card-body {
        padding: 0 247px 0 24px !important;
    }
    .sr-card-body > div:first-child {
        position: absolute;
        top: 25px;
        left: 452px;
        z-index: 2;
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        min-height: 0;
    }
    .sr-refund-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: 128px;
        height: 23px;
        padding: 0 8px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 400;
        line-height: 16px;
        white-space: nowrap;
    }
    .sr-refund-badge.no {
        background: #ffdede;
        color: #ff0000;
    }
    .sr-refund-badge.yes {
        background: #eafff0;
        color: #009933;
    }
    .sr-icon-mask {
        display: inline-block;
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
        background: currentColor;
        mask: var(--icon-url) center / contain no-repeat;
        -webkit-mask: var(--icon-url) center / contain no-repeat;
    }
    .sr-icon-refund {
        --icon-url: url("{{ asset('images/figma-icons/flight-card-refund.svg') }}");
        width: 14px;
        height: 14px;
        flex-basis: 14px;
    }
    .sr-icon-cabin {
        --icon-url: url("{{ asset('images/figma-icons/flight-card-cabin-bag.svg') }}");
    }
    .sr-icon-luggage {
        --icon-url: url("{{ asset('images/figma-icons/flight-card-luggage.svg') }}");
    }
    .sr-icon-seat {
        --icon-url: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 15 15'%3E%3Cpath d='M8.75 8.125C8.40625 8.125 8.11208 8.00271 7.8675 7.75812C7.62292 7.51354 7.50042 7.21916 7.5 6.875V3.75C7.5 3.40625 7.6225 3.11208 7.8675 2.8675C8.1125 2.62291 8.40667 2.50042 8.75 2.5H10C10.3437 2.5 10.6381 2.6225 10.8831 2.8675C11.1281 3.1125 11.2504 3.40666 11.25 3.75V6.875C11.25 7.21875 11.1277 7.51312 10.8831 7.75812C10.6385 8.00312 10.3442 8.12542 10 8.125H8.75ZM5.9375 11.25C5.65625 11.25 5.40625 11.1694 5.1875 11.0081C4.96875 10.8469 4.81771 10.6306 4.73437 10.3594L3.17187 5.17187C3.15104 5.11979 3.13813 5.0625 3.13313 5C3.12813 4.9375 3.12542 4.875 3.125 4.8125V3.125C3.125 2.94792 3.185 2.79958 3.305 2.68C3.425 2.56042 3.57333 2.50042 3.75 2.5C3.92667 2.49958 4.07521 2.55958 4.19562 2.68C4.31604 2.80042 4.37583 2.94875 4.375 3.125V5L5.9375 10H10.625C10.8021 10 10.9506 10.06 11.0706 10.18C11.1906 10.3 11.2504 10.4483 11.25 10.625C11.2496 10.8017 11.1896 10.9502 11.07 11.0706C10.9504 11.191 10.8021 11.2508 10.625 11.25H5.9375ZM5.625 13.125C5.44792 13.125 5.29958 13.065 5.18 12.945C5.06042 12.825 5.00042 12.6767 5 12.5C4.99958 12.3233 5.05958 12.175 5.18 12.055C5.30042 11.935 5.44875 11.875 5.625 11.875H10.625C10.8021 11.875 10.9506 11.935 11.0706 12.055C11.1906 12.175 11.2504 12.3233 11.25 12.5C11.2496 12.6767 11.1896 12.8252 11.07 12.9456C10.9504 13.066 10.8021 13.1258 10.625 13.125H5.625Z'/%3E%3C/svg%3E");
    }
    .sr-figma-icon {
        display: none;
    }
    .sr-depart-return {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-top: 3px;
    }
    .sr-dr-col {
        min-width: 0;
    }
    .sr-dr-col + .sr-dr-col {
        padding-left: 0 !important;
        margin-left: 0 !important;
        border-left: 0;
        border-top: 0;
    }
    .sr-segments {
        align-items: flex-start;
        width: 100%;
    }
    .sr-seg {
        min-width: 93px;
    }
    .sr-seg:first-child {
        align-items: flex-start;
        text-align: left;
    }
    .sr-seg:last-child {
        align-items: flex-end;
        text-align: right;
    }
    .sr-seg-time {
        color: #000;
        font-family: var(--font);
        font-size: 20px;
        font-weight: 850;
        line-height: 1.2;
    }
    .sr-seg-place {
        max-width: 93px;
        color: #676767;
        font-size: 12px;
        font-weight: 400;
        line-height: 1.25;
        white-space: normal;
    }
    .sr-seg-line {
        min-width: 359px;
        padding: 2px 10px 0;
    }
    .sr-seg-duration,
    .sr-seg-stop {
        color: #000;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
    }
    .sr-seg-stop.hasstop {
        color: #000;
    }
    .sr-seg-track {
        position: relative;
        height: 18px;
        margin-top: 3px;
    }
    .sr-seg-track .sr-seg-dot {
        display: none;
    }
    .sr-seg-track .sr-seg-dash {
        width: 100%;
        height: 1px;
        background: #a6a6a6;
    }
    .sr-seg-track::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: 17px;
        height: 17px;
        transform: translate(-50%, -50%);
        background: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center / contain no-repeat;
    }
    .sr-card-meta-clean {
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 14px;
        display: flex;
        align-items: center;
        gap: 0;
        margin: 0;
        padding: 8px 90px 0 0;
        border-top: 1px solid #a6a6a6;
        color: #a6a6a6;
        font-size: 11px;
        line-height: 15px;
        flex-wrap: nowrap;
    }
    .sr-card-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-width: 0;
        color: #a6a6a6;
        white-space: nowrap;
    }
    .sr-card-meta-item span:not(.sr-icon-mask) {
        color: #a6a6a6;
        font-size: 11px;
        font-weight: 400;
    }
    .sr-card-meta-item strong {
        color: #000;
        font-size: 11px;
        font-weight: 700;
    }
    .sr-card-meta-seat strong {
        font-weight: 400;
    }
    .sr-card-meta-sep {
        width: 1px;
        height: 16px;
        margin: 0 14px;
        background: #e5e7eb;
        flex: 0 0 1px;
    }
    .sr-card-footer {
        position: absolute;
        right: 24px;
        bottom: 13px;
        margin: 0;
        padding: 0;
        border: 0;
    }
    .sr-view-details {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #303191;
        font-size: 13px;
        font-weight: 400;
        line-height: 18px;
        text-decoration: none;
    }
    .sr-view-details:hover {
        text-decoration: none;
        color: #252675;
    }
    .sr-view-details::after {
        content: "";
        width: 15px;
        height: 15px;
        border: 0;
        border-radius: 0;
        background: currentColor;
        opacity: 1;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 15 15'%3E%3Cpath d='M12.3612 7.5C12.3612 6.21072 11.8491 4.97424 10.9374 4.06258C10.0258 3.15092 8.78928 2.63875 7.5 2.63875C6.21072 2.63875 4.97424 3.15092 4.06258 4.06258C3.15092 4.97424 2.63875 6.21072 2.63875 7.5C2.63875 8.78928 3.15092 10.0258 4.06258 10.9374C4.97424 11.8491 6.21072 12.3612 7.5 12.3612C8.78928 12.3612 10.0258 11.8491 10.9374 10.9374C11.8491 10.0258 12.3612 8.78928 12.3612 7.5ZM9.0925 5.9675C9.15693 5.90307 9.23342 5.85196 9.3176 5.81709C9.40178 5.78222 9.49201 5.76428 9.58313 5.76428C9.67424 5.76428 9.76447 5.78222 9.84865 5.81709C9.93283 5.85196 10.0093 5.90307 10.0737 5.9675C10.1382 6.03193 10.1893 6.10842 10.2242 6.1926C10.259 6.27678 10.277 6.36701 10.277 6.45813C10.277 6.54924 10.259 6.63947 10.2242 6.72365C10.1893 6.80783 10.1382 6.88432 10.0737 6.94875L7.99125 9.0325C7.9268 9.09713 7.85024 9.14841 7.76594 9.18339C7.68164 9.21838 7.59127 9.23639 7.5 9.23639C7.40873 9.23639 7.31836 9.21838 7.23406 9.18339C7.14976 9.14841 7.0732 9.09713 7.00875 9.0325L4.92563 6.95L4.87812 6.89687C4.76852 6.76374 4.71244 6.59456 4.72079 6.42232C4.72915 6.25008 4.80135 6.08713 4.92333 5.96523C5.0453 5.84334 5.2083 5.77124 5.38055 5.76299C5.55279 5.75474 5.72194 5.81094 5.855 5.92062L5.9075 5.96812L7.5 7.55938L9.0925 5.9675ZM13.75 7.5C13.75 10.9519 10.9519 13.75 7.5 13.75C4.04813 13.75 1.25 10.9519 1.25 7.5C1.25 4.04813 4.04813 1.25 7.5 1.25C10.9519 1.25 13.75 4.04813 13.75 7.5Z'/%3E%3C/svg%3E") center / contain no-repeat;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 15 15'%3E%3Cpath d='M12.3612 7.5C12.3612 6.21072 11.8491 4.97424 10.9374 4.06258C10.0258 3.15092 8.78928 2.63875 7.5 2.63875C6.21072 2.63875 4.97424 3.15092 4.06258 4.06258C3.15092 4.97424 2.63875 6.21072 2.63875 7.5C2.63875 8.78928 3.15092 10.0258 4.06258 10.9374C4.97424 11.8491 6.21072 12.3612 7.5 12.3612C8.78928 12.3612 10.0258 11.8491 10.9374 10.9374C11.8491 10.0258 12.3612 8.78928 12.3612 7.5ZM9.0925 5.9675C9.15693 5.90307 9.23342 5.85196 9.3176 5.81709C9.40178 5.78222 9.49201 5.76428 9.58313 5.76428C9.67424 5.76428 9.76447 5.78222 9.84865 5.81709C9.93283 5.85196 10.0093 5.90307 10.0737 5.9675C10.1382 6.03193 10.1893 6.10842 10.2242 6.1926C10.259 6.27678 10.277 6.36701 10.277 6.45813C10.277 6.54924 10.259 6.63947 10.2242 6.72365C10.1893 6.80783 10.1382 6.88432 10.0737 6.94875L7.99125 9.0325C7.9268 9.09713 7.85024 9.14841 7.76594 9.18339C7.68164 9.21838 7.59127 9.23639 7.5 9.23639C7.40873 9.23639 7.31836 9.21838 7.23406 9.18339C7.14976 9.14841 7.0732 9.09713 7.00875 9.0325L4.92563 6.95L4.87812 6.89687C4.76852 6.76374 4.71244 6.59456 4.72079 6.42232C4.72915 6.25008 4.80135 6.08713 4.92333 5.96523C5.0453 5.84334 5.2083 5.77124 5.38055 5.76299C5.55279 5.75474 5.72194 5.81094 5.855 5.92062L5.9075 5.96812L7.5 7.55938L9.0925 5.9675ZM13.75 7.5C13.75 10.9519 10.9519 13.75 7.5 13.75C4.04813 13.75 1.25 10.9519 1.25 7.5C1.25 4.04813 4.04813 1.25 7.5 1.25C10.9519 1.25 13.75 4.04813 13.75 7.5Z'/%3E%3C/svg%3E") center / contain no-repeat;
        transition: transform .16s ease;
    }
    .sr-card .sr-dr-col {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .sr-card-expanded .sr-card-meta-clean {
        top: 204px;
        bottom: auto;
        z-index: 2;
    }
    .sr-card-expanded .sr-card-footer {
        top: 212px;
        bottom: auto;
        z-index: 3;
    }

    /* Focused Figma itinerary details and fare rules replica */
    .sr-detail-panel {
        width: 835px;
        max-width: 100%;
        margin-top: 112px;
        border: 1px solid #b3b3b3;
        border-top: 0;
        border-radius: 0 0 10px 10px;
        background: #fff;
        box-shadow: 0 4px 10px 1px rgba(0,0,0,.09);
        overflow: hidden;
    }
    .sr-detail-tabs {
        display: flex;
        align-items: stretch;
        height: 40px;
        padding: 0;
        border: 0;
        border-left: 1px solid #a6a6a6;
        border-right: 1px solid #a6a6a6;
        background: #fbfcfe;
    }
    .sr-detail-tab {
        position: relative;
        display: flex;
        align-items: center;
        height: 40px;
        margin: 0;
        padding: 0;
        border: 0;
        color: #000;
        font-size: 15px;
        font-weight: 700;
        line-height: 20px;
    }
    .sr-detail-tab:first-child { width: 141px; padding-left: 32px; }
    .sr-detail-tab:nth-child(2) { width: 120px; padding-left: 0; }
    .sr-detail-tab.active { color: #303191; border: 0; }
    .sr-detail-tab.active::after {
        content: "";
        position: absolute;
        bottom: 0;
        width: 99px;
        height: 2px;
        background: #303191;
    }
    .sr-detail-tab:first-child.active::after { left: 32px; }
    .sr-detail-tab:nth-child(2).active::after { left: 0; }
    .sr-detail-body {
        min-height: 361px;
        padding: 13px 17px 15px;
        background: #fff;
    }
    .sr-detail-cols {
        position: relative;
        display: grid;
        grid-template-columns: 357px 357px;
        gap: 80px;
        align-items: start;
    }
    .sr-detail-cols::before {
        content: "";
        position: absolute;
        top: 12px;
        bottom: 10px;
        left: 400px;
        width: 1px;
        background: #a6a6a6;
    }
    .sr-detail-col {
        width: 357px;
        gap: 0;
    }
    .sr-detail-leg-head {
        position: relative;
        display: block;
        min-height: 29px;
        margin: 0 0 13px;
    }
    .sr-detail-leg-title {
        display: block;
        max-width: 210px;
        color: #000;
        font-size: 12px;
        font-weight: 700;
        line-height: 20px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sr-detail-leg-badge {
        position: absolute;
        top: 2px;
        right: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 68px;
        height: 16px;
        padding: 0;
        border-radius: 30px;
        background: #dbdcff;
        color: #303191;
        font-size: 10px;
        font-weight: 700;
        line-height: 14px;
    }
    .sr-detail-leg-badge.inbound { background: #ddffe9; color: #009933; }
    .sr-detail-leg-badge.connecting { background: #f7f7ff; color: #303191; }
    .sr-detail-seg {
        width: 357px;
        min-height: 131px;
        margin: 0;
        padding: 9px 13px 10px;
        border: 0;
        border-radius: 8px;
        background: #fbfcfe;
        border: 1px solid #eef0f4;
        box-shadow: none;
    }
    .sr-detail-seg-airline {
        height: 20px;
        margin: 0 0 18px;
        gap: 6px;
    }
    .sr-detail-seg-logo {
        width: 18px;
        height: 18px;
        border: 0;
        border-radius: 0;
        background: transparent;
    }
    .sr-detail-seg-airline-name {
        max-width: 119px;
        color: #000;
        font-size: 12px;
        font-weight: 700;
        line-height: 16px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sr-detail-seg-airline-name + span {
        color: #676767 !important;
        font-size: 10px !important;
        font-weight: 400;
    }
    .sr-detail-seg-route {
        margin: 0 0 8px;
        align-items: flex-start;
    }
    .sr-detail-seg-point { width: 91px; min-width: 91px; }
    .sr-detail-seg-point:last-child { width: 77px; min-width: 77px; }
    .sr-detail-seg-time {
        color: #000;
        font-family: var(--font);
        font-size: 16px;
        font-weight: 850;
        line-height: 18px;
    }
    .sr-detail-seg-iata {
        margin-top: 0;
        color: #676767;
        font-size: 10px;
        font-weight: 400;
        line-height: 12px;
    }
    .sr-detail-seg-airport { display: none; }
    .sr-detail-seg-mid {
        width: 129px;
        min-width: 129px;
        padding: 1px 8px 0;
        gap: 3px;
    }
    .sr-detail-seg-dur {
        color: #000;
        font-size: 10px;
        font-weight: 600;
        line-height: 13px;
    }
    .sr-detail-seg-track { height: 12px; position: relative; }
    .sr-detail-seg-line { height: 1px; background: #a6a6a6; }
    .sr-detail-seg-dot2 { display: none; }
    .sr-detail-seg-track::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: 10px;
        height: 10px;
        transform: translate(-50%, -50%);
        background: url("{{ asset('images/figma-icons/flight-card-plane.svg') }}") center / contain no-repeat;
    }
    .sr-detail-seg-stops { display: none; }
    .sr-detail-seg-meta {
        display: flex;
        gap: 0;
        padding-top: 0;
        border-top: 0;
        color: #676767;
        font-size: 10px;
        line-height: 12px;
        flex-wrap: wrap;
    }
    .sr-detail-meta-item {
        gap: 3px;
        margin-right: 13px;
        white-space: nowrap;
    }
    .sr-detail-meta-label {
        color: #676767;
        font-size: 10px;
        font-weight: 600;
    }
    .sr-detail-meta-val {
        color: #000;
        font-size: 10px;
        font-weight: 700;
    }
    .sr-detail-layover {
        width: 275px;
        height: 20px;
        margin: 4px auto 4px;
        padding: 0 18px;
        justify-content: center;
        border: 1px solid #eef0f4;
        border-radius: 10px;
        background: #fff;
        color: #303191;
        font-size: 10px;
        font-weight: 700;
        line-height: 16px;
    }
    .sr-detail-layover svg { width: 12px; height: 12px; flex: 0 0 12px; }
    .sr-fare-rules-body {
        min-height: 246px;
        padding: 15px 32px 24px;
        background: #fff;
    }
    .sr-fare-rules-body > div { margin-bottom: 0 !important; }
    .sr-fare-rules-body > div > div:first-child {
        margin: 0 0 13px !important;
        color: #000 !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }
    .sr-fare-rule-row {
        min-height: 30px;
        padding: 0;
        border: 0;
        display: grid;
        grid-template-columns: 14px 98px minmax(0, 1fr);
        align-items: center;
        column-gap: 12px;
        font-size: 12px;
        line-height: 16px;
    }
    .sr-fare-rule-icon {
        width: 14px;
        height: 14px;
        margin: 0;
        color: #676767;
        font-size: 12px;
        overflow: hidden;
    }
    .sr-fare-rule-label {
        min-width: 0;
        color: #000;
        font-size: 12px;
        font-weight: 400;
    }
    .sr-fare-rule-val {
        color: #000;
        font-size: 12px;
        font-weight: 700;
    }
    .sr-fare-rule-val.allowed { color: #009933; font-weight: 700; }
    .sr-fare-rule-val.not-allowed { color: #ff0000; font-weight: 700; }
    .sr-detail-footer { display: none; }

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
        .sr-matrix-scroll { width: 100%; }
        .sr-fare-options { gap: 10px; }
        .sr-fare-option { padding: 13px 14px; }
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
        .sr-stop-pills { padding: 8px 12px 10px; gap: 5px; }
        .sr-stop-pill  { padding: 5px 3px; font-size: 11px; }
        .sr-time-pills { padding: 8px 12px 10px; }
        .sr-time-pill  { padding: 4px 8px; font-size: 10.5px; }
        .sr-fare-options { grid-template-columns: 1fr; gap: 9px; }
        .sr-sort-bar { overflow-x: auto; flex-wrap: nowrap; scrollbar-width: none; }
        .sr-sort-bar::-webkit-scrollbar { display: none; }
        .sr-sort-btn,
        .sr-sort-label,
        .sr-result-count { flex: 0 0 auto; }
        .sr-card-head { grid-template-columns: 38px minmax(0,1fr); padding-right: 14px; }
        .sr-card-price-wrap { position: static; width: auto; min-height: 0; grid-column: 1 / -1; align-self: auto; padding: 12px 0 0; border-left: 0; border-top: 1px solid #eceef3; text-align: left; }
        .sr-card-body { padding: 0 14px 12px !important; }
        .sr-card-body > div:first-child { position: static; display: flex !important; justify-content: flex-start; padding-right: 0; margin: 0 0 10px !important; }
        .sr-card-footer { position: static; padding: 9px 14px 12px; }
        .sr-card-head .sr-book-btn { grid-column: 1 / -1; }
        .sr-depart-return { flex-direction: column; gap: 14px; }
        .sr-dr-col + .sr-dr-col { border-left: none; border-top: 1px dashed var(--gray-200); padding-left: 0; margin-left: 0; padding-top: 12px; }
        .sr-card-meta-clean { position: static; padding: 10px 0 0; margin-top: 10px; flex-wrap: wrap; border-top-color: #e5e7eb; }
        .sr-card-actions .sr-book-btn,
        .sr-pay-small-small,
        .sr-installment-btn { width: 100%; margin-left: 0; }
        .sr-pay-small-small { text-align: left; }
        .sr-seg-line { min-width: 0; }
        .sr-detail-panel { width: 100%; margin-top: 0; }
        .sr-detail-body { min-height: 0; padding: 13px 12px 16px; }
        .sr-detail-cols { display: flex; flex-direction: column; gap: 14px; }
        .sr-detail-cols::before { display: none; }
        .sr-detail-col,
        .sr-detail-seg { width: 100%; }
        .sr-detail-seg-point { width: 78px; min-width: 78px; }
        .sr-detail-seg-point:last-child { width: 70px; min-width: 70px; }
        .sr-detail-seg-mid { width: auto; min-width: 0; flex: 1; }
        .sr-detail-layover { width: min(275px, 100%); }
        .sr-fare-rules-body { min-height: 0; padding: 15px 18px 22px; }
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
        .sr-header { padding: 12px 14px; }
        .sr-header-title { font-size: 14px; }
        .sr-header-sub { font-size: 11.5px; }
        .sr-fare-cal-btn { padding: 6px 12px; font-size: 11.5px; }
        .sr-matrix-scroll { width: 100%; }
        .sr-fare-options { grid-template-columns: 1fr; }
        .sr-fare-option { min-height: 66px; border-right: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200); padding: 12px 14px; }
        .sr-fare-option:last-child { border-bottom: 1px solid var(--gray-200); }
        .sr-fare-option-price { font-size: 17px; }
        .sr-sort-bar { gap: 6px; }
        .sr-sort-btn { padding: 5px 10px; font-size: 11px; }
        .sr-card-head { gap: 10px; padding: 12px 14px 10px; }
        .sr-card-head > div:nth-child(2) { flex: 1; min-width: 0; }
        .sr-card-price-wrap { text-align: left; }
        .sr-card-price { font-size: 18px; }
        .sr-card-head .sr-book-btn { width: 100%; margin-left: 0; height: 36px; font-size: 13px; }
        .sr-card-body { padding: 0 14px 12px; }
        .sr-card-meta-clean { gap: 10px; }
        .sr-card-meta-sep { display: none; }
        .sr-seg { min-width: 68px; }
        .sr-seg-line { min-width: 88px; padding-left: 8px; padding-right: 8px; }
        .sr-seg-time { font-size: 18px; }
        .sr-detail-cols { grid-template-columns: 1fr !important; }
        .sr-modify-head { padding: 12px 16px; }
        .sr-modify-body { padding: 16px; }
    }
    @media (max-width: 380px) {
        .sr-seg-time { font-size: 16px; }
        .sr-card-price { font-size: 16px; }
        .sr-tb-pill { padding: 4px 7px; }
        .sr-tb-pill-value { font-size: 11px; }
    }

    /* Multi-city card grid */
    .mc-grid{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid #f1f5f9;}
    .mc-leg {
        margin-top: 5px;
        padding: 12px 16px 14px;
        border: 1px solid #f1f5f9;
        border-radius: 8px;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .mc-leg:nth-child(even){border-right:none;}
    /* last odd leg spans full width 
    .mc-leg.mc-span{grid-column:1/-1;border-right:none;}*/
    .mc-leg-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:2px;display:flex;align-items:center;gap:6px;}
    .mc-leg-airline{font-size:10.5px;color:#64748b;font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:5px;}
    .mc-leg-airline img{width:16px;height:16px;object-fit:contain;border-radius:3px;background:#f1f5f9;}
    .mc-row{display:flex;align-items:center;gap:0;}
    .mc-pt{display:flex;flex-direction:column;gap:1px;min-width:0;}
    .mc-time{font-size:20px;font-weight:800;color:#0f172a;font-family:'DM Mono',monospace;line-height:1;letter-spacing:-.5px;}
    .mc-city{font-size:11px;color:#64748b;font-weight:500;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px;}
    .mc-mid{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:0 8px;min-width:60px;}
    .mc-dur{font-size:10.5px;color:#64748b;font-weight:600;}
    .mc-track{width:100%;display:flex;align-items:center;}
    .mc-dot{width:5px;height:5px;border-radius:50%;background:#cbd5e1;flex-shrink:0;}
    .mc-dash{flex:1;height:1.5px;background:#cbd5e1;}
    .mc-stop{font-size:10px;font-weight:700;}
    .mc-stop.direct{color:#059669;}
    .mc-stop.hasstop{color:#d97706;}
    @media(max-width:580px){
        .mc-grid{grid-template-columns:1fr;}
        .mc-leg{border-right:none;}
        .mc-leg.mc-span{grid-column:1;}
        .mc-time{font-size:17px;}
    }

    .tw-toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .tw-toast {  min-width: 280px; max-width: 350px; padding: 14px 16px; border-radius: 8px; color: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; justify-content: space-between; align-items: center; }
    .tw-toast-content { display: flex; align-items: center; gap: 10px; color: #fff; }
    .tw-toast-icon { font-size: 18px; }
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
            <span class="tw-toast-icon" x-text="icon"></span>
            <span class="tw-toast-message" x-text="message" style="color: #fff;"></span>
        </div>

        <button class="tw-toast-close" @click="show = false">×</button>
    </div>
</div>





{{-- ══ SINGLE ALPINE SCOPE wraps EVERYTHING ══ --}}
<div x-data="flightResults()" x-init="init()" x-effect="document.body.classList.toggle('sr-filter-open', filterSheetOpen)" class="sr-results-shell">

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
            {{-- Airlines Filter --}}
            <div class="sr-panel">
                <div class="sr-panel-head">
                    <span class="sr-panel-title">Airlines</span>
                    <a class="sr-panel-reset" @click.prevent="resetAirlines()">Reset</a>
                </div>
                <div class="sr-panel-body">
                    <template x-for="airline in airlines" :key="airline.code">
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
            {{-- Stops Filter --}}
            <div class="sr-panel">
                <div class="sr-panel-head"><span class="sr-panel-title">Onward Journey</span></div>
                <div style="padding:8px 16px 4px;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-400);margin-bottom:6px;">Stops from Origin</div>
                </div>
                <div class="sr-stop-pills">
                    <template x-for="stop in stopOptions" :key="stop.value">
                        <div class="sr-stop-pill" :class="{ active: selectedStop === stop.value }" @click="selectedStop = (selectedStop === stop.value ? null : stop.value)">
                            <div x-text="stop.label"></div>
                            <div class="sr-stop-pill-sub" x-text="stop.sub"></div>
                        </div>
                    </template>
                </div>
            </div>
            {{-- Departure Time --}}
            <div class="sr-panel">
                <div class="sr-panel-head"><span class="sr-panel-title">Departure from Origin</span></div>
                <div class="sr-time-pills">
                    <template x-for="t in timeSlots" :key="t.value">
                        <div class="sr-time-pill" :class="{ active: selectedDepartTime === t.value }" @click="selectedDepartTime = (selectedDepartTime === t.value ? null : t.value)">
                            <div x-text="t.label"></div>
                            <div style="font-size:10px;opacity:.7;" x-text="t.range"></div>
                        </div>
                    </template>
                </div>
            </div>
            {{-- Arrival Time --}}
            <div class="sr-panel">
                <div class="sr-panel-head"><span class="sr-panel-title">Arrival at Destination</span></div>
                <div class="sr-time-pills">
                    <template x-for="t in timeSlots" :key="t.value">
                        <div class="sr-time-pill" :class="{ active: selectedArrivalTime === t.value }" @click="selectedArrivalTime = (selectedArrivalTime === t.value ? null : t.value)">
                            <div x-text="t.label"></div>
                            <div style="font-size:10px;opacity:.7;" x-text="t.range"></div>
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
                    <div class="sr-panel-head"><span class="sr-panel-title">Onward Journey</span></div>
                    <div style="padding:8px 16px 4px;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-400);margin-bottom:6px;">Stops from Origin</div>
                    </div>
                    <div class="sr-stop-pills">
                        <template x-for="stop in stopOptions" :key="'mobile-stop-'+stop.value">
                            <div class="sr-stop-pill" :class="{ active: selectedStop === stop.value }" @click="selectedStop = (selectedStop === stop.value ? null : stop.value)">
                                <div x-text="stop.label"></div>
                                <div class="sr-stop-pill-sub" x-text="stop.sub"></div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="sr-panel">
                    <div class="sr-panel-head"><span class="sr-panel-title">Departure from Origin</span></div>
                    <div class="sr-time-pills">
                        <template x-for="t in timeSlots" :key="'mobile-depart-'+t.value">
                            <div class="sr-time-pill" :class="{ active: selectedDepartTime === t.value }" @click="selectedDepartTime = (selectedDepartTime === t.value ? null : t.value)">
                                <div x-text="t.label"></div>
                                <div style="font-size:10px;opacity:.7;" x-text="t.range"></div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="sr-panel">
                    <div class="sr-panel-head"><span class="sr-panel-title">Arrival at Destination</span></div>
                    <div class="sr-time-pills">
                        <template x-for="t in timeSlots" :key="'mobile-arrival-'+t.value">
                            <div class="sr-time-pill" :class="{ active: selectedArrivalTime === t.value }" @click="selectedArrivalTime = (selectedArrivalTime === t.value ? null : t.value)">
                                <div x-text="t.label"></div>
                                <div style="font-size:10px;opacity:.7;" x-text="t.range"></div>
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
                    Non stop
                </button>
            </div>

            {{-- Header --}}
            <div class="sr-header">
                <div>
                    <div class="sr-header-title sr-header-title-clean">
                        @if($trip === 'multi')
                            Multi-city flights: {{ collect($routes)->pluck('from')->implode(' -> ') }} -> {{ last($routes)['to'] ?? '' }}
                        @else
                            Flights from {{ $routes[0]['from'] ?? '' }} to {{ $routes[0]['to'] ?? '' }}{{ $trip === 'return' ? ', and back' : '' }}
                        @endif
                    </div>
                    <div class="sr-header-sub sr-header-sub-clean">
                        @if($depart)
                            <span>{{ \Carbon\Carbon::createFromFormat('d/m/Y',$depart)->format('D, d M') }}</span>
                        @endif
                        @if($trip === 'return' && $return)
                            <span>{{ \Carbon\Carbon::createFromFormat('d/m/Y',$return)->format('D, d M') }}</span>
                        @endif
                        <span>{{ $totalPassengers }} passenger{{ $totalPassengers > 1 ? 's' : '' }}</span>
                        <span>{{ $cabin }}</span>
                        <span x-text="filteredFlights.length + ' flights found'"></span>
                    </div>
                    <div class="sr-header-title">
                       @if($trip === 'multi')
                            ✈ Multi-city 
                            {{ collect($routes)->pluck('from')->implode(' → ') }} → {{ last($routes)['to'] ?? '' }}
                        @else
                            ✈ Flights from {{ $routes[0]['from'] ?? '' }} → {{ $routes[0]['to'] ?? '' }}
                            {{ $trip === 'return' ? ', and back' : '' }}
                        @endif

                        
                    </div>
                    <div class="sr-header-sub">
                        @if($depart) 📅 {{ \Carbon\Carbon::createFromFormat('d/m/Y',$depart)->format('D, d M') }} @endif
                        @if($trip === 'return' && $return) — {{ \Carbon\Carbon::createFromFormat('d/m/Y',$return)->format('D, d M') }} @endif
                        · 👤 {{ $totalPassengers }} passenger{{ $totalPassengers > 1 ? 's' : '' }}
                        · 💺 {{ $cabin }}
                        · <span x-text="filteredFlights.length + ' flights found'"></span>
                    </div>
                </div>
                {{--
                <button class="sr-fare-cal-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Fare Calendar
                </button>
                --}}
            </div>

            {{-- Fare Matrix --}}
            <div class="sr-matrix">
                <div class="sr-matrix-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th class="sr-matrix-corner">
                                    <span>Airline</span>
                                    <span>/Stops</span>
                                </th>
                                <template x-for="col in matrixAirlines" :key="col.code">
                                    <th>
                                        <div class="airline-logo1">
                                            <img class="sr-mat-img" :src="col.logo" :alt="col.name">
                                        </div>
                                    </th>
                                </template>
                                <th class="sr-matrix-nav-cell"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in matrixRows" :key="row.label">
                                <tr>
                                    <td class="sr-matrix-row-label">
                                        <span x-text="row.label === 'Non stop' ? 'Direct' : (row.label === '1+ Stops' ? '2 Stop' : row.label)"></span>
                                    </td>
                                    <template x-for="col in matrixAirlines" :key="col.code">
                                        <td>
                                            <span x-show="row.prices[col.code]" @click.prevent="toggleAirline(col.code)" class="sr-matrix-price" :class="{ cheapest: row.prices[col.code] === cheapestPrice }" x-text="row.prices[col.code]"></span>
                                            <span x-show="!row.prices[col.code]" class="sr-matrix-empty">—</span>
                                        </td>
                                    </template>
                                    <td class="sr-matrix-nav-cell"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Fare Summary Bar --}}
            <div class="sr-fare-bar">
                <div class="sr-fare-bar-head">
                    @if($trip === 'oneway')
                        <span class="sr-fare-bar-title text-white">One Way Flights</span>
                    @elseif($trip === 'return')
                        <span class="sr-fare-bar-title text-white">Round Trip Flights</span>
                    @elseif($trip === 'multi')
                        <span class="sr-fare-bar-title text-white">Multi-city Flights</span>
                    @endif
                    <span class="sr-fare-bar-cta">Book Now to secure the best fares</span>
                </div>
                <div class="sr-fare-options">
                    <div class="sr-fare-option" :class="{ active: activeFare === 'cheapest' }" @click="activeFare = 'cheapest'; sortBy = 'price'">
                        <div class="sr-fare-option-label">Cheapest Fare</div>
                        <div class="sr-fare-option-price" x-text="cheapestPrice || '—'"></div>
                    </div>
                    <div class="sr-fare-option" :class="{ active: activeFare === 'fastest' }" @click="activeFare = 'fastest'; sortBy = 'duration'">
                        <div class="sr-fare-option-label">Fastest Flight</div>
                        <div class="sr-fare-option-price" x-text="_fmtPrice([...allFlights].sort((a,b)=>a.totalDuration-b.totalDuration)[0]?.price, allFlights[0]?.currency) || '—'"></div>
                    </div>
                    <div class="sr-fare-option" :class="{ active: activeFare === 'recommended' }" @click="activeFare = 'recommended'; sortBy = 'recommended'">
                        <div class="sr-fare-option-label">Best (Recommended)</div>
                        <div class="sr-fare-option-price" x-text="cheapestPrice || '—'"></div>
                    </div>
                </div>
            </div>

            {{-- Sort Bar --}}
            <div class="sr-sort-bar">
                <span class="sr-result-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 6h11"/>
                        <path d="M9 12h11"/>
                        <path d="M9 18h11"/>
                        <path d="M4 6h.01"/>
                        <path d="M4 12h.01"/>
                        <path d="M4 18h.01"/>
                    </svg>
                    <span x-text="filteredFlights.length + ' flight' + (filteredFlights.length !== 1 ? 's' : '') + ' found'"></span>
                </span>
                <label class="sr-sort-select">
                    <span>Sort:</span>
                    <select x-model="sortBy">
                        <option value="recommended">Recommended</option>
                        <option value="price">Price Low to High</option>
                        <option value="duration">Fastest</option>
                        <option value="depart">Departure</option>
                    </select>
                </label>
                <span class="sr-sort-label">Sort by:</span>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'recommended' }" @click="sortBy = 'recommended'">Recommended</button>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'price' }" @click="sortBy = 'price'">Cheapest</button>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'duration' }" @click="sortBy = 'duration'">Fastest</button>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'depart' }" @click="sortBy = 'depart'">Departure</button>
                <span class="sr-result-count" x-text="filteredFlights.length + ' result' + (filteredFlights.length !== 1 ? 's' : '')"></span>
            </div>

            {{-- ══ Flight Cards ══ --}}
            <template x-for="(flight, fi) in paginatedFlights" :key="flight.id">
                <div class="sr-card" :class="{ 'sr-card-expanded': expandedId === flight.id }" :style="'animation-delay:' + (fi * 60) + 'ms'">

                    {{-- Card Head --}}
                    <div class="sr-card-head">
                        <div class="sr-airline-logo-wrap">
                            <template x-if="flight.airlineLogo">
                                <img :src="flight.airlineLogo" :alt="flight.airline">
                            </template>
                            <template x-if="!flight.airlineLogo">
                                <span x-text="flight.airlineCode" style="font-size:8px;font-weight:800;color:var(--gray-600);text-align:center;line-height:1.2;"></span>
                            </template>
                        </div>
                        <div>
                            <div class="sr-card-airline" x-text="flight.airline"></div>
                            <div class="sr-card-class" x-text="flight.cabin + ' · ' + (flight.stops === 0 ? 'Direct' : flight.stops + ' Stop' + (flight.stops > 1 ? 's' : ''))"></div>
                        </div>
                    </div>

                    

                    {{-- Card Body --}}
                    <div class="sr-card-body" style="padding-top:12px;">
 
                    {{-- Badge row --}}
                    <div style="margin-bottom:10px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span class="sr-refund-badge" :class="flight.isRefundable ? 'yes' : 'no'">
                            <span class="sr-icon-mask sr-icon-refund" aria-hidden="true"></span>
                            <span x-text="flight.isRefundable ? 'Refundable' : 'Non Refundable'"></span>
                        </span>
                        <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                            <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;background:#eff6ff;color:#1d4ed8;">
                                Multi-city · <span x-text="flight.multiLegs.length + ' legs'"></span>
                            </span>
                        </template>
                    </div>
                
                    {{-- ── ONE WAY / RETURN ── --}}
                    <template x-if="!flight.multiLegs || flight.multiLegs.length === 0">
                        <div class="sr-depart-return">
                            {{-- Outbound --}}
                            <div class="sr-dr-col" style="padding-right:10px;">
                                <div class="sr-dr-label" x-text="'Depart ' + flight.departTime + ' · ' + flight.airline"></div>
                                <div class="sr-segments">
                                    <div class="sr-seg">
                                        <div class="sr-seg-time" x-text="flight.departTime"></div>
                                        <div class="sr-seg-place" x-text="flight.segments[0]?.fromCity"></div>
                                    </div>
                                    <div class="sr-seg-line">
                                        <div class="sr-seg-duration" x-text="flight.totalTimeLabel"></div>
                                        <div class="sr-seg-track">
                                            <div class="sr-seg-dot"></div><div class="sr-seg-dash"></div><div class="sr-seg-dot"></div>
                                        </div>
                                        <div class="sr-seg-stop" :class="{ hasstop: flight.stops > 0 }"
                                            x-text="flight.stops === 0 ? 'Non stop' : flight.stops + ' Stop'"></div>
                                    </div>
                                    <div class="sr-seg">
                                        <div class="sr-seg-time" x-text="flight.arriveTime"></div>
                                        <div class="sr-seg-place" x-text="flight.segments[flight.segments.length-1]?.toCity"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- Return inbound --}}
                            <template x-if="flight.returnSegments && flight.returnSegments.length > 0">
                                <div class="sr-dr-col" style="padding-left:10px;">
                                    <div class="sr-dr-label" x-text="'Return ' + (flight.returnSegments[0]?.departTime || '') + ' · ' + flight.airline"></div>
                                    <div class="sr-segments">
                                        <div class="sr-seg">
                                            <div class="sr-seg-time" x-text="flight.returnSegments[0]?.departTime"></div>
                                            <div class="sr-seg-place" x-text="flight.returnSegments[0]?.fromCity"></div>
                                        </div>
                                        <div class="sr-seg-line">
                                            <div class="sr-seg-duration" x-text="flight.returnTotalTimeLabel || ''"></div>
                                            <div class="sr-seg-track">
                                                <div class="sr-seg-dot"></div><div class="sr-seg-dash"></div><div class="sr-seg-dot"></div>
                                            </div>
                                            <div class="sr-seg-stop" :class="{ hasstop: (flight.returnStops||0) > 0 }"
                                                x-text="(flight.returnStops||0) === 0 ? 'Non stop' : flight.returnStops + ' Stop'"></div>
                                        </div>
                                        <div class="sr-seg">
                                            <div class="sr-seg-time" x-text="flight.returnSegments[flight.returnSegments.length-1]?.arriveTime"></div>
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
                                        <span x-text="li === 0 ? 'Depart' : 'Connecting'"></span>
                                        <span x-show="leg.departDateLabel"
                                            style="font-weight:500;color:#94a3b8;"
                                            x-text="'· ' + leg.departDateLabel"></span>
                                    </div>
                
                                    {{-- Airline row --}}
                                    <div class="mc-leg-airline">
                                        <template x-if="leg.segments[0]?.airlineLogo">
                                            <img :src="leg.segments[0].airlineLogo" :alt="leg.segments[0].airline">
                                        </template>
                                        <span x-text="leg.segments[0]?.airline || flight.validatingAirline"></span>
                                        <span style="color:#cbd5e1;margin:0 2px;">·</span>
                                        <span x-text="leg.segments[0]?.flightNo || ''"></span>
                                    </div>
                
                                    {{-- Times / route row --}}
                                    <div class="mc-row">
                                        <div class="mc-pt">
                                            <div class="mc-time" x-text="leg.departTime"></div>
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
                                                x-text="leg.stops === 0 ? 'Non stop' : leg.stops + ' Stop'"></div>
                                        </div>
                                        <div class="mc-pt" style="text-align:right;align-items:flex-end;">
                                            <div class="mc-time" x-text="leg.arriveTime"></div>
                                            <div class="mc-city" x-text="leg.toCity"></div>
                                        </div>
                                    </div>
                
                                </div>
                            </template>
                        </div>
                    </template>
                
                    {{-- Baggage & seats — shared --}}
                    <div class="sr-card-meta-clean">
                        <div class="sr-card-meta-item sr-card-meta-seat">
                            <span class="sr-icon-mask sr-icon-cabin" aria-hidden="true"></span>
                            <span>Cabin Bag:</span>
                            <strong x-text="flight.fareBreakdown[0]?.cabinBaggage[0] || '-'"></strong>
                        </div>
                        <span class="sr-card-meta-sep"></span>
                        <div class="sr-card-meta-item">
                            <span class="sr-icon-mask sr-icon-luggage" aria-hidden="true"></span>
                            <span>Luggage:</span>
                            <strong x-text="flight.fareBreakdown[0]?.baggage[0] || '-'"></strong>
                        </div>
                        <span class="sr-card-meta-sep"></span>
                        <div class="sr-card-meta-item">
                            <span class="sr-icon-mask sr-icon-seat" aria-hidden="true"></span>
                            <template x-if="!flight.multiLegs || flight.multiLegs.length === 0">
                                <strong :style="(flight.segments[0]?.seatsLeft ?? 9) <= 5 ? 'color:#dc2626' : ''"
                                    x-text="(flight.segments[0]?.seatsLeft ?? '-') + ' seats left'"></strong>
                            </template>
                            <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                                <strong :style="(flight.multiLegs[0]?.segments[0]?.seatsLeft ?? 9) <= 5 ? 'color:#dc2626' : ''"
                                    x-text="((flight.multiLegs[0]?.segments[0]?.seatsLeft) ?? '-') + ' seats left'"></strong>
                            </template>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:18px;margin-top:12px;padding-top:10px;border-top:1px solid #f0f0f0;flex-wrap:wrap;">
                        {{-- Cabin Baggage --}}
                        <div class="sr-meta-item" style="display:flex; align-items:center; gap:5px;">
                            <div class="sr-tooltip">🎒
                                <span class="sr-meta-label" style="font-size:12px; color:#6b7280;">Cabin:</span>
                            
                                <span class="sr-meta-value" style="font-size:12px; font-weight:600; color:#374151;" x-text="flight.fareBreakdown[0]?.cabinBaggage[0] || '—'"></span>
                                <div class="sr-tooltip-text">1 standard cabin bag (7kg Hand Bag) allowed — check fare rules for details.</div>
                            </div>
                        </div>
                        <div style="width:1px;height:14px;background:#e5e7eb;"></div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            🧳 <span style="font-size:12px;color:#6b7280;">Luggage:</span>
                            <span style="font-size:12px;font-weight:600;color:#374151;"
                                x-text="flight.fareBreakdown[0]?.baggage[0] || '—'"></span>
                        </div>
                        <div style="width:1px;height:14px;background:#e5e7eb;"></div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            💺
                            {{-- one-way/return: seats from segments[0] --}}
                            <template x-if="!flight.multiLegs || flight.multiLegs.length === 0">
                                <span style="font-size:12px;font-weight:500;"
                                    :style="(flight.segments[0]?.seatsLeft ?? 9) <= 5 ? 'color:#dc2626' : 'color:#374151'"
                                    x-text="(flight.segments[0]?.seatsLeft ?? '—') + ' seats left'"></span>
                            </template>
                            {{-- multi-city: seats from multiLegs[0].segments[0] --}}
                            <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                                <span style="font-size:12px;font-weight:500;"
                                    :style="(flight.multiLegs[0]?.segments[0]?.seatsLeft ?? 9) <= 5 ? 'color:#dc2626' : 'color:#374151'"
                                    x-text="((flight.multiLegs[0]?.segments[0]?.seatsLeft) ?? '—') + ' seats left'"></span>
                            </template>
                        </div>
                    </div>
                
                </div>{{-- /sr-card-body --}}

                    <div class="sr-card-price-wrap">
                        <div class="sr-card-price-label">Total Itinerary Fee</div>
                        <div class="sr-card-price" x-text="_fmtPrice(flight.price, flight.currency)"></div>
                        <div class="sr-card-actions">
                            <button class="sr-book-btn" @click="selectFlight(flight)">Book Now</button>
                            <div class="sr-pay-small-small" x-show="flight.isRefundable" x-cloak>
                                <span x-text="_travelFlexInstallmentPrice(flight)"></span>
                            </div>
                            <button
                                class="sr-installment-btn"
                                type="button"
                                :disabled="!canUseTravelFlex(flight)"
                                :title="canUseTravelFlex(flight) ? 'Continue with TravelFlex' : travelFlexUnavailableReason(flight)"
                                @click="selectTravelFlex(flight)">
                                TravelFlex
                            </button>
                        </div>
                    </div>


                    {{-- Card Footer --}}
                    <div class="sr-card-footer">
                        <a class="sr-view-details"
                           href="#"
                           @click.prevent="toggleDetails(flight.id)"
                           x-text="expandedId === flight.id ? 'Close' : 'Details'">
                        </a>
                    </div>

                    {{-- ══ Expandable Detail Panel ══ --}}
                    <div class="sr-detail-panel" x-show="expandedId === flight.id" x-transition>

                        <div class="sr-detail-tabs">
                            <div class="sr-detail-tab" :class="{ active: (activeTab[flight.id]||'details') === 'details' }" @click="setTab(flight.id,'details')">Flight Details</div>
                            <div class="sr-detail-tab" :class="{ active: activeTab[flight.id] === 'rules' }" @click="setTab(flight.id,'rules')">Fare Rules</div>
                        </div>

                        <template x-if="(activeTab[flight.id]||'details') === 'details'">
                            <div class="sr-detail-body">
                        
                                {{-- ── ONE WAY / RETURN detail ── --}}
                                <template x-if="!flight.multiLegs || flight.multiLegs.length === 0">
                                    <div :class="(flight.returnSegments && flight.returnSegments.length > 0) ? 'sr-detail-cols' : ''">
                        
                                        {{-- Outbound --}}
                                        <div class="sr-detail-col">
                                            <div class="sr-detail-leg-head">
                                                <span class="sr-detail-leg-title"
                                                    x-text="(flight.segments[0]?.fromCity||'') + ' → ' + (flight.segments[flight.segments.length-1]?.toCity||'') + (flight.departDateLabel ? ', '+flight.departDateLabel : '')">
                                                </span>
                                                <span class="sr-detail-leg-badge">Outbound</span>
                                            </div>
                                            <template x-for="(seg, si) in flight.segments" :key="'d-out-'+si">
                                                <div>
                                                    <template x-if="si > 0">
                                                        <div class="sr-detail-layover">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                            <span x-text="'Layover in '+(flight.segments[si-1]?.toCity||'')+(flight.layoverDurations?.[si-1]?' · '+flight.layoverDurations[si-1]:'')"></span>
                                                        </div>
                                                    </template>
                                                    <div class="sr-detail-seg">
                                                        <div class="sr-detail-seg-airline">
                                                            <div class="sr-detail-seg-logo">
                                                                <template x-if="seg.airlineLogo"><img :src="seg.airlineLogo" :alt="seg.airline"></template>
                                                                <template x-if="!seg.airlineLogo"><span x-text="seg.airlineCode"></span></template>
                                                            </div>
                                                            <span class="sr-detail-seg-airline-name" x-text="seg.airline"></span>
                                                            <span style="font-size:11px;color:var(--gray-400);margin-left:5px;" x-text="seg.flightNo"></span>
                                                        </div>
                                                        <div class="sr-detail-seg-route">
                                                            <div class="sr-detail-seg-point">
                                                                <div class="sr-detail-seg-time" x-text="seg.departTime"></div>
                                                                <div class="sr-detail-seg-iata" x-text="seg.fromCity"></div>
                                                                <div class="sr-detail-seg-airport" x-text="seg.fromAirport"></div>
                                                            </div>
                                                            <div class="sr-detail-seg-mid">
                                                                <span class="sr-detail-seg-dur" x-text="Math.floor(seg.duration/60)+'h '+(seg.duration%60)+'m'"></span>
                                                                <div class="sr-detail-seg-track"><div class="sr-detail-seg-dot2"></div><div class="sr-detail-seg-line"></div><div class="sr-detail-seg-dot2"></div></div>
                                                            </div>
                                                            <div class="sr-detail-seg-point" style="text-align:right;">
                                                                <div class="sr-detail-seg-time" x-text="seg.arriveTime"></div>
                                                                <div class="sr-detail-seg-iata" x-text="seg.toCity"></div>
                                                                <div class="sr-detail-seg-airport" x-text="seg.toAirport" style="text-align:right;"></div>
                                                            </div>
                                                        </div>
                                                        <div class="sr-detail-seg-meta">
                                                            <div class="sr-detail-meta-item"><span class="sr-detail-meta-label">Baggage</span><span class="sr-detail-meta-val" x-text="flight.fareBreakdown[0]?.baggage[0]||'—'"></span></div>
                                                            <div class="sr-detail-meta-item"><span class="sr-detail-meta-label">Cabin bag</span><span class="sr-detail-meta-val" x-text="flight.fareBreakdown[0]?.cabinBaggage[0]||'—'"></span></div>
                                                            <div class="sr-detail-meta-item" x-show="seg.equipment"><span class="sr-detail-meta-label">Aircraft</span><span class="sr-detail-meta-val" x-text="seg.equipment"></span></div>
                                                            <div class="sr-detail-meta-item" x-show="seg.resBookCode"><span class="sr-detail-meta-label">Class</span><span class="sr-detail-meta-val" x-text="seg.resBookCode"></span></div>
                                                            <div class="sr-detail-meta-item"><span class="sr-detail-meta-label">Seats</span><span class="sr-detail-meta-val" :style="seg.seatsLeft<=5?'color:#dc2626':''" x-text="seg.seatsLeft+' remaining'"></span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                        
                                        {{-- Return inbound --}}
                                        <template x-if="flight.returnSegments && flight.returnSegments.length > 0">
                                            <div class="sr-detail-col">
                                                <div class="sr-detail-leg-head">
                                                    <span class="sr-detail-leg-title"
                                                        x-text="(flight.returnSegments[0]?.fromCity||'') + ' → ' + (flight.returnSegments[flight.returnSegments.length-1]?.toCity||'') + (flight.returnDateLabel ? ', '+flight.returnDateLabel : '')">
                                                    </span>
                                                    <span class="sr-detail-leg-badge inbound">Inbound</span>
                                                </div>
                                                <template x-for="(seg, si) in flight.returnSegments" :key="'d-ret-'+si">
                                                    <div>
                                                        <template x-if="si > 0">
                                                            <div class="sr-detail-layover">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                                <span x-text="'Layover in '+(flight.returnSegments[si-1]?.toCity||'')+(flight.returnLayoverDurations?.[si-1]?' · '+flight.returnLayoverDurations[si-1]:'')"></span>
                                                            </div>
                                                        </template>
                                                        <div class="sr-detail-seg">
                                                            <div class="sr-detail-seg-airline">
                                                                <div class="sr-detail-seg-logo">
                                                                    <template x-if="seg.airlineLogo"><img :src="seg.airlineLogo" :alt="seg.airline"></template>
                                                                    <template x-if="!seg.airlineLogo"><span x-text="seg.airlineCode"></span></template>
                                                                </div>
                                                                <span class="sr-detail-seg-airline-name" x-text="seg.airline"></span>
                                                                <span style="font-size:11px;color:var(--gray-400);margin-left:5px;" x-text="seg.flightNo"></span>
                                                            </div>
                                                            <div class="sr-detail-seg-route">
                                                                <div class="sr-detail-seg-point">
                                                                    <div class="sr-detail-seg-time" x-text="seg.departTime"></div>
                                                                    <div class="sr-detail-seg-iata" x-text="seg.fromCity"></div>
                                                                    <div class="sr-detail-seg-airport" x-text="seg.fromAirport"></div>
                                                                </div>
                                                                <div class="sr-detail-seg-mid">
                                                                    <span class="sr-detail-seg-dur" x-text="Math.floor(seg.duration/60)+'h '+(seg.duration%60)+'m'"></span>
                                                                    <div class="sr-detail-seg-track"><div class="sr-detail-seg-dot2"></div><div class="sr-detail-seg-line"></div><div class="sr-detail-seg-dot2"></div></div>
                                                                </div>
                                                                <div class="sr-detail-seg-point" style="text-align:right;">
                                                                    <div class="sr-detail-seg-time" x-text="seg.arriveTime"></div>
                                                                    <div class="sr-detail-seg-iata" x-text="seg.toCity"></div>
                                                                    <div class="sr-detail-seg-airport" x-text="seg.toAirport" style="text-align:right;"></div>
                                                                </div>
                                                            </div>
                                                            <div class="sr-detail-seg-meta">
                                                                <div class="sr-detail-meta-item"><span class="sr-detail-meta-label">Baggage</span><span class="sr-detail-meta-val" x-text="flight.fareBreakdown[0]?.baggage[0]||'—'"></span></div>
                                                                <div class="sr-detail-meta-item" x-show="seg.equipment"><span class="sr-detail-meta-label">Aircraft</span><span class="sr-detail-meta-val" x-text="seg.equipment"></span></div>
                                                                <div class="sr-detail-meta-item"><span class="sr-detail-meta-label">Seats</span><span class="sr-detail-meta-val" :style="seg.seatsLeft<=5?'color:#dc2626':''" x-text="seg.seatsLeft+' remaining'"></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                        
                                    </div>
                                </template>
                        
                                {{-- ── MULTI-CITY detail — stacked legs ── --}}
                                <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                                    <div style="display:flex;flex-direction:column;gap:20px;">
                        
                                        <template x-for="(leg, li) in flight.multiLegs" :key="'det-leg-'+li">
                                            <div>
                                                {{-- Leg heading --}}
                                                <div class="sr-detail-leg-head">
                                                    <span class="sr-detail-leg-title"
                                                        x-text="'Leg '+(li+1)+' · '+(leg.fromCity||'')+' → '+(leg.toCity||'')+(leg.departDateLabel ? ', '+leg.departDateLabel : '')">
                                                    </span>
                                                    <span class="sr-detail-leg-badge"
                                                        :class="li === 0 ? '' : 'connecting'"
                                                        x-text="li === 0 ? 'Depart' : 'Connecting'">
                                                    </span>
                                                </div>
                        
                                                {{-- Segments within this leg --}}
                                                <template x-for="(seg, si) in leg.segments" :key="'det-l'+li+'-s'+si">
                                                    <div>
                                                        <template x-if="si > 0">
                                                            <div class="sr-detail-layover" style="margin-top:10px;">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                                <span x-text="'Layover in '+(leg.segments[si-1]?.toCity||'')+(leg.layoverDurations?.[si-1]?' · '+leg.layoverDurations[si-1]:'')"></span>
                                                            </div>
                                                        </template>
                                                        <div class="sr-detail-seg">
                                                            <div class="sr-detail-seg-airline">
                                                                <div class="sr-detail-seg-logo">
                                                                    <template x-if="seg.airlineLogo"><img :src="seg.airlineLogo" :alt="seg.airline"></template>
                                                                    <template x-if="!seg.airlineLogo"><span x-text="seg.airlineCode"></span></template>
                                                                </div>
                                                                <span class="sr-detail-seg-airline-name" x-text="seg.airline"></span>
                                                                <span style="font-size:11px;color:var(--gray-400);margin-left:5px;" x-text="seg.flightNo"></span>
                                                            </div>
                                                            <div class="sr-detail-seg-route">
                                                                <div class="sr-detail-seg-point">
                                                                    <div class="sr-detail-seg-time" x-text="seg.departTime"></div>
                                                                    <div class="sr-detail-seg-iata" x-text="seg.fromCity"></div>
                                                                    <div class="sr-detail-seg-airport" x-text="seg.fromAirport"></div>
                                                                </div>
                                                                <div class="sr-detail-seg-mid">
                                                                    <span class="sr-detail-seg-dur" x-text="Math.floor(seg.duration/60)+'h '+(seg.duration%60)+'m'"></span>
                                                                    <div class="sr-detail-seg-track"><div class="sr-detail-seg-dot2"></div><div class="sr-detail-seg-line"></div><div class="sr-detail-seg-dot2"></div></div>
                                                                </div>
                                                                <div class="sr-detail-seg-point" style="text-align:right;">
                                                                    <div class="sr-detail-seg-time" x-text="seg.arriveTime"></div>
                                                                    <div class="sr-detail-seg-iata" x-text="seg.toCity"></div>
                                                                    <div class="sr-detail-seg-airport" x-text="seg.toAirport" style="text-align:right;"></div>
                                                                </div>
                                                            </div>
                                                            <div class="sr-detail-seg-meta">
                                                                <div class="sr-detail-meta-item">
                                                                    <span class="sr-detail-meta-label">Baggage</span>
                                                                    <span class="sr-detail-meta-val"
                                                                        x-text="flight.fareBreakdown[0]?.baggage[li] || flight.fareBreakdown[0]?.baggage[0] || '—'"></span>
                                                                </div>
                                                                <div class="sr-detail-meta-item">
                                                                    <span class="sr-detail-meta-label">Cabin bag</span>
                                                                    <span class="sr-detail-meta-val"
                                                                        x-text="flight.fareBreakdown[0]?.cabinBaggage[li] || flight.fareBreakdown[0]?.cabinBaggage[0] || '—'"></span>
                                                                </div>
                                                                <div class="sr-detail-meta-item" x-show="seg.equipment">
                                                                    <span class="sr-detail-meta-label">Aircraft</span>
                                                                    <span class="sr-detail-meta-val" x-text="seg.equipment"></span>
                                                                </div>
                                                                <div class="sr-detail-meta-item" x-show="seg.resBookCode">
                                                                    <span class="sr-detail-meta-label">Class</span>
                                                                    <span class="sr-detail-meta-val" x-text="seg.resBookCode"></span>
                                                                </div>
                                                                <div class="sr-detail-meta-item">
                                                                    <span class="sr-detail-meta-label">Seats</span>
                                                                    <span class="sr-detail-meta-val"
                                                                        :style="seg.seatsLeft<=5?'color:#dc2626':''"
                                                                        x-text="seg.seatsLeft+' remaining'"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
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
                                    <div style="margin-bottom:16px;">
                                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:8px;"
                                             x-text="fb.passengerType==='ADT'?'Adult':fb.passengerType==='CHD'?'Child':'Infant'"></div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon">🧳</span>
                                            <span class="sr-fare-rule-label">Checked Bag</span>
                                            <span class="sr-fare-rule-val" x-text="fb.baggage[0] || '—'"></span>
                                        </div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon">💼</span>
                                            <span class="sr-fare-rule-label">Cabin Bag</span>
                                            <span class="sr-fare-rule-val" x-text="fb.cabinBaggage[0] || '—'"></span>
                                        </div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon" x-text="fb.refundAllowed?'✅':'❌'"></span>
                                            <span class="sr-fare-rule-label">Refund</span>
                                            <span class="sr-fare-rule-val" :class="fb.refundAllowed?'allowed':'not-allowed'" x-text="fb.refundAllowed?'Allowed':'Not Allowed'"></span>
                                        </div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon" x-text="fb.changeAllowed?'✅':'❌'"></span>
                                            <span class="sr-fare-rule-label">Changes</span>
                                            <span class="sr-fare-rule-val" :class="fb.changeAllowed?'allowed':'not-allowed'" x-text="fb.changeAllowed?'Allowed · Penalty '+_fmtPrice(fb.changePenalty,flight.currency):'Not Allowed'"></span>
                                        </div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon">💰</span>
                                            <span class="sr-fare-rule-label">Base Fare</span>
                                            <span class="sr-fare-rule-val" x-text="_fmtPrice(fb.baseFare,flight.currency)+' × '+fb.qty"></span>
                                        </div>
                                        <div class="sr-fare-rule-row" style="font-weight:700;">
                                            <span class="sr-fare-rule-icon">🧾</span>
                                            <span class="sr-fare-rule-label">Total Fare</span>
                                            <span class="sr-fare-rule-val" style="color:var(--gray-900);" x-text="_fmtPrice(fb.totalFare*fb.qty,flight.currency)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="sr-detail-footer">
                            <button class="sr-book-btn" @click="selectFlight(flight)">Book Now</button>
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
                    <div style="font-size:32px;margin-bottom:12px;">✈️</div>
                    <div style="font-size:16px;font-weight:700;color:var(--gray-700);margin-bottom:6px;">No flights match your filters</div>
                    <div style="font-size:13px;color:var(--gray-400);">Try adjusting your filters to see more results</div>
                    <button class="sr-book-btn" style="margin-top:16px;" @click="resetAll()">Clear All Filters</button>
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
                    start() { this.timer = setInterval(() => this.next(), 5000); },
                    pause() { clearInterval(this.timer); }
                }"
                x-init="start()"
                @mouseenter="pause()"
                @mouseleave="start()">

                <div class="sr-promo-slides">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="sr-promo-slide" :class="{ active: current === i }">
                            <div class="sr-promo-chip">
                                <span class="sr-promo-chip-dot"></span>
                                <span x-text="item.label" style="color: #fff;"></span>
                            </div>
                            <div class="sr-promo-title" x-text="item.title"></div>
                            <div class="sr-promo-body" style="color: #fff;"  x-text="item.body"></div>
                            <a class="sr-promo-btn" :href="item.link" x-text="item.cta"></a>
                        </div>
                    </template>
                </div>

                
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon">🛡️</span> Flexible Booking</div>
                <div class="sr-tip-body">Look for <span class="sr-tip-highlight">refundable</span> fares if your plans may change. Most ValueJet routes offer free cancellation within 24hrs.</div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon">💳</span> Travel Flex</div>
                <div class="sr-tip-body">Split your payment into <span class="sr-tip-highlight">4 instalments</span> at no extra cost with our Pay Small Small plan — available at checkout.</div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon">⏱️</span> Best Time to Fly</div>
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

            expandedId: null,
            activeTab:  {},

            timeSlots: [
                { value: 'morning',   label: 'Morning',   range: '12:00AM–11:59AM' },
                { value: 'afternoon', label: 'Afternoon', range: '12:00PM–5:59PM'  },
                { value: 'evening',   label: 'Evening',   range: '6:00PM–11:59PM'  },
            ],

            stopOptions: [
                { value: 0, label: 'Non stop', sub: '' },
                { value: 1, label: '1 Stop',   sub: '' },
                { value: 2, label: '1+ Stops', sub: '' },
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
                return flights;
            },

            get paginatedFlights() {
                return this.filteredFlights.slice(0, this.pageSize);
            },

            init() {
                this._buildDerivedData();
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

                this.matrixRows = [
                    { label: 'Non stop', style: 'color:var(--green)', prices: {} },
                    { label: '1 Stop',   style: 'color:var(--amber)', prices: {} },
                    { label: '1+ Stops', style: 'color:var(--red)',   prices: {} },
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
                return sym + parseFloat(amount).toLocaleString('en-NG', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
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
                this.selectFlight(flight);
            },

            selectFlight(flight) {
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
                form.appendChild(csrf);
                form.appendChild(fsc);
                form.appendChild(sid);
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
            icon: '⚠️',

            init() {
                let error = @json(session('error'));
                let success = @json(session('success'));

                if (error) this.showToast(error, 'error');
                if (success) this.showToast(success, 'success');
            },

            showToast(msg, type = 'error') {
                this.message = msg;
                this.type = type;
                this.icon = type === 'success' ? '✅' : '⚠️';
                this.show = true;

                setTimeout(() => this.show = false, 9000);
            }
        }
    }
</script>
