
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --navy:     #0a1940;  --navy-lt: #0f2460;
    --blue:     #1d4ed8;  --blue-lt: #eff6ff;   --blue-md: #bfdbfe;
    --indigo:   #4338ca;  --indigo-lt: #eef2ff;
    --purple:   #7c3aed;  --purple-lt: #f5f3ff;
    --green:    #059669;  --green-lt: #f0fdf4;   --green-md: #a7f3d0;
    --teal:     #0d9488;  --teal-lt:  #f0fdfa;
    --amber:    #d97706;  --amber-lt: #fff7ed;
    --red:      #dc2626;  --red-lt:   #fef2f2;
    --gray-50:  #f8fafc;  --gray-100: #f1f5f9;  --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;  --gray-400: #94a3b8;  --gray-500: #64748b;
    --gray-700: #334155;  --gray-900: #0f172a;
    --font: 'Plus Jakarta Sans', sans-serif;
    --mono: 'DM Mono', monospace;
    --radius: 14px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.05);
    --shadow:    0 4px 16px rgba(0,0,0,.08);
    --shadow-md: 0 8px 28px rgba(0,0,0,.10);
}
body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; line-height: 1.5; margin-top: 110px; }

/* ── Layout ─────────────────────────────────────────────────────────────────── */
.pg-wrap  { max-width: 1000px; margin: 0 auto; padding: 28px 16px 80px; }
.pg-grid  { display: grid; grid-template-columns: 1fr 340px; gap: 22px; align-items: start; }
.pg-main  { display: flex; flex-direction: column; gap: 16px; }
.pg-rail  { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px; }

/* ── Cards ──────────────────────────────────────────────────────────────────── */
.pc { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
.pc-head { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--gray-100); }
.pc-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; }
.pc-title { font-size: 14px; font-weight: 800; color: var(--gray-900); }
.pc-sub   { font-size: 11.5px; color: var(--gray-400); margin-top: 1px; }
.pc-body  { padding: 18px 20px; }
.pc-body-tight { padding: 12px 20px; }

/* ── Data rows ──────────────────────────────────────────────────────────────── */
.dr { display: flex; align-items: flex-start; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--gray-100); gap: 16px; font-size: 13px; }
.dr:last-child { border-bottom: none; }
.dr-lbl { color: var(--gray-500); font-weight: 500; flex-shrink: 0; max-width: 45%; }
.dr-val { color: var(--gray-900); font-weight: 700; text-align: right; }
.dr-val.mono { font-family: var(--mono); font-size: 12.5px; }

/* ── Segment timeline ───────────────────────────────────────────────────────── */
.seg-wrap  { padding: 14px 20px; }
.seg-wrap + .seg-wrap { border-top: 1px solid var(--gray-100); }
.seg-airline-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.seg-airline-left { display: flex; align-items: center; gap: 8px; }
.seg-logo { width: 28px; height: 28px; border-radius: 6px; object-fit: contain; background: var(--gray-100); }
.seg-airline-name { font-size: 13px; font-weight: 700; color: var(--gray-700); }
.seg-meta { font-size: 11.5px; color: var(--gray-400); }
.seg-pnr  { font-size: 11px; font-weight: 700; color: var(--teal); background: var(--teal-lt); padding: 2px 8px; border-radius: 999px; }
.seg-eticket { font-size: 11px; font-weight: 700; color: var(--indigo); background: var(--indigo-lt); padding: 2px 8px; border-radius: 999px; font-family: var(--mono); }

.seg-timeline { display: flex; gap: 0; }
.seg-spine { display: flex; flex-direction: column; align-items: center; width: 18px; padding-top: 5px; flex-shrink: 0; margin-right: 16px; }
.seg-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.seg-dot.dep { background: var(--blue); box-shadow: 0 0 0 3px var(--blue-md); }
.seg-dot.arr { background: var(--navy); box-shadow: 0 0 0 3px rgba(10,25,64,.2); }
.seg-line  { flex: 1; width: 2px; background: var(--gray-200); margin: 4px 0; min-height: 40px; }
.seg-stops { flex: 1; display: flex; flex-direction: column; }
.seg-stop  { display: grid; grid-template-columns: 58px 1fr auto; align-items: start; padding: 0 0 14px; gap: 12px; }
.seg-stop:last-child { padding-bottom: 0; }
.seg-time  { font-size: 18px; font-weight: 800; color: var(--gray-900); font-family: var(--mono); line-height: 1.1; }
.seg-date  { font-size: 10.5px; color: var(--gray-400); margin-top: 2px; }
.seg-place { font-size: 13px; font-weight: 700; color: var(--gray-900); }
.seg-city  { font-size: 11.5px; color: var(--gray-500); margin-top: 1px; }
.seg-terminal { font-size: 11px; color: var(--gray-400); }
.seg-dur-row { padding: 4px 0 10px; }
.seg-dur   { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--gray-400); }

.seg-baggage { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; }
.seg-bag-tag { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--green); font-weight: 600; background: var(--green-lt); padding: 2px 8px; border-radius: 999px; white-space: nowrap; }

/* Layover strip */
.layover { display: flex; align-items: center; gap: 8px; margin: 0 20px 0; padding: 8px 14px; background: var(--amber-lt); border: 1px solid #fed7aa; border-radius: 8px; font-size: 12px; color: var(--amber); font-weight: 600; }

/* ── Leg header ─────────────────────────────────────────────────────────────── */
.leg-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); cursor: pointer; user-select: none; }
.leg-header:hover { background: #eef2f7; }
.leg-route { font-size: 15px; font-weight: 800; color: var(--gray-900); display: flex; align-items: center; gap: 8px; }
.leg-meta  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.leg-badge { padding: 2px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 700; }
.leg-badge.outbound { background: var(--blue-lt); color: var(--blue); }
.leg-badge.inbound  { background: var(--purple-lt); color: var(--purple); }
.leg-badge.multi    { background: var(--teal-lt); color: var(--teal); }
.leg-badge.nonstop  { background: var(--green-lt); color: var(--green); }
.leg-badge.stop     { background: var(--amber-lt); color: var(--amber); }
.leg-dur   { font-size: 12px; color: var(--gray-500); }

/* ── Status badges ──────────────────────────────────────────────────────────── */
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.status-confirmed { background: var(--green-lt); color: var(--green); }
.status-pending   { background: var(--amber-lt); color: var(--amber); }
.status-failed    { background: var(--red-lt);   color: var(--red); }
.status-onhold    { background: var(--indigo-lt); color: var(--indigo); }

/* ── Notices ─────────────────────────────────────────────────────────────────── */
.notice { display: flex; align-items: flex-start; gap: 10px; padding: 12px 16px; border-radius: 10px; font-size: 12.5px; }
.notice.info   { background: var(--blue-lt);  color: var(--blue);  border: 1px solid var(--blue-md); }
.notice.warn   { background: var(--amber-lt); color: var(--amber); border: 1px solid #fed7aa; }
.notice.success{ background: var(--green-lt); color: var(--green); border: 1px solid var(--green-md); }
.notice.purple { background: var(--purple-lt);color: var(--purple);border: 1px solid #ddd6fe; }

/* ── Fare breakdown ──────────────────────────────────────────────────────────── */
.fare-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px solid var(--gray-100); }
.fare-row:last-child { border-bottom: none; }
.fare-lbl { color: var(--gray-500); }
.fare-val { font-family: var(--mono); font-size: 12.5px; font-weight: 700; }
.fare-total { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 2px solid var(--gray-200); }
.fare-total-lbl { font-size: 14px; font-weight: 800; color: var(--navy); }
.fare-total-val { font-size: 22px; font-weight: 800; color: var(--navy); font-family: var(--mono); }

/* ── Pax table ───────────────────────────────────────────────────────────────── */
.pax-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.pax-table th { padding: 8px 12px; text-align: left; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--gray-400); background: var(--gray-50); border-bottom: 1px solid var(--gray-200); }
.pax-table td { padding: 10px 12px; border-bottom: 1px solid var(--gray-100); color: var(--gray-700); }
.pax-table tr:last-child td { border-bottom: none; }
.pax-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; }

/* ── Timeline steps ──────────────────────────────────────────────────────────── */
.tl-step { display: flex; align-items: flex-start; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--gray-100); }
.tl-step:last-child { border-bottom: none; }
.tl-num { width: 30px; height: 30px; min-width: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
.tl-num.done    { background: var(--green); color: #fff; }
.tl-num.current { background: var(--amber); color: #fff; box-shadow: 0 0 0 4px rgba(217,119,6,.18); }
.tl-num.pending { background: var(--gray-100); color: var(--gray-400); border: 2px solid var(--gray-200); }
.tl-title { font-size: 13.5px; font-weight: 700; color: var(--gray-900); margin-bottom: 3px; }
.tl-sub   { font-size: 12px; color: var(--gray-500); line-height: 1.55; }

/* ── Buttons ─────────────────────────────────────────────────────────────────── */
.btn-primary { height: 48px; padding: 0 28px; background: var(--blue); color: #fff; border: none; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: var(--font); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background .15s; }
.btn-primary:hover { background: #1e40af; }
.btn-ghost  { height: 48px; padding: 0 22px; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 10px; font-size: 13px; font-weight: 700; color: var(--gray-700); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .15s; }
.btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }

/* ── Print ───────────────────────────────────────────────────────────────────── */
@media print { body { margin: 0; background: #fff; } .pg-rail, .btn-row, .notice { display: none !important; } .pg-grid { grid-template-columns: 1fr; } }

/* ── Responsive ──────────────────────────────────────────────────────────────── */
@media (max-width: 900px) { .pg-grid { grid-template-columns: 1fr; } .pg-rail { position: static; } }
@media (max-width: 580px) { .pg-wrap { padding: 12px 10px 60px; } .seg-stop { grid-template-columns: 52px 1fr; } .seg-baggage { display: none; } .seg-time { font-size: 15px; } }
</style>