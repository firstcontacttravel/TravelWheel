@php($signedIn = $companyUser ?? null)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Invitation and reset links carry secrets in the URL; never send them on as a referrer. --}}
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex">
    <title>@yield('title') · TravelWheel Corporate</title>
    <style>
        :root {
            --biz-brand: {{ config('brand.colors.brand') }};
            --biz-brand-dark: {{ config('brand.colors.brand_dark') }};
            --biz-brand-bg: {{ config('brand.colors.brand_bg') }};
            --biz-ink: {{ config('brand.colors.ink') }};
            --biz-text: {{ config('brand.colors.text') }};
            --biz-muted: {{ config('brand.colors.muted') }};
            --biz-line: {{ config('brand.colors.line') }};
            --biz-surface: {{ config('brand.colors.surface') }};
            --biz-danger: {{ config('brand.colors.danger') }};
            --biz-danger-bg: {{ config('brand.colors.danger_bg') }};
            --biz-success: {{ config('brand.colors.success') }};
            --biz-success-bg: {{ config('brand.colors.success_bg') }};
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--biz-surface); color: var(--biz-text); font: 15px/1.55 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        a { color: var(--biz-brand); }
        .biz-bar { background: #fff; border-bottom: 1px solid var(--biz-line); }
        .biz-bar-in { max-width: 1040px; margin: 0 auto; padding: 12px 20px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px 24px; }
        .biz-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--biz-muted); font-size: 13px; font-weight: 600; }
        .biz-brand img { height: 22px; width: auto; display: block; }
        .biz-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 18px; margin-left: auto; }
        .biz-nav a, .biz-link { color: var(--biz-text); text-decoration: none; font: inherit; font-weight: 600; font-size: 14px; background: none; border: 0; padding: 4px 0; cursor: pointer; }
        .biz-nav a:hover, .biz-link:hover, .biz-nav a[aria-current] { color: var(--biz-brand); }
        .biz-main { max-width: 1040px; margin: 0 auto; padding: 32px 20px 56px; }
        .biz-narrow { max-width: 460px; margin: 0 auto; }
        .biz-card { background: #fff; border: 1px solid var(--biz-line); border-radius: 12px; padding: 24px; margin-bottom: 18px; }
        h1 { color: var(--biz-ink); font-size: 24px; line-height: 1.25; margin: 0 0 6px; }
        h2 { color: var(--biz-ink); font-size: 17px; margin: 0 0 4px; }
        .biz-sub { color: var(--biz-muted); margin: 0 0 20px; }
        .biz-field { margin-bottom: 16px; }
        label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 5px; color: var(--biz-ink); }
        .biz-input, select { width: 100%; min-height: 44px; padding: 9px 12px; border: 1px solid #d0d5dd; border-radius: 8px; font: inherit; color: var(--biz-ink); background: #fff; }
        .biz-input:focus, select:focus { outline: 2px solid var(--biz-brand); outline-offset: 1px; border-color: var(--biz-brand); }
        .biz-help { color: var(--biz-muted); font-size: 13px; margin-top: 4px; }
        .biz-error { color: var(--biz-danger); font-size: 13px; margin-top: 4px; }
        .biz-btn { display: inline-flex; justify-content: center; align-items: center; min-height: 44px; padding: 9px 20px; border: 1px solid var(--biz-brand); border-radius: 8px; background: var(--biz-brand); color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .biz-btn:hover { background: var(--biz-brand-dark); }
        .biz-btn-full { width: 100%; }
        .biz-btn-light { background: #fff; color: var(--biz-brand); }
        .biz-btn-light:hover { background: var(--biz-brand-bg); }
        .biz-btn-small { min-height: 36px; padding: 5px 12px; font-size: 13px; }
        .biz-btn-danger { background: #fff; color: var(--biz-danger); border-color: #fda29b; }
        .biz-btn-danger:hover { background: var(--biz-danger-bg); }
        .biz-alert { border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 14px; }
        .biz-alert-ok { background: var(--biz-success-bg); color: var(--biz-success); }
        .biz-alert-bad { background: var(--biz-danger-bg); color: var(--biz-danger); }
        .biz-foot { margin: 16px 0 0; text-align: center; font-size: 14px; color: var(--biz-muted); }
        .biz-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .biz-table th { text-align: left; color: var(--biz-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; padding: 0 12px 8px 0; border-bottom: 1px solid var(--biz-line); }
        .biz-table td { padding: 12px 12px 12px 0; border-bottom: 1px solid var(--biz-line); vertical-align: middle; }
        .biz-table tr:last-child td { border-bottom: 0; }
        .biz-actions { display: flex; flex-wrap: wrap; gap: 6px; }
        .biz-actions form { margin: 0; }
        .biz-actions select { min-height: 36px; width: auto; padding: 4px 8px; font-size: 13px; }
        .biz-badge { display: inline-block; padding: 2px 9px; border-radius: 20px; background: var(--biz-brand-bg); color: var(--biz-brand); font-size: 12px; font-weight: 600; }
        .biz-badge-off { background: var(--biz-danger-bg); color: var(--biz-danger); }
        .biz-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 14px; align-items: start; }
        .biz-trap { display: none !important; }
        .biz-check { display: flex; align-items: flex-start; gap: 10px; font-weight: 400; }
        .biz-check input { width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0; }
        @media (max-width: 720px) {
            .biz-grid { grid-template-columns: 1fr; }
            .biz-nav { margin-left: 0; width: 100%; }
            .biz-table thead { display: none; }
            .biz-table, .biz-table tbody, .biz-table tr, .biz-table td { display: block; width: 100%; }
            .biz-table tr { padding: 12px 0; border-bottom: 1px solid var(--biz-line); }
            .biz-table tr:last-child { border-bottom: 0; }
            .biz-table td { padding: 3px 0; border: 0; }
        }
    </style>
</head>
<body>
    <header class="biz-bar">
        <div class="biz-bar-in">
            <a class="biz-brand" href="{{ $signedIn ? route('business.dashboard') : route('business.login') }}">
                <img src="{{ asset('assets/travelwheel.png') }}" alt="TravelWheel">
                <span>Corporate</span>
            </a>
            @if ($signedIn)
                <nav class="biz-nav" aria-label="Corporate">
                    <a href="{{ route('business.dashboard') }}" @if (request()->routeIs('business.dashboard')) aria-current="page" @endif>Dashboard</a>
                    <a href="{{ route('business.bookings.index') }}" @if (request()->routeIs('business.bookings.*')) aria-current="page" @endif>Bookings</a>
                    <a href="{{ route('business.invoices.index') }}" @if (request()->routeIs('business.invoices.*')) aria-current="page" @endif>Invoices</a>
                    <a href="{{ route('business.travellers.index') }}" @if (request()->routeIs('business.travellers.*')) aria-current="page" @endif>Travellers</a>
                    @if ($signedIn->isCompanyAdmin())
                        <a href="{{ route('business.wallet.index') }}" @if (request()->routeIs('business.wallet.*')) aria-current="page" @endif>Wallet</a>
                        <a href="{{ route('business.team.index') }}" @if (request()->routeIs('business.team.*')) aria-current="page" @endif>Team</a>
                    @endif
                    <a href="{{ route('business.account') }}" @if (request()->routeIs('business.account')) aria-current="page" @endif>Account</a>
                    <form method="POST" action="{{ route('business.logout') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="biz-link">Sign out</button>
                    </form>
                </nav>
            @endif
        </div>
    </header>

    <main class="biz-main">
        @yield('content')
    </main>
</body>
</html>
