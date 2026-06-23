<style>
    .tw-trust-strip{background:var(--tw-surface,#fff);padding:28px 20px;border-bottom:1px solid var(--tw-line,#eef0f4)}
    .tw-trust-inner{max-width:1060px;margin:0 auto;display:grid;grid-template-columns:repeat(4,auto);justify-content:center;gap:42px}
    .tw-trust-item{display:flex;align-items:center;gap:10px;color:var(--tw-text,#1f2937)!important;font-family:var(--tw-font-sans,var(--font-primary));font-size:var(--tw-text-md);font-weight:650;white-space:nowrap}
    .tw-trust-icon{width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:#f4f7f5;color:var(--tw-accent,#009933);flex:0 0 22px}
    .tw-trust-icon svg{width:15px;height:15px;display:block;stroke:currentColor}
    .tw-visa-banner{padding:52px 20px 34px;background:var(--tw-surface,#fff)}
    .tw-visa-card{max-width:1356px;height:225px;margin:0 auto;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .tw-visa-card img{width:100%;height:100%;object-fit:contain;display:block}
    @media(max-width:900px){.tw-trust-inner{grid-template-columns:repeat(2,auto);gap:18px 32px}.tw-visa-card{height:auto}}
    @media(max-width:560px){.tw-trust-inner{grid-template-columns:1fr;justify-content:center;justify-items:center;text-align:center}.tw-trust-item{justify-content:center}.tw-visa-banner{padding-top:36px}}
</style>

<section class="tw-trust-strip" aria-label="TravelWheel assurances">
    <div class="tw-trust-inner">
        <div class="tw-trust-item">
            <span class="tw-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
            <span>Best price guarantee</span>
        </div>
        <div class="tw-trust-item">
            <span class="tw-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v5c0 4.4 2.9 8.5 7 10 4.1-1.5 7-5.6 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-5"/></svg></span>
            <span>Secure booking</span>
        </div>
        <div class="tw-trust-item">
            <span class="tw-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/><path d="M5 4 3 6"/><path d="m19 4 2 2"/></svg></span>
            <span>Instant confirmation</span>
        </div>
        <div class="tw-trust-item">
            <span class="tw-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13a8 8 0 0 1 16 0"/><path d="M5 13h3v5H5a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2Z"/><path d="M19 13h-3v5h3a2 2 0 0 0 2-2v-1a2 2 0 0 0-2-2Z"/><path d="M16 18c0 1.7-1.8 3-4 3"/></svg></span>
            <span>24/7 support</span>
        </div>
    </div>
</section>

<section class="tw-visa-banner" aria-label="Nigeria e-Visa">
    <div class="tw-visa-card">
        <img src="{{ asset('assets/figma/landing/evisa-banner.png') }}" alt="Nigeria e-Visa">
    </div>
</section>
