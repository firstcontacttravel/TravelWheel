
<div>{{-- Single Livewire root element --}}
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
    /*
     * ── Booking page foundation and Traveller info ──
     * These classes had accumulated five stacked, unconditional redefinitions
     * (the original block plus "Phase 1" through "Phase 4"), including two
     * competing :root declarations — the first of which set --blue to a
     * generic #1d4ed8 and the body font to Plus Jakarta Sans, neither of
     * which belongs to this site. One canonical implementation replaces all.
     */
    :root {
        --navy:     var(--tw-brand, #303191);
        --blue:     var(--tw-brand, #303191);
        --blue-dk:  var(--tw-brand-hover, #252675);
        --blue-lt:  #f1f1ff;
        --blue-md:  #d7d8ff;
        --green:    var(--tw-accent, #00a859);
        --green-dk: #04713f;
        --green-lt: #e9f9f0;
        --amber:    #b45309;
        --amber-lt: #fffaf0;
        --red:      #b42318;
        --red-lt:   #fef3f2;
        --badgeOut: var(--tw-brand, #303191);
        --gray-50:  var(--tw-surface-soft, #f8f9fc);
        --gray-100: #f2f4f7;
        --gray-200: #e6e8ee;
        --gray-300: #d5d9e2;
        --gray-400: #98a2b3;
        --gray-500: #667085;
        --gray-600: #516079;
        --gray-700: #344054;
        --gray-900: #111827;
        --radius:   12px;
        --shadow:   0 1px 2px rgba(16,24,40,.05);
        --shadow-md:0 10px 28px rgba(16,24,40,.08);
        --font:     var(--tw-font-sans, "Open Sans", sans-serif);
        --mono:     "DM Mono", monospace;
    }
    /* margin-top clears the fixed site header; the 640px query below drops it. */
    body {
        font-family: var(--font); color: var(--gray-900);
        font-size: 14px; line-height: 1.5; margin-top: 120px;
        background: linear-gradient(180deg, #fff 0%, var(--gray-50) 42%, #fff 100%);
    }

    .bk-wrap { max-width: 1216px; margin: 0 auto; padding: 24px 16px 72px; }
    .bk-page { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; align-items: start; }
    .bk-main, .bk-rail { min-width: 0; }
    .bk-main { display: flex; flex-direction: column; gap: 12px; }
    .bk-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 18px; }

    .bk-crumb { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--gray-400); margin-bottom: 16px; flex-wrap: wrap; }
    .bk-crumb a { color: var(--gray-500); text-decoration: none; font-weight: 500; }
    .bk-crumb a:hover { color: var(--blue); }
    .bk-crumb-sep { color: var(--gray-300); }

    /*
     * ── Stepper ──
     * Three equal cells over a progress rail that actually fills as the
     * booking advances, so how far along you are is readable at a glance
     * rather than inferred from which dot happens to be coloured in.
     */
    .bk-steps {
        position: relative; display: grid; grid-template-columns: repeat(3, minmax(0,1fr));
        gap: 4px; padding: 14px 18px 16px;
        background: #fff; border: 1px solid var(--gray-200);
        border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden;
    }
    .bk-steps::before { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: var(--gray-100); }
    .bk-steps::after {
        content: ""; position: absolute; left: 0; bottom: 0; height: 3px;
        width: var(--bk-progress, 0%);
        background: linear-gradient(90deg, var(--blue) 0%, var(--green) 100%);
        transition: width .4s ease;
    }
    .bk-step { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .bk-step-dot {
        width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 12.5px; font-weight: 700; font-family: var(--mono);
        background: #fff; color: var(--gray-400); border: 1.5px solid var(--gray-200);
        transition: background .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease;
    }
    .bk-step-dot.active { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 0 0 4px rgba(48,49,145,.12); }
    .bk-step-dot.done { background: var(--green-lt); border-color: #9ae0bd; color: var(--green-dk); font-size: 0; }
    .bk-step-dot.done::after {
        content: ""; width: 14px; height: 14px; background: currentColor;
        mask: url("{{ asset('images/flight-icons/check.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/flight-icons/check.svg') }}") center / contain no-repeat;
    }
    .bk-step-txt { min-width: 0; }
    .bk-step-label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-500); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bk-step-label.active { color: var(--gray-900); font-weight: 700; }
    .bk-step-sub { display: block; font-size: 11.5px; color: var(--gray-400); line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bk-connector { display: none; }

    /* ── Accordion card ── */
    .bk-acc { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
    .bk-acc-head {
        display: flex; align-items: center; gap: 12px; padding: 15px 18px;
        cursor: pointer; user-select: none; border-bottom: 1px solid transparent;
        transition: background .15s ease;
    }
    .bk-acc-head:hover { background: #fbfcfe; }
    .bk-acc-head.open { border-bottom-color: var(--gray-100); }
    .bk-acc-icon { width: 34px; height: 34px; border-radius: 9px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-acc-title { font-size: 14px; font-weight: 700; color: var(--gray-900); line-height: 1.35; }
    .bk-acc-sub { font-size: 12px; color: var(--gray-500); margin-top: 1px; }
    .bk-acc-chevron { margin-left: auto; color: var(--gray-400); transition: transform .25s ease; flex-shrink: 0; }
    .bk-acc-chevron.open { transform: rotate(180deg); }
    .bk-acc-body { padding: 18px; }

    /*
     * ── Traveller card ──
     * One card per traveller. The head carries who it is and whether it is
     * finished; the body groups fields by what the traveller is copying from
     * — themselves, then their passport — instead of running eleven inputs
     * together in one undifferentiated grid.
     */
    .bk-pax-card { border: 1px solid var(--gray-200); border-radius: 10px; overflow: hidden; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
    .bk-pax-card + .bk-pax-card { margin-top: 10px; }
    .bk-pax-card:hover { border-color: var(--gray-300); }
    .bk-pax-card.is-open { border-color: var(--blue-md); box-shadow: var(--shadow); }
    .bk-pax-card-head { display: flex; align-items: center; gap: 11px; padding: 12px 14px; cursor: pointer; user-select: none; transition: background .15s ease; }
    .bk-pax-card-head:hover { background: var(--gray-50); }
    .bk-pax-index {
        flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gray-50); border: 1px solid var(--gray-200);
        font-family: var(--mono); font-size: 12px; font-weight: 500; color: var(--gray-600);
    }
    .bk-pax-card.is-open .bk-pax-index { background: var(--blue-lt); border-color: var(--blue-md); color: var(--blue); }
    .bk-pax-who { min-width: 0; flex: 1; }
    .bk-pax-num-lbl { display: block; font-size: 13px; font-weight: 600; color: var(--gray-900); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bk-pax-meta { display: flex; align-items: center; gap: 7px; margin-top: 2px; }
    .bk-pax-badge { flex-shrink: 0; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; line-height: 1.45; }
    .bk-pax-badge.adt { background: var(--blue-lt); color: var(--blue); }
    .bk-pax-badge.chd { background: #fff4e5; color: var(--amber); }
    .bk-pax-badge.inf { background: var(--green-lt); color: var(--green-dk); }
    .bk-primary-chip { flex-shrink: 0; padding: 2px 8px; border-radius: 6px; background: var(--gray-50); border: 1px solid var(--gray-200); color: var(--gray-600); font-size: 11px; font-weight: 600; line-height: 1.45; }
    .bk-pax-state { flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; }
    .bk-pax-complete { color: var(--green-dk); }
    .bk-pax-progress { color: var(--gray-500); font-weight: 500; }
    .bk-pax-chevron { flex-shrink: 0; color: var(--gray-400); transition: transform .2s ease; }
    .bk-pax-age { font-size: 11.5px; color: var(--gray-400); }
    .bk-tick { display: inline-block; width: 13px; height: 13px; background: currentColor;
        mask: url("{{ asset('images/flight-icons/check.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/flight-icons/check.svg') }}") center / contain no-repeat; }
    .bk-pax-card.is-open .bk-pax-chevron { transform: rotate(180deg); }
    .bk-pax-body { padding: 0 14px 16px; border-top: 1px solid var(--gray-100); }

    /* ── Field groups ── */
    .bk-fieldset { padding-top: 16px; }
    .bk-fieldset + .bk-fieldset { margin-top: 16px; border-top: 1px solid var(--gray-100); }
    .bk-fieldset-head { display: flex; align-items: baseline; gap: 9px; flex-wrap: wrap; margin-bottom: 12px; }
    .bk-fieldset-title { font-size: 12.5px; font-weight: 700; color: var(--gray-900); }
    .bk-fieldset-note { font-size: 11.5px; color: var(--gray-500); line-height: 1.5; }

    .bk-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px 12px; }
    .bk-col-2 { grid-column: span 2; }
    .bk-col-full { grid-column: 1 / -1; }
    .bk-col-half { grid-column: span 1; }

    .bk-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    /* Sentence case, muted. Every one of these used to be tracked-out capitals. */
    .bk-label { display: flex; align-items: baseline; gap: 6px; font-size: 12px; font-weight: 500; color: var(--gray-500); text-transform: none; letter-spacing: 0; }
    /* Most fields here are required, so the optional few are marked instead —
       a column of red asterisks says less than four quiet "Optional" tags. */
    .bk-optional { font-size: 11px; font-weight: 400; color: var(--gray-400); }
    .bk-req { display: none; }
    .bk-input, .bk-select {
        width: 100%; height: 44px; padding: 0 12px;
        border: 1px solid var(--gray-200); border-radius: 9px;
        background: #fff; color: var(--gray-900); font-size: 13.5px;
        font-family: var(--font); outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .bk-input::placeholder { color: var(--gray-400); }
    .bk-input:hover, .bk-select:hover { border-color: var(--gray-300); }
    .bk-input:focus, .bk-select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(48,49,145,.11); }
    .bk-select {
        appearance: none; -webkit-appearance: none; cursor: pointer; padding-right: 34px;
        background: #fff url("{{ asset('images/flight-icons/chevron-down.svg') }}") no-repeat right 12px center / 14px 14px;
    }
    .bk-error { font-size: 11.5px; color: var(--red); }
    /* Mark the control itself, not just the message beneath it — with three
       traveller cards stacked, a line of red text 40px below an otherwise
       normal-looking field is easy to scroll straight past. */
    .bk-field:has(.bk-error) .bk-input,
    .bk-field:has(.bk-error) .bk-select,
    .bk-field:has(.bk-error) .bk-radio-group { border-color: #f7b9b2; background-color: #fffbfa; }
    .bk-field:has(.bk-error) .bk-input:focus,
    .bk-field:has(.bk-error) .bk-select:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(180,35,24,.10); }
    .bk-hint { font-size: 11.5px; color: var(--gray-400); line-height: 1.45; }

    /* Gender: two mutually exclusive options, so a segmented control rather
       than bare browser radios. The real inputs stay, for keyboard and forms. */
    .bk-radio-group { display: inline-flex; gap: 3px; padding: 3px; height: 44px; border: 1px solid var(--gray-200); border-radius: 9px; background: var(--gray-50); }
    .bk-radio-opt { position: relative; display: flex; }
    .bk-radio-opt input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
    .bk-radio-opt span {
        display: flex; align-items: center; justify-content: center;
        min-width: 70px; padding: 0 14px; border-radius: 7px;
        border: 1px solid transparent; background: transparent;
        font-size: 13px; font-weight: 600; color: var(--gray-600);
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .bk-radio-opt input:hover + span { color: var(--gray-900); }
    .bk-radio-opt input:checked + span { background: #fff; border-color: var(--gray-200); color: var(--blue); box-shadow: 0 1px 2px rgba(16,24,40,.07); }
    .bk-radio-opt input:focus-visible + span { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(48,49,145,.11); }

    /* ── Contact ── */
    .bk-contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 12px; }
    .bk-contact-full { grid-column: 1 / -1; }
    .bk-phone-grid { grid-template-columns: 1fr 1fr; }
    .bk-phone-input { font-family: var(--mono); }

    /* ── Actions ── */
    .bk-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 8px; }
    .bk-btn-ghost {
        display: inline-flex; align-items: center; gap: 8px;
        height: 46px; padding: 0 20px; border-radius: 10px;
        border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700);
        font-family: var(--font); font-size: 13.5px; font-weight: 600;
        text-decoration: none; cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }
    .bk-btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-300); }
    /* Was a blue button carrying an orange box-shadow that turned orange on
       hover, left over from when this button used to be orange. */
    .bk-btn-next, .bk-btn-pay {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        height: 48px; padding: 0 26px; border: none; border-radius: 10px;
        background: var(--blue); color: #fff;
        font-family: var(--font); font-size: 14px; font-weight: 700; cursor: pointer;
        box-shadow: 0 6px 18px rgba(48,49,145,.20);
        transition: background .18s ease, box-shadow .18s ease;
    }
    .bk-btn-next:hover, .bk-btn-pay:hover { background: var(--blue-dk); box-shadow: 0 10px 24px rgba(48,49,145,.26); }
    .bk-btn-next[disabled], .bk-btn-pay[disabled] { opacity: .55; cursor: not-allowed; box-shadow: none; }
    .bk-btn-pay { height: 52px; font-size: 15px; }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Notices ── */
    .bk-notice { padding: 11px 14px; border-radius: 9px; font-size: 12.5px; display: flex; align-items: flex-start; gap: 9px; }
    .bk-notice svg { flex-shrink: 0; margin-top: 1px; }
    .bk-notice.info   { background: var(--blue-lt);  color: var(--blue);  border: 1px solid var(--blue-md); }
    .bk-notice.warn   { background: var(--amber-lt); color: var(--amber); border: 1px solid #fed7aa; }
    .bk-notice.danger { background: var(--red-lt);   color: var(--red);   border: 1px solid #fca5a5; }
    .bk-notice.green  { background: var(--green-lt); color: var(--green); border: 1px solid #a7f3d0; }

    /* ── Extra bags banner ── */
    .bk-bags-banner {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 18px; background: #fff;
        border: 1px solid var(--gray-200); border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .bk-bags-icon { font-size: 28px; flex-shrink: 0; }
    .bk-bags-text { flex: 1; }
    .bk-bags-title { font-size: 13.5px; font-weight: 700; color: var(--gray-900); }
    .bk-bags-sub   { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
    .bk-bags-btn { padding: 7px 18px; border: 1.5px solid var(--blue); border-radius: 8px; background: #fff; color: var(--blue); font-size: 13px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: all .15s; flex-shrink: 0; }
    .bk-bags-btn:hover { background: var(--blue-lt); }

    /* Passport accordion */
    .bk-pp-toggle { display: flex; align-items: center; gap: 9px; cursor: pointer; width: 100%; padding: 10px 15px; background: var(--gray-50); border-top: 1px solid var(--gray-100); border-bottom: none; border-left: none; border-right: none; transition: background .15s; user-select: none; font-family: var(--font); text-align: left; }
    .bk-pp-toggle:hover { background: var(--blue-lt); }
    .bk-pp-icon   { width: 22px; height: 22px; border-radius: 6px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-pp-label  { font-size: 13px; font-weight: 600; color: var(--gray-700); flex: 1; }
    .bk-pp-added  { font-size: 11px; color: var(--green); font-weight: 600; margin-left: 6px; }
    .bk-pp-chevron { color: var(--gray-400); transition: transform .25s; flex-shrink: 0; }
    .bk-pp-chevron.open { transform: rotate(180deg); }
    .bk-pp-body { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 14px 15px; background: #fff; border-top: 1px solid var(--gray-100); }

    /* ── Seat selection (Image 3 style) ── */
    .bk-seat-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--gray-100); }
    .bk-seat-row:last-child { border-bottom: none; }
    .bk-seat-leg  { display: flex; align-items: center; gap: 8px; flex: 1; }
    .bk-seat-leg-icon { width: 28px; height: 28px; border-radius: 6px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-seat-leg-info { flex: 1; }
    .bk-seat-leg-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
    .bk-seat-leg-route { font-size: 13px; font-weight: 700; color: var(--gray-900); }
    .bk-seat-chips { display: flex; gap: 6px; align-items: center; }
    .bk-seat-chip { display: flex; align-items: center; gap: 5px; padding: 5px 10px; border: 1.5px solid var(--gray-200); border-radius: 7px; font-size: 12px; font-weight: 600; color: var(--gray-500); background: #fff; cursor: pointer; transition: all .15s; }
    .bk-seat-chip:hover { border-color: var(--blue-md); color: var(--blue); background: var(--blue-lt); }
    .bk-seat-chip svg { opacity: .6; }
    .bk-seat-choose { padding: 6px 16px; border-radius: 7px; background: var(--blue); color: #fff; font-size: 12.5px; font-weight: 700; border: none; cursor: pointer; font-family: var(--font); transition: background .15s; }
    .bk-seat-choose:hover { background: #1e40af; }

    /* ── T&C bar ── */
    .bk-terms-bar { display: flex; align-items: center; gap: 10px; padding: 12px 0 0; font-size: 12.5px; color: var(--gray-500); }
    .bk-terms-bar a { color: var(--blue); font-weight: 600; }
    .bk-terms-bar input { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }

    /* ── Review step ── */
    .bk-review-section { margin-bottom: 20px; }
    .bk-review-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); padding-bottom: 8px; border-bottom: 1px solid var(--gray-100); margin-bottom: 10px; }
    .bk-review-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--gray-100); gap: 16px; font-size: 13px; }
    .bk-review-row:last-child { border-bottom: none; }
    .bk-review-label { color: var(--gray-500); font-weight: 500; flex-shrink: 0; }
    .bk-review-val   { color: var(--gray-900); font-weight: 700; text-align: right; }

    /* Phase 1 booking redesign shell */
    :root {
        --navy: var(--tw-brand, #303191);
        --blue: var(--tw-brand, #303191);
        --blue-lt: #f1f1ff;
        --blue-md: #d7d8ff;
        --green: var(--tw-accent, #009933);
        --gray-50: var(--tw-surface-soft, #f8f9fc);
        --gray-100: #f2f4f7;
        --gray-200: #e6e8ee;
        --gray-300: #d0d5dd;
        --gray-400: #98a2b3;
        --gray-500: #667085;
        --gray-700: #344054;
        --gray-900: #111827;
        --radius: 12px;
        --shadow: 0 1px 2px rgba(16,24,40,.05);
        --shadow-md: 0 10px 28px rgba(16,24,40,.08);
        --font: var(--tw-font-sans, 'Open Sans', 'Plus Jakarta Sans', sans-serif);
    }
    .bk-bags-banner {
        border-radius: 12px;
        border-color: var(--gray-200);
        box-shadow: var(--shadow);
    }
    .bk-bags-icon {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #f1f1ff;
        color: var(--blue);
        font-size: 19px;
    }
    .bk-bags-title {
        font-size: 14px;
        font-weight: 850;
    }
    .bk-bags-sub {
        color: var(--gray-500);
        font-size: 12px;
    }

    /* Phase 4 review, contact and summary refinement */
    .bk-icon-mask,
    .bk-inline-icon,
    .bk-mini-icon {
        display: inline-flex;
        flex: 0 0 auto;
        background: currentColor;
        mask-position: center;
        mask-repeat: no-repeat;
        mask-size: contain;
        -webkit-mask-position: center;
        -webkit-mask-repeat: no-repeat;
        -webkit-mask-size: contain;
    }
    .bk-inline-icon { width: 15px; height: 15px; }
    .bk-mini-icon { width: 13px; height: 13px; }
    .bk-icon-plane {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z'/%3E%3C/svg%3E");
    }
    .bk-icon-bag {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M8 6V5a4 4 0 0 1 8 0v1h2.5A2.5 2.5 0 0 1 21 8.5v10A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-10A2.5 2.5 0 0 1 5.5 6H8Zm2 0h4V5a2 2 0 1 0-4 0v1Zm-3 4v7h2v-7H7Zm8 0v7h2v-7h-2Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M8 6V5a4 4 0 0 1 8 0v1h2.5A2.5 2.5 0 0 1 21 8.5v10A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-10A2.5 2.5 0 0 1 5.5 6H8Zm2 0h4V5a2 2 0 1 0-4 0v1Zm-3 4v7h2v-7H7Zm8 0v7h2v-7h-2Z'/%3E%3C/svg%3E");
    }
    .bk-icon-cabin {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm1 3v12h8V6H8Zm2 2h4v2h-4V8Zm0 4h4v2h-4v-2Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm1 3v12h8V6H8Zm2 2h4v2h-4V8Zm0 4h4v2h-4v-2Z'/%3E%3C/svg%3E");
    }
    .bk-icon-meal {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 2h2v8a3 3 0 0 1-2 2.83V22H5v-9.17A3 3 0 0 1 3 10V2h2v8h2V2Zm10 0c2.21 0 4 2.24 4 5v5h-3v10h-2V2h1Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M7 2h2v8a3 3 0 0 1-2 2.83V22H5v-9.17A3 3 0 0 1 3 10V2h2v8h2V2Zm10 0c2.21 0 4 2.24 4 5v5h-3v10h-2V2h1Z'/%3E%3C/svg%3E");
    }
    .bk-icon-clock {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpath d='M12 6v6l4 2'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpath d='M12 6v6l4 2'/%3E%3C/svg%3E");
    }
    .bk-icon-users {
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.31 0-6 1.79-6 4v2h12v-2c0-2.21-2.69-4-6-4Zm7.5-.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0 1.5c-.77 0-1.5.1-2.16.28 1.6.92 2.66 2.23 2.66 3.72v1h5v-1.5c0-1.93-2.46-3.5-5.5-3.5Z'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.31 0-6 1.79-6 4v2h12v-2c0-2.21-2.69-4-6-4Zm7.5-.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0 1.5c-.77 0-1.5.1-2.16.28 1.6.92 2.66 2.23 2.66 3.72v1h5v-1.5c0-1.93-2.46-3.5-5.5-3.5Z'/%3E%3C/svg%3E");
    }

    /* ── Responsive ── */
    @media (max-width: 900px) { .bk-page { grid-template-columns: 1fr; } .bk-rail { position: static; } }
    @media (max-width: 580px) {
        .bk-pp-body { grid-template-columns: 1fr; }}
    @media (max-width: 640px) {
        body { margin-top: 0 !important; }
        section.navbarmain {
            padding-top: 104px !important;
        }
        main.navbarmain.upper-space {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }}

    /*
     * ── Flight itinerary ──
     * The same trip was previously stated three times on the way down: the
     * accordion head, then a leg header repeating it verbatim, then the
     * segment card. For a one-way non-stop the middle one carried no new
     * information at all. The head now holds the whole trip on one line and
     * the leg caption only appears where there is more than one leg to tell
     * apart.
     */
    .bk-itin-facts { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; margin-top: 3px; }
    .bk-itin-route { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: var(--gray-900); }
    .bk-itin-route .bk-itin-arrow { width: 14px; height: 14px; color: var(--gray-400); flex-shrink: 0; }
    .bk-itin-fact { font-size: 12px; color: var(--gray-500); }
    .bk-itin-fact.mono { font-family: var(--mono); }
    .bk-itin-dot { width: 3px; height: 3px; border-radius: 50%; background: var(--gray-300); flex-shrink: 0; }
    .bk-itin-chip { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; line-height: 1.45; background: var(--gray-50); border: 1px solid var(--gray-200); color: var(--gray-600); }
    .bk-itin-chip.direct { background: var(--green-lt); border-color: #9ae0bd; color: var(--green-dk); }

    .bk-itin-body { padding: 4px 18px 18px; }
    .bk-itin-leg + .bk-itin-leg { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--gray-100); }
    .bk-itin-leg-caption { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin: 12px 0 10px; }
    .bk-itin-leg-name { font-size: 12.5px; font-weight: 700; color: var(--gray-900); }
    .bk-itin-leg-when { font-size: 11.5px; color: var(--gray-500); }

    /* One segment: who flies it, then the two ends of it, then what you may carry. */
    .bk-seg-group { border: 1px solid var(--gray-200); border-radius: 10px; overflow: hidden; background: #fff; }
    .bk-seg-group + .bk-seg-group { margin-top: 10px; }
    .bk-seg-airline-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); }
    .bk-seg-airline-left { display: flex; align-items: center; gap: 9px; min-width: 0; flex-wrap: wrap; }
    .bk-seg-airline-logo { width: 22px; height: 22px; border-radius: 5px; object-fit: contain; background: #fff; border: 1px solid var(--gray-200); flex-shrink: 0; }
    .bk-seg-airline-name { font-size: 12.5px; font-weight: 600; color: var(--gray-900); }
    .bk-seg-airline-meta { font-size: 11.5px; color: var(--gray-500); }
    .bk-seg-cabin-tag { flex-shrink: 0; padding: 2px 8px; border-radius: 6px; background: #fff; border: 1px solid var(--gray-200); font-size: 11px; font-weight: 600; color: var(--gray-600); line-height: 1.45; }

    .bk-seg-body { padding: 14px; }
    .bk-seg-timeline { position: relative; padding-left: 24px; }
    .bk-seg-timeline::before { content: ""; position: absolute; left: 4px; top: 8px; bottom: 8px; width: 1px; background: var(--gray-200); }
    .bk-seg-stop { position: relative; display: flex; align-items: baseline; gap: 12px; min-width: 0; }
    .bk-seg-stop::before {
        content: ""; position: absolute; left: -24px; top: 6px;
        width: 9px; height: 9px; border-radius: 50%; box-sizing: border-box;
        background: #fff; border: 2px solid var(--blue);
    }
    .bk-seg-stop.arrive::before { border-color: var(--green); }
    .bk-seg-time { flex: 0 0 52px; font-family: var(--mono); font-size: 14px; font-weight: 500; color: var(--gray-900); line-height: 1.5; }
    .bk-seg-place-wrap { min-width: 0; }
    .bk-seg-place { font-size: 12.5px; font-weight: 600; color: var(--gray-900); line-height: 1.5; }
    .bk-seg-place-sub { font-size: 11.5px; color: var(--gray-500); line-height: 1.45; }
    .bk-seg-duration { display: flex; align-items: center; gap: 12px; padding: 7px 0; font-size: 11.5px; color: var(--gray-500); font-family: var(--mono); }
    .bk-seg-duration span:first-child { flex: 0 0 52px; }

    /* Allowances read as facts about the flight, not as a floating side panel. */
    .bk-seg-allow { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 13px; padding-top: 12px; border-top: 1px solid var(--gray-100); }
    .bk-allow-chip { display: inline-flex; align-items: center; gap: 7px; padding: 5px 10px; border-radius: 7px; background: var(--gray-50); border: 1px solid var(--gray-100); font-size: 11.5px; color: var(--gray-600); line-height: 1.45; }
    .bk-allow-chip strong { color: var(--gray-900); font-weight: 600; }
    .bk-allow-chip .bk-mini-icon { color: var(--gray-400); }
    .bk-layover-strip .bk-mini-icon { color: var(--amber); }

    .bk-layover-strip {
        position: relative; display: inline-flex; align-items: center; gap: 8px;
        margin: 10px 0 10px 24px; padding: 6px 11px;
        background: var(--amber-lt); border: 1px solid #fde8c8; border-radius: 7px;
        font-size: 11.5px; color: var(--amber); font-weight: 600;
    }

    /*
     * ── Booking summary ──
     * Read as a receipt: what you are buying, what it costs, what you pay.
     * The old version printed the trip total twice — once as a row labelled
     * with the passenger count and again as Trip Total — and showed a base
     * fare whose difference from the total was never accounted for on screen.
     */
    .bk-cart { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
    .bk-cart-head { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--gray-200); }
    .bk-cart-title { font-size: 14px; font-weight: 700; color: var(--gray-900); line-height: 1.35; }
    .bk-cart-icon {
        flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        background: var(--blue-lt);
    }
    .bk-cart-icon::after {
        content: ""; width: 16px; height: 16px; background: var(--blue);
        mask: url("{{ asset('images/flight-icons/tag.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/flight-icons/tag.svg') }}") center / contain no-repeat;
    }
    .bk-cart-head-txt { min-width: 0; }
    .bk-cart-subtitle { font-size: 11.5px; color: var(--gray-500); line-height: 1.45; margin-top: 1px; }

    .bk-cart-body { padding: 14px 18px; }
    .bk-cart-section-lbl { font-size: 12px; font-weight: 700; color: var(--gray-900); margin-bottom: 9px; text-transform: none; letter-spacing: 0; }
    .bk-cart-flight-row { display: flex; align-items: flex-start; gap: 10px; }
    .bk-cart-flight-row + .bk-cart-flight-row { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--gray-100); }
    .bk-cart-plane { flex-shrink: 0; width: 26px; height: 26px; border-radius: 7px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; }
    .bk-cart-route { font-size: 12.5px; font-weight: 600; color: var(--gray-900); line-height: 1.4; }
    .bk-cart-sub { font-size: 11.5px; color: var(--gray-500); margin-top: 1px; line-height: 1.45; }
    .bk-cart-divider { height: 1px; background: var(--gray-100); margin: 12px 0; }

    .bk-fare-section { padding: 14px 18px; border-top: 1px solid var(--gray-100); }
    .bk-fare-title { font-size: 12px; font-weight: 700; color: var(--gray-900); margin-bottom: 9px; }
    .bk-fare-row { display: flex; align-items: baseline; justify-content: space-between; gap: 14px; padding: 5px 0; font-size: 12.5px; }
    .bk-fare-lbl { color: var(--gray-500); min-width: 0; }
    .bk-fare-val { flex-shrink: 0; font-family: var(--mono); font-size: 12px; font-weight: 500; color: var(--gray-900); }
    .bk-fare-row.sum { margin-top: 5px; padding-top: 9px; border-top: 1px solid var(--gray-100); }
    .bk-fare-row.sum .bk-fare-lbl { color: var(--gray-900); font-weight: 600; }
    .bk-fare-row.sum .bk-fare-val { font-weight: 600; }
    .bk-fare-disc { color: var(--green-dk); }
    .bk-fare-view { display: inline-flex; align-items: center; gap: 4px; margin-top: 5px; font-size: 11.5px; font-weight: 600; color: var(--blue); cursor: pointer; }
    .bk-fare-view:hover { text-decoration: underline; }

    /* The one number the page is really about. */
    .bk-fare-total-row {
        display: flex; align-items: flex-end; justify-content: space-between; gap: 14px;
        padding: 14px 18px; border-top: 1px solid var(--gray-200);
        background: linear-gradient(180deg, #fbfbff 0%, #f7f7fd 100%);
    }
    .bk-fare-total-lbl { font-size: 12.5px; font-weight: 600; color: var(--gray-700); }
    .bk-fare-total-note { font-size: 11px; color: var(--gray-500); margin-top: 2px; }
    .bk-fare-total-val { display: block; font-family: var(--mono); font-size: 21px; font-weight: 500; color: var(--gray-900); line-height: 1.2; letter-spacing: -.02em; }

    .bk-promo { padding: 12px 18px; border-top: 1px solid var(--gray-100); }
    .bk-promo-summary { font-size: 12.5px; font-weight: 600; color: var(--blue); cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between; }
    .bk-promo-summary::-webkit-details-marker { display: none; }
    .bk-promo-summary::after {
        content: ""; width: 14px; height: 14px; background: currentColor; transition: transform .2s ease;
        mask: url("{{ asset('images/flight-icons/chevron-down.svg') }}") center / contain no-repeat;
        -webkit-mask: url("{{ asset('images/flight-icons/chevron-down.svg') }}") center / contain no-repeat;
    }
    .bk-promo details[open] .bk-promo-summary::after,
    details[open] > .bk-promo-summary::after { transform: rotate(180deg); }
    .bk-promo-row { display: flex; gap: 8px; margin-top: 10px; }
    .bk-promo-input { flex: 1; min-width: 0; height: 38px; padding: 0 12px; border: 1px solid var(--gray-200); border-radius: 8px; background: #fff; color: var(--gray-900); font-family: var(--font); font-size: 12.5px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .bk-promo-input::placeholder { color: var(--gray-400); }
    .bk-promo-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(48,49,145,.11); }
    .bk-promo-btn { flex-shrink: 0; height: 38px; padding: 0 16px; border: 1px solid var(--blue); border-radius: 8px; background: var(--blue); color: #fff; font-family: var(--font); font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background .15s ease; }
    .bk-promo-btn:hover { background: var(--blue-dk); border-color: var(--blue-dk); }

    .bk-rail-trust { display: flex; flex-direction: column; gap: 9px; padding: 13px 18px 15px; border-top: 1px solid var(--gray-100); }
    .bk-rail-trust-item { display: flex; align-items: flex-start; gap: 9px; font-size: 11.5px; color: var(--gray-500); line-height: 1.5; }
    .bk-rail-trust-icon { flex-shrink: 0; width: 15px; height: 15px; margin-top: 1px; color: var(--green-dk); }

    .bk-tax-detail { padding-left: 10px; border-left: 2px solid var(--blue-md); margin: 4px 0 6px; }
    .bk-tax-row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; padding: 2px 0; font-size: 11.5px; }
    .bk-tax-lbl { color: var(--gray-500); }
    .bk-tax-code { font-size: 10.5px; color: var(--gray-400); margin-left: 4px; }
    .bk-tax-val { font-family: var(--mono); font-size: 11px; color: var(--gray-700); }
</style>

@php
    // ── Core session data ──
    $flight        = session('bookingFlight') ?? [];
    $mappedFlight  = $flight['flight'] ?? $flight;
    $sessionId     = session('bookingSessionId') ?? null;
    $searchParams  = session('bookingSearchParams') ?? [];
    $fareRulesData = session('fareRules') ?? [];
    $extraServices = session('extraServices') ?? [];

    // ── Parse revalidate data (raw from API) ──
    $revalidate    = $flight['revalidate'] ?? [];
    $fareItinerary = $revalidate['AirRevalidateResponse']['AirRevalidateResult']['FareItineraries']['FareItinerary'] ?? [];
    $airFareInfo   = $fareItinerary['AirItineraryFareInfo'] ?? [];
    $fareBreakdown = $airFareInfo['FareBreakdown'] ?? [];
    $itinTotals    = $airFareInfo['ItinTotalFares'] ?? [];
    $originDest    = $fareItinerary['OriginDestinationOptions'] ?? [];

    // ── Parse Extra Services ──
    $esResult      = $extraServices['ExtraServicesResponse']['ExtraServicesResult']['ExtraServicesData'] ?? [];
    $dynBaggage    = $esResult['DynamicBaggage'] ?? [];
    $dynMeal       = $esResult['DynamicMeal'] ?? [];
    $dynSeat       = $esResult['DynamicSeat'] ?? [];

    // ── Parse Fare Rules ──
    $fareRulesResult  = $fareRulesData['FareRules1_1Response']['FareRules1_1Result'] ?? [];
    $baggageInfos     = $fareRulesResult['BaggageInfos'] ?? [];
    $fareRulesList    = $fareRulesResult['FareRules'] ?? [];

    // ── Existing mapped fields (keep your existing ones) ──
    $cabinMap = ['Y' => 'Economy', 'S' => 'Premium Economy', 'C' => 'Business', 'F' => 'First Class'];
    $cabin    = $cabinMap[$searchParams['flight_type'] ?? 'Y'] ?? 'Economy';

    $currency = $mappedFlight['currency'] ?? ($itinTotals['TotalFare']['CurrencyCode'] ?? 'NGN');
    $sym      = $currency === 'NGN' ? html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8') : ($currency === 'USD' ? '$' : $currency . ' ');
    $fmt      = fn($v) => $sym . number_format((float) $v, 2);

    // SkyLink reports 12-hour clock times ("07:15 pm"); TravelNext reports
    // 24-hour. The results page normalises the same way, so a departure does
    // not change format between choosing the flight and booking it.
    $bkTime = function ($value): string {
        $raw = trim((string) $value);
        if (! preg_match('/^(\d{1,2}):(\d{2})\s*([ap])\.?m\.?$/i', $raw, $m)) { return $raw; }
        $hour = ((int) $m[1] % 12) + (strtolower($m[3]) === 'p' ? 12 : 0);
        return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':'.$m[2];
    };

    $segments  = $flight['segments'] ?? ($mappedFlight['segments'] ?? []);
    $retSegs   = $mappedFlight['returnSegments'] ?? [];
    $multiLegs = $mappedFlight['multiLegs'] ?? [];
    $breakdown = $flight['fareBreakdown'] ?? ($mappedFlight['fareBreakdown'] ?? $fareBreakdown);
    $isReturn  = count($retSegs) > 0;
    $isMulti   = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round-trip' : ($isMulti ? 'Multi-city' : 'One-way');
    $firstSeg  = $segments[0] ?? [];
    $lastSeg   = count($segments) > 0 ? $segments[count($segments) - 1] : [];
    if ($isMulti && !empty($multiLegs)) {
        $firstSeg = $multiLegs[0]['segments'][0] ?? [];
        $lastMultiLeg = $multiLegs[count($multiLegs) - 1]['segments'] ?? [];
        $lastSeg = !empty($lastMultiLeg) ? $lastMultiLeg[count($lastMultiLeg) - 1] : [];
    }
    $stopCount = $mappedFlight['stops'] ?? 0;

    // Totals from revalidate
    $totalPrice = (float)($mappedFlight['price'] ?? ($itinTotals['TotalFare']['Amount'] ?? 0));
    $totalBase  = (float)($mappedFlight['baseFare'] ?? ($itinTotals['BaseFare']['Amount'] ?? 0));
    $totalTax   = (float)($mappedFlight['totalTax'] ?? ($itinTotals['TotalTax']['Amount'] ?? 0));
    $discount   = 0;

    $taxLabels = [
        'QT'  => 'Airport Tax',        'TE5' => 'Ticket Levy',
        'W3'  => 'Security Surcharge', 'W32' => 'Security Surcharge',
        'MA'  => 'Miscellaneous Fee',  'MAC' => 'Miscellaneous Fee',
        'NG3' => 'Nigeria Passenger Levy', 'YQF' => 'Fuel Surcharge',
        'YQI' => 'Fuel Surcharge',     'YRI' => 'Carrier Surcharge',
        'YRF' => 'Carrier Surcharge',  'GB'  => 'Air Passenger Duty',
        'UB'  => 'Passenger Service Charge', 'DE' => 'Departure Tax',
        'BE'  => 'Booking Fee',        'OtherTaxes' => 'Taxes & Fees',
    ];

    $equipMap = [
        '73H' => 'Boeing 737-800', '738' => 'Boeing 737-800',
        '7M8' => 'Boeing 737 MAX 8', '789' => 'Boeing 787-9',
        '788' => 'Boeing 787-8',    '320' => 'Airbus A320',
        '321' => 'Airbus A321',     '332' => 'Airbus A330-200',
        '333' => 'Airbus A330-300', 'E90' => 'Embraer E190',
    ];

    // Build allLegs for seat section
    $allLegs = [];
    if (!empty($segments)) {
        $allLegs[] = ['label' => 'Departure', 'route' => ($firstSeg['from'] ?? '') . ' → ' . (($segments[count($segments)-1]['to'] ?? '')), 'type' => 'outbound', 'logo' => $firstSeg['airlineLogo'] ?? ''];
    }
    if ($isReturn && !empty($retSegs)) {
        $allLegs[] = ['label' => 'Return', 'route' => ($retSegs[0]['from'] ?? '') . ' → ' . ($retSegs[count($retSegs)-1]['to'] ?? ''), 'type' => 'return', 'logo' => $retSegs[0]['airlineLogo'] ?? ''];
    }
    foreach ($multiLegs as $li => $leg) {
        $legSegs = $leg['segments'] ?? [];
        if (!empty($legSegs)) {
            $allLegs[] = ['label' => 'Leg ' . ($li + 2), 'route' => ($legSegs[0]['from'] ?? '') . ' → ' . ($legSegs[count($legSegs)-1]['to'] ?? ''), 'type' => 'multi', 'logo' => $legSegs[0]['airlineLogo'] ?? ''];
        }
    }

    // ── Parse DynamicBaggage for outbound/inbound options ──
    if ($isMulti && !empty($multiLegs)) {
        $allLegs = [];
        foreach ($multiLegs as $li => $leg) {
            $legSegs = $leg['segments'] ?? [];
            if (!empty($legSegs)) {
                $allLegs[] = [
                    'label' => 'Leg ' . ($li + 1),
                    'route' => ($legSegs[0]['from'] ?? '') . ' -> ' . ($legSegs[count($legSegs)-1]['to'] ?? ''),
                    'type' => 'multi',
                    'logo' => $legSegs[0]['airlineLogo'] ?? '',
                ];
            }
        }
    }

    $baggageOutbound = [];
    $baggageInbound  = [];
    foreach ($dynBaggage as $bag) {
        $behavior = $bag['Behavior'] ?? '';
        $services = $bag['Services'][0] ?? [];
        if ($behavior === 'PER_PAX_OUTBOUND')  $baggageOutbound = $services;
        if ($behavior === 'PER_PAX_INBOUND')   $baggageInbound  = $services;
    }

    // ── Parse DynamicMeal for outbound/inbound ──
    $mealOutbound = [];
    $mealInbound  = [];
    foreach ($dynMeal as $meal) {
        $behavior = $meal['Behavior'] ?? '';
        $services = $meal['Services'] ?? [];
        if ($behavior === 'PER_PAX_PER_SEGMENT_OUTBOUND') $mealOutbound = $services;
        if ($behavior === 'PER_PAX_PER_SEGMENT_INBOUND')  $mealInbound  = $services;
    }

    // Helper: outbound currency symbol (extra services may use AED or local)
    $esSym = fn($code) => match($code) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'AED' => 'AED ', default => $code . ' ' };
    $esFmt = fn($svc) => ($esSym($svc['ServiceCost']['CurrencyCode'] ?? '') . number_format((float)($svc['ServiceCost']['Amount'] ?? 0), 2));
    $isTravelFlexCheckout = session('bookingIntent', 'booking') === 'travelflex';
@endphp

    <div class="bk-wrap"
        x-data="{
            submitForm() { document.getElementById('bk-form').submit(); },
            taxOpen: {},
            toggleTax(key) { this.taxOpen[key] = !this.taxOpen[key]; }
        }">

        {{-- Breadcrumb --}}
        <div class="bk-crumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bk-crumb-sep">&rsaquo;</span>
            <a href="{{ route('air.flight-s') }}">Flight Results</a>
            <span class="bk-crumb-sep">&rsaquo;</span>
            <span>Complete Booking</span>
        </div>
        @if($errors->any())
        <div class="bk-notice danger" style="margin-bottom:16px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif
        {{-- Global notices --}}
        @if(!empty($flight['isPassportMandatory']))
            <div class="bk-notice danger" style="margin-bottom:12px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                <span><strong>Passport required.</strong> All passengers must carry a valid passport. Names must match exactly.</span>
            </div>
        @endif

        <div class="bk-page">

            {{-- ══════════════ MAIN COLUMN ══════════════ --}}
            <div class="bk-main">

                {{-- Stepper --}}
                @php
                    $bkSteps = [
                        ['label' => 'Traveller information', 'sub' => 'Names and documents'],
                        ['label' => 'Trip customisation',    'sub' => 'Baggage and meals'],
                        ['label' => 'Review and continue',   'sub' => $isTravelFlexCheckout ? 'Continue to TravelFlex' : 'Review and pay'],
                    ];
                @endphp
                <div class="bk-steps" style="--bk-progress: {{ [1 => '16%', 2 => '50%', 3 => '84%'][$step] ?? '16%' }};">
                    @foreach($bkSteps as $i => $bkStep)
                        @php $n = $i + 1; @endphp
                        <div class="bk-step">
                            <div class="bk-step-dot {{ $step > $n ? 'done' : ($step === $n ? 'active' : '') }}"
                                 aria-hidden="true">{{ $step > $n ? '' : $n }}</div>
                            <div class="bk-step-txt">
                                <span class="bk-step-label {{ $step === $n ? 'active' : '' }}">{{ $bkStep['label'] }}</span>
                                <span class="bk-step-sub">{{ $step > $n ? 'Done' : $bkStep['sub'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ════════ STEP 1 ════════ --}}
                @if(in_array($step, [1, 2], true))

                    {{-- ── 1. Flight Itinerary (accordion, open by default) ── --}}
                    <div class="bk-acc" x-data="{ open: false }">
                        @php
                            $routeStopCount = $flight['stops'] ?? max(0, count($segments) - 1);
                            $routeDuration = $flight['totalTimeLabel'] ?? $flight['durationLabel'] ?? '';
                            $routeDate = !empty($firstSeg['departDT'])
                                ? \Carbon\Carbon::parse($firstSeg['departDT'])->format('D, d M')
                                : '';
                        @endphp
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <span class="bk-icon-mask bk-icon-plane" style="width:17px;height:17px;"></span>
                            </div>
                            <div style="min-width:0;">
                                <div class="bk-itin-route">
                                    <span>{{ $firstSeg['from'] ?? '' }}</span>
                                    <svg class="bk-itin-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                                    </svg>
                                    <span>{{ $lastSeg['to'] ?? '' }}</span>
                                </div>
                                {{-- One line of facts. The leg header below used to repeat all
                                     of this verbatim, which said nothing new on a one-way. --}}
                                <div class="bk-itin-facts">
                                    <span class="bk-itin-chip">{{ $tripLabel }}</span>
                                    @if($routeDate)
                                        <span class="bk-itin-dot" aria-hidden="true"></span>
                                        <span class="bk-itin-fact">{{ $routeDate }}</span>
                                    @endif
                                    @if($routeDuration)
                                        <span class="bk-itin-dot" aria-hidden="true"></span>
                                        <span class="bk-itin-fact mono">{{ $routeDuration }}</span>
                                    @endif
                                    <span class="bk-itin-dot" aria-hidden="true"></span>
                                    <span class="bk-itin-chip {{ $routeStopCount === 0 ? 'direct' : '' }}">
                                        {{ $routeStopCount > 0 ? $routeStopCount . ' stop' . ($routeStopCount > 1 ? 's' : '') : 'Non-stop' }}
                                    </span>
                                    <span class="bk-itin-dot" aria-hidden="true"></span>
                                    <span class="bk-itin-fact">{{ $cabin }}</span>
                                </div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>

                        <div class="bk-itin-body" x-show="open" x-transition>
                            @if(!$isMulti)
                                <div class="bk-itin-leg">
                                    {{-- Only named when there is another leg to tell it apart from. --}}
                                    @if($isReturn && count($retSegs) > 0)
                                        <div class="bk-itin-leg-caption">
                                            <span class="bk-itin-leg-name">Outbound</span>
                                            @if(!empty($flight['departDateLabel']))
                                                <span class="bk-itin-leg-when">{{ $flight['departDateLabel'] }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    @foreach($segments as $si => $seg)
                                        @php
                                            $equip    = $seg['equipment'] ?? '';
                                            $equipLbl = $equipMap[$equip] ?? $equip;
                                            $bagStr   = implode(' / ', array_unique(array_filter((array)($breakdown[0]['baggage'] ?? []), fn($v) => $v !== ''))) ?: '1 x 23kg';
                                            $cabinBag = implode(' / ', array_unique(array_filter((array)($breakdown[0]['cabinBaggage'] ?? []), fn($v) => $v !== ''))) ?: '1 x 7kg';
                                            $layover  = ($si > 0 && !empty($flight['layoverDurations'][$si - 1]))
                                                ? $flight['layoverDurations'][$si - 1].' layover in '.($segments[$si-1]['toCity'] ?? $segments[$si-1]['to'] ?? '')
                                                : '';
                                        @endphp
                                        @include('livewire.pages.flight.partials.booking-segment', [
                                            'seg' => $seg, 'bagStr' => $bagStr, 'cabinBag' => $cabinBag,
                                            'equipLbl' => $equipLbl, 'cabin' => $cabin, 'layover' => $layover,
                                        ])
                                    @endforeach
                                </div>
                            @endif

                            {{-- ── Return leg ── --}}
                            @if($isReturn && count($retSegs) > 0)
                                <div class="bk-itin-leg">
                                    <div class="bk-itin-leg-caption">
                                        <span class="bk-itin-leg-name">Return</span>
                                        @if(!empty($flight['returnDateLabel']))
                                            <span class="bk-itin-leg-when">{{ $flight['returnDateLabel'] }}</span>
                                        @endif
                                    </div>

                                    @foreach($retSegs as $si => $seg)
                                        @php
                                            $equip    = $seg['equipment'] ?? '';
                                            $equipLbl = $equipMap[$equip] ?? $equip;
                                            $bagStr   = implode(' / ', array_unique(array_filter((array)($breakdown[0]['baggage'] ?? []), fn($v) => $v !== ''))) ?: '1 x 23kg';
                                            $cabinBag = implode(' / ', array_unique(array_filter((array)($breakdown[0]['cabinBaggage'] ?? []), fn($v) => $v !== ''))) ?: '1 x 7kg';
                                            $layover  = ($si > 0 && !empty($flight['returnLayoverDurations'][$si - 1]))
                                                ? $flight['returnLayoverDurations'][$si - 1].' layover in '.($retSegs[$si-1]['toCity'] ?? $retSegs[$si-1]['to'] ?? '')
                                                : '';
                                        @endphp
                                        @include('livewire.pages.flight.partials.booking-segment', [
                                            'seg' => $seg, 'bagStr' => $bagStr, 'cabinBag' => $cabinBag,
                                            'equipLbl' => $equipLbl, 'cabin' => $cabin, 'layover' => $layover,
                                        ])
                                    @endforeach
                                </div>
                            @endif

                            {{-- Multi-city legs --}}
                            @if($isMulti)
                                @foreach($multiLegs as $li => $leg)
                                    @php
                                        $legSegs  = $leg['segments'] ?? [];
                                        $legFirst = $legSegs[0] ?? [];
                                        $legLast  = count($legSegs) > 0 ? $legSegs[count($legSegs)-1] : [];
                                    @endphp
                                    @if(!empty($legSegs))
                                        <div class="bk-itin-leg">
                                            <div class="bk-itin-leg-caption">
                                                <span class="bk-itin-leg-name">Leg {{ $li + 1 }} · {{ $legFirst['from'] ?? '' }} to {{ $legLast['to'] ?? '' }}</span>
                                                @if(!empty($leg['departDateLabel']))
                                                    <span class="bk-itin-leg-when">{{ $leg['departDateLabel'] }}</span>
                                                @endif
                                            </div>

                                            @foreach($legSegs as $si => $seg)
                                                @php
                                                    $equip    = $seg['equipment'] ?? '';
                                                    $equipLbl = $equipMap[$equip] ?? $equip;
                                                    $bagStr   = implode(' / ', array_unique(array_filter((array)($breakdown[0]['baggage'] ?? []), fn($v) => $v !== ''))) ?: '1 x 23kg';
                                                    $cabinBag = implode(' / ', array_unique(array_filter((array)($breakdown[0]['cabinBaggage'] ?? []), fn($v) => $v !== ''))) ?: '1 x 7kg';
                                                    $layover  = ($si > 0 && !empty($leg['layoverDurations'][$si - 1]))
                                                        ? $leg['layoverDurations'][$si - 1].' layover in '.($legSegs[$si-1]['toCity'] ?? $legSegs[$si-1]['to'] ?? '')
                                                        : '';
                                                @endphp
                                                @include('livewire.pages.flight.partials.booking-segment', [
                                                    'seg' => $seg, 'bagStr' => $bagStr, 'cabinBag' => $cabinBag,
                                                    'equipLbl' => $equipLbl, 'cabin' => $cabin, 'layover' => $layover,
                                                ])
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- ── 2. Extra Services (Baggage + Meals) ── --}}
                    @if($step === 2)
                    @if(!empty($baggageOutbound) || !empty($baggageInbound) || !empty($mealOutbound) || !empty($mealInbound))
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon" style="background:#f0fdf4;color:#059669;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Extra Services</div>
                                <div class="bk-acc-sub">Add baggage or meals to your booking</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body" style="padding-top:0;">

                                {{-- ── EXTRA BAGGAGE ── --}}
                                @if(!empty($baggageOutbound) || !empty($baggageInbound))
                                <div style="padding:14px 0 10px;border-bottom:1px solid var(--gray-100);">

                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                                        <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span>
                                        Extra Check-in Baggage
                                    </div>

                                    @foreach([['outbound', $baggageOutbound], ['inbound', $baggageInbound]] as [$dir, $bagOpts])
                                    @if(!empty($bagOpts))
                                    <div style="margin-bottom:14px;">
                                        <div style="font-size:11.5px;font-weight:700;color:var(--gray-700);margin-bottom:8px;">
                                            {{ ucfirst($dir) }}
                                        </div>

                                        <div style="display:flex;flex-direction:column;gap:8px;">
                                            @foreach($bagOpts as $svc)
                                            @php
                                                $svcId  = $svc['ServiceId'];
                                                $maxQty = (int) ($svc['MaximumQuantity'] ?? 3);
                                                $price  = (float) ($svc['ServiceCost']['Amount'] ?? 0);
                                                $curr   = $svc['ServiceCost']['CurrencyCode'] ?? 'USD';
                                                $sym    = match($curr) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'AED' => 'AED ', default => $curr . ' ' };
                                                $currentQty = (int) ($selectedBaggage[$dir][$svcId] ?? 0);
                                            @endphp

                                            <div style="display:flex;align-items:center;gap:14px;padding:11px 14px;
                                                        border:1.5px solid {{ $currentQty > 0 ? 'var(--blue)' : 'var(--gray-200)' }};
                                                        border-radius:10px;background:{{ $currentQty > 0 ? 'var(--blue-lt)' : '#fff' }};
                                                        transition:all .2s;">

                                                {{-- Description --}}
                                                <div style="flex:1;">
                                                    <div style="font-size:13px;font-weight:700;color:var(--gray-900);">
                                                        {{ $svc['Description'] }}
                                                    </div>
                                                    <div style="font-size:11px;color:var(--gray-400);margin-top:2px;">
                                                        {{ $svc['FareDescription'] ?? 'per passenger' }}
                                                    </div>
                                                </div>

                                                {{-- Price --}}
                                                <div style="font-size:13px;font-weight:800;color:var(--blue);
                                                            white-space:nowrap;font-family:var(--mono);min-width:70px;text-align:right;">
                                                    +{{ $sym }}{{ number_format($price, 2) }}
                                                    <div style="font-size:10px;color:var(--gray-400);font-weight:500;font-family:var(--font);">
                                                        per bag
                                                    </div>
                                                </div>

                                                {{-- Quantity stepper --}}
                                                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                                    <button type="button"
                                                        style="width:28px;height:28px;border-radius:50%;border:1.5px solid var(--gray-200);
                                                            background:#fff;font-size:16px;color:var(--gray-700);cursor:pointer;
                                                            display:flex;align-items:center;justify-content:center;transition:all .15s;
                                                            {{ $currentQty === 0 ? 'opacity:.35;cursor:not-allowed;' : '' }}"
                                                        wire:click="$set('selectedBaggage.{{ $dir }}.{{ $svcId }}', {{ max(0, $currentQty - 1) }})"
                                                        {{ $currentQty === 0 ? 'disabled' : '' }}>
                                                        -
                                                    </button>

                                                    <span style="font-size:16px;font-weight:800;color:var(--gray-900);
                                                                min-width:20px;text-align:center;">
                                                        {{ $currentQty }}
                                                    </span>

                                                    <button type="button"
                                                        style="width:28px;height:28px;border-radius:50%;border:1.5px solid var(--gray-200);
                                                            background:#fff;font-size:16px;color:var(--gray-700);cursor:pointer;
                                                            display:flex;align-items:center;justify-content:center;transition:all .15s;
                                                            {{ $currentQty >= $maxQty ? 'opacity:.35;cursor:not-allowed;' : '' }}"
                                                        wire:click="$set('selectedBaggage.{{ $dir }}.{{ $svcId }}', {{ min($maxQty, $currentQty + 1) }})"
                                                        {{ $currentQty >= $maxQty ? 'disabled' : '' }}>
                                                        +
                                                    </button>
                                                </div>

                                                {{-- Line total --}}
                                                @if($currentQty > 0)
                                                <div style="font-size:12px;font-weight:800;color:var(--green);
                                                            white-space:nowrap;font-family:var(--mono);min-width:65px;text-align:right;">
                                                    = {{ $sym }}{{ number_format($price * $currentQty, 2) }}
                                                </div>
                                                @endif

                                                {{-- Hidden input for form submission --}}
                                                @if($currentQty > 0)
                                                <input type="hidden"
                                                    name="extra_baggage[{{ $dir }}][{{ $svcId }}]"
                                                    value="{{ $currentQty }}">
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                                @endif

                                {{-- ── MEALS ── --}}
                                @if(!empty($mealOutbound) || !empty($mealInbound))
                                <div style="padding-top:14px;">

                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                                        <span class="bk-mini-icon bk-icon-meal" aria-hidden="true"></span>
                                        Meal Preferences
                                    </div>

                                    @foreach([['outbound', $mealOutbound], ['inbound', $mealInbound]] as [$dir, $mealSegs])
                                    @if(!empty($mealSegs))
                                    <div style="margin-bottom:14px;">
                                        <div style="font-size:11.5px;font-weight:700;color:var(--gray-700);margin-bottom:8px;">
                                            {{ ucfirst($dir) }}
                                        </div>

                                        @foreach($mealSegs as $si => $segMeals)
                                        <div style="margin-bottom:10px;">
                                            <div style="font-size:10.5px;font-weight:700;color:var(--gray-400);
                                                        text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                                                Segment {{ $si + 1 }}
                                            </div>

                                            <div style="display:flex;flex-direction:column;gap:7px;">
                                                @foreach($segMeals as $svc)
                                                @php
                                                    $svcId    = $svc['ServiceId'];
                                                    $price    = (float) ($svc['ServiceCost']['Amount'] ?? 0);
                                                    $curr     = $svc['ServiceCost']['CurrencyCode'] ?? 'AED';
                                                $sym    = match($curr) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'AED' => 'AED ', default => $curr . ' ' };
                                                    $isChecked = !empty($selectedMeals[$dir][$si][$svcId]);
                                                @endphp

                                                <label style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                                                            border:1.5px solid {{ $isChecked ? 'var(--amber)' : 'var(--gray-200)' }};
                                                            border-radius:10px;cursor:pointer;
                                                            background:{{ $isChecked ? 'var(--amber-lt)' : '#fff' }};
                                                            transition:all .2s;">

                                                    <input type="checkbox"
                                                        wire:model.live="selectedMeals.{{ $dir }}.{{ $si }}.{{ $svcId }}"
                                                        style="width:16px;height:16px;accent-color:var(--amber);
                                                                cursor:pointer;flex-shrink:0;">

                                                    <div style="flex:1;">
                                                        <div style="font-size:12.5px;font-weight:700;color:var(--gray-900);">
                                                            {{ $svc['Description'] }}
                                                        </div>
                                                    </div>

                                                    <div style="font-size:12.5px;font-weight:800;
                                                                color:{{ $isChecked ? 'var(--amber)' : 'var(--gray-500)' }};
                                                                white-space:nowrap;font-family:var(--mono);">
                                                        +{{ $sym }}{{ number_format($price, 2) }}
                                                    </div>

                                                    @if($isChecked)
                                                    <span style="font-size:11px;font-weight:700;color:var(--amber);white-space:nowrap;">Added</span>
                                                    @endif

                                                    {{-- Hidden input for form submission --}}
                                                    @if($isChecked)
                                                    <input type="hidden"
                                                        name="extra_meal[{{ $dir }}][{{ $si }}][]"
                                                        value="{{ $svcId }}">
                                                    @endif
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                                @endif

                                {{-- ── LIVE EXTRAS SUMMARY (shows only when something selected) ── --}}
                                @if($extrasTotal > 0)
                                <div style="margin-top:14px;padding:12px 16px;background:var(--green-lt);
                                            border:1.5px solid #a7f3d0;border-radius:10px;
                                            display:flex;align-items:center;justify-content:space-between;">
                                    <span style="font-size:12.5px;font-weight:700;color:var(--green);">
                                        Extras selected
                                    </span>
                                    <span style="font-size:14px;font-weight:800;color:var(--green);font-family:var(--mono);">
                                        +{{ $esFmt(['ServiceCost' => ['CurrencyCode' => 'NGN', 'Amount' => $extrasTotal]]) }}
                                    </span>
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>

                    @else
                    {{-- Fallback banner if no extra services available --}}
                    <div class="bk-bags-banner">
                        <div class="bk-bags-icon"><span class="bk-icon-mask bk-icon-bag" style="width:18px;height:18px;" aria-hidden="true"></span></div>
                        <div class="bk-bags-text">
                            <div class="bk-bags-title">Add extra check-in bags</div>
                            <div class="bk-bags-sub">No additional baggage options available for this route</div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Fare Rules (from BaggageInfos + FareRules) ── --}}
                    @if(!empty($baggageInfos) || !empty($fareRulesList))
                    <div class="bk-acc" x-data="{ open: false }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon" style="background:#fef2f2;color:#dc2626;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Fare & Baggage Rules</div>
                                <div class="bk-acc-sub">Baggage allowance per segment</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body" style="padding-top:14px;">

                                {{-- Baggage per segment table --}}
                                @if(!empty($baggageInfos))
                                <div style="margin-bottom:16px;">
                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Baggage Allowance by Segment</div>
                                    <div style="border:1px solid var(--gray-200);border-radius:9px;overflow:hidden;">
                                        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                                            <thead>
                                                <tr style="background:var(--gray-50);">
                                                    <th style="padding:9px 14px;text-align:left;font-weight:700;color:var(--gray-500);font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--gray-200);">Flight</th>
                                                    <th style="padding:9px 14px;text-align:left;font-weight:700;color:var(--gray-500);font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--gray-200);">Route</th>
                                                    <th style="padding:9px 14px;text-align:left;font-weight:700;color:var(--gray-500);font-size:11px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--gray-200);">Allowance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($baggageInfos as $bi => $bagInfo)
                                                    @php $b = $bagInfo['BaggageInfo'] ?? $bagInfo; @endphp
                                                    <tr style="{{ $bi % 2 === 0 ? '' : 'background:var(--gray-50);' }}">
                                                        <td style="padding:10px 14px;font-weight:700;color:var(--blue);font-family:var(--mono);border-bottom:1px solid var(--gray-100);">
                                                            {{ $b['FlightNo'] ?? '-' }}
                                                        </td>
                                                        <td style="padding:10px 14px;color:var(--gray-700);font-weight:600;border-bottom:1px solid var(--gray-100);">
                                                            {{ $b['Departure'] ?? '' }} → {{ $b['Arrival'] ?? '' }}
                                                        </td>
                                                        <td style="padding:10px 14px;border-bottom:1px solid var(--gray-100);">
                                                            <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:var(--green-lt);color:var(--green);border-radius:999px;font-size:11.5px;font-weight:700;">
                                                                <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span> {{ $b['Baggage'] ?? '-' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                                {{-- Fare Rules per city pair --}}
                                @if(!empty($fareRulesList))
                                <div>
                                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Fare Rules by Route</div>
                                    @foreach($fareRulesList as $frItem)
                                        @php
                                            $fr = $frItem['FareRule'] ?? $frItem;
                                            $rawRules = (string)($fr['Rules'] ?? '');
                                            $rulesText = trim(strip_tags(preg_replace('/<(br|\/p|\/div|\/li)>/i', "\n", html_entity_decode($rawRules, ENT_QUOTES | ENT_HTML5))));
                                        @endphp
                                        <div style="border:1px solid var(--gray-200);border-radius:9px;margin-bottom:8px;overflow:hidden;">
                                            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 14px;background:var(--gray-50);border-bottom:1px solid var(--gray-100);">
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span style="font-size:11.5px;font-weight:700;color:var(--navy);font-family:var(--mono);">{{ $fr['Airline'] ?? '' }}</span>
                                                    <span style="font-size:12px;font-weight:700;color:var(--gray-700);">
                                                        {{ substr($fr['CityPair'] ?? '', 0, 3) }} → {{ substr($fr['CityPair'] ?? '', 3, 3) }}
                                                    </span>
                                                </div>
                                                <span style="font-size:10.5px;padding:2px 8px;border-radius:999px;background:var(--blue-lt);color:var(--blue);font-weight:700;">
                                                    {{ $fr['Category'] ?? 'General' }}
                                                </span>
                                            </div>
                                            @if($rulesText !== '')
                                            <div style="padding:12px 14px;font-size:12px;color:var(--gray-600);line-height:1.7;white-space:pre-wrap;">{{ $rulesText }}</div>
                                            @else
                                            <div style="padding:12px 14px;font-size:12px;color:var(--gray-400);font-style:italic;">No specific rules text available for this route.</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── 4. Passenger Count ── --}}
                    <div class="bk-actions">
                        <button class="bk-btn-ghost" wire:click="back">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Traveller Info
                        </button>
                        <button class="bk-btn-next" wire:click="proceed" wire:loading.attr="disabled" wire:target="proceed">
                            <span wire:loading.remove wire:target="proceed" style="display:inline-flex;align-items:center;gap:7px;color:#fff;">
                                Review Booking
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
                            <span wire:loading wire:target="proceed">Updating...</span>
                        </button>
                    </div>
                    @endif

                    @if($step === 1)
                    {{-- ── Traveller details, one card per traveller ── --}}
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Traveller details</div>
                                <div class="bk-acc-sub">{{ $this->getTotalPassengers() }} {{ $this->getTotalPassengers() === 1 ? 'passenger' : 'passengers' }} &middot; names must match the passport exactly</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">
                                @error('passengers') <span class="bk-error" style="display:block;margin-bottom:10px;">{{ $message }}</span> @enderror

                                @foreach($this->passengers as $i => $pax)
                                    @php
                                        $typeLabel  = match($pax['type']) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Traveller' };
                                        $typeAge    = match($pax['type']) { 'ADT' => '18 yrs and over', 'CHD' => '2-12 yrs', 'INF' => 'Under 2', default => '' };
                                        $badgeClass = strtolower($pax['type']);
                                        $required   = ['title', 'last_name', 'first_name', 'dob', 'nationality', 'gender'];
                                        $filled     = count(array_filter($required, fn ($f) => ! empty($pax[$f])));
                                        $isComplete = $filled === count($required);
                                        $fullName   = trim(($pax['first_name'] ?? '').' '.($pax['last_name'] ?? ''));
                                        $titleOptions = in_array($pax['type'], ['CHD', 'INF'], true)
                                            ? ['Master', 'Miss']
                                            : ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr'];
                                    @endphp

                                    <div class="bk-pax-card" wire:key="pax-{{ $i }}-{{ $pax['type'] }}"
                                         x-data="{ cardOpen: {{ $i === 0 ? 'true' : 'false' }} }"
                                         :class="{ 'is-open': cardOpen }">

                                        <div class="bk-pax-card-head" @click="cardOpen = !cardOpen"
                                             role="button" :aria-expanded="cardOpen ? 'true' : 'false'">
                                            <span class="bk-pax-index" aria-hidden="true">{{ $i + 1 }}</span>
                                            <span class="bk-pax-who">
                                                <span class="bk-pax-num-lbl">{{ $fullName !== '' ? $fullName : $typeLabel.' '.($i + 1) }}</span>
                                                <span class="bk-pax-meta">
                                                    <span class="bk-pax-badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                                                    @if($pax['is_primary'])
                                                        <span class="bk-primary-chip">Main contact</span>
                                                    @endif
                                                    <span class="bk-pax-age">{{ $typeAge }}</span>
                                                </span>
                                            </span>
                                            <span class="bk-pax-state {{ $isComplete ? 'bk-pax-complete' : 'bk-pax-progress' }}">
                                                @if($isComplete)
                                                    <span class="bk-tick" aria-hidden="true"></span> Complete
                                                @else
                                                    {{ count($required) - $filled }} left
                                                @endif
                                            </span>
                                            <svg class="bk-pax-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                        </div>

                                        <div x-show="cardOpen" x-transition>
                                            <div class="bk-pax-body">

                                                {{-- Identity: what the traveller copies off themselves --}}
                                                <div class="bk-fieldset">
                                                    <div class="bk-fieldset-head">
                                                        <span class="bk-fieldset-title">Traveller</span>
                                                        <span class="bk-fieldset-note">Exactly as printed on the passport or ID.</span>
                                                    </div>
                                                    <div class="bk-form-grid">
                                                        <div class="bk-field">
                                                            <label class="bk-label">Title</label>
                                                            <select class="bk-select" wire:model="passengers.{{ $i }}.title">
                                                                <option value="">Select</option>
                                                                @foreach($titleOptions as $t)
                                                                    <option value="{{ $t }}">{{ $t }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error("passengers.{$i}.title") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="bk-field bk-col-2">
                                                            <label class="bk-label">First name</label>
                                                            <input class="bk-input" type="text" placeholder="As on passport"
                                                                   wire:model.blur="passengers.{{ $i }}.first_name">
                                                            @error("passengers.{$i}.first_name") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>

                                                        <div class="bk-field">
                                                            <label class="bk-label">Middle name <span class="bk-optional">Optional</span></label>
                                                            <input class="bk-input" type="text" placeholder="If any"
                                                                   wire:model.blur="passengers.{{ $i }}.middle_name">
                                                        </div>
                                                        <div class="bk-field bk-col-2">
                                                            <label class="bk-label">Last name</label>
                                                            <input class="bk-input" type="text" placeholder="As on passport"
                                                                   wire:model.blur="passengers.{{ $i }}.last_name">
                                                            @error("passengers.{$i}.last_name") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>

                                                        <div class="bk-field">
                                                            <label class="bk-label">Date of birth</label>
                                                            <input class="bk-input" type="date"
                                                                   wire:model.blur="passengers.{{ $i }}.dob"
                                                                   max="{{ now()->subDay()->format('Y-m-d') }}">
                                                            @if($pax['type'] === 'CHD') <span class="bk-hint">2-12 years old on the travel date</span>
                                                            @elseif($pax['type'] === 'INF') <span class="bk-hint">Under 2 on the travel date</span> @endif
                                                            @error("passengers.{$i}.dob") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="bk-field">
                                                            <label class="bk-label">Nationality</label>
                                                            <select class="bk-select" wire:model="passengers.{{ $i }}.nationality">
                                                                @foreach($this->nationalities as $code => $name)
                                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error("passengers.{$i}.nationality") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="bk-field">
                                                            <label class="bk-label">Gender</label>
                                                            <div class="bk-radio-group" role="radiogroup">
                                                                <label class="bk-radio-opt">
                                                                    <input type="radio" wire:model="passengers.{{ $i }}.gender" value="M" aria-label="Male">
                                                                    <span>Male</span>
                                                                </label>
                                                                <label class="bk-radio-opt">
                                                                    <input type="radio" wire:model="passengers.{{ $i }}.gender" value="F" aria-label="Female">
                                                                    <span>Female</span>
                                                                </label>
                                                            </div>
                                                            @error("passengers.{$i}.gender") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Travel document: what the traveller copies off the passport --}}
                                                <div class="bk-fieldset">
                                                    <div class="bk-fieldset-head">
                                                        <span class="bk-fieldset-title">Travel document</span>
                                                        <span class="bk-fieldset-note">The passport must stay valid for 6 months after travel.</span>
                                                    </div>
                                                    <div class="bk-form-grid">
                                                        <div class="bk-field">
                                                            <label class="bk-label">Passport number <span class="bk-optional">Optional</span></label>
                                                            <input class="bk-input" type="text" placeholder="A12345678"
                                                                   wire:model.blur="passengers.{{ $i }}.passport_no">
                                                            @error("passengers.{$i}.passport_no") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="bk-field">
                                                            <label class="bk-label">Issuing country <span class="bk-optional">Optional</span></label>
                                                            <select class="bk-select" wire:model="passengers.{{ $i }}.passport_issue_country">
                                                                @foreach($this->nationalities as $code => $name)
                                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error("passengers.{$i}.passport_issue_country") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="bk-field">
                                                            <label class="bk-label">Frequent flyer <span class="bk-optional">Optional</span></label>
                                                            <input class="bk-input" type="text" placeholder="BA12345678"
                                                                   wire:model.blur="passengers.{{ $i }}.frequent_flyer_number">
                                                            @error("passengers.{$i}.frequent_flyer_number") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>

                                                        <div class="bk-field">
                                                            <label class="bk-label">Issue date <span class="bk-optional">Optional</span></label>
                                                            <input class="bk-input" type="date"
                                                                   wire:model.blur="passengers.{{ $i }}.passport_issue_date"
                                                                   max="{{ now()->format('Y-m-d') }}">
                                                            @error("passengers.{$i}.passport_issue_date") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="bk-field">
                                                            <label class="bk-label">Expiry date <span class="bk-optional">Optional</span></label>
                                                            <input class="bk-input" type="date"
                                                                   wire:model.blur="passengers.{{ $i }}.passport_exp"
                                                                   min="{{ now()->addDay()->format('Y-m-d') }}">
                                                            @error("passengers.{$i}.passport_exp") <span class="bk-error">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>

                    {{-- ── Contact details ── --}}
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Contact details</div>
                                <div class="bk-acc-sub">Where the e-ticket and any schedule changes are sent</div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">
                                <div class="bk-contact-grid">
                                    <div class="bk-field">
                                        <label class="bk-label">Email address</label>
                                        <input class="bk-input" type="email" placeholder="you@example.com"
                                               wire:model.blur="contactEmail">
                                        @error('contactEmail') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="bk-field">
                                        <label class="bk-label">Confirm email address</label>
                                        <input class="bk-input" type="email" placeholder="Type it again"
                                               wire:model.blur="contactEmailConfirm">
                                        @error('contactEmailConfirm') <span class="bk-error">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="bk-field bk-col-half">
                                        <label class="bk-label">Mobile number</label>
                                        <input class="bk-input bk-phone-input" type="tel" placeholder="+234 800 000 0000"
                                               wire:model.blur="contactPhoneFull">
                                        <span class="bk-hint">Include the country code — the airline may use this to reach you about changes.</span>
                                        {{-- contactPhone/AreaCode/CountryCode are all derived server-side from
                                             this one input, so showing every one of their messages printed up to
                                             four errors under a single field. Only the first is useful. --}}
                                        @php
                                            $phoneError = $errors->first('contactPhoneFull')
                                                ?: $errors->first('contactPhone')
                                                ?: $errors->first('contactAreaCode')
                                                ?: $errors->first('contactCountryCode');
                                        @endphp
                                        @if($phoneError) <span class="bk-error">{{ $phoneError }}</span> @endif
                                    </div>
                                </div>

                                {{-- Kept in the DOM: the controller still binds these split fields, and
                                     contactPhoneFull is derived from them server-side. --}}
                                <div class="bk-field" style="display:none;">
                                    <label class="bk-label">Area code</label>
                                    <input class="bk-input" type="text" wire:model.blur="contactAreaCode">
                                </div>
                                <div class="bk-field" style="display:none;">
                                    <label class="bk-label">Mobile number</label>
                                    <input class="bk-input" type="tel" wire:model.blur="contactPhone">
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- Actions --}}
                    <div class="bk-actions">
                        <a href="{{ route('air.flight-s') }}" class="bk-btn-ghost">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Go Back
                        </a>
                        <button class="bk-btn-next"
                                wire:click="proceed"
                                wire:loading.attr="disabled"
                                wire:target="proceed">
                            <span wire:loading.remove wire:target="proceed" style="display:inline-flex;align-items:center;gap:7px; color:#fff;">
                                Continue
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
                            <span wire:loading wire:target="proceed">Validating...</span>
                        </button>
                    </div>



                    @endif {{-- /STEP 1 TRAVELLER DETAILS --}}
                @endif {{-- /STEPS 1 AND 2 --}}

                {{-- ════════ STEP 2 ════════ --}}
                @if($step === 3)

                    <div class="bk-notice info" style="margin-bottom:4px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>
                            {{ $isTravelFlexCheckout
                                ? 'Review all details carefully before continuing to TravelFlex. No payment is due at this step.'
                                : 'Review all details carefully before payment. Name corrections after ticketing may incur fees.' }}
                        </span>
                    </div>

                    {{-- Review accordion --}}
                    <div class="bk-acc" x-data="{ open: true }">
                        <div class="bk-acc-head" :class="{ open }" @click="open = !open">
                            <div class="bk-acc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div class="bk-acc-title">Booking Summary</div>
                                <div class="bk-acc-sub">
                                    {{ $isTravelFlexCheckout
                                        ? 'Confirm all details before starting your TravelFlex application'
                                        : 'Confirm all details are correct before paying' }}
                                </div>
                            </div>
                            <svg class="bk-acc-chevron" :class="{ open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div x-show="open" x-transition>
                            <div class="bk-acc-body">

                                <div class="bk-review-section">
                                    <div class="bk-review-title">Passengers ({{ $this->getTotalPassengers() }})</div>
                                    @foreach($this->passengers as $i => $pax)
                                        @php
                                            $ptLabel = match($pax['type']) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' };
                                            $dobStr  = !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '-';
                                            $natName = $this->nationalities[$pax['nationality']] ?? $pax['nationality'];
                                        @endphp
                                        <div class="bk-review-row">
                                            <span class="bk-review-label">{{ $ptLabel }} {{ $i + 1 }}{{ $pax['is_primary'] ? ' · Primary' : '' }}</span>
                                            <span class="bk-review-val">
                                                {{ $pax['title'] }} {{ strtoupper($pax['first_name']) }} {{ strtoupper($pax['last_name']) }}
                                                <br><span style="font-size:11px;color:var(--gray-500);font-weight:500;">DOB: {{ $dobStr }} · {{ $natName }}@if(!empty($pax['passport_no'])) · Passport: {{ $pax['passport_no'] }} @endif</span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="bk-review-section">
                                    <div class="bk-review-title">Contact</div>
                                    <div class="bk-review-row">
                                        <span class="bk-review-label">Email</span>
                                        <span class="bk-review-val">{{ $contactEmail }}</span>
                                    </div>
                                    <div class="bk-review-row">
                                        <span class="bk-review-label">Phone</span>
                                        <span class="bk-review-val">{{ $contactPhoneFull ?: ('+' . $contactCountryCode . ' ' . $contactPhone) }}</span>
                                    </div>
                                </div>

                                <div class="bk-review-section" style="margin-bottom:0;">
                                    <div class="bk-review-title">Fare Policy</div>
                                    @foreach($breakdown as $fb)
                                        @php
                                            $ptl    = match($fb['passengerType'] ?? '') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                                            $bagStr = implode(' / ', array_unique(array_filter((array)($fb['baggage'] ?? []), fn($v) => $v !== ''))) ?: '-';
                                            $refund = !empty($fb['refundAllowed']);
                                            $change = !empty($fb['changeAllowed']);
                                        @endphp
                                        <div class="bk-review-row">
                                            <span class="bk-review-label">{{ $ptl }} · Baggage</span>
                                            <span class="bk-review-val">{{ $bagStr }}</span>
                                        </div>
                                    @endforeach
                                    <div class="bk-review-row">
                                            <span class="bk-review-label">Refund</span>
                                            <span class="bk-review-val" style="color:{{ !empty($flight['isRefundable']) ? 'var(--green)' : 'var(--red)' }}">{{ !empty($flight['isRefundable']) ? 'Allowed' : 'Not allowed' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="bk-form" method="POST" action="{{ route('flights.book') }}" style="display:none;">
                        @csrf
                        <input type="hidden" name="fare_source_code" value="{{ $flight['flight']['fareSourceCode'] ?? $flight['fareSourceCode'] ?? '' }}">
                        <input type="hidden" name="session_id"            value="{{ $sessionId }}">
                        <input type="hidden" name="intent"                value="{{ session('bookingIntent', 'booking') }}">
                        <input type="hidden" name="contact[email]"        value="{{ $contactEmail }}">
                        <input type="hidden" name="contact[phone]"        value="{{ $contactPhone }}">
                        <input type="hidden" name="contact[area_code]"    value="{{ $contactAreaCode }}">
                        <input type="hidden" name="contact[country_code]" value="{{ $contactCountryCode }}">
                        @foreach($this->passengers as $i => $pax)
                        @foreach([
                        'type','title','first_name','middle_name','last_name',
                        'gender','dob','nationality',
                        'passport_no','passport_issue_country','passport_issue_date','passport_exp', 'frequent_flyer_number'
                        ] as $field)
                        <input type="hidden" name="passengers[{{ $i }}][{{ $field }}]" value="{{ $pax[$field] ?? '' }}">
                        @endforeach
                        @endforeach

                        {{-- ── Extra Services (baggage) ── --}}
                        @foreach($selectedBaggage as $direction => $items)
                            @foreach($items as $svcId => $qty)
                                @if($qty > 0)
                                <input type="hidden" name="extra_baggage[{{ $direction }}][{{ $svcId }}]" value="{{ $qty }}">
                                @endif
                            @endforeach
                        @endforeach

                        {{-- ── Extra Services (meals) ── --}}
                        @foreach($selectedMeals as $direction => $segments)
                            @foreach($segments as $segmentIndex => $items)
                                @foreach($items as $svcId => $checked)
                                    @if($checked)
                                    <input type="hidden" name="extra_meal[{{ $direction }}][{{ $segmentIndex }}][{{ $svcId }}]" value="1">
                                    @endif
                                @endforeach
                            @endforeach
                        @endforeach
                    </form>



                    <div class="bk-actions">
                        <button class="bk-btn-ghost" wire:click="back">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Edit Details
                        </button>
                        <button class="bk-btn-pay" @click="submitForm()">
                            @if($isTravelFlexCheckout)
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                Continue to TravelFlex
                            @else
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                Confirm &amp; Pay {{ $fmt($this->getTotalPrice()) }}
                            @endif
                        </button>
                    </div>

                @endif {{-- /step 2 --}}

            </div>{{-- /bk-main --}}


            {{-- ══════════════ RIGHT RAIL: MY CART ══════════════ --}}
            <aside class="bk-rail">

                {{-- My Cart --}}
                <div class="bk-cart">
                    <div class="bk-cart-head">
                        <span class="bk-cart-icon" aria-hidden="true"></span>
                        <div class="bk-cart-head-txt">
                            <div class="bk-cart-title">Booking summary</div>
                            <div class="bk-cart-subtitle">
                                {{ $isTravelFlexCheckout
                                    ? 'Reviewed before your TravelFlex application'
                                    : 'Reviewed before payment' }}
                            </div>
                        </div>
                    </div>
                    <div class="bk-cart-body">
                        <div class="bk-cart-section">
                            <div class="bk-cart-section-lbl">Your flight</div>

                            @if($isMulti && !empty($allLegs))
                                @foreach($allLegs as $leg)
                                    <div class="bk-cart-flight-row">
                                        <span class="bk-cart-plane"><span class="bk-icon-mask bk-icon-plane" style="width:14px;height:14px;" aria-hidden="true"></span></span>
                                        <div>
                                            <div class="bk-cart-route">{{ $leg['route'] ?? '' }}</div>
                                            <div class="bk-cart-sub">{{ $leg['label'] ?? 'Multi-city' }} &middot; {{ $cabin }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="bk-cart-flight-row">
                                    <span class="bk-cart-plane"><span class="bk-icon-mask bk-icon-plane" style="width:14px;height:14px;" aria-hidden="true"></span></span>
                                    <div>
                                        <div class="bk-cart-route">{{ ($firstSeg['from'] ?? '') }} to {{ ($lastSeg['to'] ?? '') }}</div>
                                        <div class="bk-cart-sub">
                                            @if(!empty($flight['departDateLabel'])){{ $flight['departDateLabel'] }} &middot; @endif{{ $tripLabel }} &middot; {{ $cabin }}
                                        </div>
                                    </div>
                                </div>

                                @if($isReturn && !empty($retSegs))
                                    <div class="bk-cart-flight-row">
                                        <span class="bk-cart-plane" style="transform:scaleX(-1);"><span class="bk-icon-mask bk-icon-plane" style="width:14px;height:14px;" aria-hidden="true"></span></span>
                                        <div>
                                            <div class="bk-cart-route">{{ ($retSegs[0]['from'] ?? '') }} to {{ ($retSegs[count($retSegs)-1]['to'] ?? '') }}</div>
                                            <div class="bk-cart-sub">
                                                @if(!empty($flight['returnDateLabel'])){{ $flight['returnDateLabel'] }} &middot; @endif Return &middot; {{ $cabin }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Fare summary using real revalidate data --}}


                    <div class="bk-fare-section">
                        <div class="bk-fare-title">Fare breakdown</div>

                        {{-- SkyLink-sourced fares carry no real per-passenger-type tax/markup
                             breakdown (no such endpoint exists for that supplier) — show one
                             blended total instead. This used to be guarded by empty($breakdown)
                             alone, back when SkyLink's fareBreakdown was always []; once that
                             got populated (for the Fare Rules tab, which only needs
                             baggage/refund info, not fare math) this branch stopped firing for
                             SkyLink and the per-type branch below rendered instead — with no tax
                             data to work with it silently showed "Taxes & Fees: ₦0.00" per
                             passenger while Trip Total (computed separately) legitimately
                             included real taxes and markup, so the two did not reconcile on
                             screen. Trip Total is driven by $this->getTotalPrice(), not this
                             section, so the charged amount was never wrong — only this display. --}}
                        @if(empty($breakdown) || ($mappedFlight['source'] ?? null) === 'skylink')
                            @php
                                $blendedPax = max(1, (int) ($searchParams['adults'] ?? 1) + (int) ($searchParams['childs'] ?? 0) + (int) ($searchParams['kids'] ?? 0));
                                // Whatever sits between the base fare and what is actually
                                // charged is taxes, carrier fees and our service charge. It was
                                // previously left off the summary entirely, so a base fare and a
                                // trip total appeared with an unexplained gap between them.
                                $totalFees = max(0, $totalPrice - $totalBase);
                            @endphp
                            <div>
                                <div class="bk-fare-row">
                                    <span class="bk-fare-lbl">Base fare</span>
                                    <span class="bk-fare-val">{{ $fmt($totalBase) }}</span>
                                </div>
                                @if($totalFees > 0)
                                    <div class="bk-fare-row">
                                        <span class="bk-fare-lbl">Taxes, fees and charges</span>
                                        <span class="bk-fare-val">{{ $fmt($totalFees) }}</span>
                                    </div>
                                @endif
                                {{-- Only worth a line of its own when it differs from the total
                                     below; with no extras or discount the two are the same
                                     number printed twice, a few pixels apart. --}}
                                @if($extrasTotal > 0 || $discount > 0)
                                    <div class="bk-fare-row sum">
                                        <span class="bk-fare-lbl">Flight for {{ $blendedPax }} {{ $blendedPax === 1 ? 'traveller' : 'travellers' }}</span>
                                        <span class="bk-fare-val">{{ $fmt($totalPrice) }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- The per-type breakdown below needs real tax/markup data per
                             passenger, which SkyLink's fareBreakdown doesn't carry (see the
                             comment above) — skip it there so the blended row above isn't
                             immediately followed by a second, non-reconciling summary. --}}
                        @foreach(($mappedFlight['source'] ?? null) === 'skylink' ? [] : $breakdown as $fb)
                        @php
                            $ptCode  = $fb['passengerType'] ?? ($fb['PassengerTypeQuantity']['Code'] ?? 'ADT');
                            $ptQty   = (int)($fb['qty'] ?? ($fb['PassengerTypeQuantity']['Quantity'] ?? 1));
                            $ptLabel = match($ptCode) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                            $paxFare = $fb['PassengerFare'] ?? [];
                            $base    = (float)($fb['baseFare'] ?? ($paxFare['BaseFare']['Amount'] ?? 0));
                            $taxes   = $fb['taxes'] ?? ($paxFare['Taxes'] ?? []);
                            $serviceTax  = (float)($fb['serviceTax']  ?? ($paxFare['ServiceTax']['Amount']  ?? 0));
                            $surcharges  = (float)($fb['surcharges']  ?? ($paxFare['Surcharges']['Amount']  ?? 0));

                            $taxGroups = [];
                            foreach ($taxes as $tax) {
                                $code = $tax['TaxCode'] ?? 'OtherTaxes';
                                $taxGroups[$code] = ($taxGroups[$code] ?? 0) + (float)($tax['Amount'] ?? 0);
                            }
                            $rawTaxBreakdownTotal = array_sum($taxGroups);
                            $rawTotalPaxFare = (float)($fb['totalFare'] ?? ($paxFare['TotalFare']['Amount'] ?? 0));
                            $hasNegativeTaxLine = collect($taxGroups)->contains(fn($amount) => $amount < 0);
                            $preferredTaxTotal = $serviceTax > 0
                                ? $serviceTax + $surcharges
                                : ($hasNegativeTaxLine ? 0.0 : max($rawTaxBreakdownTotal + $surcharges, 0));

                            if ($rawTotalPaxFare <= 0) { $totalPaxFare = $base + $preferredTaxTotal; }
                            elseif ($rawTotalPaxFare < $base) { $totalPaxFare = $base + $rawTotalPaxFare; }
                            else { $totalPaxFare = $rawTotalPaxFare; }

                            $derivedTaxAmt = max($totalPaxFare - $base, 0);
                            if ($hasNegativeTaxLine || $rawTaxBreakdownTotal <= 0 || abs($preferredTaxTotal - $derivedTaxAmt) > 1) {
                                $taxGroups   = $derivedTaxAmt > 0 ? ['OtherTaxes' => $derivedTaxAmt] : [];
                                $totalTaxAmt = $derivedTaxAmt;
                            } else {
                                $totalTaxAmt = $preferredTaxTotal;
                            }
                            $baseTotal = $base * $ptQty;
                            $taxTotal  = $totalTaxAmt * $ptQty;
                            $bagArr = (array)($fb['baggage']      ?? ($fb['Baggage']      ?? []));
                            $cabArr = (array)($fb['cabinBaggage'] ?? ($fb['CabinBaggage'] ?? []));
                            $bagStr = implode(', ', array_unique(array_filter($bagArr))) ?: '-';
                            $cabStr = implode(', ', array_unique(array_filter($cabArr))) ?: '-';
                        @endphp

                        <div style="padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid var(--gray-100);"
                            x-data="{ showTax: false }">

                            <div class="bk-fare-row" style="padding-bottom:4px;">
                                <span class="bk-fare-lbl" style="font-weight:700;color:var(--gray-700);">{{ $ptLabel }} x {{ $ptQty }}</span>
                                <span class="bk-fare-val" style="font-weight:800;">{{ $fmt($totalPaxFare * $ptQty) }}</span>
                            </div>
                            <div class="bk-fare-row">
                                <span class="bk-fare-lbl">Base Fare</span>
                                <span class="bk-fare-val">{{ $fmt($baseTotal) }}</span>
                            </div>
                            <div class="bk-fare-row">
                                <span class="bk-fare-lbl">
                                    Taxes & Fees
                                    <button type="button" @click="showTax = !showTax"
                                        style="background:none;border:none;color:var(--blue);cursor:pointer;
                                            font-size:11px;font-family:var(--font);padding:0;margin-left:4px;"
                                        x-text="showTax ? 'Hide' : 'Breakdown'">
                                    </button>
                                </span>
                                <span class="bk-fare-val">{{ $fmt($taxTotal) }}</span>
                            </div>
                            <div x-show="showTax" x-transition style="margin:4px 0 2px;">
                                <div class="bk-tax-detail">
                                    @foreach($taxGroups as $code => $amount)
                                    <div class="bk-tax-row">
                                        <span class="bk-tax-lbl">
                                            {{ $taxLabels[$code] ?? $code }}
                                            <span class="bk-tax-code">({{ $code }})</span>
                                        </span>
                                        <span class="bk-tax-val">{{ $fmt($amount * $ptQty) }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--green);font-weight:600;background:var(--green-lt);padding:2px 8px;border-radius:999px;">
                                    <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span> {{ $bagStr }}
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--blue);font-weight:600;background:var(--blue-lt);padding:2px 8px;border-radius:999px;">
                                    <span class="bk-mini-icon bk-icon-cabin" aria-hidden="true"></span> {{ $cabStr }}
                                </span>
                            </div>
                        </div>
                        @endforeach

                        @if($extrasTotal > 0)
                        <div style="padding:10px 0;border-bottom:1px solid var(--gray-100);">
                            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;
                                        color:var(--gray-400);margin-bottom:8px;">Extras Added</div>

                            {{-- Baggage lines --}}
                            @foreach($selectedBaggage as $dir => $items)
                                @foreach($items as $svcId => $qty)
                                    @if($qty > 0)
                                    @php
                                        // Find the service from parsed extras
                                        $matchedSvc = null;
                                        foreach ($dynBaggage as $bag) {
                                            $bagDir = str_contains(strtoupper($bag['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                                            if ($bagDir === $dir) {
                                                foreach (($bag['Services'][0] ?? []) as $s) {
                                                    if ((string)$s['ServiceId'] === (string)$svcId) { $matchedSvc = $s; break 2; }
                                                }
                                            }
                                        }
                                        $bagPrice = (float) ($matchedSvc['ServiceCost']['Amount'] ?? 0);
                                        $bagCurr  = $matchedSvc['ServiceCost']['CurrencyCode'] ?? 'USD';
                                        $bagSym   = match($bagCurr) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'AED' => 'AED ', default => $bagCurr . ' ' };
                                        $lineTotal = $bagPrice * (int)$qty;
                                    @endphp
                                    <div class="bk-fare-row" style="padding:3px 0;">
                                        <span class="bk-fare-lbl" style="font-size:11.5px;">
                                            <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span> {{ $matchedSvc['Description'] ?? 'Baggage' }}
                                            <span style="color:var(--gray-400);font-size:10.5px;">x {{ $qty }} ({{ ucfirst($dir) }})</span>
                                        </span>
                                        <span class="bk-fare-val" style="color:var(--green);">
                                            +{{ $bagSym }}{{ number_format($lineTotal, 2) }}
                                        </span>
                                    </div>
                                    @endif
                                @endforeach
                            @endforeach

                            {{-- Meal lines --}}
                            @foreach($selectedMeals as $dir => $segments)
                                @foreach($segments as $si => $items)
                                    @foreach($items as $svcId => $checked)
                                        @if($checked)
                                        @php
                                            $matchedMeal = null;
                                            foreach ($dynMeal as $meal) {
                                                $mDir = str_contains(strtoupper($meal['Behavior'] ?? ''), 'OUTBOUND') ? 'outbound' : 'inbound';
                                                if ($mDir === $dir) {
                                                    foreach (($meal['Services'][$si] ?? []) as $s) {
                                                        if ((string)$s['ServiceId'] === (string)$svcId) { $matchedMeal = $s; break 2; }
                                                    }
                                                }
                                            }
                                            $mealPrice = (float) ($matchedMeal['ServiceCost']['Amount'] ?? 0);
                                            $mealCurr  = $matchedMeal['ServiceCost']['CurrencyCode'] ?? 'AED';
                                            $mealSym   = match($mealCurr) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'AED' => 'AED ', default => $mealCurr . ' ' };
                                        @endphp
                                        <div class="bk-fare-row" style="padding:3px 0;">
                                            <span class="bk-fare-lbl" style="font-size:11.5px;">
                                                <span class="bk-mini-icon bk-icon-meal" aria-hidden="true"></span> {{ $matchedMeal['Description'] ?? 'Meal' }}
                                                <span style="color:var(--gray-400);font-size:10.5px;">(Seg {{ $si + 1 }}, {{ ucfirst($dir) }})</span>
                                            </span>
                                            <span class="bk-fare-val" style="color:var(--amber);">
                                                +{{ $mealSym }}{{ number_format($mealPrice, 2) }}
                                            </span>
                                        </div>
                                        @endif
                                    @endforeach
                                @endforeach
                            @endforeach

                            {{-- Extras subtotal --}}
                            <div class="bk-fare-row" style="padding-top:6px;border-top:1px dashed var(--gray-200);margin-top:4px;">
                                <span class="bk-fare-lbl" style="font-weight:700;color:var(--gray-700);">Extras Subtotal</span>
                                <span class="bk-fare-val" style="font-weight:800;color:var(--green);">
                                    +{{ $esFmt(['ServiceCost' => ['CurrencyCode' => 'USD', 'Amount' => $extrasTotal]]) }}
                                </span>
                            </div>
                        </div>
                        @endif

                        @if($discount > 0)
                        <div class="bk-fare-row">
                            <span class="bk-fare-lbl bk-fare-disc">Discount</span>
                            <span class="bk-fare-val bk-fare-disc">-{{ $fmt($discount) }}</span>
                        </div>
                        @endif
                    </div>

                    @php
                        $payNow  = (float) $this->getTotalPrice();
                        $payPax  = max(1, $this->getTotalPassengers());
                    @endphp
                    <div class="bk-fare-total-row">
                        <div>
                            <span class="bk-fare-total-lbl">Total to pay</span>
                            <div class="bk-fare-total-note">
                                @if($extrasTotal > 0)
                                    Flight and extras
                                @elseif($payPax > 1)
                                    {{ $fmt($payNow / $payPax) }} per traveller
                                @else
                                    All taxes and charges included
                                @endif
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <span class="bk-fare-total-val">{{ $fmt($payNow) }}</span>
                            @if($extrasTotal > 0)
                                <div class="bk-fare-total-note">
                                    incl. {{ $esFmt(['ServiceCost' => ['CurrencyCode' => 'USD', 'Amount' => $extrasTotal]]) }} extras
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Promo codes --}}
                    <div class="bk-promo">
                        <details>
                            <summary class="bk-promo-summary">Have a promo code?</summary>
                            <div class="bk-promo-row">
                                <input class="bk-promo-input" type="text" placeholder="Enter promo code">
                                <button class="bk-promo-btn" type="button">Apply</button>
                            </div>
                        </details>
                    </div>

                    <div class="bk-rail-trust">
                        <div class="bk-rail-trust-item">
                            <svg class="bk-rail-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 13c0 5-3.5 7.5-7.5 8.8a1.5 1.5 0 0 1-1 0C7.5 20.5 4 18 4 13V6.5a1.5 1.5 0 0 1 1-1.4l6.5-2.4a1.5 1.5 0 0 1 1 0L19 5.1a1.5 1.5 0 0 1 1 1.4V13Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                            <span>Secure checkout. Your fare is reviewed before ticketing.</span>
                        </div>
                        <div class="bk-rail-trust-item">
                            <svg class="bk-rail-trust-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/>
                                <path d="M8 9h8"/>
                                <path d="M8 13h5"/>
                            </svg>
                            <span>Need help? TravelWheel support is available for booking questions.</span>
                        </div>
                    </div>
                </div>

            </aside>

        </div>{{-- /bk-page --}}
    </div>{{-- /bk-wrap --}}
</div>{{-- /Livewire root --}}
