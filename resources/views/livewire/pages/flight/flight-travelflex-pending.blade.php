{{-- resources/views/livewire/pages/flight/flight-travelflex-pending.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex — Plan Pending Activation'])

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
    $finalDest = $isReturn && !empty($retSegs) ? $retSegs[count($retSegs)-1] : (!empty($segments) ? $segments[count($segments)-1] : []);
    $breakdown = $bookingFlight['fareBreakdown'] ?? $mf['fareBreakdown'] ?? [];
    $contact   = session('bookingContact', []);
    $passengers= session('bookingPassengers', []);
    $total     = (float)($mf['price'] ?? 0);
    $uniqueId  = session('bookingUniqueId', '');
    $tktLimit  = session('bookingTktTimeLimit', '');
    $tfPlan    = session('travelFlexPlan', []);
    $downPayment   = (float)($tfPlan['down_payment']   ?? 0);
    $downPercent   = (int)  ($tfPlan['down_percent']   ?? 30);
    $repaymentPlan = $tfPlan['repayment_plan']          ?? '';
    $grandTotal    = (float)($tfPlan['grand_total']    ?? 0);
    $totalInterest = (float)($tfPlan['total_interest'] ?? 0);
    $schedule      = $tfPlan['schedule']               ?? [];
    $remainingBal  = $total - $downPayment;
    $tktFmt = ''; $tktHours = 0;
    if ($tktLimit) { try { $td=\Carbon\Carbon::parse($tktLimit); $tktFmt=$td->format('D, d M Y \a\t H:i'); $tktHours=max(0,(int)now()->diffInHours($td,false)); } catch (\Throwable $e) {} }
    $equipMap = ['73H'=>'Boeing 737-800','738'=>'Boeing 737-800','320'=>'Airbus A320','321'=>'Airbus A321','789'=>'Boeing 787-9','332'=>'Airbus A330-200'];
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
@include('livewire.pages.flight.partials._shared_styles');
<style>
    
    .tf-pnd-hero { background:linear-gradient(135deg,#1e3a5f,var(--indigo),var(--purple)); border-radius:18px; padding:28px; margin-bottom:22px; color:#fff; display:flex; align-items:flex-start; gap:18px; }
    .tf-pnd-hero-icon { font-size:48px; flex-shrink:0; }
    .schedule-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .schedule-table th { padding:8px 12px; text-align:left; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--gray-400); background:var(--gray-50); border-bottom:1px solid var(--gray-200); }
    .schedule-table td { padding:10px 12px; border-bottom:1px solid var(--gray-100); }
    .schedule-table tr:last-child td { border-bottom:none; }
    .loan-bar { display:flex; background:var(--navy); border-radius:12px; overflow:hidden; }
    .loan-bar-item { flex:1; padding:12px 14px; border-right:1px solid rgba(255,255,255,.08); text-align:center; }
    .loan-bar-item:last-child { border-right:none; }
    .loan-bar-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,.55); margin-bottom:3px; }
    .loan-bar-val { font-size:14px; font-weight:800; color:#fff; font-family:var(--mono); }
</style>

<div class="pg-wrap" x-data="{}">

    <div class="tf-pnd-hero">
        <div class="tf-pnd-hero-icon">📬</div>
        <div>
            <div style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;background:rgba(255,255,255,.15);border-radius:999px;font-size:11px;font-weight:700;margin-bottom:8px;">📆 TravelFlex Plan</div>
            <div style="font-size:20px;font-weight:800;margin-bottom:6px;">Down Payment Received — Awaiting Verification</div>
            <div style="font-size:13px;opacity:.88;line-height:1.65;max-width:520px;">
                We've received your payment notification. Our team will verify your transfer and activate your TravelFlex plan within <strong>2–4 business hours</strong>. Your e-ticket will be issued immediately after activation.
            </div>
            @if($uniqueId)<div style="display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:8px 16px;background:rgba(255,255,255,.15);border-radius:8px;font-size:13px;font-weight:700;font-family:var(--mono);">📋 {{ $uniqueId }}</div>@endif
        </div>
    </div>

    <div class="pg-grid">
        <div class="pg-main">

            {{-- Timeline --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">🗺️</div>
                    <div><div class="pc-title">What Happens Next</div></div>
                </div>
                <div class="pc-body" style="padding:14px 20px 4px;">
                    @foreach([
                        ['done','✓','Flight Reserved','Your seat is on hold. Ref: '.$uniqueId],
                        ['done','✓','TravelFlex Plan Created',$repaymentPlan.' instalment plan set up. Down payment: '.$fmt($downPayment)],
                        ['done','✓','Down Payment Notified','You\'ve confirmed your bank transfer.'],
                        ['current','⏳','Payment Verification','Verifying your transfer. 2–4 business hours (Mon–Fri 8am–6pm).'],
                        ['pending','5','E-Ticket Issued & Plan Activated','Ticket sent to '.($contact['email']??'you').'. Repayment schedule begins.'],
                    ] as [$cls,$num,$title,$sub])
                    <div class="tl-step">
                        <div class="tl-num {{ $cls }}">{{ $num }}</div>
                        <div>
                            <div class="tl-title">{{ $title }}
                                @if($cls==='current')<span style="font-size:10.5px;background:var(--amber-lt);color:var(--amber);padding:2px 7px;border-radius:999px;font-weight:700;margin-left:6px;">In Progress</span>@endif
                            </div>
                            <div class="tl-sub">{{ $sub }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Deadline --}}
            @if($tktFmt)
            <div style="background:var(--amber-lt);border:1px solid #fed7aa;border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:12px;">
                <span style="font-size:24px;flex-shrink:0;">⏰</span>
                <div>
                    <div style="font-size:13px;font-weight:800;color:#92400e;margin-bottom:3px;">Booking Hold Expires</div>
                    <div style="font-size:12.5px;color:#78350f;line-height:1.55;">Your seat reservation expires <strong>{{ $tktFmt }}</strong>@if($tktHours>0) ({{ $tktHours }}h remaining)@endif. Ensure payment clears before this time.</div>
                </div>
            </div>
            @endif

            {{-- Loan Summary Bar --}}
            <div class="loan-bar">
                <div class="loan-bar-item"><div class="loan-bar-lbl">Ticket Cost</div><div class="loan-bar-val">{{ $fmt($total) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Down Paid ({{ $downPercent }}%)</div><div class="loan-bar-val" style="color:#86efac;">{{ $fmt($downPayment) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Balance Due</div><div class="loan-bar-val">{{ $fmt($remainingBal) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Grand Total</div><div class="loan-bar-val" style="color:#c4b5fd;">{{ $fmt($grandTotal) }}</div></div>
            </div>

            {{-- Flight Itinerary --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">✈️</div>
                    <div><div class="pc-title">Flight Itinerary</div><div class="pc-sub">{{ $tripLabel }} · {{ $mf['cabin']??'Economy' }} · {{ $mf['airline']??'' }}</div></div>
                </div>
                @if(!$isMulti)
                @include('livewire.pages.flight.partials._render_leg', ['legSegs'=>$segments,'legLabel'=>'Outbound','legBadgeClass'=>'outbound','legLayovers'=>$mf['layoverDurations']??[],'legStops'=>$mf['stops']??max(0,count($segments)-1),'legDuration'=>$mf['totalTimeLabel']??'','legDate'=>$mf['departDateLabel']??'','breakdown'=>$breakdown,'equipMap'=>$equipMap,'tripDetails'=>[]])
                @endif
                @if($isReturn && !empty($retSegs))
                @include('livewire.pages.flight.partials._render_leg', ['legSegs'=>$retSegs,'legLabel'=>'Return','legBadgeClass'=>'inbound','legLayovers'=>$mf['returnLayoverDurations']??[],'legStops'=>$mf['returnStops']??max(0,count($retSegs)-1),'legDuration'=>$mf['returnTotalTimeLabel']??'','legDate'=>$mf['returnDateLabel']??'','breakdown'=>$breakdown,'equipMap'=>$equipMap,'tripDetails'=>[]])
                @endif
                @if($isMulti) @foreach($multiLegs as $li=>$leg) @php $ls=$leg['segments']??[]; @endphp @if(!empty($ls)) @include('livewire.pages.flight.partials._render_leg',['legSegs'=>$ls,'legLabel'=>'Leg '.($li+1),'legBadgeClass'=>'multi','legLayovers'=>$leg['layoverDurations']??[],'legStops'=>$leg['stops']??max(0,count($ls)-1),'legDuration'=>$leg['totalTimeLabel']??'','legDate'=>$leg['departDateLabel']??'','breakdown'=>$breakdown,'equipMap'=>$equipMap,'tripDetails'=>[]]) @endif @endforeach @endif
            </div>

            {{-- Repayment Schedule --}}
            @if(!empty($schedule))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--purple-lt);color:var(--purple);">📅</div>
                    <div><div class="pc-title">Your Repayment Schedule</div><div class="pc-sub">{{ count($schedule) }} instalment(s) · {{ $repaymentPlan }}</div></div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="schedule-table">
                        <thead><tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($schedule as $i=>$inst)
                            <tr>
                                <td style="color:var(--gray-400);font-weight:700;">{{ $i+1 }}</td>
                                <td><strong>{{ $inst['label'] ?? (($i+1).'. Payment') }}</strong></td>
                                <td><span style="padding:2px 8px;background:var(--blue-lt);color:var(--blue);border-radius:999px;font-size:10.5px;font-weight:700;">{{ $inst['dueDate']??'—' }}</span></td>
                                <td style="font-family:var(--mono);">{{ $fmt($inst['principal']??0) }}</td>
                                <td style="font-family:var(--mono);color:var(--amber);">{{ $fmt($inst['interest']??0) }}</td>
                                <td><strong style="font-family:var(--mono);color:var(--indigo);">{{ $fmt($inst['total']??0) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Passengers --}}
            @if(!empty($passengers))
            <div class="pc">
                <div class="pc-head"><div class="pc-icon" style="background:#f0f9ff;color:#0369a1;">👥</div><div><div class="pc-title">Passengers</div></div></div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table">
                        <thead><tr><th>#</th><th>Name</th><th>Type</th><th>DOB</th><th>Nationality</th><th>Passport</th></tr></thead>
                        <tbody>
                            @foreach($passengers as $i=>$pax)
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

            <div class="notice purple">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Repayment schedule begins from plan activation date. You'll receive reminder emails 3 days before each due date. Missed payments may result in cancellation.</span>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;" class="btn-row">
                <a href="{{ route('home') }}" class="btn-primary" style="background:linear-gradient(135deg,var(--indigo),var(--purple));">Back to Home</a>
                <a href="#" onclick="window.print()" class="btn-ghost">Print / Save</a>
            </div>
        </div>

        {{-- RAIL --}}
        <aside class="pg-rail">
            <div class="pc">
                <div style="padding:14px 18px;background:linear-gradient(135deg,var(--navy),var(--indigo),var(--purple));">
                    <div style="font-size:15px;font-weight:800;color:#fff;">📆 TravelFlex Summary</div>
                </div>
                <div class="pc-body">
                    <div class="dr"><span class="dr-lbl">Route</span><span class="dr-val">@if($isMulti)@foreach($routeLines as $line)<div>{{ $line['route'] }}</div>@endforeach @else {{ ($firstSeg['from']??'') }} → {{ ($finalDest['to']??'') }} @endif</span></div>
                    <div class="dr"><span class="dr-lbl">Trip Type</span><span class="dr-val">{{ $tripLabel }}</span></div>
                    @if($isReturn && !empty($mf['returnDateLabel']))<div class="dr"><span class="dr-lbl">Return</span><span class="dr-val">{{ $mf['returnDateLabel'] }}</span></div>@endif
                    @if($uniqueId)<div class="dr"><span class="dr-lbl">Booking Ref</span><span class="dr-val mono">{{ $uniqueId }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Down Paid</span><span class="dr-val" style="color:var(--green);">{{ $fmt($downPayment) }} ({{ $downPercent }}%)</span></div>
                    <div class="dr"><span class="dr-lbl">Balance</span><span class="dr-val">{{ $fmt($remainingBal) }}</span></div>
                    <div class="dr"><span class="dr-lbl">Repayment</span><span class="dr-val">{{ $repaymentPlan }}</span></div>
                    <div class="dr"><span class="dr-lbl">Status</span><span class="dr-val"><span class="status-badge status-pending" style="font-size:10px;">⏳ Pending</span></span></div>
                </div>
                <div class="fare-total"><span class="fare-total-lbl">Grand Total</span><span class="fare-total-val">{{ $fmt($grandTotal) }}</span></div>
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
