{{--
    TravelWheel Console — layout frame
    =================================

    Filament's default stacks a full-width topbar ABOVE the sidebar. The console
    puts the rail full-height on the left with the topbar starting after it, so
    navigation is a fixed edge of the window rather than a panel that begins
    below a bar. That is a structural change, which is why this view is forked
    rather than styled.

    Everything Filament's own layout provides is preserved: all render hooks,
    the $store.sidebar Alpine store, the mobile close overlay, and the
    max-content-width class.
--}}
@php
    use Filament\Support\Enums\Width;
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;

    $hasTopbar = filament()->hasTopbar();
    $hasNavigation = filament()->hasNavigation();
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getMaxContentWidth() ?? Width::Full);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base
    :livewire="$livewire"
    @class([
        'fi-body-has-navigation' => $hasNavigation,
        'fi-body-has-topbar' => $hasTopbar,
        'tc-body',
    ])
>
    <div class="fi-layout tc-shell">
        {{ FilamentView::renderHook(PanelsRenderHook::LAYOUT_START, scopes: $renderHookScopes) }}

        @if ($hasNavigation)
            {{-- Drawer scrim. Only ever visible below the rail's breakpoint,
                 where the rail slides in over the content. --}}
            <div
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                x-transition.opacity.300ms
                class="fi-sidebar-close-overlay tc-scrim"
            ></div>

            @livewire(filament()->getSidebarLivewireComponent())
        @endif

        <div class="fi-main-ctn tc-shell-main">
            @if ($hasTopbar)
                {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_BEFORE, scopes: $renderHookScopes) }}

                @livewire(filament()->getTopbarLivewireComponent())

                {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_AFTER, scopes: $renderHookScopes) }}
            @endif

            {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_BEFORE, scopes: $renderHookScopes) }}

            <main
                @class([
                    'fi-main',
                    'tc-main',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}

                {{ $slot }}

                {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
            </main>

            {{ FilamentView::renderHook(PanelsRenderHook::CONTENT_AFTER, scopes: $renderHookScopes) }}

            {{ FilamentView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
        </div>

        {{ FilamentView::renderHook(PanelsRenderHook::LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
