{{--
    TravelWheel Console — topbar
    ============================

    A 48px bar carrying only what is global: the mobile rail toggle, search,
    notifications and the account menu. Page identity lives in the page header
    below it, not here — Filament's default repeated the resource name in the
    breadcrumb, the page title AND the table heading, three times on one screen.

    Filament's own Livewire components are rendered unchanged; only the frame
    and the search affordance are ours.
--}}
@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $hasGlobalSearch = filament()->isGlobalSearchEnabled()
        && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Topbar;
    $hasNotifications = filament()->hasDatabaseNotifications()
        && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Topbar;
    $hasUserMenu = filament()->hasUserMenu()
        && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Topbar;
@endphp

<div class="fi-topbar tc-topbar">
    {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_START) }}

    <div class="tc-topbar-start">
        {{-- Below the rail's breakpoint the rail becomes a drawer, driven by
             the same $store.sidebar the layout and overlay use. --}}
        <button
            type="button"
            class="tc-icon-btn tc-topbar-menu"
            x-data="{}"
            x-on:click="$store.sidebar.open()"
            aria-label="{{ __('filament-panels::layout.actions.sidebar.expand.label') }}"
        >
            <svg class="fi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
            </svg>
        </button>

        {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_LOGO_BEFORE) }}
        {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER) }}
    </div>

    <div class="tc-topbar-search">
        {{ FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE) }}

        @if ($hasGlobalSearch)
            {{-- Filament's global search owns the input and the results; the
                 console supplies the trigger affordance and the ⌘K hint, and
                 focuses the real field rather than reimplementing it. --}}
            <div
                class="tc-search"
                x-data="{
                    focus() {
                        this.$el.querySelector('input')?.focus()
                    },
                }"
                x-on:keydown.window.prevent.cmd.k="focus()"
                x-on:keydown.window.prevent.ctrl.k="focus()"
            >
                {{-- Filament's field already renders a magnifier AND a loading
                     indicator in its prefix slot; the indicator is worth
                     keeping, so the console styles that prefix rather than
                     drawing a second icon beside it. --}}
                @livewire(Filament\Livewire\GlobalSearch::class)

                <kbd class="tc-kbd" aria-hidden="true">
                    <span class="tc-kbd-mod">Ctrl</span>K
                </kbd>
            </div>
        @endif

        {{ FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER) }}
    </div>

    <div class="fi-topbar-end tc-topbar-end">
        {{-- Filament's own switcher, not a hand-rolled button: it persists the
             choice and dispatches `theme-changed`, which dark-mode.js listens
             for to toggle `.dark` on <html>. Reimplementing that would have
             meant reimplementing the persistence and the chart redraw with it. --}}
        <x-filament-panels::theme-switcher />

        @if ($hasNotifications)
            @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
            ])
        @endif

        @if ($hasUserMenu)
            <x-filament-panels::user-menu />
        @endif
    </div>

    {{ FilamentView::renderHook(PanelsRenderHook::TOPBAR_END) }}
</div>
