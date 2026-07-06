<style>
    .protocol-page {
        background: #f6f8f7;
        color: #14201b;
        margin-top: 100px;
    }

    .protocol-wrap {
        max-width: 1180px;
        margin: 0 auto;
        padding: 32px 16px 56px;
    }

    .protocol-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
        gap: 24px;
        align-items: stretch;
        margin-bottom: 24px;
    }

    .protocol-hero-main,
    .protocol-panel,
    .protocol-plan,
    .protocol-summary,
    .protocol-success {
        background: #fff;
        border: 1px solid #dfe8e3;
        border-radius: 8px;
        box-shadow: 0 14px 34px rgba(16, 33, 25, .08);
    }

    .protocol-hero-main {
        padding: 28px;
        position: relative;
        overflow: hidden;
    }

    .protocol-hero-main:before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 6px;
        background: #0d9c53;
    }

    .protocol-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0b7a42;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .protocol-title {
        font-size: 18px;
        line-height: 1.15;
        margin: 0 0 12px;
        font-weight: 800;
        color: #102119;
    }

    .protocol-copy {
        color: #627069;
        line-height: 1.7;
        margin: 0;
    }

    .protocol-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 22px;
    }

    .protocol-stat {
        border: 1px solid #e4eee8;
        border-radius: 8px;
        padding: 12px;
        background: #fbfdfc;
        min-height: 82px;
    }

    .protocol-stat i,
    .protocol-feature-icon,
    .protocol-step-icon {
        color: #0d9c53;
    }

    .protocol-stat strong {
        display: block;
        font-size: 13px;
        margin-top: 6px;
    }

    .protocol-stat span {
        color: #74817a;
        font-size: 12px;
    }

    .protocol-form-card {
        padding: 22px;
    }

    .protocol-panel {
        padding: 22px;
        height: auto;
    }

    .protocol-panel-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #102119;
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .protocol-field {
        margin-bottom: 16px;
    }

    .protocol-field label,
    .protocol-label {
        color: #425049;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .protocol-page .form-control,
    .protocol-page .form-select {
        min-height: 46px;
        border: 1px solid #d7e2dc;
        border-radius: 8px;
        color: #15231c;
        box-shadow: none;
    }

    .protocol-page .form-control:focus,
    .protocol-page .form-select:focus {
        border-color: #0d9c53;
        box-shadow: 0 0 0 .18rem rgba(13, 156, 83, .12);
    }

    .protocol-btn,
    .protocol-page .btn-success {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        border-radius: 8px;
        border: 0;
        background: #0d9c53;
        color: #fff;
        font-weight: 800;
        padding: 10px 18px;
        text-decoration: none;
    }

    .protocol-btn:hover,
    .protocol-page .btn-success:hover {
        background: #087c41;
        color: #fff;
    }

    .protocol-btn-secondary {
        background: #eef5f1;
        color: #0b6f3d;
    }

    .protocol-btn-secondary:hover {
        background: #dcebe4;
        color: #084b2b;
    }

    .protocol-grid {
        display: grid;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        gap: 24px;
    }

    .protocol-list {
        display: grid;
        gap: 10px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .protocol-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #53615a;
        line-height: 1.45;
    }

    .protocol-list i {
        color: #0d9c53;
        margin-top: 2px;
        font-size: 18px;
        flex: 0 0 auto;
    }

    .protocol-note {
        border-left: 4px solid #d39b18;
        background: #fff8e7;
        color: #6d5212;
        padding: 12px 14px;
        border-radius: 8px;
        margin-top: 18px;
        font-size: 13px;
    }

    .protocol-list-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 18px;
        margin-top: 16px;
    }

    .protocol-list-row li {
        flex: 0 0 auto;
        font-size: 13px;
    }

    .protocol-plan-simple {
        background: #fff;
        border: 1px solid rgba(13, 156, 83, .45);
        border-radius: 8px;
        box-shadow: 0 16px 40px rgba(13, 156, 83, .14);
        padding: 22px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 10px;
    }

    .protocol-plan-simple .protocol-price {
        margin: 0;
    }

    .protocol-plan-simple-plain {
        border-color: #dfe8e3;
        box-shadow: 0 14px 34px rgba(16, 33, 25, .08);
    }

    .protocol-plans-row {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 20px;
        margin-bottom: 24px;
    }

    .protocol-plans-row .protocol-hero-main {
        flex: 1 1 320px;
    }

    .protocol-plans-row .protocol-plan-simple {
        flex: 1 1 220px;
        max-width: 260px;
    }

    .protocol-plan-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .protocol-plan {
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .protocol-plan-featured {
        border-color: rgba(13, 156, 83, .45);
        box-shadow: 0 16px 40px rgba(13, 156, 83, .14);
    }

    .protocol-plan-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .protocol-plan h4 {
        font-size: 20px;
        font-weight: 800;
        margin: 0;
    }

    .protocol-price {
        font-size: 30px;
        font-weight: 900;
        color: #102119;
        margin: 0;
    }

    .protocol-price small {
        color: #7a867f;
        font-size: 13px;
        font-weight: 600;
    }

    .protocol-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        background: #eef7f2;
        color: #0b7a42;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .protocol-steps {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .protocol-step {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #dfe8e3;
        background: #fff;
        border-radius: 999px;
        padding: 8px 12px;
        color: #53615a;
        font-size: 13px;
        font-weight: 700;
    }

    .protocol-step-active {
        background: #0d9c53;
        border-color: #0d9c53;
        color: #fff;
    }

    .protocol-step-active i {
        color: #fff;
    }

    .protocol-checkout-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 24px;
        align-items: start;
    }

    .protocol-payment-grid {
        display: grid;
        grid-template-columns: minmax(0, 4fr) minmax(0, 6fr);
        gap: 24px;
        align-items: start;
    }

    .protocol-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 800;
        color: #102119;
        margin-bottom: 14px;
    }

    .protocol-subpanel {
        border: 1px solid #e0e9e4;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
        background: #fbfdfc;
    }

    .protocol-detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .protocol-detail {
        border-bottom: 1px solid #dfe8e3;
        padding-bottom: 8px;
        min-width: 0;
    }

    .protocol-detail span {
        display: block;
        color: #75817b;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .protocol-detail strong {
        display: block;
        color: #17241d;
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .protocol-total-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        color: #53615a;
        margin-bottom: 12px;
    }

    .protocol-grand-total {
        border-top: 1px solid #dfe8e3;
        padding-top: 14px;
        margin-top: 14px;
        font-size: 18px;
        color: #102119;
        font-weight: 900;
    }

    .protocol-success {
        padding: 34px;
        text-align: center;
        max-width: 720px;
        margin: 0 auto;
    }

    .protocol-success-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e9f8ef;
        color: #0d9c53;
        font-size: 42px;
        margin-bottom: 16px;
    }

    .protocol-hide {
        display: none;
    }

    .ph-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-style: normal;
    }

    .ph-icon svg {
        width: 1em;
        height: 1em;
        fill: currentColor;
    }

    .protocol-widget-hero {
        min-height: 430px;
        background:
            linear-gradient(180deg, rgba(12, 32, 23, .58), rgba(12, 32, 23, .30)),
            url("{{ asset('assets/image/Protocol.jpg') }}") center / cover no-repeat;
        padding: 44px 16px 58px;
        display: flex;
        align-items: center;
    }

    .protocol-widget-inner {
        width: min(1120px, 100%);
        margin: 0 auto;
    }

    .protocol-widget-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 10px;
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(255, 255, 255, .78);
        border-radius: 8px 8px 0 0;
        scrollbar-width: none;
    }

    .protocol-widget-tabs::-webkit-scrollbar {
        display: none;
    }

    .protocol-widget-tabs a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
        color: #344139;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        padding: 9px 12px;
        border-radius: 8px;
    }

    .protocol-widget-tabs a.active,
    .protocol-widget-tabs a:hover {
        background: #eaf7f0;
        color: #0b7a42;
    }

    .protocol-widget-tabs img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .protocol-widget-card {
        background: #fff;
        border: 1px solid rgba(255, 255, 255, .88);
        border-radius: 0 0 8px 8px;
        box-shadow: 0 22px 48px rgba(8, 26, 18, .22);
        padding: 22px;
    }

    .protocol-widget-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .protocol-widget-head h1 {
        font-size: 24px;
        line-height: 1.2;
        margin: 0;
        color: #102119;
        font-weight: 900;
    }

    .protocol-widget-head span {
        display: block;
        color: #0b7a42;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 4px;
    }

    .protocol-widget-fields {
        display: grid;
        grid-template-columns: minmax(220px, 1.15fr) minmax(190px, .9fr) minmax(190px, .9fr) auto;
        gap: 12px;
        align-items: end;
    }

    .protocol-widget-field label {
        display: block;
        color: #425049;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .protocol-widget-submit {
        min-width: 150px;
        height: 46px;
    }

    .protocol-vw-fields {
        grid-template-columns: 1.2fr 1fr 1fr;
    }

    .protocol-vw-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 22px;
    }

    .protocol-vw-search {
        width: 214px;
        height: 64px;
    }

    .protocol-vw-search:disabled {
        cursor: not-allowed;
        opacity: .55;
        transform: none;
    }

    @media (max-width: 991px) {
        .protocol-hero,
        .protocol-grid,
        .protocol-checkout-grid,
        .protocol-payment-grid,
        .protocol-widget-fields,
        .protocol-vw-fields {
            grid-template-columns: 1fr;
        }

        .protocol-plan-grid,
        .protocol-detail-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .protocol-wrap {
            padding: 18px 12px 40px;
        }

        .protocol-hero-main,
        .protocol-panel,
        .protocol-plan,
        .protocol-summary,
        .protocol-success {
            padding: 18px;
        }

        .protocol-title {
            font-size: 13px;
        }

        .protocol-stat-grid {
            grid-template-columns: 1fr;
        }

        .protocol-widget-hero {
            min-height: auto;
            padding: 24px 12px 36px;
        }

        .protocol-widget-card {
            padding: 16px;
        }

        .protocol-widget-head {
            display: block;
        }

        .protocol-vw-search {
            width: 100%;
            height: 56px;
        }
    }
</style>
