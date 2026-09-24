{{--
    TravelWheel Console — navigation rail
    ====================================

    Replaces Filament's stacked sidebar with a 56px icon rail whose groups open
    as flyouts. With 10 groups and 36 resources the stacked list ran well past
    the fold, so the busiest screen in the panel was reached by scrolling a
    navigation column; here every group is one glance and one hover away, and
    the rail costs 56px instead of 293px of horizontal space.

    Filament's own render hooks, the $store.sidebar Alpine store (which the
    layout and the mobile overlay both drive) and the navigation API are all
    preserved — only the markup and the interaction change.
--}}
@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $navigation = filament()->getNavigation();
    $panel = filament();

    // A group with no label holds the panel's ungrouped items — Dashboard and
    // anything else registered without a group. It pins to the top of the rail
    // rather than becoming a nameless flyout.
    $pinned = [];
    $groups = [];

    foreach ($navigation as $group) {
        if (filled($group->getLabel())) {
            $groups[] = $group;

            continue;
        }

        foreach ($group->getItems() as $item) {
            $pinned[] = $item;
        }
    }
@endphp

<div>
    <aside
        x-data="{
            open: null,
            openedBy: null,
            closeTimer: null,
            top: 0,
            show(label, by, el) {
                clearTimeout(this.closeTimer)
                this.open = label
                this.openedBy = by

                // The flyout is position:fixed so it escapes the rail's scroll
                // container — a scrollable ancestor clips on BOTH axes, because
                // CSS computes overflow-x to auto as soon as overflow-y is not
                // visible. Fixed means its offset has to be measured.
                if (el) {
                    this.top = el.getBoundingClientRect().top - 6
                    this.$nextTick(() => this.clamp(el))
                }
            },
            clamp(el) {
                const panel = el.querySelector('.tc-flyout')
                if (!panel) return
                const maxTop = window.innerHeight - panel.offsetHeight - 8
                if (this.top > maxTop) this.top = Math.max(8, maxTop)
            },
            scheduleClose() {
                // A short grace period, because the pointer has to cross a gap
                // between the rail and the flyout to reach it.
                clearTimeout(this.closeTimer)
                this.closeTimer = setTimeout(() => {
                    if (this.openedBy !== 'click') this.open = null
                }, 140)
            },
            hide() {
                clearTimeout(this.closeTimer)
                this.open = null
                this.openedBy = null
            },
        }"
        x-on:keydown.escape.window="hide()"
        x-on:click.outside="hide()"
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar tc-rail"
    >
        {{ FilamentView::renderHook(PanelsRenderHook::SIDEBAR_START) }}

        <div class="tc-rail-brand">
            <a href="{{ filament()->getUrl() }}" class="tc-rail-mark" title="{{ $panel->getBrandName() }}">
                {{-- Set in type: the only logo asset in the project is a white
                     wordmark on a near-opaque white field, which renders
                     invisible anywhere it is placed. --}}
                <span aria-hidden="true">TW</span>
                <span class="tc-visually-hidden">{{ $panel->getBrandName() }}</span>
            </a>
        </div>

        {{-- A landmark's label names the REGION, not an action. This carried
             `actions.sidebar.expand.label`, so screen readers announced the
             rail as "Expand sidebar navigation". --}}
        <nav class="fi-sidebar-nav tc-rail-nav" aria-label="Main navigation">
            {{ FilamentView::renderHook(PanelsRenderHook::SIDEBAR_NAV_START) }}

            @if ($pinned !== [])
                <ul class="tc-rail-list">
                    @foreach ($pinned as $item)
                        <li>
                            <a
                                href="{{ $item->getUrl() }}"
                                @class(['tc-rail-btn', 'is-active' => $item->isActive()])
                                @if ($item->isActive()) aria-current="page" @endif
                                x-on:mouseenter="hide()"
                            >
                                @if ($icon = $item->getIcon())
                                    {{ \Filament\Support\generate_icon_html($icon, size: \Filament\Support\Enums\IconSize::Medium) }}
                                @endif
                                <span class="tc-rail-tip">{{ $item->getLabel() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <hr class="tc-rail-rule">
            @endif

            <ul class="tc-rail-list tc-rail-groups">
                @foreach ($groups as $group)
                    @php
                        $label = $group->getLabel();
                        $items = $group->getItems();
                        $isActive = $group->isActive();
                    @endphp

                    <li
                        class="tc-rail-group"
                        x-on:mouseenter="show(@js($label), 'hover', $el)"
                        x-on:mouseleave="scheduleClose()"
                    >
                        <button
                            type="button"
                            @class(['tc-rail-btn', 'is-active' => $isActive])
                            x-on:click="open === @js($label) && openedBy === 'click' ? hide() : show(@js($label), 'click', $el.closest('.tc-rail-group'))"
                            x-bind:aria-expanded="open === @js($label) ? 'true' : 'false'"
                            aria-haspopup="true"
                        >
                            @if ($icon = $group->getIcon())
                                {{ \Filament\Support\generate_icon_html($icon, size: \Filament\Support\Enums\IconSize::Medium) }}
                            @endif
                            <span class="tc-rail-tip">{{ $label }}</span>
                        </button>

                        <div
                            class="tc-flyout"
                            x-cloak
                            x-show="open === @js($label)"
                            x-transition.opacity.duration.100ms
                            x-on:mouseenter="show(@js($label), openedBy ?? 'hover')"
                            {{-- An OBJECT binding, not a string. Alpine applies a string
                                 style binding with setAttribute, which rewrites the whole
                                 attribute and wipes the `display: none` that x-show sets —
                                 so every group's flyout appeared at once, stacked. --}}
                            x-bind:style="{ top: top + 'px' }"
                            x-on:mouseleave="scheduleClose()"
                        >
                            <p class="tc-flyout-title tc-t-label">{{ $label }}</p>
                            <ul>
                                @foreach ($items as $item)
                                    <li>
                                        <a
                                            href="{{ $item->getUrl() }}"
                                            @class(['tc-flyout-item', 'is-active' => $item->isActive()])
                                            @if ($item->isActive()) aria-current="page" @endif
                                            @if ($item->shouldOpenUrlInNewTab()) target="_blank" rel="noopener" @endif
                                            x-on:click="$store.sidebar.close()"
                                        >
                                            @if ($itemIcon = $item->getIcon())
                                                {{ \Filament\Support\generate_icon_html($itemIcon, size: \Filament\Support\Enums\IconSize::Small) }}
                                            @endif
                                            <span class="tc-truncate">{{ $item->getLabel() }}</span>
                                            @if (filled($badge = $item->getBadge()))
                                                <span class="tc-tag tc-tag-count">{{ $badge }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{ FilamentView::renderHook(PanelsRenderHook::SIDEBAR_NAV_END) }}
        </nav>

        @php
            $isAuthenticated = filament()->auth()->check();
            $hasDatabaseNotificationsInSidebar = filament()->hasDatabaseNotifications()
                && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Sidebar;
        @endphp

        <div class="tc-rail-foot">
            @if ($isAuthenticated && $hasDatabaseNotificationsInSidebar)
                @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                    'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                ])
            @endif

        </div>

        {{ FilamentView::renderHook(PanelsRenderHook::SIDEBAR_FOOTER) }}
    </aside>

    <x-filament-actions::modals />
</div>
