<style>
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

    .cargo-vw-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-top: 6px;
    }

    .cargo-vw-panel {
        border: 1px solid #dfe8e3;
        border-radius: 8px;
        padding: 20px;
        background: #fbfdfc;
    }

    .cargo-vw-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 800;
        color: #102119;
        margin-bottom: 14px;
    }

    .cargo-vw-panel .vw-field {
        margin-bottom: 16px;
    }

    .cargo-vw-search {
        width: 100%;
    }

    .cargo-grid {
        display: grid;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        gap: 24px;
    }

    .cargo-panel {
        background: #fff;
        border: 1px solid #dfe8e3;
        border-radius: 8px;
        box-shadow: 0 14px 34px rgba(16, 33, 25, .08);
        padding: 22px;
    }

    .cargo-panel-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #102119;
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .cargo-list {
        display: grid;
        gap: 10px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .cargo-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #53615a;
        line-height: 1.45;
    }

    .cargo-list i {
        color: #0d9c53;
        margin-top: 2px;
        font-size: 18px;
        flex: 0 0 auto;
    }

    .cargo-steps {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .cargo-step {
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

    .cargo-step-active {
        background: #0d9c53;
        border-color: #0d9c53;
        color: #fff;
    }

    .cargo-step-active i {
        color: #fff;
    }

    .cargo-copy {
        color: #627069;
        line-height: 1.7;
        margin: 0 0 14px;
    }

    .cargo-note {
        border-left: 4px solid #d39b18;
        background: #fff8e7;
        color: #6d5212;
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 13px;
    }

    .cargo-page {
        background: #f6f8f7;
        color: #14201b;
        margin-top: 100px;
    }

    .cargo-wrap {
        max-width: 1180px;
        margin: 0 auto;
        padding: 32px 16px 56px;
    }

    .cargo-success {
        background: #fff;
        border: 1px solid #dfe8e3;
        border-radius: 8px;
        box-shadow: 0 14px 34px rgba(16, 33, 25, .08);
        padding: 34px;
        text-align: center;
        max-width: 720px;
        margin: 0 auto;
    }

    .cargo-success-icon {
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

    .cargo-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0b7a42;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .cargo-title {
        font-size: 26px;
        line-height: 1.2;
        margin: 0 0 12px;
        font-weight: 800;
        color: #102119;
    }

    .cargo-success-detail {
        border: 1px solid #e0e9e4;
        border-radius: 8px;
        padding: 18px 20px;
        margin: 22px 0;
        background: #fbfdfc;
        text-align: left;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .cargo-success-detail span {
        display: block;
        color: #75817b;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .cargo-success-detail strong {
        display: block;
        color: #17241d;
        font-size: 15px;
        overflow-wrap: anywhere;
    }

    .cargo-btn {
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

    .cargo-btn:hover {
        background: #087c41;
        color: #fff;
    }

    @media (max-width: 991px) {
        .cargo-vw-grid,
        .cargo-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .cargo-success {
            padding: 22px;
        }

        .cargo-success-detail {
            grid-template-columns: 1fr;
        }
    }
</style>
