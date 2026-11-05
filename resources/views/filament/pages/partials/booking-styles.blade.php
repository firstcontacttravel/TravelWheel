<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .bk { font-family: 'DM Sans', sans-serif; color: #1a1a1a; max-width: 1160px; width: 100%; margin: 0 auto; }
    .bk *, .bk *::before, .bk *::after { box-sizing: border-box; }

    .bk-top { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; justify-content: space-between; margin-bottom: 18px; }
    .bk-state { background: #fff; border: 1px solid #e0ddd6; border-radius: 14px; padding: 14px 16px; box-shadow: 0 2px 10px rgba(0,0,0,.05); flex: 1 1 280px; max-width: 420px; }
    .bk-tabs { display: flex; background: #fff; border: 1px solid #e0ddd6; border-radius: 14px; padding: 5px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .bk-tab { padding: 10px 22px; border: 0; border-radius: 10px; background: none; color: #777; font: 600 13.5px 'DM Sans', sans-serif; cursor: pointer; transition: background .2s, color .2s; }
    .bk-tab:hover { background: #f0f3ff; color: #0d1883; }
    .bk-tab.on { background: #0d1883; color: #fff; box-shadow: 0 4px 14px rgba(13,24,131,.25); }

    .bk-card { background: #fff; border: 1px solid #e0ddd6; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,.06); display: grid; grid-template-columns: 220px 1fr; overflow: hidden; }
    .bk-side { background: #f7f8ff; border-right: 1px solid #e8eaf5; padding: 22px 16px; }
    .bk-main { padding: 22px 20px; display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .bk-step { background: #fff; border: 1px solid #e4e6f0; border-radius: 14px; padding: 18px; }
    .bk-step.tint { background: #fafbff; }
    .bk-label { display: inline-flex; background: #eef1ff; color: #0d1883; font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; margin-bottom: 14px; }
    .bk-empty { display: flex; align-items: center; justify-content: center; min-height: 110px; color: #aaa; font-size: 13px; text-align: center; }

    .bk-type { position: relative; display: flex; width: 100%; align-items: center; gap: 10px; padding: 10px 11px; margin-bottom: 8px; border: 1.5px solid #dde0f0; border-radius: 10px; background: #fff; text-align: left; cursor: pointer; overflow: hidden; transition: border-color .2s, background .2s; font-family: inherit; }
    .bk-type:hover { border-color: #0d1883; background: #f0f3ff; }
    .bk-type.on { border-color: #0d1883; background: #eef1ff; }
    .bk-type.on::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 3px; background: #0d1883; }
    .bk-thumb { width: 54px; height: 35px; flex-shrink: 0; background: #e8ecff; border-radius: 6px; overflow: hidden; }
    .bk-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .bk-type b { display: block; font-size: 12.5px; font-weight: 600; color: #1a1a1a; }
    .bk-type small { font-size: 10.5px; color: #888; }

    .bk-cats { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
    .bk-cat { position: relative; padding: 12px; border: 2px solid #e0e4f0; border-radius: 14px; background: #fff; text-align: left; cursor: pointer; font-family: inherit; transition: border-color .2s, background .2s, transform .2s, box-shadow .2s; }
    .bk-cat:hover { border-color: #0d1883; background: #f5f7ff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(13,24,131,.1); }
    .bk-cat.on { border-color: #0d1883; background: #eef1ff; box-shadow: 0 6px 18px rgba(13,24,131,.15); }
    .bk-cat-img { height: 72px; background: #e8ecff; border-radius: 9px; margin-bottom: 9px; overflow: hidden; }
    .bk-cat-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .bk-cat b { display: block; font-size: 13.5px; font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }
    .bk-cat em { display: block; font-style: normal; font-size: 12px; font-weight: 700; color: #0d1883; }
    .bk-cat small { display: block; font-size: 10.5px; color: #888; margin-top: 2px; }

    .bk-spec { display: flex; flex-wrap: wrap; gap: 8px 22px; margin-top: 14px; padding: 12px 14px; border-radius: 10px; background: #f7f8ff; border: 1px solid #e0e4f8; font-size: 12.5px; color: #444; }
    .bk-spec span { color: #888; }

    .bk-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .bk-grid .full { grid-column: 1 / -1; }
    .bk-field label { display: block; margin-bottom: 4px; font-size: 10.5px; font-weight: 600; color: #555; letter-spacing: .04em; text-transform: uppercase; }
    .bk-field input, .bk-field select, .bk-field textarea { width: 100%; padding: 10px 12px; border: 1.5px solid #dde0ee; border-radius: 10px; background: #fafbff; color: #1a1a1a; font: 400 13.5px 'DM Sans', sans-serif; outline: none; transition: border-color .2s, box-shadow .2s; }
    .bk-field input:focus, .bk-field select:focus, .bk-field textarea:focus { border-color: #0d1883; box-shadow: 0 0 0 3px rgba(13,24,131,.07); }
    .bk-err { margin-top: 4px; font-size: 11.5px; color: #c0392b; }

    .bk-chips { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; }
    .bk-chip { padding: 7px 15px; border: 1.5px solid #dde0ee; border-radius: 20px; background: #fff; color: #555; font: 600 12.5px 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; }
    .bk-chip:hover { border-color: #0d1883; color: #0d1883; }
    .bk-chip.on { background: #0d1883; border-color: #0d1883; color: #fff; }
    .bk-chips input { width: 90px; padding: 7px 10px; border: 1.5px solid #dde0ee; border-radius: 10px; font: 400 13px 'DM Sans', sans-serif; }

    .bk-route { margin-top: 12px; padding: 9px 13px; border-radius: 9px; background: #eef1ff; border: 1px solid #c5cef8; font-size: 12.5px; font-weight: 600; color: #0d1883; }
    .bk-route.bad { background: #fff0f0; border-color: #ffc5c5; color: #c0392b; }
    .bk-btn-line { margin-top: 12px; padding: 9px 18px; border: 1.5px solid #0d1883; border-radius: 10px; background: #fff; color: #0d1883; font: 600 13px 'DM Sans', sans-serif; cursor: pointer; }
    .bk-btn-line:hover { background: #f0f3ff; }

    .bk-price { margin-top: 14px; padding: 13px 15px; background: #f7f8ff; border: 1.5px solid #c5cef8; border-radius: 10px; }
    .bk-price h4 { margin: 0 0 9px; font-size: 9.5px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: .08em; }
    .bk-price div { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12.5px; border-bottom: 1px solid #e8eaf5; color: #666; }
    .bk-price div b { color: #1a1a1a; font-weight: 600; }
    .bk-price .total { padding-top: 8px; margin-top: 2px; border-top: 1.5px solid #c5cef8; border-bottom: 0; font-size: 14.5px; font-weight: 700; color: #555; }
    .bk-price .total b { color: #0d1883; font-weight: 700; }

    .bk-pays { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .bk-pay { padding: 12px 14px; border: 2px solid #e0e4f0; border-radius: 12px; background: #fff; text-align: left; cursor: pointer; font-family: inherit; transition: border-color .2s, background .2s; }
    .bk-pay:hover { border-color: #0d1883; }
    .bk-pay.on { border-color: #0d1883; background: #eef1ff; }
    .bk-pay b { display: block; font-size: 13px; font-weight: 600; color: #1a1a1a; }
    .bk-pay small { font-size: 11.5px; color: #777; }
    .bk-submit { width: 100%; padding: 13px; border: 0; border-radius: 11px; background: #0d1883; color: #fff; font: 700 14.5px 'DM Sans', sans-serif; cursor: pointer; box-shadow: 0 4px 14px rgba(13,24,131,.25); transition: background .2s; }
    .bk-submit:hover { background: #0a1369; }
    .bk-submit[disabled] { opacity: .6; cursor: wait; }

    .bk-note { margin-top: 12px; padding: 11px 14px; border-radius: 10px; background: #eef1ff; border: 1px solid #c5cef8; font-size: 13px; line-height: 1.5; color: #0d1883; }
    .bk-note.warn { background: #fff8e8; border-color: #f2d99a; color: #8a6100; }
    .bk-one { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }
    .bk-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; }
    .bk-guest { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #eef0f6; }
    .bk-guest b { display: block; font-size: 13.5px; font-weight: 600; }
    .bk-guest small { font-size: 11.5px; color: #888; }
    .bk-count { display: flex; align-items: center; gap: 10px; }
    .bk-count span { min-width: 22px; text-align: center; font-size: 14px; font-weight: 600; }
    .bk-count button { width: 32px; height: 32px; border: 1.5px solid #dde0ee; border-radius: 50%; background: #fff; color: #0d1883; font: 700 16px 'DM Sans', sans-serif; line-height: 1; cursor: pointer; }
    .bk-count button:hover { border-color: #0d1883; background: #f0f3ff; }
    .bk-suggest { position: relative; }
    .bk-suggest ul { position: absolute; z-index: 20; inset: 100% 0 auto 0; margin: 4px 0 0; padding: 4px; list-style: none; max-height: 240px; overflow-y: auto; background: #fff; border: 1px solid #dde0ee; border-radius: 10px; box-shadow: 0 8px 22px rgba(0,0,0,.12); }
    .bk-suggest button { display: block; width: 100%; padding: 8px 10px; border: 0; border-radius: 7px; background: none; text-align: left; font: 400 12.5px 'DM Sans', sans-serif; color: #1a1a1a; cursor: pointer; }
    .bk-suggest button:hover { background: #f0f3ff; }
    .bk-suggest b { color: #0d1883; margin-right: 6px; }

    @media (max-width: 768px) {
        .bk-card { grid-template-columns: 1fr; }
        .bk-side { border-right: 0; border-bottom: 1px solid #e8eaf5; }
        .bk-grid, .bk-pays { grid-template-columns: 1fr; }
        .bk-tabs { width: 100%; }
        .bk-tab { flex: 1; padding: 10px 8px; }
    }
</style>
