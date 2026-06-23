<style>
    .tw-partners{background:var(--tw-surface,#fff);padding:32px 20px 20px}
    .tw-partners-panel{max-width:1561px;margin:0 auto;background:var(--tw-surface,#fff);border:1px solid var(--tw-line,#eef0f4);border-radius:var(--tw-radius-panel,22px);padding:34px 0 38px;overflow:hidden;box-shadow:var(--tw-shadow-md,0 12px 34px rgba(17,24,39,.05))}
    .tw-partners-title{margin:0 0 26px;text-align:center;color:var(--tw-brand,#303191)!important;font-family:var(--tw-font-sans,var(--font-primary));font-size:var(--tw-text-3xl);font-weight:800;line-height:var(--tw-leading-tight,1.15)}
    .tw-partner-slider{height:118px;position:relative;display:grid;place-items:center;overflow:hidden}
    .tw-partner-slider::before,.tw-partner-slider::after{content:'';position:absolute;top:0;width:13%;height:100%;z-index:2;pointer-events:none}
    .tw-partner-slider::before{left:0;background:linear-gradient(90deg,#fff 0%,rgba(255,255,255,0) 100%)}
    .tw-partner-slider::after{right:0;background:linear-gradient(270deg,#fff 0%,rgba(255,255,255,0) 100%)}
    .tw-partner-track{display:flex;gap:24px;width:max-content;animation:twPartnerScroll 26s linear infinite}
    .tw-partner-logo{width:176px;height:78px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--tw-line,#eef0f4);border-radius:var(--tw-radius-lg,14px);padding:16px;box-shadow:var(--tw-shadow-sm,0 8px 22px rgba(48,49,145,.08))}
    .tw-partner-logo img{max-width:138px;max-height:52px;object-fit:contain;filter:saturate(.98)}
    @keyframes twPartnerScroll{0%{transform:translateX(0)}100%{transform:translateX(calc(-204px * 8))}}
    @media(max-width:760px){.tw-partners-panel{border-radius:18px;padding:26px 0}.tw-partner-logo{width:150px}.tw-partner-track{gap:16px}@keyframes twPartnerScroll{0%{transform:translateX(0)}100%{transform:translateX(calc(-166px * 8))}}}
</style>

@php
    $partners = [
        'First-con.png',
        'TL.png',
        'HOB.png',
        'natureborn.png',
        'upstarts.png',
        'Rebate.jpg',
        'airspace.png',
        'allianz.png',
        'oasis.png',
        'loungeone.png',
        'soula.png',
        'Spiffy.png',
    ];
@endphp

<section class="tw-partners" aria-label="Strategic partners">
    <div class="tw-partners-panel">
        <h2 class="tw-partners-title">Strategic Partners</h2>
        <div class="tw-partner-slider">
            <div class="tw-partner-track">
                @foreach(array_merge($partners, $partners) as $partner)
                    <div class="tw-partner-logo">
                        <img src="{{ asset('assets/img/' . $partner) }}" alt="">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
