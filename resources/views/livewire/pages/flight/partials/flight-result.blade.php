<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy:    #0a1940;
            --blue:    #1d4ed8;
            --blue-lt: #eff6ff;
            --blue-md: #bfdbfe;
            --green:   #059669;
            --amber:   #d97706;
            --red:     #dc2626;
            --gray-50: #f8fafc;
            --gray-100:#f1f5f9;
            --gray-200:#e2e8f0;
            --gray-400:#94a3b8;
            --gray-500:#64748b;
            --gray-700:#334155;
            --gray-900:#0f172a;
            --radius:  10px;
            --shadow:  0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
            --shadow-md: 0 4px 16px rgba(0,0,0,.10);
            --font: 'Plus Jakarta Sans', sans-serif;
            --mono: 'DM Mono', monospace;
        }
        body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; line-height: 1.5; }

        /* Utility classes */
        .sr-container { width:100%; max-width:100vw; overflow-x:hidden; box-sizing:border-box; }

        /* ── Top Search Bar ── */
        .sr-topbar { background: var(--navy); padding: 0 24px; position: sticky; top: 0; z-index: 200; box-shadow: 0 2px 12px rgba(0,0,0,.25); }
        .sr-topbar-inner { max-width: 1280px; margin: 0 auto; display: flex; align-items: center; gap: 4px; height: 60px; overflow-x: auto; scrollbar-width: none; position: relative; }
        .sr-topbar-inner::-webkit-scrollbar { display: none; }
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
        .sr-check-img { width: 25px; height: 25px; object-fit: contain; border-radius: 4px; background: #fff; padding: 2px; display: inline-flex; align-items: center; justify-content: center; margin-right: 6px; }
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
        .sr-matrix-scroll{
        width: 750px;
        max-width:100vw;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
        scrollbar-width:thin;
        scrollbar-color:var(--gray-200) transparent;
    }

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
        .sr-matrix .airline-logo { width: 30px; height: 30px; border-radius: 6px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; margin: 0 auto 4px; font-size: 9px; font-weight: 700; color: var(--gray-500); }
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
        .sr-dr-col { flex: 1; gap: 2px; }
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
        .sr-more-flights { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: var(--gray-50); border-top: 1px solid var(--gray-100); cursor: pointer; font-size: 12.5px; font-weight: 700; color: var(--blue); transition: background .15s; }
        .sr-more-flights:hover { background: var(--blue-lt); }

        /* ── Flight Detail Panel ── */
        .sr-detail-panel { border-top: 1px solid var(--gray-200); background: var(--gray-50); }
        .sr-detail-tabs { display: flex; border-bottom: 1px solid var(--gray-200); background: #fff; padding: 0 18px; }
        .sr-detail-tab { padding: 10px 18px; font-size: 12.5px; font-weight: 700; color: var(--gray-500); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all .15s; }
        .sr-detail-tab:hover { color: var(--blue); }
        .sr-detail-tab.active { color: var(--blue); border-bottom-color: var(--blue); }
        .sr-detail-body { padding: 18px; }
        .sr-detail-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 720px) { .sr-detail-cols { grid-template-columns: 1fr; } }
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
        .sr-promo { background: linear-gradient(135deg, var(--navy) 0%, #1e3a8a 100%); border-radius: var(--radius); padding: 18px; color: #fff; box-shadow: var(--shadow-md); }
        .sr-promo-label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--blue-md); margin-bottom: 6px; }
        .sr-promo-title { font-size: 15px; font-weight: 800; line-height: 1.3; margin-bottom: 8px; }
        .sr-promo-body { font-size: 12px; color: rgba(255,255,255,.65); line-height: 1.5; margin-bottom: 14px; }
        .sr-promo-btn { display: inline-block; padding: 8px 18px; background: #fff; color: var(--navy); border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; transition: opacity .15s; }
        .sr-promo-btn:hover { opacity: .9; }
        .sr-tip-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); padding: 16px; }
        .sr-tip-title { font-size: 12.5px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .sr-tip-icon { font-size: 16px; }
        .sr-tip-body { font-size: 12px; color: var(--gray-500); line-height: 1.6; }
        .sr-tip-highlight { color: var(--blue); font-weight: 700; }

        /* ── Responsive ── */
        @media (max-width: 1100px) { .sr-page { grid-template-columns: 232px 1fr; } .sr-rail { display: none; } }
        @media (max-width: 720px) { .sr-page { grid-template-columns: 1fr; padding: 12px 10px 32px; } .sr-sidebar { position: static; } .sr-seg-time { font-size: 17px; } .sr-card-price { font-size: 18px; } }
    </style>

    @php
        $searchParams = session('searchParams', []);
        $trip        = $searchParams['trip'] ?? 'oneway';
        $from        = $searchParams['from_city'] ?? $searchParams['from'] ?? 'Lagos';
        $to          = $searchParams['to_city'] ?? $searchParams['to'] ?? 'Abuja';
        $depart      = $searchParams['depart'] ?? null;
        $return      = $searchParams['returning'] ?? null;
        $adults = $searchParams['adults'] ?? 1;
        $childs = $searchParams['childs'] ?? 0;
        $kids   = $searchParams['kids'] ?? 0;
        $totalPassengers = $adults + $childs + $kids;
        $cabinMap = ['Y' => 'Economy', 'S' => 'Premium Economy', 'C' => 'Business', 'F' => 'First Class'];
        $cabin = $cabinMap[$searchParams['flight_type'] ?? 'Y'] ?? 'Economy';
    @endphp

    {{-- ── Main Page ── --}}
    <div class="sr-page" x-data="flightResults()" x-init="init()">

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
                                <img class="sr-check-img" :src="airline.airlineLogo" :alt="airline.name">
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
                <div style="padding:8px 16px 4px;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-400);margin-bottom:6px;">Stops from Origin</div></div>
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
        <main class="sr-main">

            {{-- Header --}}
            <div class="sr-header">
                <div>
                    <div class="sr-header-title">
                        ✈ Flights from {{ $from }} → {{ $to }}
                        @if($trip === 'return') , and back @elseif($trip === 'multi') (Multi-city) @endif
                    </div>
                    <div class="sr-header-sub">
                        @if($depart) 📅 {{ \Carbon\Carbon::createFromFormat('d/m/Y',$depart)->format('D, d M') }} @endif
                        @if($trip === 'return' && $return) — {{ \Carbon\Carbon::createFromFormat('d/m/Y',$return)->format('D, d M') }} @endif
                        · 👤 {{ $totalPassengers }} passenger{{ $totalPassengers > 1 ? 's' : '' }}
                        · 💺 {{ $cabin }}
                        · <span x-text="filteredFlights.length + ' flights found'"></span>
                    </div>
                </div>
                <button class="sr-fare-cal-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Fare Calendar
                </button>
            </div>

            {{-- Fare Matrix --}}
            <div class="sr-matrix">
                <div class="sr-matrix-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align:left;width:110px;"></th>
                                <template x-for="col in matrixAirlines" :key="col.code">
                                    <th>
                                        <div class="airline-logo" x-text="col.code"></div>
                                        <div class="airline-name" x-text="col.name"></div>
                                    </th>
                                </template>
                                <th style="width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in matrixRows" :key="row.label">
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span style="display:inline-block;width:28px;height:2px;background:currentColor;border-radius:2px;vertical-align:middle;" :style="row.style"></span>
                                            <span x-text="row.label"></span>
                                        </div>
                                    </td>
                                    <template x-for="col in matrixAirlines" :key="col.code">
                                        <td>
                                            <span x-show="row.prices[col.code]" class="sr-matrix-price" :class="{ cheapest: row.prices[col.code] === cheapestPrice }" x-text="row.prices[col.code]"></span>
                                            <span x-show="!row.prices[col.code]" class="sr-matrix-empty">—</span>
                                        </td>
                                    </template>
                                    <td><button class="sr-next-btn">›</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Fare Summary Bar --}}
            <div class="sr-fare-bar">
                <div class="sr-fare-bar-head">
                    <span class="sr-fare-bar-title">Round Trip Flights</span>
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
                        <div class="sr-fare-option-label">Recommended</div>
                        <div class="sr-fare-option-price" x-text="cheapestPrice || '—'"></div>
                    </div>
                </div>
            </div>

            {{-- Sort Bar --}}
            <div class="sr-sort-bar">
                <span class="sr-sort-label">Sort by:</span>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'recommended' }" @click="sortBy = 'recommended'">Recommended</button>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'price' }" @click="sortBy = 'price'">Cheapest</button>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'duration' }" @click="sortBy = 'duration'">Fastest</button>
                <button class="sr-sort-btn" :class="{ active: sortBy === 'depart' }" @click="sortBy = 'depart'">Departure</button>
                <span class="sr-result-count" x-text="filteredFlights.length + ' result' + (filteredFlights.length !== 1 ? 's' : '')"></span>
            </div>

            {{-- ══ Flight Cards ══ --}}
            <template x-for="(flight, fi) in paginatedFlights" :key="flight.id">
                <div class="sr-card" :style="'animation-delay:' + (fi * 60) + 'ms'">

                    {{-- Card Head: logo · airline · price · Book Now --}}
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
                        <div class="sr-card-price-wrap">
                            <div class="sr-card-price-label">Full Pay</div>
                            <div class="sr-card-price" x-text="_fmtPrice(flight.price, flight.currency)"></div>
                            <div class="sr-card-price-sub">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0;"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                Pay Small Small <span x-text="_fmtPrice(Math.round(flight.price/4), flight.currency)"></span>
                            </div>
                        </div>
                        <button class="sr-book-btn" @click="selectFlight(flight)" style="flex-shrink:0;">Book Now</button>
                    </div>

                    {{-- Card Body: summary segments --}}
                    <div class="sr-card-body" style="padding-top:12px;">

                        {{-- Refundable badge --}}
                        <div style="margin-bottom:10px;">
                            <span class="sr-refund-badge" :class="flight.isRefundable ? 'yes' : 'no'" x-text="flight.isRefundable ? 'Refundable' : 'Non Refundable'"></span>
                        </div>

                        <div class="sr-depart-return">

                            {{-- Outbound summary --}}
                            <div class="sr-dr-col">
                                <div class="sr-dr-label" x-text="'Depart ' + flight.departTime + ' · ' + flight.airline"></div>
                                <div class="sr-segments">
                                    <div class="sr-seg">
                                        <div class="sr-seg-time" x-text="flight.departTime"></div>
                                        <div class="sr-seg-place" x-text="flight.segments[0]?.from"></div>
                                    </div>
                                    <div class="sr-seg-line">
                                        <div class="sr-seg-duration" x-text="flight.durationLabel"></div>
                                        <div class="sr-seg-track">
                                            <div class="sr-seg-dot"></div>
                                            <div class="sr-seg-dash"></div>
                                            <div class="sr-seg-dot"></div>
                                        </div>
                                        <div class="sr-seg-stop" :class="{ hasstop: flight.stops > 0 }" x-text="flight.stops === 0 ? 'Non stop' : flight.stops + ' Stop'"></div>
                                    </div>
                                    <div class="sr-seg">
                                        <div class="sr-seg-time" x-text="flight.arriveTime"></div>
                                        <div class="sr-seg-place" x-text="flight.segments[flight.segments.length-1]?.to"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Return summary (Return trip only) --}}
                            <template x-if="flight.returnSegments && flight.returnSegments.length > 0">
                                <div class="sr-dr-col ps-5">
                                    <div class="sr-dr-label" x-text="'Return ' + (flight.returnSegments[0]?.departTime || '') + ' · ' + flight.airline"></div>
                                    <div class="sr-segments">
                                        <div class="sr-seg">
                                            <div class="sr-seg-time" x-text="flight.returnSegments[0]?.departTime"></div>
                                            <div class="sr-seg-place" x-text="flight.returnSegments[0]?.from"></div>
                                        </div>
                                        <div class="sr-seg-line">
                                            <div class="sr-seg-duration" x-text="flight.returnDurationLabel || ''"></div>
                                            <div class="sr-seg-track">
                                                <div class="sr-seg-dot"></div>
                                                <div class="sr-seg-dash"></div>
                                                <div class="sr-seg-dot"></div>
                                            </div>
                                            <div class="sr-seg-stop" :class="{ hasstop: (flight.returnStops||0) > 0 }" x-text="(flight.returnStops||0) === 0 ? 'Non stop' : flight.returnStops + ' Stop'"></div>
                                        </div>
                                        <div class="sr-seg">
                                            <div class="sr-seg-time" x-text="flight.returnSegments[flight.returnSegments.length-1]?.arriveTime"></div>
                                            <div class="sr-seg-place" x-text="flight.returnSegments[flight.returnSegments.length-1]?.to"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>

                    {{-- Card Footer: view details link --}}
                    <div class="sr-card-footer">
                        <a class="sr-view-details"
                           href="#"
                           @click.prevent="toggleDetails(flight.id)"
                           x-text="expandedId === flight.id ? 'Close Flight Details' : 'View Flight Details'">
                        </a>
                    </div>

                    {{-- ══ Expandable Flight Detail Panel ══ --}}
                    <div class="sr-detail-panel" x-show="expandedId === flight.id" x-transition>

                        {{-- Tabs --}}
                        <div class="sr-detail-tabs">
                            <div class="sr-detail-tab" :class="{ active: (activeTab[flight.id]||'details') === 'details' }" @click="setTab(flight.id,'details')">Flight Details</div>
                            <div class="sr-detail-tab" :class="{ active: activeTab[flight.id] === 'rules' }" @click="setTab(flight.id,'rules')">Fare Rules</div>
                        </div>

                        {{-- ── Flight Details Tab ── --}}
                        <template x-if="(activeTab[flight.id]||'details') === 'details'">
                            <div class="sr-detail-body">
                                <div :class="(flight.returnSegments && flight.returnSegments.length > 0) ? 'sr-detail-cols' : ''">

                                    {{-- Outbound legs --}}
                                    <div class="sr-detail-col">
                                        <div class="sr-detail-leg-head">
                                            <span class="sr-detail-leg-title" x-text="(flight.segments[0]?.from || '') + ' to ' + (flight.segments[flight.segments.length-1]?.to || '') + (flight.departDateLabel ? ', ' + flight.departDateLabel : '')"></span>
                                            <span class="sr-detail-leg-badge">Outbound</span>
                                        </div>
                                        <template x-for="(seg, si) in flight.segments" :key="'out-'+si">
                                            <div>
                                                <template x-if="si > 0">
                                                    <div class="sr-detail-layover">
                                                        <hr>
                                                    </div>
                                                </template>
                                                <div class="sr-detail-seg">
                                                    <div class="sr-detail-seg-airline">
                                                        <div class="sr-detail-seg-logo">
                                                            <template x-if="flight.airlineLogo"><img :src="flight.airlineLogo" :alt="seg.airline||flight.airline"></template>
                                                            <template x-if="!flight.airlineLogo"><span x-text="(seg.flightNo||'').substring(0,2)"></span></template>
                                                        </div>
                                                        <span class="sr-detail-seg-airline-name" x-text="seg.airline || flight.airline"></span>
                                                    </div>
                                                    <div class="sr-detail-seg-route">
                                                        <div class="sr-detail-seg-point">
                                                            <div class="sr-detail-seg-time" x-text="seg.departTime"></div>
                                                            <div class="sr-detail-seg-iata" x-text="seg.from"></div>
                                                            <div class="sr-detail-seg-airport" x-text="seg.fromAirport || seg.from"></div>
                                                        </div>
                                                        <div class="sr-detail-seg-mid">
                                                            <span class="sr-detail-seg-dur" x-text="Math.floor(seg.duration/60)+'h '+(seg.duration%60)+'m'"></span>
                                                            <div class="sr-detail-seg-track">
                                                                <div class="sr-detail-seg-dot2"></div>
                                                                <div class="sr-detail-seg-line"></div>
                                                                <div class="sr-detail-seg-dot2"></div>
                                                            </div>
                                                            <span class="sr-detail-seg-stops" style="font-size:10px;">Non stop</span>
                                                        </div>
                                                        <div class="sr-detail-seg-point" style="text-align:right;">
                                                            <div class="sr-detail-seg-time" x-text="seg.arriveTime"></div>
                                                            <div class="sr-detail-seg-iata" x-text="seg.to"></div>
                                                            <div class="sr-detail-seg-airport" x-text="seg.toAirport || seg.to" style="text-align:right;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="sr-detail-seg-meta">
                                                        <div class="sr-detail-meta-item">
                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                                                            <span class="sr-detail-meta-label">Baggage</span>
                                                            <span class="sr-detail-meta-val" x-text="flight.fareBreakdown[0]?.baggage || '—'"></span>
                                                        </div>
                                                        <div class="sr-detail-meta-item">
                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
                                                            <span class="sr-detail-meta-label">Airline</span>
                                                            <span class="sr-detail-meta-val" x-text="(seg.flightNo||'') + ' · ' + (flight.fareBreakdown[0]?.fareBasisCode || flight.cabin)"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Inbound / Return legs --}}
                                    <template x-if="flight.returnSegments && flight.returnSegments.length > 0">
                                        <div class="sr-detail-col">
                                            <div class="sr-detail-leg-head">
                                                <span class="sr-detail-leg-title" x-text="(flight.returnSegments[0]?.from||'') + ' to ' + (flight.returnSegments[flight.returnSegments.length-1]?.to||'') + (flight.returnDateLabel ? ', ' + flight.returnDateLabel : '')"></span>
                                                <span class="sr-detail-leg-badge inbound">Inbound</span>
                                            </div>
                                            <template x-for="(seg, si) in flight.returnSegments" :key="'ret-'+si">
                                                <div>
                                                    <template x-if="si > 0">
                                                        <div class="sr-detail-layover">
                                                            <hr>
                                                        </div>
                                                    </template>
                                                    <div class="sr-detail-seg">
                                                        <div class="sr-detail-seg-airline">
                                                            <div class="sr-detail-seg-logo">
                                                                <template x-if="flight.airlineLogo"><img :src="flight.airlineLogo" :alt="seg.airline||flight.airline"></template>
                                                                <template x-if="!flight.airlineLogo"><span x-text="(seg.flightNo||'').substring(0,2)"></span></template>
                                                            </div>
                                                            <span class="sr-detail-seg-airline-name" x-text="seg.airline || flight.airline"></span>
                                                        </div>
                                                        <div class="sr-detail-seg-route">
                                                            <div class="sr-detail-seg-point">
                                                                <div class="sr-detail-seg-time" x-text="seg.departTime"></div>
                                                                <div class="sr-detail-seg-iata" x-text="seg.from"></div>
                                                                <div class="sr-detail-seg-airport" x-text="seg.fromAirport || seg.from"></div>
                                                            </div>
                                                            <div class="sr-detail-seg-mid">
                                                                <span class="sr-detail-seg-dur" x-text="Math.floor(seg.duration/60)+'h '+(seg.duration%60)+'m'"></span>
                                                                <div class="sr-detail-seg-track">
                                                                    <div class="sr-detail-seg-dot2"></div>
                                                                    <div class="sr-detail-seg-line"></div>
                                                                    <div class="sr-detail-seg-dot2"></div>
                                                                </div>
                                                                <span class="sr-detail-seg-stops" style="font-size:10px;">Non stop</span>
                                                            </div>
                                                            <div class="sr-detail-seg-point" style="text-align:right;">
                                                                <div class="sr-detail-seg-time" x-text="seg.arriveTime"></div>
                                                                <div class="sr-detail-seg-iata" x-text="seg.to"></div>
                                                                <div class="sr-detail-seg-airport" x-text="seg.toAirport || seg.to" style="text-align:right;"></div>
                                                            </div>
                                                        </div>
                                                        <div class="sr-detail-seg-meta">
                                                            <div class="sr-detail-meta-item">
                                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                                                                <span class="sr-detail-meta-label">Baggage</span>
                                                                <span class="sr-detail-meta-val" x-text="flight.fareBreakdown[0]?.baggage || '—'"></span>
                                                            </div>
                                                            <div class="sr-detail-meta-item">
                                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
                                                                <span class="sr-detail-meta-label">Airline</span>
                                                                <span class="sr-detail-meta-val" x-text="(seg.flightNo||'') + ' · ' + flight.cabin"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Multi-city extra legs --}}
                                    <template x-if="flight.multiLegs && flight.multiLegs.length > 0">
                                        <template x-for="(leg, li) in flight.multiLegs" :key="'leg-'+li">
                                            <div class="sr-detail-col" style="grid-column:1/-1;">
                                                <div class="sr-detail-leg-head">
                                                    <span class="sr-detail-leg-title" x-text="(leg.segments[0]?.from||'') + ' to ' + (leg.segments[leg.segments.length-1]?.to||'')"></span>
                                                    <span class="sr-detail-leg-badge connecting">Connecting Flight</span>
                                                </div>
                                                <template x-for="(seg, si) in leg.segments" :key="'ml-'+li+'-'+si">
                                                    <div class="sr-detail-seg">
                                                        <div class="sr-detail-seg-airline">
                                                            <div class="sr-detail-seg-logo"><span x-text="(seg.flightNo||'').substring(0,2)"></span></div>
                                                            <span class="sr-detail-seg-airline-name" x-text="seg.airline"></span>
                                                        </div>
                                                        <div class="sr-detail-seg-route">
                                                            <div class="sr-detail-seg-point">
                                                                <div class="sr-detail-seg-time" x-text="seg.departTime"></div>
                                                                <div class="sr-detail-seg-iata" x-text="seg.from"></div>
                                                                <div class="sr-detail-seg-airport" x-text="seg.fromAirport||seg.from"></div>
                                                            </div>
                                                            <div class="sr-detail-seg-mid">
                                                                <span class="sr-detail-seg-dur" x-text="Math.floor(seg.duration/60)+'h '+(seg.duration%60)+'m'"></span>
                                                                <div class="sr-detail-seg-track">
                                                                    <div class="sr-detail-seg-dot2"></div>
                                                                    <div class="sr-detail-seg-line"></div>
                                                                    <div class="sr-detail-seg-dot2"></div>
                                                                </div>
                                                            </div>
                                                            <div class="sr-detail-seg-point" style="text-align:right;">
                                                                <div class="sr-detail-seg-time" x-text="seg.arriveTime"></div>
                                                                <div class="sr-detail-seg-iata" x-text="seg.to"></div>
                                                                <div class="sr-detail-seg-airport" x-text="seg.toAirport||seg.to" style="text-align:right;"></div>
                                                            </div>
                                                        </div>
                                                        <div class="sr-detail-seg-meta">
                                                            <div class="sr-detail-meta-item">
                                                                <span class="sr-detail-meta-label">Baggage</span>
                                                                <span class="sr-detail-meta-val" x-text="flight.fareBreakdown[0]?.baggage||'—'"></span>
                                                            </div>
                                                            <div class="sr-detail-meta-item">
                                                                <span class="sr-detail-meta-label">Airline</span>
                                                                <span class="sr-detail-meta-val" x-text="seg.flightNo||''"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </template>

                                </div>
                            </div>
                        </template>

                        {{-- ── Fare Rules Tab ── --}}
                        <template x-if="activeTab[flight.id] === 'rules'">
                            <div class="sr-fare-rules-body">
                                <template x-for="fb in flight.fareBreakdown" :key="fb.passengerType">
                                    <div style="margin-bottom:16px;">
                                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:8px;"
                                             x-text="fb.passengerType==='ADT'?'Adult':fb.passengerType==='CHD'?'Child':'Infant'"></div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon">🧳</span>
                                            <span class="sr-fare-rule-label">Checked Bag</span>
                                            <span class="sr-fare-rule-val" x-text="fb.baggage||'—'"></span>
                                        </div>
                                        <div class="sr-fare-rule-row">
                                            <span class="sr-fare-rule-icon">💼</span>
                                            <span class="sr-fare-rule-label">Cabin Bag</span>
                                            <span class="sr-fare-rule-val" x-text="fb.cabinBaggage||'—'"></span>
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

                        {{-- Detail panel footer --}}
                        <div class="sr-detail-footer">
                            <button class="sr-book-btn" @click="selectFlight(flight)">Book Now</button>
                        </div>

                    </div>{{-- /sr-detail-panel --}}

                </div>{{-- /sr-card --}}
            </template>

            {{-- Load More --}}
            <template x-if="filteredFlights.length > pageSize">
                <div style="text-align:center;padding:8px 0;">
                    <button class="sr-book-btn" style="background:var(--gray-100);color:var(--gray-700);box-shadow:none;border:1.5px solid var(--gray-200);" @click="pageSize += 5" x-text="'Show more (' + (filteredFlights.length - pageSize) + ' remaining)'"></button>
                </div>
            </template>

            {{-- No results --}}
            <template x-if="filteredFlights.length === 0">
                <div style="text-align:center;padding:48px 24px;background:#fff;border-radius:var(--radius);border:1px solid var(--gray-200);">
                    <div style="font-size:32px;margin-bottom:12px;">✈️</div>
                    <div style="font-size:16px;font-weight:700;color:var(--gray-700);margin-bottom:6px;">No flights match your filters</div>
                    <div style="font-size:13px;color:var(--gray-400);">Try adjusting your filters to see more results</div>
                    <button class="sr-book-btn" style="margin-top:16px;" @click="resetAll()">Clear All Filters</button>
                </div>
            </template>

        </main>

        {{-- ══ RIGHT RAIL ══ --}}
        <aside class="sr-rail">
            <div class="sr-promo">
                <div class="sr-promo-label">💡 Travel Tip</div>
                <div class="sr-promo-title">Book early, save big on domestic routes</div>
                <div class="sr-promo-body">Flights between Lagos and Port Harcourt fill up fast on weekends. Booking 2+ weeks in advance can save up to 35%.</div>
                <a class="sr-promo-btn" href="#">Set Price Alert</a>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon">🛡️</span> Flexible Booking</div>
                <div class="sr-tip-body">Look for <span class="sr-tip-highlight">refundable</span> fares if your plans may change. Most ValueJet routes offer free cancellation within 24hrs.</div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon">💳</span> Pay Small Small</div>
                <div class="sr-tip-body">Split your payment into <span class="sr-tip-highlight">4 instalments</span> at no extra cost with our Pay Small Small plan — available at checkout.</div>
            </div>
            <div class="sr-tip-card">
                <div class="sr-tip-title"><span class="sr-tip-icon">⏱️</span> Best Time to Fly</div>
                <div class="sr-tip-body">Morning departures (6–9AM) typically have the <span class="sr-tip-highlight">lowest delay rates</span> on the LOS–PHC route.</div>
            </div>
        </aside>

    </div>

    <script>
        function flightResults() {
            return {

                selectedAirlines: [],
                selectedStop: null,
                selectedDepartTime: null,
                selectedArrivalTime: null,
                sortBy: 'recommended',
                activeFare: 'recommended',
                pageSize: 5,

                // ── Detail panel state ──
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

                allFlights: {!! (function() {
                    $data = session('flightResults', []);
                    if (is_string($data)) return $data ?: '[]';
                    return json_encode($data ?: []);
                })() !!},

                airlines: [],
                matrixAirlines: [],
                matrixRows: [],
                cheapestPrice: '',

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

                // ── Detail panel toggles ──
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