@extends('design-system.layout')

@section('title', 'Responsive harness')

@push('styles')
<style>
    .rh-shell { padding: var(--tc-space-6) var(--tc-space-7) var(--tc-space-10); }
    .rh-head { display: flex; align-items: flex-end; justify-content: space-between;
               gap: var(--tc-space-5); flex-wrap: wrap; margin-bottom: var(--tc-space-6); }
    .rh-form { display: flex; align-items: flex-end; gap: var(--tc-space-3); flex-wrap: wrap; }
    .rh-frames { display: flex; gap: var(--tc-space-6); align-items: flex-start;
                 overflow-x: auto; padding-bottom: var(--tc-space-5); }
    .rh-frame { flex: none; }
    .rh-frame figcaption { display: flex; align-items: baseline; justify-content: space-between;
                           gap: var(--tc-space-3); margin-bottom: var(--tc-space-2); }
    .rh-frame iframe { display: block; border: 1px solid var(--tc-border-default);
                       border-radius: var(--tc-radius-md); background: var(--tc-bg-surface);
                       box-shadow: var(--tc-shadow-popover); }
</style>
@endpush

@section('body')
<div class="rh-shell">
    <header class="rh-head">
        <div>
            <p class="tc-t-label">TravelWheel Console · Phase 1 tooling</p>
            <h1 class="tc-t-heading" style="margin-top:var(--tc-space-2)">Responsive harness</h1>
            <p class="tc-t-base" style="max-width:74ch;margin-top:var(--tc-space-2)">
                Each frame below is a real browsing context at a fixed CSS width, so the page inside
                it evaluates its own media queries against that width. This exists because the
                browser window on this machine would not resize below 1531px, which left every
                phone breakpoint unverifiable. An iframe sidesteps that entirely.
            </p>
        </div>
        <form class="rh-form" method="get">
            <label class="tc-field" style="width:320px">
                <span class="tc-field-label">Path to preview</span>
                {{--
                    Same-origin relative paths only. Accepting an arbitrary URL here would turn a
                    dev tool into an open frame-embedder pointed at anything.
                --}}
                <input class="tc-input tc-mono" name="path" value="{{ $path }}"
                       placeholder="/design-system" pattern="/.*" required>
            </label>
            <button class="tc-btn tc-btn-primary" type="submit">Preview</button>
            <button class="tc-btn" type="button" data-tc-theme>Toggle dark</button>
        </form>
    </header>

    @if ($rejected)
        <p class="tc-t-body" style="margin-bottom:var(--tc-space-5);padding:var(--tc-space-4);
                  border:1px solid var(--tc-status-critical-border);border-radius:var(--tc-radius-sm);
                  background:var(--tc-status-critical-bg);color:var(--tc-status-critical)">
            Only same-origin paths beginning with <span class="tc-mono">/</span> are previewed.
            Showing <span class="tc-mono">{{ $path }}</span> instead.
        </p>
    @endif

    <div class="rh-frames">
        @foreach ($viewports as $label => $size)
            <figure class="rh-frame" style="margin:0">
                <figcaption>
                    <span class="tc-t-title">{{ $label }}</span>
                    <span class="tc-t-micro tc-mono">{{ $size[0] }}&times;{{ $size[1] }}</span>
                </figcaption>
                <iframe src="{{ $path }}" width="{{ $size[0] }}" height="{{ $size[1] }}"
                        title="{{ $label }} preview at {{ $size[0] }} pixels" loading="lazy"></iframe>
            </figure>
        @endforeach
    </div>
</div>
@endsection
