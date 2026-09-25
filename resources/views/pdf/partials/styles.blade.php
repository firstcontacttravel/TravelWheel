{{--
    One stylesheet for both travel documents, included into each one's <style>
    block. Before this the e-ticket painted its header #303191 and the itinerary
    painted its own #39328f — two different brand blues on two PDFs attached to
    the same email. Colours now come from config/brand.php.

    DomPDF constraints this is written against: no flexbox, no grid, no CSS
    custom properties (so the values below are interpolated by PHP), and only
    partial inline-block. Layout is therefore tables, and `position: fixed`
    is used for the footer so it repeats on every page.

    DejaVu Sans ships only Regular and Bold, so font-weight is a binary tool:
    700 is the only step up, and the old templates' 800/900 were flattening to
    the same Bold while reading as shouting in the markup.
--}}
@php
    $c = config('brand.colors');
    $sans = 'DejaVu Sans, sans-serif';
    $mono = 'DejaVu Sans Mono, monospace';
@endphp

@page { margin: 26px 30px 44px; }
* { box-sizing: border-box; }
body {
    margin: 0;
    color: {{ $c['ink'] }};
    background: #fff;
    font-family: {{ $sans }};
    font-size: 9px;
    line-height: 1.45;
}
table { border-collapse: collapse; width: 100%; }
td, th { vertical-align: top; }
.right { text-align: right; }
.center { text-align: center; }
.mono { font-family: {{ $mono }}; }
.brand-ink { color: {{ $c['brand'] }}; }
.muted { color: {{ $c['muted'] }}; }
.nowrap { white-space: nowrap; }
.avoid-break { page-break-inside: avoid; }

{{-- ── Masthead ────────────────────────────────────────────────────────────
   Solid brand band. The wordmark is set in type rather than placed as an
   image: public/assets/img/alt-logo.png is a white wordmark on a near-opaque
   white field, so it rendered invisible on the itinerary's white header — and
   embedded ~272 KB twice, which is why that PDF was 347 KB against the
   e-ticket's 51 KB. --}}
.masthead { background: {{ $c['brand'] }}; color: #fff; padding: 16px 20px; }
.masthead .wordmark { font-size: 17px; font-weight: 700; letter-spacing: -.3px; }
.masthead .doctype { margin-top: 3px; color: #c3c4f2; font-size: 8px; letter-spacing: .5px; }
.masthead .stamp {
    padding: 5px 11px; border: 1px solid rgba(255,255,255,.45);
    color: #fff; font-size: 8px; font-weight: 700; white-space: nowrap;
}

{{-- ── Hero route ─────────────────────────────────────────────────────────
   The loudest thing on the page: at a gate or a desk this is what someone
   looks for first. --}}
.hero { padding: 18px 20px 16px; border-bottom: 1px solid {{ $c['line'] }}; }
.hero .code { color: {{ $c['brand'] }}; font-size: 30px; font-weight: 700; line-height: 1; }
.hero .place { margin-top: 5px; color: {{ $c['muted'] }}; font-size: 8px; }
.hero .mid { padding: 0 14px; }
.hero .dur { color: {{ $c['text'] }}; font-size: 9px; font-weight: 700; }
.hero .rule { margin: 6px 0 4px; border-top: 1px solid {{ $c['line'] }}; }
.hero .stops { color: {{ $c['muted'] }}; font-size: 8px; }

{{-- ── Reference strip ────────────────────────────────────────────────────
   Everything a traveller is ever asked to read out, on one line. --}}
.refs { border-bottom: 1px solid {{ $c['line'] }}; background: {{ $c['panel'] }}; }
.refs td { width: 25%; padding: 11px 20px; border-right: 1px solid {{ $c['line'] }}; }
.refs td.last { border-right: 0; }
.refs .k { color: {{ $c['muted'] }}; font-size: 7.5px; }
.refs .v { margin-top: 3px; font-size: 12px; font-weight: 700; }
.refs .v.code { font-family: {{ $mono }}; color: {{ $c['brand'] }}; }
.refs .sub { margin-top: 2px; color: {{ $c['muted'] }}; font-size: 7.5px; }

{{-- ── Sections ───────────────────────────────────────────────────────────
   Sentence case, not the tracked-out uppercase every label used to carry.
   When all of them shout none of them lead. --}}
.section { margin: 16px 0 7px; color: {{ $c['ink'] }}; font-size: 10px; font-weight: 700; }
.section .count { color: {{ $c['muted'] }}; font-weight: normal; }

{{-- ── Journey timeline ───────────────────────────────────────────────────
   A spine with a node at every airport, so a connection reads as one journey.
   The old layout drew each leg as a separate boxed card with nothing between
   them, so a two-hour connection in Amsterdam was invisible. --}}
.leg { page-break-inside: avoid; }
.leg .spine { width: 16px; padding: 0; }
.leg .dot {
    width: 7px; height: 7px; margin: 3px 0 0 1px;
    border: 2px solid {{ $c['brand'] }}; background: #fff;
    border-radius: 4px;
}
.leg .dot.small { width: 5px; height: 5px; margin-left: 2px; border-color: {{ $c['subtle'] }}; border-width: 1px; }
{{-- The spine.
   A <div> rule refused to draw here whether it used a border or a background,
   with or without an explicit height — the only technique that renders
   reliably inside this nesting is a <td> carrying a background colour, so the
   connecting line is a one-cell nested table. --}}
.leg .rail { width: 12px; }
.leg .rail td.pad { width: 4px; }
.leg .rail td.ink { width: 1px; height: 62px; background: {{ $c['line'] }}; }
.stop-time { width: 44px; padding: 0 0 0 2px; font-family: {{ $mono }}; font-size: 11px; font-weight: 700; }
.stop-body { padding: 0 0 0 4px; }
.stop-place { font-size: 10px; font-weight: 700; }
.stop-airport { margin-top: 1px; color: {{ $c['muted'] }}; font-size: 8px; }
.stop-day { color: {{ $c['muted'] }}; font-size: 8px; }

.carrier { padding: 7px 0 7px 50px; }
.carrier .name { font-size: 9px; font-weight: 700; }
.carrier .no { color: {{ $c['muted'] }}; font-family: {{ $mono }}; font-size: 8.5px; }
.facts { margin-top: 5px; }
.facts td {
    padding: 5px 8px; border: 1px solid {{ $c['line'] }}; background: {{ $c['panel'] }};
    color: {{ $c['muted'] }}; font-size: 7.5px;
}
.facts td strong { display: block; margin-top: 1px; color: {{ $c['ink'] }}; font-size: 8.5px; font-weight: 700; }

.layover {
    margin: 4px 0 4px 50px; padding: 5px 9px;
    border: 1px solid #fde8c8; background: #fffaf0;
    color: #92400e; font-size: 8px;
}

{{-- ── Data tables ────────────────────────────────────────────────────────--}}
.grid { border: 1px solid {{ $c['line'] }}; page-break-inside: avoid; }
.grid th {
    padding: 7px 9px; border-bottom: 1px solid {{ $c['line'] }}; background: {{ $c['panel'] }};
    color: {{ $c['muted'] }}; font-size: 7.5px; font-weight: normal; text-align: left;
}
.grid td { padding: 8px 9px; border-top: 1px solid {{ $c['line_soft'] }}; font-size: 8.5px; }
.grid tr:first-child td { border-top: 0; }
.grid td.name { font-weight: 700; }

.pair { border: 1px solid {{ $c['line'] }}; page-break-inside: avoid; }
.pair td { padding: 8px 10px; border-right: 1px solid {{ $c['line_soft'] }}; border-top: 1px solid {{ $c['line_soft'] }}; }
.pair tr:first-child td { border-top: 0; }
.pair td.last { border-right: 0; }
.pair .k { color: {{ $c['muted'] }}; font-size: 7.5px; }
.pair .v { margin-top: 2px; font-size: 9px; font-weight: 700; }
.pair .v.code { font-family: {{ $mono }}; color: {{ $c['brand'] }}; }

.money { border: 1px solid {{ $c['line'] }}; page-break-inside: avoid; }
.money td { padding: 6px 10px; border-top: 1px solid {{ $c['line_soft'] }}; font-size: 8.5px; }
.money tr:first-child td { border-top: 0; }
.money td.amt { text-align: right; font-family: {{ $mono }}; font-weight: 700; }
.money tr.total td { border-top: 1px solid {{ $c['line'] }}; background: {{ $c['panel'] }}; font-size: 10px; font-weight: 700; }

{{-- ── Notes ──────────────────────────────────────────────────────────────--}}
.note { margin-top: 12px; padding: 9px 11px; border: 1px solid {{ $c['line'] }}; background: {{ $c['panel'] }}; font-size: 8px; page-break-inside: avoid; }
.note.warn { border-color: #fde8c8; background: #fffaf0; color: #92400e; }
.note.danger { border-color: #fee4e2; background: {{ $c['danger_bg'] }}; color: {{ $c['danger'] }}; }
.note strong { font-weight: 700; }

.footer {
    position: fixed; left: 0; right: 0; bottom: -30px;
    padding-top: 6px; border-top: 1px solid {{ $c['line'] }};
    color: {{ $c['subtle'] }}; font-size: 7px;
}

.watermark {
    position: fixed; top: 44%; left: 0; width: 100%;
    color: rgba(48,49,145,.06); font-size: 44px; font-weight: 700;
    text-align: center; transform: rotate(-24deg);
}
