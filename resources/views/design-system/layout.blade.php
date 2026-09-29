<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Design system') — TravelWheel Console</title>
    {{--
        Loaded on its own, with none of the panel's existing theme.css. The
        specimen has to be judged as the new language, not as the new language
        arguing with the old one.
    --}}
    @vite('resources/css/admin/system.css')
    @stack('styles')
</head>
<body class="tc-root">
    @yield('body')

    <script>
        // Theme choice lives in localStorage so a reload keeps whichever mode
        // you were judging. Phase 2 moves this into the panel shell proper.
        (function () {
            const KEY = 'tc-theme';
            const root = document.documentElement;
            const apply = (mode) => {
                root.classList.toggle('tc-dark', mode === 'dark');
                document.body.classList.toggle('tc-dark', mode === 'dark');
            };
            let saved = null;
            try { saved = localStorage.getItem(KEY); } catch (e) { /* private mode */ }
            apply(saved ?? 'light');
            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-tc-theme]')) return;
                const next = root.classList.contains('tc-dark') ? 'light' : 'dark';
                apply(next);
                try { localStorage.setItem(KEY, next); } catch (e) { /* ignore */ }
            });
        })();
    </script>
</body>
</html>
