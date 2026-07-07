<style>
    .lounge-page {
        background: #f6f8f7;
        color: #14201b;
        margin-top: 100px;
    }

    .lounge-wrap {
        max-width: 1180px;
        margin: 0 auto;
        padding: 32px 16px 56px;
    }

    .lounge-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
        gap: 24px;
        align-items: stretch;
        margin-bottom: 24px;
    }

    .lounge-hero-main,
    .lounge-panel,
    .lounge-plan,
    .lounge-summary,
    .lounge-success {
        background: #fff;
        border: 1px solid #dfe8e3;
        border-radius: 8px;
        box-shadow: 0 14px 34px rgba(16, 33, 25, .08);
    }

    .lounge-hero-main {
        padding: 28px;
        position: relative;
        overflow: hidden;
    }

    .lounge-hero-main:before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 6px;
        background: #0d9c53;
    }

    .lounge-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0b7a42;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .lounge-title {
        font-size: 18px;
        line-height: 1.15;
        margin: 0 0 12px;
        font-weight: 800;
        color: #102119;
    }

    .lounge-copy {
        color: #627069;
        line-height: 1.7;
        margin: 0;
    }

    .lounge-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 22px;
    }

    .lounge-stat {
        border: 1px solid #e4eee8;
        border-radius: 8px;
        padding: 12px;
        background: #fbfdfc;
        min-height: 82px;
    }

    .lounge-stat i,
    .lounge-feature-icon,
    .lounge-step-icon {
        color: #0d9c53;
    }

    .lounge-stat strong {
        display: block;
        font-size: 13px;
        margin-top: 6px;
    }

    .lounge-stat span {
        color: #74817a;
        font-size: 12px;
    }

    .lounge-form-card {
        padding: 22px;
    }

    .lounge-panel {
        padding: 22px;
        height: auto;
    }

    .lounge-panel-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #102119;
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .lounge-field {
        margin-bottom: 16px;
    }

    .lounge-field label,
    .lounge-label {
        color: #425049;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .lounge-page .form-control,
    .lounge-page .form-select {
        min-height: 46px;
        border: 1px solid #d7e2dc;
        border-radius: 8px;
        color: #15231c;
        box-shadow: none;
    }

    .lounge-page .form-control:focus,
    .lounge-page .form-select:focus {
        border-color: #0d9c53;
        box-shadow: 0 0 0 .18rem rgba(13, 156, 83, .12);
    }

    .lounge-btn,
    .lounge-page .btn-success {
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

    .lounge-btn:hover,
    .lounge-page .btn-success:hover {
        background: #087c41;
        color: #fff;
    }

    .lounge-btn-secondary {
        background: #eef5f1;
        color: #0b6f3d;
    }

    .lounge-btn-secondary:hover {
        background: #dcebe4;
        color: #084b2b;
    }

    .lounge-grid {
        display: grid;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        gap: 24px;
    }

    .lounge-list {
        display: grid;
        gap: 10px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .lounge-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #53615a;
        line-height: 1.45;
    }

    .lounge-list i {
        color: #0d9c53;
        margin-top: 2px;
        font-size: 18px;
        flex: 0 0 auto;
    }

    .lounge-note {
        border-left: 4px solid #d39b18;
        background: #fff8e7;
        color: #6d5212;
        padding: 12px 14px;
        border-radius: 8px;
        margin-top: 18px;
        font-size: 13px;
    }

    .lounge-list-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 18px;
        margin-top: 16px;
    }

    .lounge-list-row li {
        flex: 0 0 auto;
        font-size: 13px;
    }

    .lounge-plan-simple {
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

    .lounge-plan-simple .lounge-price {
        margin: 0;
    }

    .lounge-plan-simple-plain {
        border-color: #dfe8e3;
        box-shadow: 0 14px 34px rgba(16, 33, 25, .08);
    }

    .lounge-plans-row {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 20px;
        margin-bottom: 24px;
    }

    .lounge-plans-row .lounge-hero-main {
        flex: 1 1 320px;
    }

    .lounge-plans-row .lounge-plan-simple {
        flex: 1 1 220px;
        max-width: 260px;
    }

    .lounge-plan-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .lounge-plan {
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .lounge-plan-featured {
        border-color: rgba(13, 156, 83, .45);
        box-shadow: 0 16px 40px rgba(13, 156, 83, .14);
    }

    .lounge-plan-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .lounge-plan h4 {
        font-size: 20px;
        font-weight: 800;
        margin: 0;
    }

    .lounge-price {
        font-size: 30px;
        font-weight: 900;
        color: #102119;
        margin: 0;
    }

    .lounge-price small {
        color: #7a867f;
        font-size: 13px;
        font-weight: 600;
    }

    .lounge-chip {
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

    .lounge-steps {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .lounge-step {
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

    .lounge-step-active {
        background: #0d9c53;
        border-color: #0d9c53;
        color: #fff;
    }

    .lounge-step-active i {
        color: #fff;
    }

    .lounge-checkout-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 24px;
        align-items: start;
    }

    .lounge-payment-grid {
        display: grid;
        grid-template-columns: minmax(0, 4fr) minmax(0, 6fr);
        gap: 24px;
        align-items: start;
    }

    .lounge-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 800;
        color: #102119;
        margin-bottom: 14px;
    }

    .lounge-subpanel {
        border: 1px solid #e0e9e4;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
        background: #fbfdfc;
    }

    .lounge-detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .lounge-detail {
        border-bottom: 1px solid #dfe8e3;
        padding-bottom: 8px;
        min-width: 0;
    }

    .lounge-detail span {
        display: block;
        color: #75817b;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .lounge-detail strong {
        display: block;
        color: #17241d;
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .lounge-total-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        color: #53615a;
        margin-bottom: 12px;
    }

    .lounge-grand-total {
        border-top: 1px solid #dfe8e3;
        padding-top: 14px;
        margin-top: 14px;
        font-size: 18px;
        color: #102119;
        font-weight: 900;
    }

    .lounge-success {
        padding: 34px;
        text-align: center;
        max-width: 720px;
        margin: 0 auto;
    }

    .lounge-success-icon {
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

    .lounge-hide {
        display: none;
    }

    .lounge-vw-fields {
        grid-template-columns: 1.2fr 1fr 1fr;
    }

    .lounge-vw-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 22px;
    }

    .lounge-vw-search {
        width: 214px;
        height: 64px;
    }

    .lounge-vw-search:disabled {
        cursor: not-allowed;
        opacity: .55;
        transform: none;
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

    @media (max-width: 991px) {
        .lounge-hero,
        .lounge-grid,
        .lounge-checkout-grid,
        .lounge-payment-grid,
        .lounge-vw-fields {
            grid-template-columns: 1fr;
        }

        .lounge-plan-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .lounge-detail-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .lounge-wrap {
            padding: 18px 12px 40px;
        }

        .lounge-hero-main,
        .lounge-panel,
        .lounge-plan,
        .lounge-summary,
        .lounge-success {
            padding: 18px;
        }

        .lounge-title {
            font-size: 13px;
        }

        .lounge-stat-grid,
        .lounge-plan-grid {
            grid-template-columns: 1fr;
        }

        .lounge-vw-search {
            width: 100%;
            height: 56px;
        }
    }
</style>
