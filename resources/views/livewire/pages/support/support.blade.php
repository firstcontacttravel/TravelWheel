<div class="support-root">
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
            <a class="active" href="{{ route('air.support') }}" aria-current="page"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
            <a href="{{ route('air.carhire') }}"><img src="{{ asset('assets/Car Hire 70.png') }}" alt=""><span>Car Hire</span></a>  
        </nav>

        <div class="vw-card">
            <h3 class="support-heading">Support Products</h3>
            <p class="support-subheading">Pick a service below to select options and fill in your details.</p>

            <div class="support-cards-grid">
                <a href="{{ route('air.support.flight-assist.form') }}" class="support-product-card">
                    <div class="spc-icon">✈️</div>
                    <h4>Flight Assist</h4>
                    <p>Date changes &amp; rerouting help for an existing booking.</p>
                    <span class="spc-price">₦25,000</span>
                </a>
                {{--
                <a href="{{ route('air.support.extra-luggage.form') }}" class="support-product-card">
                    <div class="spc-icon">🧳</div>
                    <h4>Extra Luggage</h4>
                    <p>Add extra luggage allowance to your flight.</p>
                    <span class="spc-price">₦25,000</span>
                </a>

                <a href="{{ route('air.support.visa-confirmation.form') }}" class="support-product-card">
                    <div class="spc-icon">📄</div>
                    <h4>Visa Confirmation</h4>
                    <p>Get a visa confirmation letter processed.</p>
                    <span class="spc-price">₦50,000</span>
                </a>--}}

                <a href="{{ route('air.support.yellow-card.form') }}" class="support-product-card">
                    <div class="spc-icon">💛</div>
                    <h4>Yellow Card Assistance</h4>
                    <p>Standard or fast-track Yellow Card application.</p>
                    <span class="spc-price">From ₦30,000</span>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
    .support-root .vw-card { padding: 18px 22px 20px; }
    .support-heading { text-align: center; color: rgba(13, 24, 131, 1); font-weight: 700; font-size: 16px; margin-bottom: 4px; }
    .support-subheading { text-align: center; color: #667085; font-size: 12.5px; margin-bottom: 16px; }
    .support-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
    .support-product-card {
        display: block;
        background: #fff;
        border: 1px solid #e6e8ee;
        border-radius: 12px;
        padding: 14px 12px;
        text-align: center;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 14px rgba(16,24,40,.06);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .support-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(13,24,131,.12);
        border-color: rgba(13, 24, 131, .25);
        color: inherit;
    }
    .support-product-card .spc-icon {
        width: 36px; height: 36px; margin: 0 auto 8px;
        border-radius: 50%;
        background: #eef1ff;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px;
    }
    .support-product-card h4 { color: rgba(13, 24, 131, 1); font-weight: 700; font-size: 13px; margin-bottom: 3px; }
    .support-product-card p { color: #667085; font-size: 10.5px; line-height: 1.4; margin-bottom: 6px; }
    .support-product-card .spc-price { display: inline-block; color: #0d1883; font-weight: 700; font-size: 11px; background: #eef1ff; padding: 3px 10px; border-radius: 999px; }
</style>
</div>
