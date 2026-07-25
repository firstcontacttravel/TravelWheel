@props(['title', 'updated' => 'July 2026', 'sections' => []])

<div class="legal-doc">
    <div class="legal-doc__container">
        <header class="legal-doc__hero">
            <span class="legal-doc__eyebrow">
                <i class="ph-fill ph-scales" aria-hidden="true"></i>
                Legal
            </span>
            <h1 class="legal-doc__title">{{ $title }}</h1>
            <p class="legal-doc__updated">
                <i class="ph-fill ph-clock-countdown" aria-hidden="true"></i>
                Last Updated: {{ $updated }}
            </p>
        </header>

        <div class="legal-doc__grid">
            @if(count($sections))
                <aside class="legal-doc__toc" aria-label="Table of contents for {{ $title }}">
                    <nav class="legal-doc__toc-card">
                        <span class="legal-doc__toc-heading">On this page</span>
                        <ol class="legal-doc__toc-list">
                            @foreach($sections as $section)
                                <li><a href="#{{ $section['id'] }}">{{ $section['label'] }}</a></li>
                            @endforeach
                        </ol>
                    </nav>
                </aside>
            @endif

            <div class="legal-doc__content">
                {{ $slot }}

                <x-legal.callout variant="success">
                    <p>By using the TravelWheel website, mobile platforms, or any of our booking channels, or by making a booking or payment through TravelWheel, you acknowledge that you have read, understood, and agreed to be bound by this {{ $title }} together with our other applicable policies, including our Terms &amp; Conditions.</p>
                </x-legal.callout>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .legal-doc {
            /* This is a fixed-light document layout: pin the neutral scale to its
               light values so text stays readable even when the visitor's OS/browser
               is set to dark mode (the global stylesheet inverts --color-neutral-*
               under prefers-color-scheme: dark, which would wash out text on our
               explicitly white cards). */
            --color-neutral-50: #fafafa;
            --color-neutral-100: #f5f5f5;
            --color-neutral-200: #e5e5e5;
            --color-neutral-300: #d4d4d4;
            --color-neutral-400: #a3a3a3;
            --color-neutral-500: #737373;
            --color-neutral-600: #525252;
            --color-neutral-700: #404040;
            --color-neutral-800: #262626;
            --color-neutral-900: #171717;
            --color-neutral-950: #0a0a0a;

            font-family: var(--font-primary);
            color: var(--color-neutral-700);
            background: linear-gradient(180deg, var(--color-neutral-50) 0%, #fff 420px);
            padding: var(--space-10) 0 var(--space-16);
        }
        .legal-doc__container {
            width: min(100% - var(--space-8), 1160px);
            margin-inline: auto;
        }

        /* Hero */
        .legal-doc__hero {
            position: relative;
            overflow: hidden;
            max-width: 100%;
            margin: 0 0 -36px;
            padding: var(--space-10) var(--space-8) var(--space-16);
            border-radius: var(--radius-2xl);
            background: var(--gradient-brand);
            box-shadow: var(--shadow-lg);
        }
        .legal-doc__hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 88% -10%, rgba(255,255,255,.22), transparent 55%),
                radial-gradient(circle at 8% 120%, rgba(255,255,255,.12), transparent 45%);
            pointer-events: none;
        }
        .legal-doc__eyebrow {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            margin: 0 0 var(--space-4);
            padding: 6px 14px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.28);
            border-radius: var(--radius-full);
            color: #fff;
            font-size: var(--text-xs);
            font-weight: var(--font-bold);
            letter-spacing: var(--tracking-wider);
            text-transform: uppercase;
            backdrop-filter: blur(6px);
        }
        .legal-doc__title {
            position: relative;
            z-index: 1;
            max-width: 780px;
            margin: 0 0 var(--space-3);
            color: #fff;
            font-size: var(--text-4xl);
            font-weight: var(--font-extrabold);
            line-height: var(--leading-tight);
            text-shadow: 0 2px 18px rgba(0,0,0,.12);
        }
        .legal-doc__updated {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            margin: 0;
            color: rgba(255,255,255,.88);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
        }

        /* Grid */
        .legal-doc__grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-8);
            align-items: start;
        }
        .legal-doc__toc-card {
            background: #fff;
            border: 1px solid var(--color-neutral-200);
            border-radius: var(--radius-xl);
            padding: var(--space-5);
            box-shadow: var(--shadow-sm);
            transition: box-shadow .2s ease;
        }
        .legal-doc__toc-card:hover { box-shadow: var(--shadow-md); }
        .legal-doc__toc-heading {
            display: block;
            margin-bottom: var(--space-3);
            color: var(--color-neutral-500);
            font-size: var(--text-xs);
            font-weight: var(--font-bold);
            letter-spacing: var(--tracking-wider);
            text-transform: uppercase;
        }
        .legal-doc__toc-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 2px;
            max-height: 60vh;
            overflow-y: auto;
        }
        .legal-doc__toc-list a {
            display: block;
            padding: var(--space-2) var(--space-3);
            border-left: 3px solid transparent;
            border-radius: var(--radius-lg);
            color: var(--color-neutral-600);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
            text-decoration: none;
            line-height: var(--leading-snug);
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }
        .legal-doc__toc-list a:hover,
        .legal-doc__toc-list a:focus-visible {
            background: var(--color-primary-50);
            color: var(--color-primary);
            outline: none;
        }
        .legal-doc__toc-list a.is-active {
            background: var(--color-primary-50);
            border-left-color: var(--color-primary);
            color: var(--color-primary);
            font-weight: var(--font-bold);
        }

        /* Content card */
        .legal-doc__content {
            position: relative;
            min-width: 0;
            background: #fff;
            border: 1px solid var(--color-neutral-200);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-md);
        }
        .legal-doc__content > p:first-of-type {
            font-size: var(--text-lg);
            color: var(--color-neutral-600);
            line-height: var(--leading-relaxed);
        }
        .legal-doc__content section {
            scroll-margin-top: 150px;
        }
        .legal-doc__content section + section {
            margin-top: var(--space-10);
            padding-top: var(--space-10);
            border-top: 1px solid var(--color-neutral-200);
        }
        .legal-doc__content h2 {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin: 0 0 var(--space-4);
            color: var(--color-neutral-900);
            font-size: var(--text-2xl);
            font-weight: var(--font-bold);
            line-height: var(--leading-snug);
        }
        .legal-doc__section-number {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--gradient-brand);
            color: #fff;
            font-size: var(--text-sm);
            font-weight: var(--font-extrabold);
        }
        .legal-doc__anchor {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            width: 30px;
            height: 30px;
            border-radius: var(--radius-lg);
            color: var(--color-neutral-400) !important;
            font-size: var(--text-base);
            opacity: 0;
            transition: opacity .15s ease, background-color .15s ease, color .15s ease;
        }
        .legal-doc__content h2:hover .legal-doc__anchor,
        .legal-doc__anchor:focus-visible {
            opacity: 1;
        }
        .legal-doc__anchor:hover {
            background: var(--color-primary-50);
            color: var(--color-primary) !important;
        }
        .legal-doc__content h3 {
            margin: var(--space-5) 0 var(--space-2);
            color: var(--color-neutral-900);
            font-size: var(--text-lg);
            font-weight: var(--font-semibold);
            line-height: var(--leading-snug);
        }
        .legal-doc__content p {
            margin: 0 0 var(--space-4);
            color: var(--color-neutral-700);
            font-size: var(--text-base);
            line-height: var(--leading-relaxed);
        }
        .legal-doc__content ul,
        .legal-doc__content ol {
            margin: 0 0 var(--space-4);
            padding-left: var(--space-6);
            color: var(--color-neutral-700);
            font-size: var(--text-base);
            line-height: var(--leading-relaxed);
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }
        .legal-doc__content ul { list-style: disc; }
        .legal-doc__content ol { list-style: decimal; }
        .legal-doc__content a { color: var(--color-primary); font-weight: var(--font-semibold); }
        .legal-doc__content strong { color: var(--color-neutral-900); }
        .legal-doc__content table {
            width: 100%;
            margin: 0 0 var(--space-4);
            border-collapse: collapse;
            font-size: var(--text-sm);
        }
        .legal-doc__content th,
        .legal-doc__content td {
            padding: var(--space-3);
            border: 1px solid var(--color-neutral-200);
            text-align: left;
            vertical-align: top;
        }
        .legal-doc__content th {
            background: var(--color-neutral-100);
            color: var(--color-neutral-900);
            font-weight: var(--font-bold);
        }

        /* Callouts */
        .legal-callout {
            display: flex;
            gap: var(--space-3);
            align-items: flex-start;
            margin: var(--space-5) 0;
            padding: var(--space-4) var(--space-5);
            border: 1px solid;
            border-radius: var(--radius-lg);
            font-size: var(--text-sm);
            line-height: var(--leading-relaxed);
        }
        .legal-callout__icon {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 15px;
            color: #fff;
        }
        .legal-callout__body :last-child { margin-bottom: 0; }
        .legal-callout--info { background: var(--color-info-light); border-color: var(--color-info); color: var(--color-info-dark); }
        .legal-callout--info .legal-callout__icon { background: var(--color-info); }
        .legal-callout--warning { background: var(--color-warning-light); border-color: var(--color-warning); color: var(--color-warning-dark); }
        .legal-callout--warning .legal-callout__icon { background: var(--color-warning); }
        .legal-callout--danger { background: var(--color-error-light); border-color: var(--color-error); color: var(--color-error-dark); }
        .legal-callout--danger .legal-callout__icon { background: var(--color-error); }
        .legal-callout--success { background: var(--color-success-light); border-color: var(--color-success); color: var(--color-success-dark); }
        .legal-callout--success .legal-callout__icon { background: var(--color-success); }

        /* .navbarmain * forces var(--font-primary) !important on every descendant
           (see resources/views/layouts/app.blade.php), which otherwise beats the
           Phosphor icon font's own !important rule and renders icons as tofu boxes.
           Out-specificity it here so icons inside this component always render. */
        .legal-doc i.ph-fill { font-family: "Phosphor-Fill" !important; }
        .legal-doc i.ph { font-family: "Phosphor" !important; }

        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }

        @media (max-width: 640px) {
            .legal-doc__hero { padding: var(--space-8) var(--space-5) var(--space-12); margin-bottom: -24px; }
            .legal-doc__title { font-size: var(--text-3xl); }
            .legal-doc__content { padding: var(--space-5); }
            .legal-doc__content h2 { font-size: var(--text-xl); }
            .legal-doc__anchor { display: none; }
        }

        @media (min-width: 1024px) {
            .legal-doc__grid { grid-template-columns: 270px minmax(0, 1fr); }
            .legal-doc__toc { position: sticky; top: 150px; align-self: start; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var links = document.querySelectorAll('.legal-doc__toc-list a');
            if (!links.length || !('IntersectionObserver' in window)) return;

            var linkByTargetId = {};
            links.forEach(function (link) {
                linkByTargetId[link.getAttribute('href').slice(1)] = link;
            });

            var sections = document.querySelectorAll('.legal-doc__content section[id]');
            if (!sections.length) return;

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    var link = linkByTargetId[entry.target.id];
                    if (!link || !entry.isIntersecting) return;
                    links.forEach(function (l) { l.classList.remove('is-active'); });
                    link.classList.add('is-active');
                });
            }, { rootMargin: '-150px 0px -65% 0px', threshold: 0 });

            sections.forEach(function (section) { observer.observe(section); });
        });
    </script>
@endonce
