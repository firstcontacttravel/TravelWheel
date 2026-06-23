<style>
    .tw-deals{background:var(--tw-surface,#fff);padding:18px 20px 48px}
    .tw-deals-panel{max-width:1561px;margin:0 auto;background:var(--tw-surface,#fff);border:1px solid var(--tw-line,#eef0f4);border-radius:var(--tw-radius-panel,22px);padding:36px 42px 38px;box-shadow:var(--tw-shadow-md,0 12px 34px rgba(17,24,39,.05))}
    .tw-deals-title{margin:0 0 22px;color:var(--tw-brand,#303191)!important;font-family:var(--tw-font-sans,var(--font-primary));font-size:var(--tw-text-3xl);font-weight:800;line-height:var(--tw-leading-tight,1.15)}
    .tw-deal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
    .tw-deal-card{background:var(--tw-surface,#fff);border:1px solid var(--tw-line,#eef0f4);border-radius:var(--tw-radius-lg,14px);overflow:hidden;min-height:342px;padding:14px 14px 18px;box-shadow:var(--tw-shadow-sm,0 8px 24px rgba(17,24,39,.05));transition:transform .18s,box-shadow .18s,border-color .18s}
    .tw-deal-card:hover{transform:translateY(-2px);box-shadow:0 14px 34px rgba(17,24,39,.08);border-color:#e1e5ee}
    .tw-deal-image{height:210px;border-radius:10px;overflow:hidden;background:#f3f4f6}
    .tw-deal-image img{width:100%;height:100%;object-fit:cover;display:block}
    .tw-deal-kind{margin:14px 0 4px;color:var(--tw-accent,#009933)!important;font-size:var(--tw-text-sm);font-weight:600;line-height:1}
    .tw-deal-name{margin:0;color:var(--tw-brand,#303191)!important;font-family:var(--tw-font-sans,var(--font-primary));font-size:var(--tw-text-xl);font-weight:700;line-height:var(--tw-leading-snug,1.3)}
    .tw-deal-name.hotel{font-size:var(--tw-text-lg)}
    .tw-deal-meta{margin-top:8px;display:flex;align-items:baseline;gap:6px;color:var(--tw-muted,#676767)!important;font-size:var(--tw-text-sm)}
    .tw-deal-meta span,.tw-deal-meta strong{color:inherit}
    .tw-deal-meta strong{color:var(--tw-ink,#000)!important;font-size:var(--tw-text-2xl);font-weight:800;line-height:1}
    .tw-deal-meta em{font-style:normal;color:#676767!important;margin-left:auto}
    @media(max-width:1050px){.tw-deals-panel{padding:30px 24px}.tw-deal-grid{gap:18px}}
    @media(max-width:760px){.tw-deal-grid{grid-template-columns:1fr}.tw-deals-panel{border-radius:18px;padding:24px 16px}.tw-deal-card{min-height:auto}}
</style>

<section class="tw-deals" aria-label="Deals">
    <div class="tw-deals-panel">
        <h2 class="tw-deals-title">Deals</h2>
        <div class="tw-deal-grid">
            <article class="tw-deal-card">
                <div class="tw-deal-image">
                    <img src="{{ asset('assets/figma/landing/deal-heathrow.png') }}" alt="London destination">
                </div>
                <div class="tw-deal-kind">Flight</div>
                <h3 class="tw-deal-name">Lagos - Heathrow</h3>
                <div class="tw-deal-meta"><span>From</span><strong>N2,576,920</strong><em>United Airways</em></div>
            </article>

            <article class="tw-deal-card">
                <div class="tw-deal-image">
                    <img src="{{ asset('assets/figma/landing/deal-germany.png') }}" alt="Germany destination">
                </div>
                <div class="tw-deal-kind">Flight</div>
                <h3 class="tw-deal-name">Lagos - Garmany</h3>
                <div class="tw-deal-meta"><span>From</span><strong>N1,976,920</strong><em>Arik Airways</em></div>
            </article>

            <article class="tw-deal-card">
                <div class="tw-deal-image">
                    <img src="{{ asset('assets/figma/landing/deal-hotel.png') }}" alt="Moroccan House Marrakech">
                </div>
                <div class="tw-deal-kind">Hotel</div>
                <h3 class="tw-deal-name hotel">Moroccan House Marrakech, MA</h3>
                <div class="tw-deal-meta"><span>From</span><strong>N2,576,920</strong><em>/Night</em></div>
            </article>
        </div>
    </div>
</section>
