{{-- resources/views/livewire/pages/flight/flight-pending.blade.php --}}
@component('layouts.app', ['title' => 'Awaiting Payment Confirmation'])

@php
    $bookingFlight = session('bookingFlight', []);
    $mf            = $bookingFlight['flight'] ?? $bookingFlight;
    $currency  = $mf['currency'] ?? 'NGN';
    $sym       = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt       = fn($v) => $sym . number_format((float)$v, 2);
    $segments  = $mf['segments']       ?? [];
    $retSegs   = $mf['returnSegments'] ?? [];
    $multiLegs = $mf['multiLegs']      ?? [];
    $isReturn  = count($retSegs) > 0;
    $isMulti   = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round Trip' : ($isMulti ? 'Multi-City' : 'One Way');
    $firstSeg  = $segments[0] ?? [];
    $lastSeg   = !empty($segments) ? $segments[count($segments)-1] : [];
    $finalDest = $isReturn && !empty($retSegs) ? $retSegs[count($retSegs)-1] : $lastSeg;
    $breakdown = $bookingFlight['fareBreakdown'] ?? $mf['fareBreakdown'] ?? [];
    $contact   = session('bookingContact', []);
    $passengers= session('bookingPassengers', []);
    $total     = (float)($mf['price'] ?? 0);
    $uniqueId  = session('bookingUniqueId', '');   // API hold ref (NOT shown as booking ref)
    $bookingRef = $bookingRef ?? session('bookingRef', $dbBooking?->booking_ref ?? ''); // OUR ref
    $tktLimit  = session('bookingTktTimeLimit', '');
    $dbBooking = $dbBooking ?? null;
    $tktFmt = ''; $tktHours = 0;
    if ($tktLimit) {
        try { $td = \Carbon\Carbon::parse($tktLimit); $tktFmt = $td->format('D, d M Y \a\t H:i'); $tktHours = max(0,(int)now()->diffInHours($td,false)); } catch (\Throwable $e) {}
    }
    $equipMap = ['73H'=>'Boeing 737-800','738'=>'Boeing 737-800','7M8'=>'Boeing 737 MAX 8','789'=>'Boeing 787-9','788'=>'Boeing 787-8','320'=>'Airbus A320','321'=>'Airbus A321','332'=>'Airbus A330-200','333'=>'Airbus A330-300','E90'=>'Embraer E190'];
    $routeLines = [];
    if ($isMulti) {
        foreach ($multiLegs as $li => $leg) {
            $routeLines[] = [
                'label' => 'Leg ' . ($li + 1),
                'route' => ($leg['from'] ?? '') . ' → ' . ($leg['to'] ?? ''),
                'date'  => $leg['departDateLabel'] ?? '',
            ];
        }
    }
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    .pnd-hero { background:linear-gradient(135deg,#1e3a5f 0%,var(--blue) 100%); border-radius:18px; padding:28px 28px; margin-bottom:22px; color:#fff; display:flex; align-items:flex-start; gap:18px; }
    .pnd-hero-icon { font-size:48px; flex-shrink:0; }
    .pnd-hero-title { font-size:21px; font-weight:800; margin-bottom:6px; }
    .pnd-hero-sub { font-size:13.5px; opacity:.88; line-height:1.65; max-width:520px; }
    .pnd-ref { display:inline-flex; align-items:center; gap:8px; margin-top:12px; padding:8px 16px; background:rgba(255,255,255,.15); border-radius:8px; font-size:13px; font-weight:700; font-family:var(--mono); }
    .deadline-box { background:var(--amber-lt); border:1px solid #fed7aa; border-radius:12px; padding:14px 18px; display:flex; align-items:flex-start; gap:12px; }
    
</style>
@include('livewire.pages.flight.partials._shared_styles');
<div class="pg-wrap" x-data="{}">

    <div class="pnd-hero">
        <div class="pnd-hero-icon">📬</div>
        <div>
            <div class="pnd-hero-title">Payment Received — Awaiting Verification</div>
            <div class="pnd-hero-sub">
                Thank you! We've noted your bank transfer. Our team will verify and issue your e-ticket within
                <strong>2–4 business hours</strong> (Mon–Fri 8am–6pm).
                Confirmation will be sent to <strong>{{ $contact['email'] ?? '' }}</strong>.
            </div>
            @if($uniqueId)<div class="pnd-ref">📋 Booking Ref: {{ $bookingRef }}</div>@endif
        </div>
    </div>

    <div class="pg-grid">
        <div class="pg-main">

            {{-- What happens next --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">🗺️</div>
                    <div><div class="pc-title">What Happens Next</div></div>
                </div>
                <div class="pc-body" style="padding:14px 20px 4px;">
                    @foreach([
                        ['done',    '✓', 'Booking Created',          'Your seats are reserved with the airline. Ref: '.$uniqueId],
                        ['done',    '✓', 'Payment Notified',         'You\'ve confirmed your bank transfer.'],
                        ['current', '⏳','Payment Verification',      'Our team is verifying your transfer. Expected: 2–4 business hours (Mon–Fri 8am–6pm).'],
                        ['pending', '4', 'E-Ticket Issued',           'Your ticket will be emailed to '.($contact['email']??'you').' immediately after verification.'],
                    ] as [$cls, $num, $title, $sub])
                    <div class="tl-step">
                        <div class="tl-num {{ $cls }}">{{ $num }}</div>
                        <div>
                            <div class="tl-title">{{ $title }}
                                @if($cls === 'current') <span style="font-size:10.5px;background:var(--amber-lt);color:var(--amber);padding:2px 7px;border-radius:999px;font-weight:700;margin-left:6px;">In Progress</span> @endif
                            </div>
                            <div class="tl-sub">{{ $sub }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Deadline warning --}}
            @if($tktFmt)
            <div class="deadline-box">
                <span style="font-size:28px;flex-shrink:0;">⏰</span>
                <div>
                    <div style="font-size:13.5px;font-weight:800;color:#92400e;margin-bottom:3px;">Ticketing Deadline</div>
                    <div style="font-size:12.5px;color:#78350f;line-height:1.55;">
                        Your seat reservation expires <strong>{{ $tktFmt }}</strong>
                        @if($tktHours > 0)({{ $tktHours }} hours remaining)@endif.
                        Ensure payment is verified before this time.
                    </div>
                </div>
            </div>
            @endif

            {{-- Flight Itinerary --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">✈️</div>
                    <div>
                        <div class="pc-title">Flight Itinerary</div>
                        <div class="pc-sub">{{ $tripLabel }} · {{ $mf['cabin'] ?? 'Economy' }} · {{ $mf['airline'] ?? '' }}</div>
                    </div>
                </div>

                {{-- Outbound --}}
                @if(!$isMulti)
                @include('livewire.pages.flight.partials._render_leg', [
                    'legSegs' => $segments, 'legLabel' => 'Outbound', 'legBadgeClass' => 'outbound',
                    'legLayovers' => $mf['layoverDurations'] ?? [],
                    'legStops' => $mf['stops'] ?? max(0, count($segments)-1),
                    'legDuration' => $mf['totalTimeLabel'] ?? '', 'legDate' => $mf['departDateLabel'] ?? '',
                    'breakdown' => $breakdown, 'equipMap' => $equipMap, 'tripDetails' => [],
                ])
                @endif

                {{-- Return --}}
                @if($isReturn && !empty($retSegs))
                @include('livewire.pages.flight.partials._render_leg', [
                    'legSegs' => $retSegs, 'legLabel' => 'Return', 'legBadgeClass' => 'inbound',
                    'legLayovers' => $mf['returnLayoverDurations'] ?? [],
                    'legStops' => $mf['returnStops'] ?? max(0, count($retSegs)-1),
                    'legDuration' => $mf['returnTotalTimeLabel'] ?? '', 'legDate' => $mf['returnDateLabel'] ?? '',
                    'breakdown' => $breakdown, 'equipMap' => $equipMap, 'tripDetails' => [],
                ])
                @endif

                {{-- Multi-city --}}
                @if($isMulti)
                    @foreach($multiLegs as $li => $leg)
                        @php $legSegs = $leg['segments'] ?? []; @endphp
                        @if(!empty($legSegs))
                        @include('livewire.pages.flight.partials._render_leg', [
                            'legSegs' => $legSegs, 'legLabel' => 'Leg '.($li+1), 'legBadgeClass' => 'multi',
                            'legLayovers' => $leg['layoverDurations'] ?? [],
                            'legStops' => $leg['stops'] ?? max(0, count($legSegs)-1),
                            'legDuration' => $leg['totalTimeLabel'] ?? '', 'legDate' => $leg['departDateLabel'] ?? '',
                            'breakdown' => $breakdown, 'equipMap' => $equipMap, 'tripDetails' => [],
                        ])
                        @endif
                    @endforeach
                @endif
            </div>

            {{-- Passengers --}}
            @if(!empty($passengers))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:#f0f9ff;color:#0369a1;">👥</div>
                    <div><div class="pc-title">Passengers</div></div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table">
                        <thead><tr><th>#</th><th>Name</th><th>Type</th><th>DOB</th><th>Nationality</th><th>Passport</th></tr></thead>
                        <tbody>
                            @foreach($passengers as $i => $pax)
                            @php $c=match($pax['type']??'ADT'){'ADT'=>['#dbeafe','#1d4ed8'],'CHD'=>['#fef3c7','#d97706'],'INF'=>['#f0fdf4','#059669'],default=>['#f1f5f9','#64748b']}; @endphp
                            <tr>
                                <td style="color:var(--gray-400)">{{ $i+1 }}</td>
                                <td><strong>{{ $pax['title']??'' }} {{ strtoupper($pax['first_name']??'') }} {{ strtoupper($pax['last_name']??'') }}</strong></td>
                                <td><span class="pax-badge" style="background:{{$c[0]}};color:{{$c[1]}}">{{ match($pax['type']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'} }}</span></td>
                                <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
                                <td>{{ $pax['nationality']??'—' }}</td>
                                <td style="font-family:var(--mono);font-size:12px;">{{ $pax['passport_no']??'—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div style="display:flex;gap:12px;flex-wrap:wrap;" class="btn-row">
                <a href="{{ route('home') }}" class="btn-primary">Back to Home</a>
                <a href="#" onclick="window.print()" class="btn-ghost">Print / Save</a>
            </div>
        </div>

        {{-- RAIL --}}
        <aside class="pg-rail">
            <div class="pc">
                <div style="padding:14px 18px;background:var(--navy);">
                    <div style="font-size:15px;font-weight:800;color:#fff;">Booking Summary</div>
                </div>
                <div class="pc-body">
                    @php
                        $pndOutFirst = $firstSeg ?? [];
                        $pndOutLast  = !empty($segments) ? $segments[count($segments)-1] : [];
                        $pndOutRoute = ($pndOutFirst['from']??'') . ' → ' . ($pndOutLast['to']??'');
                        
                        $pndRetFirst = $retSegs[0] ?? [];
                        $pndRetLast  = !empty($retSegs) ? $retSegs[count($retSegs)-1] : [];
                        $pndRetRoute = ($pndRetFirst['from']??'') . ' → ' . ($pndRetLast['to']??'');
                    @endphp
                    
                    <div class="dr"><span class="dr-lbl">Route</span>
                        <span class="dr-val">
                            @if($isMulti)
                                @foreach($routeLines as $line)
                                    <div>{{ $line['route'] }}</div>
                                    @if(!empty($line['date']))<span style="font-size:11px;color:var(--gray-400);">{{ $line['label'] }} · {{ $line['date'] }}</span>@endif
                                @endforeach
                            @elseif($isReturn)
                                {{ $pndOutRoute }}<br><span style="font-size:11px;color:var(--gray-400);">{{ $pndRetRoute }}</span>
                            @else
                                {{ $pndOutRoute }}
                            @endif
                        </span>
                    </div>
                    <div class="dr"><span class="dr-lbl">Trip Type</span><span class="dr-val">{{ $tripLabel }}</span></div>
                    @if($isReturn && !empty($mf['returnDateLabel']))<div class="dr"><span class="dr-lbl">Return Date</span><span class="dr-val">{{ $mf['returnDateLabel'] }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Airline</span><span class="dr-val">{{ $mf['airline']??'—' }}</span></div>
                    <div class="dr"><span class="dr-lbl">Cabin</span><span class="dr-val">{{ $mf['cabin']??'Economy' }}</span></div>
                    @if($uniqueId)<div class="dr"><span class="dr-lbl">Booking Ref</span><span class="dr-val mono">{{ $bookingRef }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Payment</span><span class="dr-val">
                        <span class="status-badge status-pending" style="font-size:10.5px;">⏳ Awaiting Verification</span>
                    </span></div>
                </div>
                <div class="fare-total">
                    <span class="fare-total-lbl">Total</span>
                    <span class="fare-total-val">{{ $fmt($total) }}</span>
                </div>
            </div>
            <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px 18px;box-shadow:var(--shadow-sm);">
                <div style="font-size:13px;font-weight:800;color:var(--gray-900);margin-bottom:8px;">Need Help?</div>
                <div style="font-size:12.5px;color:var(--gray-500);line-height:1.65;">
                    📧 <a href="mailto:support@travelwheel.com" style="color:var(--blue);font-weight:600;">support@travelwheel.com</a><br>
                    📞 <strong>+234 800 000 0000</strong><br>
                    Quote: <strong style="font-family:var(--mono);color:var(--navy);">{{ $uniqueId }}</strong>
                </div>
            </div>
        </aside>
    </div>
</div>
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endcomponent