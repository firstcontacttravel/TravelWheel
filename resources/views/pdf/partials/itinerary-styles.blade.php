{{--
    Stylesheet for the flight itinerary (pdf.itinerary).

    Every size is in pt. DomPDF converts px through its dpi option, which
    ItineraryPdfService sets to 150, so the old 9px body text printed at 4.3pt.
    Points print at the size written whatever the dpi.

    Line heights are not what a browser would draw. DomPDF sets each line at
    (line-height / font-size) x the font's own height, and Plex's height is
    1.43 x its size. So line-height: 1 gives Plex its natural 1.43 spacing,
    and the smaller values below tighten single-line labels and headings.

    Type is IBM Plex Sans and Plex Mono, bundled in resources/fonts/pdf under
    the SIL Open Font License. Plex Mono carries every code a traveller reads
    out at a desk: booking reference, PNR, ticket numbers, airport codes.

    DomPDF has no flexbox, grid or CSS variables, so layout is tables and the
    colours below are interpolated by PHP from config/brand.php.
--}}
@php
    $c = config('brand.colors');
    $font = fn (string $file) => str_replace('\\', '/', resource_path('fonts/pdf/'.$file));
@endphp

@font-face { font-family: 'Plex Sans'; font-weight: 400; font-style: normal; src: url('{{ $font('IBMPlexSans-Regular.ttf') }}') format('truetype'); }
@font-face { font-family: 'Plex Sans'; font-weight: 500; font-style: normal; src: url('{{ $font('IBMPlexSans-Medium.ttf') }}') format('truetype'); }
@font-face { font-family: 'Plex Sans'; font-weight: 600; font-style: normal; src: url('{{ $font('IBMPlexSans-SemiBold.ttf') }}') format('truetype'); }
@font-face { font-family: 'Plex Mono'; font-weight: 400; font-style: normal; src: url('{{ $font('IBMPlexMono-Regular.ttf') }}') format('truetype'); }
@font-face { font-family: 'Plex Mono'; font-weight: 500; font-style: normal; src: url('{{ $font('IBMPlexMono-Medium.ttf') }}') format('truetype'); }

@page { margin: 38pt 40pt 58pt; }

body {
    margin: 0;
    color: {{ $c['text'] }};
    background: #fff;
    font-family: 'Plex Sans', 'DejaVu Sans', sans-serif;
    font-size: 9.5pt;
    line-height: 1;
}
table { width: 100%; border-collapse: collapse; }
td, th { padding: 0; vertical-align: top; text-align: left; }
.right { text-align: right; }
.center { text-align: center; }
.mono { font-family: 'Plex Mono', 'DejaVu Sans Mono', monospace; }
.muted { color: {{ $c['muted'] }}; }
.keep { page-break-inside: avoid; }

.label { color: {{ $c['muted'] }}; font-size: 8pt; line-height: 0.92; }

{{-- Masthead: wordmark left, the document and its reference right. --}}
.masthead td { vertical-align: bottom; padding-bottom: 10pt; }
.wordmark { color: {{ $c['brand'] }}; font-size: 16pt; font-weight: 600; line-height: 0.8; }
.doc-name { color: {{ $c['muted'] }}; font-size: 8.5pt; line-height: 0.9; }
.doc-ref { color: {{ $c['ink'] }}; font-size: 13pt; font-weight: 500; line-height: 0.86; }
.masthead-rule { height: 0; border-top: 1.5pt solid {{ $c['brand'] }}; }

{{-- Trip heading: where the traveller is going, then the shape of the trip. --}}
.trip { margin-top: 16pt; }
.trip-title { color: {{ $c['ink'] }}; font-size: 20pt; font-weight: 600; line-height: 0.84; }
.trip-meta { margin-top: 4pt; color: {{ $c['muted'] }}; font-size: 9.5pt; line-height: 0.96; }
.trip-meta span { margin-right: 16pt; }

{{-- Status: the one coloured block on the page, so it is not missed. --}}
.status { margin-top: 14pt; padding: 8pt 12pt 9pt; border-left: 3pt solid; }
.status-title { font-size: 10pt; font-weight: 600; line-height: 0.91; }
.status-body { margin-top: 2pt; color: {{ $c['text'] }}; font-size: 9pt; line-height: 1; }

{{-- Reference facts: what someone reads out to an airline or to us. --}}
.facts { margin-top: 14pt; border-top: 0.75pt solid {{ $c['line'] }}; border-bottom: 0.75pt solid {{ $c['line'] }}; }
.facts td { width: 33.33%; padding: 8pt 0 9pt 14pt; border-left: 0.75pt solid {{ $c['line'] }}; }
.facts td.first { padding-left: 0; border-left: 0; }
.facts .value { margin-top: 3pt; color: {{ $c['ink'] }}; font-size: 11pt; font-weight: 500; line-height: 0.89; }
.facts .value.mono { font-size: 11.5pt; }
.facts .value.quiet { color: {{ $c['muted'] }}; font-size: 9.5pt; font-weight: 400; }

{{-- Section headings. --}}
.section { margin-top: 20pt; }
.section-title { color: {{ $c['ink'] }}; font-size: 12pt; font-weight: 600; line-height: 0.87; }
.section-note { margin-top: 1pt; color: {{ $c['muted'] }}; font-size: 8.5pt; line-height: 0.9; }

{{-- One journey (outbound, return or a multi-city leg). --}}
.journey { margin-top: 12pt; }
.journey-head td { padding-bottom: 5pt; vertical-align: bottom; line-height: 0.9; }
.journey-name { color: {{ $c['ink'] }}; font-size: 11pt; font-weight: 600; }
.journey-date { color: {{ $c['muted'] }}; font-size: 9.5pt; padding-left: 6pt; }
.journey-total { color: {{ $c['muted'] }}; font-size: 8.5pt; }

{{-- One flight. --}}
.flight { border: 0.75pt solid {{ $c['line'] }}; page-break-inside: avoid; }
.flight-carrier td { padding: 6pt 12pt; background: {{ $c['surface'] }}; border-bottom: 0.75pt solid {{ $c['line'] }}; vertical-align: middle; line-height: 0.92; }
.carrier-name { color: {{ $c['ink'] }}; font-size: 9.5pt; font-weight: 600; }
.carrier-no { color: {{ $c['muted'] }}; font-size: 9pt; padding-left: 5pt; }
.carrier-cabin { color: {{ $c['text'] }}; font-size: 8.5pt; }

.flight-route td { padding: 10pt 12pt 9pt; }
.time { color: {{ $c['ink'] }}; font-size: 17pt; font-weight: 600; line-height: 0.78; }
.day-shift { color: {{ $c['warning'] }}; font-size: 8pt; font-weight: 600; }
.place { margin-top: 5pt; color: {{ $c['ink'] }}; font-size: 10pt; line-height: 0.91; }
.place .code { color: {{ $c['brand'] }}; font-weight: 500; }
.airport { margin-top: 1pt; color: {{ $c['muted'] }}; font-size: 8.5pt; line-height: 0.95; }
.date { margin-top: 2pt; color: {{ $c['text'] }}; font-size: 8.5pt; line-height: 0.95; }
.leg-mid { padding-top: 5pt !important; }
.leg-duration { color: {{ $c['text'] }}; font-size: 8.5pt; line-height: 0.9; }
.leg-line { margin: 4pt 0 3pt; height: 0; border-top: 0.75pt solid {{ $c['subtle'] }}; }
.leg-stops { color: {{ $c['muted'] }}; font-size: 8pt; line-height: 0.92; }

.flight-details td { width: 25%; padding: 6pt 12pt 7pt; border-top: 0.75pt solid {{ $c['line_soft'] }}; }
.flight-details .value { margin-top: 1pt; color: {{ $c['ink'] }}; font-size: 9pt; line-height: 0.93; }

.connection { margin: 6pt 0; padding: 6pt 12pt; border-left: 2pt solid {{ $c['subtle'] }}; background: {{ $c['surface'] }}; color: {{ $c['text'] }}; font-size: 8.5pt; line-height: 0.95; }
.connection strong { color: {{ $c['ink'] }}; font-weight: 600; }

{{-- Travellers. --}}
.people { margin-top: 8pt; }
.people th { padding: 0 8pt 5pt 0; border-bottom: 0.75pt solid {{ $c['line'] }}; color: {{ $c['muted'] }}; font-size: 8pt; font-weight: 400; line-height: 0.92; }
.people td { padding: 7pt 8pt 7pt 0; border-bottom: 0.75pt solid {{ $c['line_soft'] }}; font-size: 9.5pt; line-height: 0.92; vertical-align: middle; }
.people td.name { color: {{ $c['ink'] }}; font-weight: 500; }

{{-- Closing notes, two columns. --}}
.notes { margin-top: 22pt; border-top: 0.75pt solid {{ $c['line'] }}; }
.notes td { width: 50%; padding-top: 12pt; }
.notes td.gap { padding-left: 24pt; }
.note-title { color: {{ $c['ink'] }}; font-size: 9.5pt; font-weight: 600; line-height: 0.92; }
.note-body { margin-top: 3pt; color: {{ $c['text'] }}; font-size: 8.5pt; line-height: 1; }
.note-body .ref { color: {{ $c['ink'] }}; font-weight: 500; }

{{-- Repeats on every page. --}}
.footer { position: fixed; left: 0; right: 0; bottom: -36pt; padding-top: 7pt; border-top: 0.75pt solid {{ $c['line'] }}; color: {{ $c['muted'] }}; font-size: 7.5pt; line-height: 0.93; }
{{-- Room for "Page 1 of 2", which ItineraryPdfService draws on every page
   after rendering: DomPDF cannot count the total pages from CSS. --}}
.footer td.right { padding-right: 62pt; }

.watermark {
    position: fixed; top: 46%; left: -40pt; right: -40pt;
    color: rgba(48, 49, 145, 0.05); font-size: 30pt; font-weight: 600; line-height: 0.84;
    text-align: center; white-space: nowrap; transform: rotate(-28deg);
}
