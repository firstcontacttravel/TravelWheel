@push('head')
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
@endpush
<div class="carhire-root">
<link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">

<section class="vw-hero">
    <div class="vw-hero__inner">
        <h1>All Your Travel Needs In One Place</h1>
        <p class="vw-hero__subtitle">Simplifying Access To Travel.</p>

        @if(session('error'))
            <div class="vw-errors" role="alert"><strong>{{ session('error') }}</strong></div>
        @endif

        <nav class="vw-product-tabs" aria-label="TravelWheel services">
            <a href="{{ route('air.flight') }}"><img src="{{ asset('assets/Flight 70.png') }}" alt=""><span>Flights</span></a>
            <!-- <a href="{{ route('air.hotel') }}"><img src="{{ asset('assets/Hotel 70.png') }}" alt=""><span>Hotels</span></a> -->
            <a href="{{ route('air.lounge') }}"><img src="{{ asset('assets/Lounge 70.png') }}" alt=""><span>Lounge</span></a>
            <a href="{{ route('air.protocol') }}"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
            <a href="{{ route('air.insurance') }}"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
            <a href="{{ route('air.visa') }}"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
            <a href="{{ route('air.cargo') }}"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
            <a href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
            <a class="active" href="{{ route('air.carhire') }}" aria-current="page"><img src="{{ asset('assets/Car Hire 70.png') }}" alt=""><span>Car Hire</span></a>

        </nav>

        <div class="vw-card">
            <div class="vw-card__head">
                <div>
                    <span class="vw-kicker">Ground transport</span>
                    <h2>Car Hire &amp; Airport Transfers</h2>
                </div>
            </div>
            <p style="margin:0 0 20px;color:#667085;font-size:13px;line-height:1.6;max-width:640px;">
                Reserve a chauffeur-driven car by the hour, or book a fixed-price airport/port transfer — pick your vehicle, enter your pickup and drop-off, and get an instant price.
            </p>
            <div class="vw-card__footer">
                <p>Every booking includes a professional driver and fuel. Choose Car Hire for flexible rental hours, or Transfer for a one-way point-to-point ride.</p>
                <a href="#carhire-widget" class="vw-search-btn">Get Started</a>
            </div>
        </div>
    </div>
</section>

<style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f0f2f8; min-height: 100vh; color: #1a1a1a; }
        .page { max-width: 1200px; margin: 0 auto; padding: 28px 16px 60px; }

        /* ── Header ── */
        .page-header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 22px; }
        .page-icon { width: 50px; height: 50px; background: #0d1883; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .page-icon svg { width: 24px; height: 24px; fill: white; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 600; color: #0d1883; margin-bottom: 5px; }
        .page-header p { font-size: 13px; color: #777; line-height: 1.6; max-width: 520px; }

        /* ── State Selector ── */
        .state-selector-wrap { background: #fff; border-radius: 16px; border: 1px solid #e0ddd6; padding: 20px 22px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .state-selector-wrap .ss-label { font-size: 10.5px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .state-selector-wrap .ss-label svg { width: 13px; height: 13px; fill: #0d1883; }
        .state-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .state-select { flex: 1; min-width: 200px; max-width: 340px; padding: 10px 14px; border: 1.5px solid #dde0ee; border-radius: 10px; font-size: 13.5px; font-family: 'DM Sans', sans-serif; color: #1a1a1a; background: #fafbff; outline: none; transition: border-color .2s, box-shadow .2s; cursor: pointer; }
        .state-select:focus { border-color: #0d1883; box-shadow: 0 0 0 3px rgba(13,24,131,.07); }
        .state-badge { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .state-badge.available { background: #e8f8f0; color: #1a7a4e; border: 1px solid #a8e6c4; }
        .state-badge.available svg { fill: #1a7a4e; }
        .state-badge.unavailable { background: #fff0f0; color: #c0392b; border: 1px solid #ffc5c5; }
        .state-badge.unavailable svg { fill: #c0392b; }
        .state-badge svg { width: 13px; height: 13px; }
        .state-badge span.dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .state-badge.available .dot { background: #1a7a4e; }
        .state-badge.unavailable .dot { background: #c0392b; }

        /* ── Unavailable message ── */
        .unavail-box { display: none; background: #fff; border-radius: 18px; border: 1.5px solid #ffc5c5; padding: 44px 28px; text-align: center; box-shadow: 0 4px 20px rgba(192,57,43,.08); }
        .unavail-box.show { display: block; }
        .unavail-icon { width: 64px; height: 64px; background: #fff0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .unavail-icon svg { width: 30px; height: 30px; fill: #e74c3c; }
        .unavail-box h3 { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 600; color: #c0392b; margin-bottom: 8px; }
        .unavail-box p { font-size: 13.5px; color: #777; line-height: 1.7; max-width: 440px; margin: 0 auto 18px; }
        .unavail-states { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
        .unavail-states span { background: #eef1ff; color: #0d1883; font-size: 11.5px; font-weight: 600; padding: 4px 12px; border-radius: 20px; border: 1px solid #c5cef8; }

        /* ── Main content (tabs + panels) — hidden until Lagos selected ── */
        .main-content { display: none; }
        .main-content.show { display: block; }

        /* ── Tabs ── */
        .tab-bar { display: flex; margin-bottom: 20px; background: #fff; border-radius: 14px; padding: 5px; border: 1px solid #e0ddd6; width: fit-content; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .tab-btn { display: flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 10px; border: none; font-size: 13.5px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .22s; color: #999; background: transparent; }
        .tab-btn svg { width: 15px; height: 15px; fill: currentColor; }
        .tab-btn.active { background: #0d1883; color: white; box-shadow: 0 4px 14px rgba(13,24,131,.25); }
        .tab-btn:not(.active):hover { background: #f0f3ff; color: #0d1883; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Cards ── */
        .outer-card { background: #fff; border-radius: 20px; border: 1px solid #e0ddd6; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        .card-layout { display: grid; grid-template-columns: 210px 1fr; }
        @media(max-width:768px) { .card-layout { grid-template-columns: 1fr; } }
        .sidebar { background: #f7f8ff; border-right: 1px solid #e8eaf5; padding: 22px 16px; border-radius: 20px 0 0 20px; }
        @media(max-width:768px) { .sidebar { border-right: none; border-bottom: 1px solid #e8eaf5; border-radius: 20px 20px 0 0; } }
        .section-label { display: inline-flex; align-items: center; background: #eef1ff; color: #0d1883; font-size: 10px; font-weight: 700; letter-spacing: .07em; padding: 4px 10px; border-radius: 20px; margin-bottom: 14px; text-transform: uppercase; }
        .content-area { padding: 22px 20px; display: flex; flex-direction: column; gap: 16px; }
        .inner-card { background: #fff; border-radius: 14px; border: 1px solid #e4e6f0; padding: 18px; }
        .inner-card.tinted { background: #fafbff; }

        /* ── Type sidebar cards ── */
        .type-card { display: flex; align-items: center; gap: 10px; padding: 10px 11px; border-radius: 10px; border: 1.5px solid #dde0f0; cursor: pointer; transition: border-color .2s, background .2s; margin-bottom: 7px; background: #fff; position: relative; overflow: hidden; user-select: none; }
        .type-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #0d1883; opacity: 0; transition: opacity .2s; }
        .type-card:hover { border-color: #0d1883; background: #f0f3ff; }
        .type-card:hover::before { opacity: 1; }
        .type-card.active { border-color: #0d1883; background: #eef1ff; }
        .type-card.active::before { opacity: 1; }
        .type-thumb { width: 52px; height: 33px; background: #e8ecff; border-radius: 6px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .type-thumb img { width: 100%; height: 100%; object-fit: contain; }
        .type-thumb svg { width: 36px; height: 22px; }
        .type-info h6 { font-size: 12px; font-weight: 600; color: #1a1a1a; margin-bottom: 1px; }
        .type-info small { font-size: 10px; color: #999; }
        .type-check { margin-left: auto; width: 17px; height: 17px; border-radius: 50%; background: #0d1883; display: none; align-items: center; justify-content: center; flex-shrink: 0; }
        .type-card.active .type-check { display: flex; }
        .type-check svg { width: 9px; height: 9px; }

        /* ── Transfer sidebar locked state ── */
        .sidebar-locked { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 180px; gap: 10px; padding: 20px 10px; text-align: center; }
        .sidebar-locked svg { width: 32px; height: 32px; fill: #c5cef8; }
        .sidebar-locked p { font-size: 11.5px; color: #bbb; line-height: 1.5; }

        /* ── Horizontal category/model cards ── */
        .cat-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 120px; gap: 8px; }
        .cat-empty svg { width: 32px; height: 32px; opacity: .3; fill: #aaa; }
        .cat-empty p { font-size: 12.5px; color: #bbb; }
        .cat-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .cat-thumb-card { flex: 1; min-width: 110px; max-width: 170px; border: 2px solid #e0e4f0; border-radius: 14px; padding: 13px 10px; cursor: pointer; transition: border-color .2s, background .2s, transform .2s, box-shadow .2s; background: #fff; text-align: center; position: relative; user-select: none; }
        .cat-thumb-card:hover { border-color: #0d1883; background: #f5f7ff; transform: translateY(-3px); box-shadow: 0 6px 18px rgba(13,24,131,.1); }
        .cat-thumb-card.active { border-color: #0d1883; background: #eef1ff; box-shadow: 0 6px 18px rgba(13,24,131,.15); }
        .cat-thumb-card.dimmed { opacity: .35; transform: none; pointer-events: none; }
        .ctc-img { width: 100%; height: 58px; background: #e8ecff; border-radius: 9px; display: flex; align-items: center; justify-content: center; margin-bottom: 9px; overflow: hidden; }
        .ctc-img img { width: 100%; height: 100%; object-fit: contain; }
        .ctc-img svg { width: 50px; height: 32px; }
        .ctc-name { font-size: 13px; font-weight: 600; color: #1a1a1a; margin-bottom: 3px; }
        .ctc-price { font-size: 11.5px; font-weight: 700; color: #0d1883; }
        .ctc-pax { font-size: 10px; color: #999; margin-top: 2px; }
        .ctc-check { position: absolute; top: 7px; right: 7px; width: 18px; height: 18px; background: #0d1883; border-radius: 50%; display: none; align-items: center; justify-content: center; }
        .cat-thumb-card.active .ctc-check { display: flex; }
        .ctc-check svg { width: 10px; height: 10px; fill: white; }

        /* ── Detail / expand panel ── */
        .cat-detail-panel { display: none; border: 2px solid #0d1883; border-radius: 16px; overflow: hidden; margin-top: 14px; animation: slideDown .28s cubic-bezier(.34,1.3,.64,1); }
        .cat-detail-panel.visible { display: block; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .detail-inner { display: grid; grid-template-columns: 320px 1fr; }
        @media(max-width:640px) { .detail-inner { grid-template-columns: 1fr; } }

        /* ── Carousel ── */
        .carousel-wrap { position: relative; background: #0a1060; overflow: hidden; min-height: 240px; }
        .carousel-track { display: flex; transition: transform .4s cubic-bezier(.25,.46,.45,.94); }
        .carousel-slide { min-width: 100%; height: 240px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #0d1883; }
        .carousel-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .carousel-slide .slide-fb { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
        .carousel-slide .slide-fb svg { width: 120px; height: 80px; }
        .car-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; background: #0a1f44; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s, transform .2s; z-index: 5; }
        .car-nav:hover { background: #123a7a; transform: translateY(-50%) scale(1.1); }
        .car-nav svg { width: 16px; height: 16px; fill: #ffffff; }
        .car-nav.prev { left: 10px; }
        .car-nav.next { right: 10px; }
        .car-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; z-index: 5; }
        .car-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,.4); cursor: pointer; transition: background .2s, transform .2s; border: none; }
        .car-dot.active { background: white; transform: scale(1.3); }
        .slide-label { position: absolute; bottom: 28px; left: 12px; background: rgba(0,0,0,.55); color: white; font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; backdrop-filter: blur(4px); z-index: 5; }

        /* ── Detail info panel ── */
        .detail-info { padding: 20px 22px; display: flex; flex-direction: column; gap: 12px; background: #fff; }
        .di-cat-name { font-family: 'Playfair Display', serif; font-size: 19px; font-weight: 600; color: #0d1883; margin-bottom: 3px; }
        .di-type-tag { display: inline-flex; background: #eef1ff; color: #0d1883; font-size: 9.5px; font-weight: 700; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; letter-spacing: .06em; }
        .di-price { font-size: 20px; font-weight: 700; color: #0d1883; }
        .di-price span { font-size: 11.5px; font-weight: 400; color: #999; margin-left: 3px; }
        .di-pax { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #666; margin-top: 4px; }
        .di-pax svg { width: 13px; height: 13px; fill: #0d1883; opacity: .7; }
        .feat-label { font-size: 10px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: .09em; margin-bottom: 7px; }
        .feat-list { list-style: none; display: flex; flex-direction: column; gap: 5px; }
        .feat-list li { display: flex; align-items: flex-start; gap: 7px; font-size: 12px; color: #444; line-height: 1.4; }
        .feat-list li svg { width: 12px; height: 12px; fill: #0d1883; flex-shrink: 0; margin-top: 1px; opacity: .85; }
        .di-actions { display: flex; gap: 9px; margin-top: 4px; }
        .btn-change { padding: 9px 13px; background: #f0f3ff; color: #0d1883; border: 1.5px solid #c5cef8; border-radius: 9px; font-size: 11.5px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: 5px; white-space: nowrap; }
        .btn-change:hover { background: #e0e8ff; }
        .btn-change svg { width: 11px; height: 11px; fill: #0d1883; }
        .btn-proceed-cat { flex: 1; padding: 10px 13px; background: linear-gradient(135deg,#0d1883,#2d39b6); color: white; border: none; border-radius: 9px; font-size: 11.5px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-proceed-cat:hover { background: linear-gradient(135deg,#0b1570,#1e2d9e); transform: translateY(-1px); }
        .btn-proceed-cat svg { width: 11px; height: 11px; fill: white; }

        /* ── Distance + pricing ── */
        .dist-status { display: none; align-items: center; gap: 7px; font-size: 12px; color: #0d1883; padding: 8px 12px; background: #eef1ff; border-radius: 8px; margin-bottom: 10px; }
        .dist-status.show { display: flex; }
        .dist-status svg { width: 14px; height: 14px; fill: #0d1883; animation: spin .8s linear infinite; }
        .dist-result { display: none; background: #eef1ff; border: 1px solid #c5cef8; border-radius: 9px; padding: 9px 13px; margin-bottom: 10px; font-size: 12px; color: #0d1883; font-weight: 600; }
        .dist-result.show { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px; }
        .price-breakdown { background: #f7f8ff; border: 1.5px solid #c5cef8; border-radius: 10px; padding: 13px 15px; display: none; margin-top: 8px; }
        .price-breakdown.show { display: block; }
        .pb-title { font-size: 9.5px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 9px; }
        .pb-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; border-bottom: 1px solid #e8eaf5; }
        .pb-row:last-of-type { border-bottom: none; }
        .pb-row span { color: #666; } .pb-row strong { color: #1a1a1a; }
        .pb-total { display: flex; justify-content: space-between; padding-top: 7px; margin-top: 3px; border-top: 1.5px solid #c5cef8; font-size: 13.5px; font-weight: 700; }
        .pb-total span { color: #555; } .pb-total strong { color: #0d1883; }

        /* ── Forms ── */
        .form-section-title { font-family: 'Playfair Display', serif; font-size: 17px; font-weight: 600; color: #0d1883; margin-bottom: 4px; }
        .form-chip { display: inline-flex; align-items: center; gap: 6px; background: #eef1ff; border: 1px solid #c5cef8; border-radius: 20px; padding: 4px 11px; font-size: 11px; font-weight: 600; color: #0d1883; margin-bottom: 14px; }
        .form-chip svg { width: 11px; height: 11px; fill: #0d1883; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        @media(max-width:540px) { .form-row { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 10.5px; font-weight: 600; color: #555; letter-spacing: .04em; text-transform: uppercase; }
        .form-group input, .form-group select { padding: 10px 12px; border: 1.5px solid #dde0ee; border-radius: 9px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #1a1a1a; background: #fafbff; transition: border-color .2s, box-shadow .2s; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #0d1883; box-shadow: 0 0 0 3px rgba(13,24,131,.07); background: #fff; }
        .pay-title { font-size: 10.5px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; display: block; }
        .pay-opts { display: flex; gap: 10px; margin-bottom: 15px; }
        .pay-opt { flex: 1; border: 1.5px solid #dde0ee; border-radius: 10px; padding: 10px 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: border-color .2s, background .2s; background: #fafbff; user-select: none; }
        .pay-opt:hover { border-color: #0d1883; background: #f0f3ff; }
        .pay-opt.active { border-color: #0d1883; background: #eef1ff; }
        .pay-opt input[type="radio"] { display: none; }
        .pay-dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid #ccc; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .pay-opt.active .pay-dot { border-color: #0d1883; }
        .pay-dot-inner { width: 6px; height: 6px; border-radius: 50%; background: #0d1883; display: none; }
        .pay-opt.active .pay-dot-inner { display: block; }
        .pay-label { font-size: 12px; font-weight: 600; color: #1a1a1a; }
        .pay-sub { font-size: 10px; color: #999; }
        .btn-review { width: 100%; padding: 13px; background: linear-gradient(135deg,#0d1883,#2d39b6); color: white; border: none; border-radius: 11px; font-size: 13.5px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; letter-spacing: .02em; }
        .btn-review:hover { background: linear-gradient(135deg,#0b1570,#1e2d9e); transform: translateY(-1px); }
        .btn-calc { width: 100%; padding: 10px; background: #f0f3ff; color: #0d1883; border: 1.5px solid #c5cef8; border-radius: 9px; font-size: 12.5px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; margin-bottom: 12px; }
        .btn-calc:hover { background: #e0e8ff; }
        .btn-proceed-form { width: 100%; padding: 11px; background: linear-gradient(135deg,#0d1883,#2d39b6); color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; margin-top: 12px; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-proceed-form:hover { background: linear-gradient(135deg,#0b1570,#1e2d9e); transform: translateY(-1px); }
        .btn-proceed-form svg { width: 12px; height: 12px; fill: white; }
        .secure-note { text-align: center; font-size: 10.5px; color: #bbb; margin-top: 9px; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .secure-note svg { width: 10px; height: 10px; opacity: .5; }
        .alert-box { background: #fff0f0; border: 1px solid #ffc5c5; color: #c0392b; border-radius: 9px; padding: 9px 13px; font-size: 12px; margin-bottom: 12px; display: none; }

        /* ── Transfer route grid ── */
        .tr-loc-grid { display: grid; grid-template-columns: 1fr 32px 1fr; align-items: end; gap: 8px; margin-bottom: 12px; }
        @media(max-width:500px) { .tr-loc-grid { grid-template-columns: 1fr; } .tr-arrow { display: none; } }
        .tr-arrow { display: flex; align-items: center; justify-content: center; padding-bottom: 2px; }
        .tr-arrow svg { width: 18px; height: 18px; fill: #0d1883; opacity: .4; }

        /* ── Fare Rules (compact row + modal trigger, GetTransfer-style) ── */
        .fare-rules-row { display: flex; flex-direction: column; gap: 7px; margin: 4px 0 14px; padding-top: 2px; }
        .fr-free-cancel { display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #1a7a4e; }
        .fr-free-cancel svg { width: 13px; height: 13px; fill: #1a7a4e; flex-shrink: 0; }
        .fare-rules-link { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; padding: 0; cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: 12.5px; font-weight: 600; color: #6b86c9; transition: color .2s; width: fit-content; }
        .fare-rules-link:hover { color: #0d1883; text-decoration: underline; }
        .fare-rules-link svg { width: 14px; height: 14px; fill: #6b86c9; flex-shrink: 0; transition: fill .2s; }
        .fare-rules-link:hover svg { fill: #0d1883; }

        /* ── Fare Rules modal content (reused inside the shared overlay) ── */
        .fr-section { margin-bottom: 14px; }
        .fr-section:last-child { margin-bottom: 0; }
        .fr-section h6 { font-size: 11.5px; font-weight: 700; color: #0d1883; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
        .fr-section ul { list-style: none; display: flex; flex-direction: column; gap: 5px; }
        .fr-section li { display: flex; align-items: flex-start; gap: 6px; font-size: 12.5px; color: #444; line-height: 1.55; }
        .fr-section li::before { content: '•'; color: #0d1883; flex-shrink: 0; line-height: 1.55; }

        /* ── Modal — entire overlay is built inline by openModal() in JS ── */
        @keyframes modalIn { from { opacity: 0; transform: translateY(20px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

<div class="page" id="carhire-widget">

    <div class="page-header">
        <div class="page-icon"><svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg></div>
        <div>
            <h1>Ground Transport</h1>
            <p>Book a car hire or arrange a seamless airport/port transfer. Professional drivers, all vehicle types, across Nigeria.</p>
        </div>
    </div>

    {{-- ══ STATE SELECTOR ══ --}}
    <div class="state-selector-wrap">
        <div class="ss-label">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            Select your state
        </div>
        <div class="state-row">
            <select class="state-select" id="stateSelect" onchange="onStateChange(this.value)">
                <option value="">— Choose your state —</option>
                <optgroup label="South West">
                    <option value="lagos">Lagos</option>
                    <option value="ogun">Ogun</option>
                    <option value="oyo">Oyo</option>
                    <option value="osun">Osun</option>
                    <option value="ondo">Ondo</option>
                    <option value="ekiti">Ekiti</option>
                </optgroup>
                <optgroup label="South East">
                    <option value="anambra">Anambra</option>
                    <option value="enugu">Enugu</option>
                    <option value="imo">Imo</option>
                    <option value="abia">Abia</option>
                    <option value="ebonyi">Ebonyi</option>
                </optgroup>
                <optgroup label="South South">
                    <option value="rivers">Rivers</option>
                    <option value="delta">Delta</option>
                    <option value="edo">Edo</option>
                    <option value="cross_river">Cross River</option>
                    <option value="akwa_ibom">Akwa Ibom</option>
                    <option value="bayelsa">Bayelsa</option>
                </optgroup>
                <optgroup label="North West">
                    <option value="kano">Kano</option>
                    <option value="kaduna">Kaduna</option>
                    <option value="sokoto">Sokoto</option>
                    <option value="katsina">Katsina</option>
                    <option value="zamfara">Zamfara</option>
                    <option value="kebbi">Kebbi</option>
                    <option value="jigawa">Jigawa</option>
                </optgroup>
                <optgroup label="North East">
                    <option value="borno">Borno</option>
                    <option value="yobe">Yobe</option>
                    <option value="adamawa">Adamawa</option>
                    <option value="taraba">Taraba</option>
                    <option value="bauchi">Bauchi</option>
                    <option value="gombe">Gombe</option>
                </optgroup>
                <optgroup label="North Central">
                    <option value="abuja">FCT Abuja</option>
                    <option value="kogi">Kogi</option>
                    <option value="kwara">Kwara</option>
                    <option value="benue">Benue</option>
                    <option value="plateau">Plateau</option>
                    <option value="nasarawa">Nasarawa</option>
                    <option value="niger">Niger</option>
                </optgroup>
            </select>
            <div id="stateBadge" style="display:none;"></div>
        </div>
    </div>

    {{-- Unavailable message --}}
    <div class="unavail-box" id="unavailBox">
        <div class="unavail-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        </div>
        <h3>Service Not Available Yet</h3>
        <p>We currently offer ground transport services only in <strong>Lagos State</strong>. We are actively expanding to other states across Nigeria.</p>
        <p style="margin-top:8px;font-size:12.5px;color:#aaa;">Currently available in:</p>
        <div class="unavail-states">
            <span>🟢 Lagos State</span>
        </div>
    </div>

    {{-- Main booking content — only shown for Lagos --}}
    <div class="main-content" id="mainContent">
        <div class="tab-bar">
            <button class="tab-btn active" id="tab-ch" onclick="switchTab('ch')">
                <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
                Car Rental
            </button>
            <button class="tab-btn" id="tab-tr" onclick="switchTab('tr')">
                <svg viewBox="0 0 24 24"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z"/></svg>
                Pick up 'n' Drop off
            </button>
        </div>

        {{-- ════════ CAR HIRE ════════ --}}
        <div class="tab-panel active" id="panel-ch">
            <div class="outer-card">
                <div class="card-layout">

                    <div class="sidebar">
                        <div class="section-label">1 · Vehicle type</div>
                        @foreach(['saloon','suv','van','bus','luxury'] as $vtype)
                        <div class="type-card" onclick="ch_setType('{{ $vtype }}',this)">
                            <div class="type-thumb">
                                <img src="{{ $typeThumbs[$vtype] }}" onerror="this.style.display='none';this.nextElementSibling.style.display='block';" alt="{{ ucfirst($vtype) }}">
                                <svg style="display:none" viewBox="0 0 60 38" fill="none"><rect x="3" y="12" width="50" height="18" rx="3" fill="#0d1883" opacity="0.3"/><circle cx="14" cy="32" r="5" fill="#0d1883" opacity="0.7"/><circle cx="42" cy="32" r="5" fill="#0d1883" opacity="0.7"/></svg>
                            </div>
                            <div class="type-info">
                                <h6>{{ $vtype === 'van' ? 'Mini Van' : ucfirst($vtype) }}</h6>
                                <small>{{ $categories[$vtype]['items'][0]['passengers'] ?? '—' }}</small>
                            </div>
                            <div class="type-check"><svg viewBox="0 0 12 10" fill="none"><path d="M1 5l3 3 7-7" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg></div>
                        </div>
                        @endforeach
                    </div>

                    <div class="content-area">

                        <div class="inner-card tinted">
                            <div class="section-label">2 · Select category</div>
                            <div id="ch_typeLbl" style="font-size:12px;font-weight:500;color:#0d1883;margin-bottom:14px;display:none;"></div>
                            <div id="ch_catArea">
                                <div class="cat-empty">
                                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/></svg>
                                    <p>Choose a vehicle type first</p>
                                </div>
                            </div>

                            {{-- Step 2.5: Model picker — shown after category selected, before detail panel --}}
                            <div id="ch_modelCard" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid #e4e6f0;">
                                <div class="section-label" style="margin-bottom:10px;">2.5 · Choose a model</div>
                                <p id="ch_modelIntro" style="font-size:12px;color:#888;margin-bottom:12px;"></p>
                                <div id="ch_modelArea" class="cat-row"></div>
                            </div>

                            <div id="ch_detailPanel" class="cat-detail-panel"></div>
                        </div>

                        <div class="inner-card" id="ch_pricingCard" style="display:none;">
                            <div class="section-label">3 · Route &amp; duration</div>
                            <div id="ch_alertA" class="alert-box"></div>
                            <div class="form-row" style="margin-bottom:10px;">
                                <div class="form-group">
                                    <label>Pick-up Location</label>
                                    <input type="text" id="ch_pickup" placeholder="e.g. Victoria Island, Lagos" oninput="ch_clearDist()">
                                </div>
                                <div class="form-group">
                                    <label>Drop-off Location</label>
                                    <input type="text" id="ch_dropoff" placeholder="e.g. Lekki Phase 1, Lagos" oninput="ch_clearDist()">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:10px;">
                                <label>Rental Duration (hours)</label>
                                <input type="number" id="ch_hours" placeholder="e.g. 4" min="1" oninput="ch_calcPrice()">
                            </div>
                            <button class="btn-calc" onclick="ch_calcDist()">📍 Get Final Quote</button>
                            <div class="dist-status" id="ch_calcSt">
                                <svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
                                Calculating via Google Maps...
                            </div>
                            <div class="dist-result" id="ch_distRes">
                                <div>Distance: <strong id="ch_distKmLbl">—</strong> &nbsp;·&nbsp; Drive time: <strong id="ch_driveLbl">—</strong></div>
                                <span style="font-size:10.5px;color:#888;">via Google Maps</span>
                            </div>
                            <div class="price-breakdown" id="ch_priceBox">
                                <div class="pb-title">Price Breakdown</div>
                                <div class="pb-row"><span>Base price</span><strong id="pb_base">₦0</strong></div>
                                <div class="pb-row"><span>Fuel (<span id="pb_km">0</span> km × ₦<span id="pb_frate">0</span>/km)</span><strong id="pb_fuel">₦0</strong></div>
                                <div class="pb-row"><span>Hourly (<span id="pb_hrs">0</span> hrs × ₦<span id="pb_hrate">0</span>/hr)</span><strong id="pb_hrly">₦0</strong></div>
                                <div class="pb-total"><span>Total Estimate</span><strong id="pb_total">₦0</strong></div>
                            </div>
                            <button class="btn-proceed-form" onclick="ch_goToForm()">
                                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                Proceed to Booking Form
                            </button>
                        </div>

                        <div class="inner-card" id="ch_formCard" style="display:none;">
                            <div class="form-section-title">Booking Details</div>
                            <div class="form-chip">
                                <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
                                <span id="ch_chipTxt"></span>
                            </div>
                            <div id="ch_alertB" class="alert-box"></div>
                            <div class="form-row">
                                <div class="form-group"><label>Full Name</label><input type="text" id="ch_name" placeholder="Enter your full name"></div>
                                <div class="form-group"><label>Email</label><input type="email" id="ch_email" placeholder="Enter your email"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Phone Number</label><input type="tel" id="ch_phone" placeholder="Enter your phone"></div>
                                <div class="form-group"><label>Passengers</label><input type="number" id="ch_pax" placeholder="e.g. 2" min="1"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Pick-up Date</label><input type="date" id="ch_date"></div>
                                <div class="form-group"><label>Pick-up Time</label><select id="ch_time"></select></div>
                            </div>

                            {{-- ══ FARE RULES ══ --}}
                            <div class="fare-rules-row">
                                <div class="fr-free-cancel">
                                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                    Free cancellation 5h before the trip
                                </div>
                                <button type="button" class="fare-rules-link" onclick="openFareRulesModal()">
                                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 15h-1.5v-6H12v6zm0-7.75c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25zM12.75 17H11v-1h.75v-4.25H11v-1h1.75V17z"/></svg>
                                    Fare Rules
                                </button>
                            </div>

                            <span class="pay-title">Payment Method</span>
                            <div class="pay-opts">
                                <label class="pay-opt active" id="ch_p_budpay" onclick="ch_pay('budpay')">
                                    <input type="radio" checked>
                                    <div class="pay-dot"><div class="pay-dot-inner"></div></div>
                                    <div><div class="pay-label">BudPay</div><div class="pay-sub">Card, bank transfer</div></div>
                                </label>
                                <label class="pay-opt" id="ch_p_seerbit" onclick="ch_pay('seerbit')">
                                    <input type="radio">
                                    <div class="pay-dot"><div class="pay-dot-inner"></div></div>
                                    <div><div class="pay-label">SeerBit</div><div class="pay-sub">Card, USSD, bank</div></div>
                                </label>
                            </div>
                            <button type="button" class="btn-review" onclick="openModal('ch')">Review &amp; Proceed to Payment</button>
                            <div class="secure-note"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>Secured payment · Your data is protected</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ════════ TRANSFER ════════ --}}
        <div class="tab-panel" id="panel-tr">
            <div class="outer-card">
                {{-- No sidebar for transfer — single column content area --}}
                <div class="content-area" style="padding:22px 24px;">

                    {{-- Step 1: Route --}}
                    <div class="inner-card tinted">
                        <div class="section-label">1 · Your route</div>
                        <p style="font-size:12.5px;color:#888;margin-bottom:14px;">Enter pick-up and drop-off — Google Maps calculates the exact road distance automatically.</p>
                        <div class="tr-loc-grid">
                            <div class="form-group">
                                <label>Pick-up Location</label>
                                <input type="text" id="tr_from" placeholder="e.g. Murtala Muhammed Airport">
                            </div>
                            <div class="tr-arrow"><svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg></div>
                            <div class="form-group">
                                <label>Drop-off Location</label>
                                <input type="text" id="tr_to" placeholder="e.g. Victoria Island, Lagos">
                            </div>
                        </div>
                        <button class="btn-calc" onclick="tr_calcDist()">📍 Get Final Quote</button>
                        <div class="dist-status" id="tr_calcSt">
                            <svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
                            Calculating via Google Maps...
                        </div>
                        <div class="dist-result" id="tr_distRes">
                            <div>Distance: <strong id="tr_distKmLbl">—</strong> &nbsp;·&nbsp; Drive time: <strong id="tr_driveLbl">—</strong></div>
                            <span style="font-size:10.5px;color:#888;">via Google Maps</span>
                        </div>
                        <div id="tr_alertA" class="alert-box"></div>
                    </div>

                    {{-- Step 2: Vehicle type — shown after distance calculated --}}
                    <div class="inner-card tinted" id="tr_typeCard" style="display:none;">
                        <div class="section-label">2 · Vehicle type</div>
                        <p style="font-size:12.5px;color:#888;margin-bottom:14px;">Select the type of vehicle you need.</p>
                        <div id="tr_typeCards" style="display:flex;gap:10px;flex-wrap:wrap;">
                            @foreach(['saloon','suv','van','bus','luxury'] as $vtype)
                            <div class="type-card" onclick="tr_setType('{{ $vtype }}',this)" style="flex:1;min-width:130px;max-width:200px;margin-bottom:0;">
                                <div class="type-thumb">
                                    <img src="{{ $transferVehicles[$vtype]['thumb'] }}" onerror="this.style.display='none';this.nextElementSibling.style.display='block';" alt="{{ ucfirst($vtype) }}">
                                    <svg style="display:none" viewBox="0 0 60 38" fill="none"><rect x="3" y="12" width="50" height="18" rx="3" fill="#0d1883" opacity="0.3"/><circle cx="14" cy="32" r="5" fill="#0d1883" opacity="0.7"/><circle cx="42" cy="32" r="5" fill="#0d1883" opacity="0.7"/></svg>
                                </div>
                                <div class="type-info">
                                    <h6>{{ $vtype === 'van' ? 'Mini Van' : ucfirst($vtype) }}</h6>
                                    <small>{{ count($transferVehicles[$vtype]['models']) }} model{{ count($transferVehicles[$vtype]['models']) > 1 ? 's' : '' }}</small>
                                </div>
                                <div class="type-check"><svg viewBox="0 0 12 10" fill="none"><path d="M1 5l3 3 7-7" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg></div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Step 3: Select car model --}}
                    <div class="inner-card tinted" id="tr_modelCard" style="display:none;">
                        <div class="section-label">3 · Select car</div>
                        <div id="tr_typeLbl" style="font-size:12px;font-weight:500;color:#0d1883;margin-bottom:14px;"></div>
                        <div id="tr_modelArea">
                            <div class="cat-empty">
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/></svg>
                                <p>Choose a vehicle type first</p>
                            </div>
                        </div>
                        <div id="tr_detailPanel" class="cat-detail-panel"></div>
                    </div>

                    {{-- Step 4: Booking form --}}
                    <div class="inner-card" id="tr_formCard" style="display:none;">
                        <div class="form-section-title">Transfer Details</div>
                        <div class="form-chip"><svg viewBox="0 0 24 24"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z"/></svg><span id="tr_chipTxt"></span></div>
                        <div id="tr_alertB" class="alert-box"></div>
                        <div class="form-row">
                            <div class="form-group"><label>Full Name</label><input type="text" id="tr_name" placeholder="Enter your full name"></div>
                            <div class="form-group"><label>Email</label><input type="email" id="tr_email" placeholder="Enter your email"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Phone Number</label><input type="tel" id="tr_phone" placeholder="Enter your phone"></div>
                            <div class="form-group"><label>Passengers</label><input type="number" id="tr_pax" placeholder="e.g. 2" min="1"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Flight / Vessel No. (optional)</label><input type="text" id="tr_flight" placeholder="e.g. LH 401"></div>
                            <div class="form-group"><label>Special Requests (optional)</label><input type="text" id="tr_notes" placeholder="e.g. child seat, extra luggage"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Arrival / Pick-up Date</label><input type="date" id="tr_date"></div>
                            <div class="form-group"><label>Arrival / Pick-up Time</label><select id="tr_time"></select></div>
                        </div>

                        {{-- ══ FARE RULES ══ --}}
                        <div class="fare-rules-row">
                            <div class="fr-free-cancel">
                                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                Free cancellation 5h before the trip
                            </div>
                            <button type="button" class="fare-rules-link" onclick="openFareRulesModal()">
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 15h-1.5v-6H12v6zm0-7.75c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25zM12.75 17H11v-1h.75v-4.25H11v-1h1.75V17z"/></svg>
                                Fare Rules
                            </button>
                        </div>

                        <span class="pay-title">Payment Method</span>
                        <div class="pay-opts">
                            <label class="pay-opt active" id="tr_p_budpay" onclick="tr_pay('budpay')">
                                <input type="radio" checked>
                                <div class="pay-dot"><div class="pay-dot-inner"></div></div>
                                <div><div class="pay-label">BudPay</div><div class="pay-sub">Card, bank transfer</div></div>
                            </label>
                            <label class="pay-opt" id="tr_p_seerbit" onclick="tr_pay('seerbit')">
                                <input type="radio">
                                <div class="pay-dot"><div class="pay-dot-inner"></div></div>
                                <div><div class="pay-label">SeerBit</div><div class="pay-sub">Card, USSD, bank</div></div>
                            </label>
                        </div>
                        <button type="button" class="btn-review" onclick="openModal('tr')">Review &amp; Proceed to Payment</button>
                        <div class="secure-note"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>Secured payment · Your data is protected</div>
                    </div>

                </div>
            </div>
        </div>

    </div>{{-- /main-content --}}

</div>{{-- /page --}}

{{-- Hidden submit forms --}}
<form id="ch_form" method="POST" action="{{ route('air.carhire.submit') }}" style="display:none">
    @csrf
    <input type="hidden" name="car_type"         id="h_ch_type">
    <input type="hidden" name="category"          id="h_ch_cat">
    <input type="hidden" name="car_model"         id="h_ch_model">
    <input type="hidden" name="price"             id="h_ch_price">
    <input type="hidden" name="distance_km"       id="h_ch_dist">
    <input type="hidden" name="rental_hours"      id="h_ch_hours">
    <input type="hidden" name="full_name"         id="h_ch_name">
    <input type="hidden" name="email"             id="h_ch_email">
    <input type="hidden" name="phone_number"      id="h_ch_phone">
    <input type="hidden" name="passengers"        id="h_ch_pax">
    <input type="hidden" name="pickup_location"   id="h_ch_pickup">
    <input type="hidden" name="dropoff_location"  id="h_ch_dropoff">
    <input type="hidden" name="pickup_date"       id="h_ch_date">
    <input type="hidden" name="pickup_time"       id="h_ch_time">
    <input type="hidden" name="payment_option"    id="h_ch_payment">
</form>
<form id="tr_form" method="POST" action="{{ route('air.transfer.submit') }}" style="display:none">
    @csrf
    <input type="hidden" name="vehicle_type"      id="h_tr_type">
    <input type="hidden" name="vehicle_name"      id="h_tr_vname">
    <input type="hidden" name="price"             id="h_tr_price">
    <input type="hidden" name="distance_km"       id="h_tr_dist">
    <input type="hidden" name="pickup_location"   id="h_tr_from">
    <input type="hidden" name="dropoff_location"  id="h_tr_to">
    <input type="hidden" name="full_name"         id="h_tr_name">
    <input type="hidden" name="email"             id="h_tr_email">
    <input type="hidden" name="phone_number"      id="h_tr_phone">
    <input type="hidden" name="passengers"        id="h_tr_pax">
    <input type="hidden" name="flight_number"     id="h_tr_flight">
    <input type="hidden" name="special_requests"  id="h_tr_notes">
    <input type="hidden" name="pickup_date"       id="h_tr_date">
    <input type="hidden" name="pickup_time"       id="h_tr_time">
    <input type="hidden" name="payment_option"    id="h_tr_payment">
</form>

<script>
/* ══ ALL DATA FROM BACKEND ══ */
const CH_DATA     = @json($categories);
const TR_VEHICLES = @json($transferVehicles);
const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const DIST_URL    = '{{ route("air.carhire.distance") }}';
const SLIDE_LABELS = ['Front', 'Rear', 'Interior'];
const PAX_LIMITS = { saloon:3, suv:3, van:5, bus:12, luxury:4 };
const SVG_FB = `<svg viewBox="0 0 60 38" fill="none"><rect x="3" y="10" width="50" height="22" rx="3" fill="#0d1883" opacity="0.3"/><circle cx="14" cy="34" r="5" fill="#0d1883" opacity="0.6"/><circle cx="42" cy="34" r="5" fill="#0d1883" opacity="0.6"/></svg>`;

/* ── state ── */
let chSelType=null, chSelCat=null, chSelModel=null, chDistKm=0, chFinalPrice=0, chPayment='budpay';
let trSelType=null, trSelModel=null, trDistKm=0, trFinalPrice=0, trPayment='budpay';
let activeModal=null;

/* ══ FARE RULES MODAL ══ */
const FARE_RULES_HTML = `
    <div class="fr-section">
        <h6>Cancellation</h6>
        <ul>
            <li>Cancellation is free of charge up to 5 hours prior to the trip. The money will be refunded in full to the card, bank account or credit limit according to the terms of the agreement.</li>
            <li>If you cancel a paid order less than 5 hours before the start of the trip, we will not be able to refund the money.</li>
            <li>If an order canceled less than 5 hours before the start of the trip has not been paid, you will have to pay a penalty of 100% of the order value.</li>
        </ul>
    </div>
    <div class="fr-section">
        <h6>Changing the Order</h6>
        <ul>
            <li>We do not charge a fee for the very fact of making changes, but if you change your route, car class or make other significant changes, this may result in a change in price.</li>
        </ul>
    </div>
    <div class="fr-section">
        <h6>What is Included in the Transfer Price</h6>
        <ul>
            <li>The price includes: a trip from point A to point B, transport fees, tips, meeting the passenger with the sign, escorting with baggage from the meeting point to the car.</li>
            <li>Possible car options: Toyota Hiace, Opel Vivaro, Hyundai H1 or similar.</li>
            <li>Free waiting time is 90 minutes. Additional waiting time is charged separately.</li>
        </ul>
    </div>
    <div class="fr-section">
        <h6>Baggage Allowance</h6>
        <ul>
            <li>Baggage count is calculated based on the standard size of one piece of baggage: 55x45x25 cm (22x18x10 inches).</li>
            <li>Please contact Customer Support if the passenger is going to have oversize baggage. We will reach the service provider in order to pick the appropriate car.</li>
        </ul>
    </div>`;

function openFareRulesModal() {
    const old = document.getElementById('_gtOverlay');
    if (old) old.remove();

    const overlay = document.createElement('div');
    overlay.id = '_gtOverlay';
    overlay.style.cssText = [
        'position:fixed','top:0','left:0','width:100%','height:100%',
        'background:rgba(10,12,40,0.72)',
        'z-index:2147483647',
        'display:flex','align-items:center','justify-content:center',
        'padding:20px','box-sizing:border-box',
        'font-family:DM Sans,sans-serif'
    ].join(';');
    overlay.onclick = function(e){ if(e.target===overlay) closeModal(); };

    overlay.innerHTML = `
    <div style="background:#fff;border-radius:20px;width:100%;max-width:480px;max-height:85vh;overflow-y:auto;
                box-shadow:0 24px 60px rgba(13,24,131,.3);animation:gtModalIn .22s ease both;position:relative;">
        <style>@keyframes gtModalIn{from{opacity:0;transform:translateY(18px) scale(.96)}to{opacity:1;transform:none}}</style>
        <div style="background:linear-gradient(135deg,#0d1883,#2d39b6);padding:20px 22px 16px;border-radius:20px 20px 0 0;position:relative;">
            <button onclick="closeModal()" style="position:absolute;top:13px;right:14px;width:28px;height:28px;background:rgba(255,255,255,.18);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;line-height:1;">✕</button>
            <h2 style="font-family:'Playfair Display',serif;font-size:18px;color:#fff;margin:0;">Fare Rules</h2>
        </div>
        <div style="padding:18px 22px 22px;">${FARE_RULES_HTML}</div>
    </div>`;

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
}

/* ══ STATE SELECTOR ══ */
function onStateChange(val) {
    const badge   = document.getElementById('stateBadge');
    const unavail = document.getElementById('unavailBox');
    const main    = document.getElementById('mainContent');

    if (!val) {
        badge.style.display = 'none';
        unavail.classList.remove('show');
        main.classList.remove('show');
        return;
    }

    badge.style.display = 'inline-flex';

    if (val === 'lagos') {
        badge.className = 'state-badge available';
        badge.innerHTML = `<span class="dot"></span> Service Available`;
        unavail.classList.remove('show');
        main.classList.add('show');
    } else {
        const stateLabel = document.getElementById('stateSelect').options[document.getElementById('stateSelect').selectedIndex].text;
        badge.className = 'state-badge unavailable';
        badge.innerHTML = `<span class="dot"></span> Not Available in ${stateLabel}`;
        main.classList.remove('show');
        unavail.classList.add('show');
    }
}

/* ══ TABS ══ */
function switchTab(t) {
    ['ch','tr'].forEach(x => {
        document.getElementById('tab-'+x).classList.toggle('active', x===t);
        document.getElementById('panel-'+x).classList.toggle('active', x===t);
    });
}

/* ══ DISTANCE (shared) ══ */
async function getDistance(fromEl, toEl) {
    const res = await fetch(DIST_URL, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF_TOKEN, 'Accept':'application/json' },
        body: JSON.stringify({
            origin:      fromEl.value.trim(),
            destination: toEl.value.trim(),
            origin_lat:  fromEl.dataset.lat,
            origin_lng:  fromEl.dataset.lng,
            dest_lat:    toEl.dataset.lat,
            dest_lng:    toEl.dataset.lng,
        }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Distance calculation failed.');
    return data;
}

/* ══ HELPERS ══ */
function esc(s)  { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
function escQ(s) { return (s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function showErr(id, msg) { const el=document.getElementById(id); el.textContent=msg; el.style.display='block'; el.scrollIntoView({behavior:'smooth',block:'center'}); }
function buildCarousel(slides, dots, id) {
    return `<div class="carousel-wrap">
        <div class="carousel-track" id="${id}Track">${slides}</div>
        <button class="car-nav prev" onclick="carGoTo('${id}',carGetIdx('${id}')-1)"><svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg></button>
        <button class="car-nav next" onclick="carGoTo('${id}',carGetIdx('${id}')+1)"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></button>
        <div class="car-dots" id="${id}Dots">${dots}</div>
    </div>`;
}
function carGetIdx(id) { const t=document.getElementById(id+'Track'); return t?parseInt(t.dataset.idx||'0'):0; }
function carGoTo(id, idx) {
    const track=document.getElementById(id+'Track'); if(!track) return;
    idx=Math.max(0,Math.min(idx,track.children.length-1));
    track.dataset.idx=idx; track.style.transform=`translateX(-${idx*100}%)`;
    document.querySelectorAll('#'+id+'Dots .car-dot').forEach((d,i)=>d.classList.toggle('active',i===idx));
}
function buildSlides(images, name) {
    if (!images||!images.length) return `<div class="carousel-slide"><div class="slide-fb">${SVG_FB}</div></div>`;
    return images.map((img,i)=>`<div class="carousel-slide">
        ${img?`<img src="${img}" alt="${esc(name)} ${SLIDE_LABELS[i]||''}">` : `<div class="slide-fb">${SVG_FB}</div>`}
        <span class="slide-label">${SLIDE_LABELS[i]||('View '+(i+1))}</span>
    </div>`).join('');
}
function buildDots(count, trackId) {
    return Array.from({length:count},(_,i)=>`<button class="car-dot${i===0?' active':''}" onclick="carGoTo('${trackId}',${i})"></button>`).join('');
}

/* ══════════════════════════════════════
   CAR HIRE
══════════════════════════════════════ */
function ch_setType(type, el) {
    document.querySelectorAll('#panel-ch .type-card').forEach(c=>c.classList.remove('active'));
    el.classList.add('active');
    chSelType=type; chSelCat=null; chSelModel=null; chDistKm=0; chFinalPrice=0;
    const lbl=document.getElementById('ch_typeLbl');
    lbl.textContent=(type==='van'?'Mini Van':type.charAt(0).toUpperCase()+type.slice(1))+' — choose a category';
    lbl.style.display='block';
    document.getElementById('ch_modelCard').style.display='none';
    document.getElementById('ch_pricingCard').style.display='none';
    document.getElementById('ch_formCard').style.display='none';
    document.getElementById('ch_detailPanel').classList.remove('visible');
    document.getElementById('ch_detailPanel').innerHTML='';
    ch_renderThumbs(type, null);
}

function ch_renderThumbs(type, activeName) {
    const cats=CH_DATA[type]?.items||[];
    const area=document.getElementById('ch_catArea');
    if (!cats.length) { area.innerHTML=`<div class="cat-empty"><p>No categories available.</p></div>`; return; }
    area.innerHTML=`<div class="cat-row">`+cats.map(cat=>{
        const isActive=activeName===cat.name;
        const isDimmed=activeName&&activeName!==cat.name;
        const cls=`cat-thumb-card${isActive?' active':''}${isDimmed?' dimmed':''}`;
        const thumbImg=cat.images?.[0]||'';
        const thumb=thumbImg?`<img src="${thumbImg}" alt="${esc(cat.name)}" onerror="this.parentNode.innerHTML=SVG_FB">`:SVG_FB;
        return `<div class="${cls}" onclick="ch_selectCat('${esc(type)}','${escQ(cat.name)}')">
            <div class="ctc-check"><svg viewBox="0 0 12 10" fill="none"><path d="M1 5l3 3 7-7" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg></div>
            <div class="ctc-img">${thumb}</div>
            <div class="ctc-name">${esc(cat.name)}</div>
            <div class="ctc-price">From &#8358;${Number(cat.price).toLocaleString()}</div>
            <div class="ctc-pax">&#128100; ${esc(cat.passengers||'—')}</div>
        </div>`;
    }).join('')+`</div>`;
}

function ch_selectCat(type, name) {
    const cat=(CH_DATA[type]?.items||[]).find(c=>c.name===name);
    if (!cat) return;
    if (chSelCat?.name===name) {
        // deselect
        chSelCat=null; chSelModel=null;
        document.getElementById('ch_modelCard').style.display='none';
        document.getElementById('ch_detailPanel').classList.remove('visible');
        document.getElementById('ch_detailPanel').innerHTML='';
        document.getElementById('ch_pricingCard').style.display='none';
        document.getElementById('ch_formCard').style.display='none';
        ch_renderThumbs(type,null);
        return;
    }
    chSelCat=cat; chSelModel=null;
    ch_renderThumbs(type, name);
    // Hide detail panel until model is picked
    document.getElementById('ch_detailPanel').classList.remove('visible');
    document.getElementById('ch_detailPanel').innerHTML='';
    document.getElementById('ch_pricingCard').style.display='none';
    document.getElementById('ch_formCard').style.display='none';
    // Show the model picker
    ch_renderModels(cat, null);
}

function ch_renderModels(cat, activeName) {
    const models = cat.models || [];
    const area = document.getElementById('ch_modelArea');
    const intro = document.getElementById('ch_modelIntro');
    intro.textContent = cat.name + ' — select the specific car model you prefer';
    if (!models.length) {
        area.innerHTML = `<div class="cat-empty"><p>No models listed for this category.</p></div>`;
        document.getElementById('ch_modelCard').style.display = 'block';
        return;
    }
    area.innerHTML = models.map(m => {
        const isActive = activeName === m.name;
        const isDimmed = activeName && activeName !== m.name;
        const cls = `cat-thumb-card${isActive?' active':''}${isDimmed?' dimmed':''}`;
        const thumb = m.image
            ? `<img src="${m.image}" alt="${esc(m.name)}" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.display='none'">`
            : SVG_FB;
        return `<div class="${cls}" onclick="ch_selectModel('${escQ(m.name)}')">
            <div class="ctc-check"><svg viewBox="0 0 12 10" fill="none"><path d="M1 5l3 3 7-7" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg></div>
            <div class="ctc-img">${thumb}</div>
            <div class="ctc-name" style="font-size:12px;">${esc(m.name)}</div>
        </div>`;
    }).join('');
    document.getElementById('ch_modelCard').style.display = 'block';
    document.getElementById('ch_modelCard').scrollIntoView({behavior:'smooth', block:'nearest'});
}

function ch_selectModel(name) {
    if (!chSelCat) return;
    const models = chSelCat.models || [];
    const model = models.find(m => m.name === name);
    if (!model) return;
    if (chSelModel === name) {
        // deselect
        chSelModel = null;
        ch_renderModels(chSelCat, null);
        document.getElementById('ch_detailPanel').classList.remove('visible');
        document.getElementById('ch_detailPanel').innerHTML = '';
        document.getElementById('ch_pricingCard').style.display = 'none';
        return;
    }
    chSelModel = name;
    ch_renderModels(chSelCat, name);
    ch_buildDetail(chSelType, chSelCat, model);  // pass model as third arg
}

function ch_buildDetail(type, cat, model) {
    const typeName = type === 'van' ? 'Mini Van' : type.charAt(0).toUpperCase() + type.slice(1);

    // Images: use model's own image (repeated 3x for carousel), fallback to cat images
    const modelImg = model?.image || null;
    const images = model?.images ||
        (modelImg ? [modelImg, modelImg, modelImg] : (cat.images || []));

    const slides = buildSlides(images, model?.name || cat.name);
    const dots   = buildDots(images.length || 1, 'chCar');

    // Features: model's own features, fallback to category features
    const feats = (model?.features || cat.features || [])
        .map(f => `<li><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>${esc(f)}</li>`)
        .join('');

    // Title = model name; tag = category (Regular / Standard / Executive)
    const displayName = model?.name || cat.name;
    const catTag      = model ? cat.name + ' · ' + typeName : typeName;

    const panel = document.getElementById('ch_detailPanel');
    panel.innerHTML = `<div class="detail-inner">
        ${buildCarousel(slides, dots, 'chCar')}
        <div class="detail-info">
            <div>
                <div class="di-cat-name">${esc(displayName)}</div>
                <div class="di-type-tag">${esc(catTag)}</div>
            </div>
            <div>
                <div class="di-price">&#8358;${Number(cat.price).toLocaleString()} <span>base price / trip</span></div>
                <div class="di-pax"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>${esc(cat.passengers || '—')} passengers</div>
            </div>
            <div><div class="feat-label">What's included</div><ul class="feat-list">${feats}</ul></div>
            <div class="di-actions">
                <button class="btn-change" onclick="ch_resetCat()"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>Change</button>
                <button class="btn-proceed-cat" onclick="ch_openPricing()"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>Book This Vehicle</button>
            </div>
        </div>
    </div>`;
    panel.classList.add('visible');
    document.getElementById('chCarTrack').dataset.idx = '0';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function ch_resetCat() {
    chSelCat=null; chSelModel=null;
    document.getElementById('ch_modelCard').style.display='none';
    document.getElementById('ch_detailPanel').classList.remove('visible');
    document.getElementById('ch_pricingCard').style.display='none';
    document.getElementById('ch_formCard').style.display='none';
    if(chSelType) ch_renderThumbs(chSelType,null);
}
function ch_openPricing() { const c=document.getElementById('ch_pricingCard'); c.style.display='block'; document.getElementById('ch_formCard').style.display='none'; c.scrollIntoView({behavior:'smooth',block:'nearest'}); }
function ch_clearDist() { chDistKm=0; chFinalPrice=0; document.getElementById('ch_distRes').classList.remove('show'); document.getElementById('ch_priceBox').classList.remove('show'); }

async function ch_calcDist() {
    const fromEl=document.getElementById('ch_pickup'), toEl=document.getElementById('ch_dropoff');
    const alertEl=document.getElementById('ch_alertA');
    alertEl.style.display='none';
    if (!fromEl.value.trim()||!toEl.value.trim()) { showErr('ch_alertA','Please enter both pick-up and drop-off locations.'); return; }
    const hrs=parseFloat(document.getElementById('ch_hours').value)||0;
    if (!hrs) { showErr('ch_alertA','Please enter the rental duration in hours first.'); return; }
    document.getElementById('ch_calcSt').classList.add('show');
    document.getElementById('ch_distRes').classList.remove('show');
    document.getElementById('ch_priceBox').classList.remove('show');
    try {
        const r=await getDistance(fromEl, toEl);
        chDistKm=r.distance_km;
        document.getElementById('ch_distKmLbl').textContent=r.distance_text;
        document.getElementById('ch_driveLbl').textContent=r.drive_time;
        document.getElementById('ch_distRes').classList.add('show');
        ch_calcPrice();
    } catch(e) { showErr('ch_alertA', e.message); }
    finally { document.getElementById('ch_calcSt').classList.remove('show'); }
}

function ch_calcPrice() {
    if (!chSelCat||!chSelType||!chDistKm) return;
    const td=CH_DATA[chSelType]; const fuelRate=td?.fuel_rate_per_km||0; const hrRate=td?.hourly_rate||0;
    const hrs=parseFloat(document.getElementById('ch_hours').value)||0;
    const base=chSelCat.price, fuel=chDistKm*fuelRate, hrly=hrs*hrRate;
    chFinalPrice=base+fuel+hrly;
    document.getElementById('pb_base').textContent='₦'+base.toLocaleString();
    document.getElementById('pb_km').textContent=chDistKm;
    document.getElementById('pb_frate').textContent=fuelRate.toLocaleString();
    document.getElementById('pb_fuel').textContent='₦'+fuel.toLocaleString();
    document.getElementById('pb_hrs').textContent=hrs;
    document.getElementById('pb_hrate').textContent=hrRate.toLocaleString();
    document.getElementById('pb_hrly').textContent='₦'+hrly.toLocaleString();
    document.getElementById('pb_total').textContent='₦'+chFinalPrice.toLocaleString();
    document.getElementById('ch_priceBox').classList.add('show');
}

function ch_goToForm() {
    if (!chDistKm) { showErr('ch_alertA','Please calculate the distance first.'); return; }
    const hrs=parseFloat(document.getElementById('ch_hours').value)||0;
    if (!hrs) { showErr('ch_alertA','Please enter rental hours.'); return; }
    const typeName=chSelType==='van'?'Mini Van':chSelType.charAt(0).toUpperCase()+chSelType.slice(1);
    document.getElementById('ch_chipTxt').textContent=typeName+' · '+chSelCat.name+' · '+chSelModel+' · ₦'+chFinalPrice.toLocaleString();
    const paxInput=document.getElementById('ch_pax');
    const maxPax=PAX_LIMITS[chSelType]??20;
    paxInput.max=maxPax; paxInput.placeholder='e.g. 2 (max '+maxPax+')';
    const fc=document.getElementById('ch_formCard'); fc.style.display='block'; fc.scrollIntoView({behavior:'smooth',block:'start'});
}

function ch_pay(m) { chPayment=m; ['budpay','seerbit'].forEach(x=>document.getElementById('ch_p_'+x).classList.toggle('active',x===m)); }

/* ══════════════════════════════════════
   TRANSFER
══════════════════════════════════════ */
function tr_setType(type, el) {
    document.querySelectorAll('#tr_typeCards .type-card').forEach(c=>c.classList.remove('active'));
    el.classList.add('active');
    trSelType=type; trSelModel=null; trFinalPrice=0;
    const lbl=document.getElementById('tr_typeLbl');
    lbl.textContent=(type==='van'?'Mini Van':type.charAt(0).toUpperCase()+type.slice(1))+' — choose a car model';
    document.getElementById('tr_modelCard').style.display='block';
    document.getElementById('tr_detailPanel').classList.remove('visible');
    document.getElementById('tr_detailPanel').innerHTML='';
    document.getElementById('tr_formCard').style.display='none';
    tr_renderModels(type, null);
    document.getElementById('tr_modelCard').scrollIntoView({behavior:'smooth',block:'nearest'});
}

function tr_renderModels(type, activeName) {
    const models=TR_VEHICLES[type]?.models||[];
    const area=document.getElementById('tr_modelArea');
    if (!models.length) { area.innerHTML=`<div class="cat-empty"><p>No models available.</p></div>`; return; }
    area.innerHTML=`<div class="cat-row">`+models.map(m=>{
        const isActive=activeName===m.name;
        const isDimmed=activeName&&activeName!==m.name;
        const cls=`cat-thumb-card${isActive?' active':''}${isDimmed?' dimmed':''}`;
        const thumbImg=m.images?.[0]||'';
        const thumb=thumbImg?`<img src="${thumbImg}" alt="${esc(m.name)}" onerror="this.parentNode.innerHTML=SVG_FB">`:SVG_FB;
        const price=trDistKm>0?Math.round(trDistKm*m.rate_per_km):null;
        const priceLabel=price?`&#8358;${price.toLocaleString()}`:`&#8358;${m.rate_per_km}/km`;
        return `<div class="${cls}" onclick="tr_selectModel('${esc(type)}','${escQ(m.name)}')">
            <div class="ctc-check"><svg viewBox="0 0 12 10" fill="none"><path d="M1 5l3 3 7-7" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg></div>
            <div class="ctc-img">${thumb}</div>
            <div class="ctc-name">${esc(m.name)}</div>
            <div class="ctc-price">${priceLabel}</div>
            <div class="ctc-pax">&#128100; ${esc(m.passengers||'—')}</div>
        </div>`;
    }).join('')+`</div>`;
}

function tr_selectModel(type, name) {
    const model=(TR_VEHICLES[type]?.models||[]).find(m=>m.name===name);
    if (!model) return;
    if (trSelModel?.name===name) { trSelModel=null; trFinalPrice=0; document.getElementById('tr_detailPanel').classList.remove('visible'); document.getElementById('tr_formCard').style.display='none'; tr_renderModels(type,null); return; }
    trSelModel=model;
    trFinalPrice=trDistKm>0?Math.round(trDistKm*model.rate_per_km):0;
    tr_renderModels(type, name);
    tr_buildDetail(type, model);
    document.getElementById('tr_formCard').style.display='none';
}

function tr_buildDetail(type, model) {
    const typeName=type==='van'?'Mini Van':type.charAt(0).toUpperCase()+type.slice(1);
    const images=model.images||[];
    const slides=buildSlides(images, model.name);
    const dots=buildDots(images.length||1, 'trCar');
    const feats=(model.features||[]).map(f=>`<li><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>${esc(f)}</li>`).join('');
    const price=trDistKm>0?Math.round(trDistKm*model.rate_per_km):null;
    trFinalPrice=price||0;
    const priceDisplay=price
        ?`&#8358;${price.toLocaleString()} <span>total for ${trDistKm} km</span>`
        :`&#8358;${model.rate_per_km}/km <span>calculate route to see total</span>`;
    const panel=document.getElementById('tr_detailPanel');
    panel.innerHTML=`<div class="detail-inner">
        ${buildCarousel(slides, dots, 'trCar')}
        <div class="detail-info">
            <div><div class="di-cat-name">${esc(model.name)}</div><div class="di-type-tag">${esc(typeName)}</div></div>
            <div>
                <div class="di-price">${priceDisplay}</div>
                <div class="di-pax"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>${esc(model.passengers||'—')} passengers</div>
            </div>
            <div><div class="feat-label">What's included</div><ul class="feat-list">${feats}</ul></div>
            <div class="di-actions">
                <button class="btn-change" onclick="tr_resetModel()"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>Change</button>
                <button class="btn-proceed-cat" onclick="tr_openForm()"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>Book This Car</button>
            </div>
        </div>
    </div>`;
    panel.classList.add('visible');
    document.getElementById('trCarTrack').dataset.idx='0';
    panel.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function tr_resetModel() { trSelModel=null; trFinalPrice=0; document.getElementById('tr_detailPanel').classList.remove('visible'); document.getElementById('tr_formCard').style.display='none'; if(trSelType) tr_renderModels(trSelType,null); }

function tr_openForm() {
    if (!trDistKm) { showErr('tr_alertA','Please calculate the route distance before booking.'); document.getElementById('tr_from').scrollIntoView({behavior:'smooth',block:'nearest'}); return; }
    const typeName=trSelType==='van'?'Mini Van':trSelType.charAt(0).toUpperCase()+trSelType.slice(1);
    document.getElementById('tr_chipTxt').textContent=typeName+' · '+trSelModel.name+' · '+trDistKm+' km · ₦'+trFinalPrice.toLocaleString();
    const paxInput=document.getElementById('tr_pax');
    const maxPax=PAX_LIMITS[trSelType]??20;
    paxInput.max=maxPax; paxInput.placeholder='e.g. 2 (max '+maxPax+')';
    const fc=document.getElementById('tr_formCard'); fc.style.display='block'; fc.scrollIntoView({behavior:'smooth',block:'start'});
}

/* Fix 2: Only fully reset when BOTH location fields are cleared — preserves autocomplete mid-selection */
function tr_clearDist() {
    trDistKm = 0;
    trFinalPrice = 0;
    document.getElementById('tr_distRes').classList.remove('show');
    const fromVal = document.getElementById('tr_from').value.trim();
    const toVal   = document.getElementById('tr_to').value.trim();
    if (!fromVal || !toVal) {
        document.getElementById('tr_typeCard').style.display = 'none';
        trSelType = null; trSelModel = null;
        document.getElementById('tr_modelCard').style.display = 'none';
        document.getElementById('tr_formCard').style.display = 'none';
    }
}

async function tr_calcDist() {
    const fromEl=document.getElementById('tr_from'), toEl=document.getElementById('tr_to');
    const alertEl=document.getElementById('tr_alertA');
    alertEl.style.display='none';
    if (!fromEl.value.trim()||!toEl.value.trim()) { showErr('tr_alertA','Please enter both pick-up and drop-off locations.'); return; }
    document.getElementById('tr_calcSt').classList.add('show');
    document.getElementById('tr_distRes').classList.remove('show');
    try {
        const r=await getDistance(fromEl, toEl);
        trDistKm=r.distance_km;
        document.getElementById('tr_distKmLbl').textContent=r.distance_text;
        document.getElementById('tr_driveLbl').textContent=r.drive_time;
        document.getElementById('tr_distRes').classList.add('show');
        /* Unlock vehicle type card */
        document.getElementById('tr_typeCard').style.display = 'block';
        /* Update model prices if a type was already selected */
        if (trSelType) { tr_renderModels(trSelType, trSelModel?.name||null); if(trSelModel) tr_buildDetail(trSelType, trSelModel); }
    } catch(e) { showErr('tr_alertA', e.message); }
    finally { document.getElementById('tr_calcSt').classList.remove('show'); }
}

function tr_pay(m) { trPayment=m; ['budpay','seerbit'].forEach(x=>document.getElementById('tr_p_'+x).classList.toggle('active',x===m)); }

/* ══════════════════════════════════════
   MODAL
══════════════════════════════════════ */
function ri(lbl, val, full, hi) {
    return `<div style="background:#f7f8ff;border:1px solid #e4e8f5;border-radius:8px;padding:8px 11px;${full?'grid-column:1/-1':''}">
        <div style="font-size:9.5px;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px;">${lbl}</div>
        <div style="font-size:${hi?'15px':'12.5px'};font-weight:${hi?'700':'600'};color:${hi?'#0d1883':'#1a1a1a'};word-break:break-word;">${val}</div>
    </div>`;
}

function rsec(icon, title, rows) {
    return `<div style="margin-bottom:13px;">
        <div style="font-size:9.5px;font-weight:700;color:#0d1883;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
            ${icon} ${title}
            <span style="flex:1;height:1px;background:#e0e4f8;display:block;"></span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;">${rows}</div>
    </div>`;
}

function fmtDate(v) {
    if (!v) return '—';
    const d = new Date(v + 'T00:00:00');
    return d.toLocaleDateString('en-NG', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
}

/* FIX 3: fmtTime now works for both select value (HH:MM) and time input */
function fmtTime(v) {
    if (!v) return '—';
    const parts = v.split(':');
    if (parts.length < 2) return v;
    const hr = parseInt(parts[0]);
    const m  = parts[1];
    return (hr % 12 || 12) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
}

function openModal(tab) {
    activeModal = tab;

    ['ch_alertA','ch_alertB','tr_alertA','tr_alertB'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    let html = '';

    if (tab === 'ch') {
        if (!chSelType || !chSelCat)  { showErr('ch_alertB', 'Please select a vehicle type and category.'); return; }
        if (!chSelModel)              { showErr('ch_alertB', 'Please select a car model.'); return; }
        if (!chDistKm)                { showErr('ch_alertA', 'Please calculate the route distance first.'); return; }
        const hrs = parseFloat(document.getElementById('ch_hours').value) || 0;
        if (!hrs)                     { showErr('ch_alertA', 'Please enter the rental duration in hours.'); return; }
        if (!chFinalPrice)            { showErr('ch_alertA', 'Price could not be calculated. Check your inputs.'); return; }

        const fields = [
            { id:'ch_name',  l:'Full Name' },
            { id:'ch_email', l:'Email Address' },
            { id:'ch_phone', l:'Phone Number' },
            { id:'ch_pax',   l:'Number of Passengers' },
            { id:'ch_date',  l:'Pick-up Date' },
            { id:'ch_time',  l:'Pick-up Time' },
        ];
        for (const f of fields) {
            if (!document.getElementById(f.id).value.trim()) {
                showErr('ch_alertB', 'Please fill in your ' + f.l + '.');
                return;
            }
        }

        const chPax = parseInt(document.getElementById('ch_pax').value);
        const chMaxPax = PAX_LIMITS[chSelType] ?? 20;
        if (chPax > chMaxPax) {
            showErr('ch_alertB', 'A ' + (chSelType === 'van' ? 'Mini Van' : chSelType) + ' can take a maximum of ' + chMaxPax + ' passengers.');
            return;
        }

        const typeName = chSelType === 'van' ? 'Mini Van' : chSelType.charAt(0).toUpperCase() + chSelType.slice(1);

        html =
            rsec('🚘', 'Vehicle',
                ri('Type', typeName) +
                ri('Category', esc(chSelCat.name)) +
                ri('Model', esc(chSelModel)) +
                ri('Distance', document.getElementById('ch_distKmLbl').textContent) +
                ri('Duration', hrs + ' hour' + (hrs !== 1 ? 's' : '')) +
                ri('Total Price', '₦' + chFinalPrice.toLocaleString(), true, true)
            ) +
            rsec('👤', 'Customer',
                ri('Full Name',   esc(document.getElementById('ch_name').value)) +
                ri('Email',       esc(document.getElementById('ch_email').value)) +
                ri('Phone',       esc(document.getElementById('ch_phone').value)) +
                ri('Passengers',  document.getElementById('ch_pax').value)
            ) +
            rsec('📍', 'Route',
                ri('Pick-up',  esc(document.getElementById('ch_pickup').value),  true) +
                ri('Drop-off', esc(document.getElementById('ch_dropoff').value), true) +
                ri('Date', fmtDate(document.getElementById('ch_date').value)) +
                ri('Time', fmtTime(document.getElementById('ch_time').value))
            ) +
            `<div style="margin-bottom:13px;">
                <div style="font-size:9.5px;font-weight:700;color:#0d1883;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    💳 Payment <span style="flex:1;height:1px;background:#e0e4f8;display:block;"></span>
                </div>
                <span style="display:inline-flex;align-items:center;gap:6px;background:#eef1ff;color:#0d1883;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#0d1883"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                    ${chPayment === 'budpay' ? 'BudPay' : 'SeerBit'}
                </span>
            </div>`;

    } else {
        /* FIX 4: was using undefined trSelVehicle — now correctly uses trSelModel and trSelType */
        if (!trSelType || !trSelModel) { showErr('tr_alertB', 'Please select a vehicle type and model first.'); return; }
        if (!trDistKm)                 { showErr('tr_alertA', 'Please calculate the route distance first.'); return; }

        const fields = [
            { id:'tr_name',  l:'Full Name' },
            { id:'tr_email', l:'Email Address' },
            { id:'tr_phone', l:'Phone Number' },
            { id:'tr_pax',   l:'Number of Passengers' },
            { id:'tr_date',  l:'Pick-up Date' },
            { id:'tr_time',  l:'Pick-up Time' },
        ];
        for (const f of fields) {
            if (!document.getElementById(f.id).value.trim()) {
                showErr('tr_alertB', 'Please fill in your ' + f.l + '.');
                return;
            }
        }

        const trPax    = parseInt(document.getElementById('tr_pax').value);
        const trMaxPax = PAX_LIMITS[trSelType] ?? 20;
        if (trPax > trMaxPax) {
            showErr('tr_alertB', (trSelType === 'van' ? 'Mini Van' : trSelType.charAt(0).toUpperCase() + trSelType.slice(1)) + ' can take a maximum of ' + trMaxPax + ' passengers.');
            return;
        }

        const typeName = trSelType === 'van' ? 'Mini Van' : trSelType.charAt(0).toUpperCase() + trSelType.slice(1);

        html =
            rsec('🚗', 'Transfer',
                ri('Type',    typeName) +
                ri('Vehicle', esc(trSelModel.name)) +
                ri('Distance', document.getElementById('tr_distKmLbl').textContent) +
                ri('Pick-up',  esc(document.getElementById('tr_from').value), true) +
                ri('Drop-off', esc(document.getElementById('tr_to').value),   true) +
                ri('Total Price', '₦' + trFinalPrice.toLocaleString(), true, true)
            ) +
            rsec('👤', 'Customer',
                ri('Full Name',    esc(document.getElementById('tr_name').value)) +
                ri('Email',        esc(document.getElementById('tr_email').value)) +
                ri('Phone',        esc(document.getElementById('tr_phone').value)) +
                ri('Passengers',   document.getElementById('tr_pax').value) +
                ri('Flight/Vessel',esc(document.getElementById('tr_flight').value) || 'N/A') +
                ri('Special Req.', esc(document.getElementById('tr_notes').value)  || 'None')
            ) +
            rsec('📅', 'Schedule',
                ri('Date', fmtDate(document.getElementById('tr_date').value)) +
                ri('Time', fmtTime(document.getElementById('tr_time').value))
            ) +
            `<div style="margin-bottom:13px;">
                <div style="font-size:9.5px;font-weight:700;color:#0d1883;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    💳 Payment <span style="flex:1;height:1px;background:#e0e4f8;display:block;"></span>
                </div>
                <span style="display:inline-flex;align-items:center;gap:6px;background:#eef1ff;color:#0d1883;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#0d1883"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                    ${trPayment === 'budpay' ? 'BudPay' : 'SeerBit'}
                </span>
            </div>`;
    }

    /* Remove any previous modal overlay completely */
    const old = document.getElementById('_gtOverlay');
    if (old) old.remove();

    /* Build the entire overlay as one div appended straight to body.
       This guarantees it is never inside any transformed/filtered ancestor. */
    const overlay = document.createElement('div');
    overlay.id = '_gtOverlay';
    overlay.style.cssText = [
        'position:fixed','top:0','left:0','width:100%','height:100%',
        'background:rgba(10,12,40,0.72)',
        'z-index:2147483647',
        'display:flex','align-items:center','justify-content:center',
        'padding:20px','box-sizing:border-box',
        'font-family:DM Sans,sans-serif'
    ].join(';');
    overlay.onclick = function(e){ if(e.target===overlay) closeModal(); };

    overlay.innerHTML = `
    <div style="background:#fff;border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;
                box-shadow:0 24px 60px rgba(13,24,131,.3);animation:gtModalIn .22s ease both;position:relative;">
        <style>@keyframes gtModalIn{from{opacity:0;transform:translateY(18px) scale(.96)}to{opacity:1;transform:none}}</style>
        <div style="background:linear-gradient(135deg,#0d1883,#2d39b6);padding:22px 22px 18px;border-radius:20px 20px 0 0;position:relative;">
            <button onclick="closeModal()" style="position:absolute;top:13px;right:14px;width:28px;height:28px;background:rgba(255,255,255,.18);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;line-height:1;">✕</button>
            <div style="width:36px;height:36px;background:rgba(255,255,255,.18);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;font-size:20px;">✓</div>
            <h2 style="font-family:'Playfair Display',serif;font-size:18px;color:#fff;margin:0 0 3px;">Review Your Booking</h2>
            <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0;">Confirm all details before payment</p>
        </div>
        <div style="padding:16px 20px 4px;">
            <div style="background:#fffbea;border:1px solid #f0d96a;border-radius:9px;padding:9px 12px;font-size:11.5px;color:#7a5c00;margin-bottom:14px;display:flex;gap:7px;align-items:flex-start;">
                <span style="font-size:15px;flex-shrink:0;margin-top:-1px;">⚠</span>
                Double-check your details. Once payment starts, changes may not be possible.
            </div>
            <div id="_gtContent">${html}</div>
        </div>
        <div style="display:flex;gap:10px;padding:14px 20px 22px;">
            <button onclick="closeModal()" style="flex:1;padding:11px;background:#f0f3ff;color:#0d1883;border:1.5px solid #c5cef8;border-radius:10px;font-size:12.5px;font-weight:600;font-family:DM Sans,sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;">
                ✏ Edit Details
            </button>
            <button id="confirmBtn" onclick="submitBooking()" style="flex:1.6;padding:11px;background:linear-gradient(135deg,#0d1883,#2d39b6);color:white;border:none;border-radius:10px;font-size:12.5px;font-weight:600;font-family:DM Sans,sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;">
                🔒 Confirm &amp; Pay
            </button>
        </div>
    </div>`;

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const ov = document.getElementById('_gtOverlay');
    if (ov) ov.remove();
    document.body.style.overflow = '';
}

/* ══ SUBMIT ══ */
function submitBooking() {
    const btn=document.getElementById('confirmBtn');
    btn.disabled=true; btn.innerHTML='Processing...';
    if (activeModal==='ch') {
        document.getElementById('h_ch_type').value    = chSelType;
        document.getElementById('h_ch_cat').value     = chSelCat.name;
        document.getElementById('h_ch_model').value   = chSelModel;
        document.getElementById('h_ch_price').value   = chFinalPrice;
        document.getElementById('h_ch_dist').value    = chDistKm;
        document.getElementById('h_ch_hours').value   = document.getElementById('ch_hours').value;
        document.getElementById('h_ch_name').value    = document.getElementById('ch_name').value;
        document.getElementById('h_ch_email').value   = document.getElementById('ch_email').value;
        document.getElementById('h_ch_phone').value   = document.getElementById('ch_phone').value;
        document.getElementById('h_ch_pax').value     = document.getElementById('ch_pax').value;
        document.getElementById('h_ch_pickup').value  = document.getElementById('ch_pickup').value;
        document.getElementById('h_ch_dropoff').value = document.getElementById('ch_dropoff').value;
        document.getElementById('h_ch_date').value    = document.getElementById('ch_date').value;
        document.getElementById('h_ch_time').value    = document.getElementById('ch_time').value;
        document.getElementById('h_ch_payment').value = chPayment;
        document.getElementById('ch_form').submit();
    } else {
        document.getElementById('h_tr_type').value    = trSelType;
        document.getElementById('h_tr_vname').value   = trSelModel.name;
        document.getElementById('h_tr_price').value   = trFinalPrice;
        document.getElementById('h_tr_dist').value    = trDistKm;
        document.getElementById('h_tr_from').value    = document.getElementById('tr_from').value;
        document.getElementById('h_tr_to').value      = document.getElementById('tr_to').value;
        document.getElementById('h_tr_name').value    = document.getElementById('tr_name').value;
        document.getElementById('h_tr_email').value   = document.getElementById('tr_email').value;
        document.getElementById('h_tr_phone').value   = document.getElementById('tr_phone').value;
        document.getElementById('h_tr_pax').value     = document.getElementById('tr_pax').value;
        document.getElementById('h_tr_flight').value  = document.getElementById('tr_flight').value;
        document.getElementById('h_tr_notes').value   = document.getElementById('tr_notes').value;
        document.getElementById('h_tr_date').value    = document.getElementById('tr_date').value;
        document.getElementById('h_tr_time').value    = document.getElementById('tr_time').value;
        document.getElementById('h_tr_payment').value = trPayment;
        document.getElementById('tr_form').submit();
    }
}

/* ══ TIME PICKERS — populate both car hire and transfer selects ══ */
function generateTimeOptions() {
    ['ch_time', 'tr_time'].forEach(function(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;
        select.innerHTML = '<option value="">Select time</option>';
        for (let h = 0; h <= 23; h++) {
            for (let m = 0; m < 60; m += 30) {
                const val   = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0');
                const label = (h % 12 || 12) + ':' + String(m).padStart(2,'0') + ' ' + (h >= 12 ? 'PM' : 'AM');
                const o = document.createElement('option');
                o.value = val; o.textContent = label;
                select.appendChild(o);
            }
        }
    });
}
document.addEventListener('DOMContentLoaded', generateTimeOptions);

/* ══ GOOGLE PLACES AUTOCOMPLETE ══ */
window.initAutocomplete = function () {
    const chPickup  = document.getElementById('ch_pickup');
    const chDropoff = document.getElementById('ch_dropoff');
    const trFrom    = document.getElementById('tr_from');
    const trTo      = document.getElementById('tr_to');

    const options = { componentRestrictions: { country: "ng" } };

    /* Car hire — store lat/lng on place_changed */
    [chPickup, chDropoff].forEach(input => {
        if (!input) return;
        const ac = new google.maps.places.Autocomplete(input, options);
        ac.addListener("place_changed", () => {
            const place = ac.getPlace();
            if (!place.geometry) return;
            input.dataset.lat = place.geometry.location.lat();
            input.dataset.lng = place.geometry.location.lng();
        });
    });

    /* Transfer — store lat/lng on place_changed, clear dist on manual typing */
    [trFrom, trTo].forEach(input => {
        if (!input) return;
        const ac = new google.maps.places.Autocomplete(input, options);
        ac.addListener("place_changed", () => {
            const place = ac.getPlace();
            if (!place.geometry) return;
            input.dataset.lat = place.geometry.location.lat();
            input.dataset.lng = place.geometry.location.lng();
        });
        /* Attach clearDist AFTER autocomplete is set up so it doesn't fire during dropdown selection */
        input.addEventListener('input', tr_clearDist);
    });
};
</script>
</div>
@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initAutocomplete" async defer></script>
@endpush