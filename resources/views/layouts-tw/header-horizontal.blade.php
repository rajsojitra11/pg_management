@php
    use Modules\MenuMaster\Models\MenuMaster;
    use Nwidart\Modules\Facades\Module;
    use Illuminate\Support\Facades\Cache;

    $menus = Cache::remember('menu_tree', 3600, function () {
        return MenuMaster::parentMenus()
            ->with('children.children')
            ->orderBy('order_display', 'ASC')
            ->orderBy('menu_title', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
    });

    $isMenuActive = function ($menu) use (&$isMenuActive) {
        $route = str_replace('.index', '', $menu->menu_route);
        if (request()->routeIs($route . '.*') || request()->routeIs($route)) {
            return true;
        }
        if ($menu->children && $menu->children->count() > 0) {
            foreach ($menu->children as $child) {
                if ($isMenuActive($child)) {
                    return true;
                }
            }
        }
        return false;
    };

    $isMenuEnabled = function ($menu, $isChild = false) {
        if (empty($menu->module_name)) {
            return true;
        }
        $moduleExists = $menu->module_name ? Module::collections()->has(strtolower($menu->module_name)) : false;
        return $moduleExists ? Module::isEnabled(strtolower($menu->module_name)) : ($isChild ? false : true);
    };

    // Helper: check permissions with comma-separated if_can strings
    $userCanAny = function ($ifCan) {
        if (empty($ifCan)) {
            return true;
        }
        if ($ifCan === 'system-administration-access') {
            return auth()->user()->hasRole('Super_Admin');
        }
        return auth()
            ->user()
            ->canany(array_map('trim', explode(',', $ifCan)));
    };

    $hasPermissionForAnyChild = function ($menu) use (&$hasPermissionForAnyChild, $isMenuEnabled, $userCanAny) {
        if (!$menu->children || $menu->children->count() == 0) {
            return false;
        }
        foreach ($menu->children as $child) {
            $childEnabled =
                $child->children->count() > 0 ? $isMenuEnabled($child, false) : $isMenuEnabled($child, true);
            if ($childEnabled) {
                if ($userCanAny($child->if_can)) {
                    return true;
                }
                if ($hasPermissionForAnyChild($child)) {
                    return true;
                }
            }
        }
        return false;
    };
@endphp

{{-- Horizontal Header --}}
<header id="erp-top-bar" class="erp-header fixed top-0 left-0 right-0 z-20">
    {{-- Top bar --}}
    <div class="h-14 border-b border-zinc-200 flex items-center px-3 sm:px-4 gap-2 sm:gap-3">

        {{-- Mobile hamburger --}}
        <button id="hamburger-btn"
            class="lg:hidden text-zinc-500 hover:text-zinc-700 p-2 rounded-md hover:bg-zinc-100 shrink-0">
            <i class="fa-solid fa-bars"></i>
        </button>

        {{-- Logo — icon-only on xs, full logo on sm+ --}}
        <a href="{{ route('dashboard') }}" class="flex items-center shrink-0">
            <span class="erp-logo-wrap erp-logo-wrap--horizontal">
                @if (setting()->logo != '')
                    <img src="{{ asset('setting/logo/' . setting()->logo) }}" class="h-8 object-contain erp-logo"
                        alt="{{ config('app.name') }}">
                @else
                    <img src="{{ asset('assets-tw/img/favicon.png') }}"
                        class="h-8 w-8 object-contain erp-logo sm:hidden" alt="{{ config('app.name') }}">
                    <img src="{{ asset('assets-tw/img/logo.png') }}" class="h-8 object-contain erp-logo hidden sm:block"
                        style="max-width: 160px;" alt="{{ config('app.name') }}">
                @endif
            </span>
        </a>

        <div class="flex-1"></div>

        {{-- Global search (full on sm+) --}}
        <button id="global-search-btn" type="button"
            class="hidden sm:flex items-center gap-2 h-9 px-3 rounded-md border border-zinc-200 bg-zinc-50 text-sm text-zinc-500 hover:bg-zinc-100 hover:border-zinc-300 shrink-0"
            style="min-width: 180px; max-width: 240px;"
            title="{{ __('message.common.search') }} (Ctrl+K)" onclick="erpCommandPalette()">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
            <span class="hidden sm:inline">{{ __('message.common.search') }}...</span>
            <kbd class="inline-flex ml-auto text-2xs px-1.5 py-0.5 rounded border border-zinc-300 bg-white text-zinc-400 font-mono">Ctrl+K</kbd>
        </button>
        {{-- Mobile search (icon only) --}}
        <button id="global-search-btn-mobile" type="button"
            class="sm:hidden p-2 text-zinc-500 hover:text-zinc-700 rounded-md hover:bg-zinc-100 shrink-0"
            title="{{ __('message.common.search') }} (Ctrl+K)" onclick="erpCommandPalette()">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>

        {{-- Year selector --}}
        <div class="relative shrink-0" id="year-selector-wrapper">
            @php
                $sessionYearId = getSessionYearId();
                $currentYearDisplay = '';
                if ($sessionYearId) {
                    $currentYear = \Modules\Year\Models\Year::find($sessionYearId);
                    $currentYearDisplay = $currentYear ? getFormattedYear($currentYear) : session()->get('year');
                } else {
                    $currentYearDisplay = session()->get('year');
                }
            @endphp
            <button id="year-selector-btn"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-calendar text-xs text-zinc-400"></i>
                <span id="selected_year">{{ $currentYearDisplay }}</span>
                <i class="fa-solid fa-chevron-down text-[10px] text-zinc-400"></i>
            </button>
            <div id="year-dropdown"
                class="absolute top-full right-0 mt-1 w-64 bg-white border border-zinc-200 rounded-lg shadow-lg z-50"
                style="display:none">
                <div class="p-2 border-b border-zinc-100 year-search-container">
                    <input type="text"
                        class="year-search-input w-full rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-1"
                        placeholder="Search year..." autocomplete="off">
                </div>
                <div class="max-h-60 overflow-y-auto py-1">
                    {!! getYear(false, false) !!}
                </div>
                <div class="year-search-results px-2 pb-2"></div>
            </div>
        </div>

        {{-- Impersonation --}}
        @impersonating
            <a href="{{ route('impersonate.leave') }}"
                class="h-9 px-3 rounded-md bg-amber-500 text-white text-sm font-medium hover:bg-amber-600 whitespace-nowrap inline-flex items-center gap-1.5 shrink-0">
                <i class="fa-solid fa-user-secret text-xs"></i>
                <span class="hidden sm:inline">Impersonating <strong>{{ Auth::user()->name }}</strong></span>
                <span class="inline-flex items-center rounded bg-amber-700 px-1.5 py-0.5 text-xs">Leave</span>
            </a>
        @endImpersonating

        @if (!app('impersonate')->isImpersonating())
            @canImpersonate($guard = null)
            <div class="relative shrink-0" id="impersonate-dropdown">
                <button
                    class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-1.5"
                    onclick="var dd=this.nextElementSibling; dd.style.display=dd.style.display==='none'?'':'none'">
                    <i class="fa-solid fa-user-secret text-xs"></i>
                    <span class="hidden sm:inline">Impersonate</span>
                </button>
                <div class="dropdown-menu absolute top-full right-0 mt-1 w-72 bg-white border border-zinc-200 rounded-lg shadow-lg p-2 z-50"
                    style="display:none">
                    <input type="text"
                        class="impersonate-search-input w-full rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm placeholder:text-zinc-400 mb-2"
                        placeholder="Search users..." autocomplete="off">
                    <div class="impersonate-user-list max-h-60 overflow-y-auto">
                        <p class="text-sm text-zinc-400 text-center py-2">Type to search users...</p>
                    </div>
                </div>
            </div>
            @endCanImpersonate
        @endif

        {{-- Dark mode --}}
        <button id="dark-mode-btn" class="p-2 text-zinc-500 hover:text-zinc-700 rounded-md hover:bg-zinc-100 shrink-0"
            title="Toggle dark mode">
            <i class="fa-solid fa-moon erp-dark-icon"></i>
            <i class="fa-solid fa-sun erp-light-icon" style="display:none"></i>
        </button>

        {{-- Layout toggle (switch to vertical) --}}
        <button id="layout-toggle-btn"
            class="p-2 text-zinc-500 hover:text-zinc-700 rounded-md hover:bg-zinc-100 shrink-0"
            title="Switch to vertical layout" onclick="switchLayout('vertical')">
            <i class="fa-solid fa-grip-lines-vertical"></i>
        </button>

        {{-- User menu --}}
        <div class="relative shrink-0">
            <button id="user-menu-btn" class="flex items-center gap-2 p-1.5 rounded-md hover:bg-zinc-100">
                <div class="h-8 w-8 rounded-full bg-zinc-200 flex items-center justify-center shrink-0 overflow-hidden">
                    @php $userPhoto = Auth::user()->profile?->profile_photo; @endphp
                    @if ($userPhoto)
                        <img src="{{ asset('storage/' . $userPhoto) }}" alt="{{ Auth::user()->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-zinc-600 font-medium text-xs">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                    @endif
                </div>
                <span class="hidden sm:inline text-sm font-medium text-zinc-700">{{ Auth::user()->name }}</span>
                <i class="hidden sm:inline fa-solid fa-chevron-down text-xs text-zinc-400"></i>
            </button>
            <div id="user-menu-dropdown" class="absolute top-full right-0 mt-1 w-48 rounded-lg shadow-lg py-1 z-50"
                style="display:none; background-color: var(--erp-bg); border: 1px solid var(--erp-border);">
                @can('password-change')
                    <a href="{{ route('profile') }}"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                        <i class="fa-solid fa-user text-zinc-400 w-4"></i> {{ __('user::message.my_profile') }}
                    </a>
                    <a href="{{ route('profile.change-password') }}"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                        <i class="fa-solid fa-key text-zinc-400 w-4"></i> {{ __('user::message.change_password') }}
                    </a>
                    @can('menu-master-list')
                        <a href="{{ route('menumasters.index') }}"
                            class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                            <i class="fa-solid fa-bars text-zinc-400 w-4"></i> {{ __('menumaster::message.add') }}
                        </a>
                    @endcan
                @endcan
                <div class="border-t border-zinc-100 my-1"></div>
                <a href="javascript:void(0);" onclick="$('#globalLogoutModal').removeClass('hidden')"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="fa-solid fa-right-from-bracket text-red-400 w-4"></i> {{ __('user::message.Logout') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Horizontal nav bar (desktop only) --}}
    <div class="erp-hnav-wrapper hidden lg:flex items-center border-b border-zinc-200 h-10 relative">
        <button id="hnav-left"
            class="erp-hnav-arrow absolute left-0 z-10 h-8 w-8 flex items-center justify-center rounded-full shadow-md"
            type="button" style="display:none; margin-left:4px;">
            <i class="fa-solid fa-chevron-left text-xs"></i>
        </button>
        <div id="hnav-scroll" class="flex overflow-x-auto scroll-smooth whitespace-nowrap mx-2 gap-0.5 erp-hnav-scroll">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
                class="erp-hnav-item inline-flex items-center gap-1.5 px-3 h-10 text-sm transition-colors shrink-0
                      {{ request()->routeIs('dashboard') ? 'erp-hnav-active' : '' }}">
                <i class="fa-solid fa-house text-xs"></i>
                <span>{{ __('lang.dashboard') }}</span>
            </a>

            {{-- Menu items --}}
            @foreach ($menus as $menu)
                @php
                    // Dashboard is rendered as a fixed link above; skip its menu row to avoid duplication.
                    if ($menu->menu_route === 'dashboard') {
                        continue;
                    }
                    if ($menu->module_name != null) {
                        $moduleName = Module::getModulePath($menu->module_name);
                        if (!file_exists($moduleName)) {
                            continue;
                        }
                    }
                    $enabled = $isMenuEnabled($menu, false);
                    $isActive = $isMenuActive($menu);
                    $hasOwnPermission = $userCanAny($menu->if_can);
                    $hasChildPerm = $hasPermissionForAnyChild($menu);
                    $shouldShowMenu = $enabled && ($hasOwnPermission || $hasChildPerm);
                    $menuUrlActive = false;
                    if (
                        $menu->menu_route != 'javascript:void(0)' &&
                        !is_null($menu->menu_route) &&
                        $menu->menu_route != ''
                    ) {
                        $menuUrlActive = Route::has($menu->menu_route);
                    } else {
                        $menuUrlActive = true;
                    }
                @endphp

                @if ($shouldShowMenu && $menuUrlActive)
                    @if ($menu->children->count() > 0)
                        {{-- Menu with dropdown --}}
                        <button
                            class="erp-hnav-item inline-flex items-center gap-1.5 px-3 h-10 text-sm transition-colors shrink-0 {{ $isActive ? 'erp-hnav-active' : '' }}"
                            data-hnav-menu="{{ $menu->id }}" type="button">
                            <i class="{{ $menu->menu_icon }} text-xs"></i>
                            <span>{{ __($menu->menu_title) }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] ml-0.5"></i>
                        </button>
                    @else
                        {{-- Single link --}}
                        <a href="{{ route($menu->menu_route) }}"
                            class="erp-hnav-item inline-flex items-center gap-1.5 px-3 h-10 text-sm transition-colors shrink-0 {{ $isActive ? 'erp-hnav-active' : '' }}">
                            <i class="{{ $menu->menu_icon }} text-xs"></i>
                            <span>{{ __($menu->menu_title) }}</span>
                        </a>
                    @endif
                @endif
            @endforeach
        </div>
        <button id="hnav-right"
            class="erp-hnav-arrow absolute right-0 z-10 h-8 w-8 flex items-center justify-center rounded-full shadow-md"
            type="button" style="display:none; margin-right:4px;">
            <i class="fa-solid fa-chevron-right text-xs"></i>
        </button>
    </div>

    {{-- Dropdown panels (positioned absolutely under clicked button via JS) --}}
    @foreach ($menus as $menu)
        @php
            if ($menu->module_name != null && !file_exists(Module::getModulePath($menu->module_name))) {
                continue;
            }
            $enabled = $isMenuEnabled($menu, false);
            $hasOwnPermission = $userCanAny($menu->if_can);
            $hasChildPerm = $hasPermissionForAnyChild($menu);
            $shouldShowMenu = $enabled && ($hasOwnPermission || $hasChildPerm);
            // Count visible children for column sizing
            $visibleGroupCount = 0;
            $visibleFlatCount = 0;
            if ($shouldShowMenu && $menu->children->count() > 0) {
                foreach ($menu->children as $ch) {
                    $chEnabled = $ch->children->count() > 0 ? $isMenuEnabled($ch, false) : $isMenuEnabled($ch, true);
                    $chPerm = $userCanAny($ch->if_can);
                    if ($chEnabled && $chPerm) {
                        if ($ch->children->count() > 0) {
                            $visibleGroupCount++;
                        } else {
                            $visibleFlatCount++;
                        }
                    }
                }
            }
            $totalVisible = $visibleGroupCount + $visibleFlatCount;
            $hasGroups = $visibleGroupCount > 0;
        @endphp
        @if ($shouldShowMenu && $menu->children->count() > 0)
            <div class="erp-hnav-dropdown" data-hnav-panel="{{ $menu->id }}" style="display:none">
                @if ($hasGroups)
                    {{-- Multi-column grouped layout --}}
                    <div class="erp-hnav-dropdown-grid"
                        style="grid-template-columns: repeat({{ min($visibleGroupCount, 3) }}, minmax(180px, auto));">
                        @foreach ($menu->children as $child)
                            @php
                                if (
                                    $child->module_name != null &&
                                    !file_exists(Module::getModulePath($child->module_name))
                                ) {
                                    continue;
                                }
                                $childEnabled =
                                    $child->children->count() > 0
                                        ? $isMenuEnabled($child, false)
                                        : $isMenuEnabled($child, true);
                                $childPerm = $userCanAny($child->if_can);
                                $childIsActive = $isMenuActive($child);
                                $childUrlActive =
                                    $child->menu_route !== 'javascript:void(0)' &&
                                    $child->menu_route !== null &&
                                    $child->menu_route !== ''
                                        ? Route::has($child->menu_route)
                                        : true;
                            @endphp
                            @if ($childEnabled && $childPerm && $childUrlActive)
                                @if ($child->children->count() > 0)
                                    <div>
                                        <p
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-400 px-2 pb-1.5 mb-1 border-b border-zinc-100">
                                            <i
                                                class="{{ $child->menu_icon }} w-4 text-center mr-1"></i>{{ __($child->menu_title) }}
                                        </p>
                                        <div class="space-y-0.5">
                                            @foreach ($child->children as $grandchild)
                                                @php
                                                    if (
                                                        $grandchild->module_name != null &&
                                                        !file_exists(Module::getModulePath($grandchild->module_name))
                                                    ) {
                                                        continue;
                                                    }
                                                    $gcEnabled = $isMenuEnabled($grandchild, true);
                                                    $gcActive =
                                                        $grandchild->menu_route !== 'javascript:void(0)' &&
                                                        request()->routeIs($grandchild->menu_route . '*');
                                                    $gcUrlActive =
                                                        $grandchild->menu_route !== 'javascript:void(0)' &&
                                                        $grandchild->menu_route !== null &&
                                                        $grandchild->menu_route !== ''
                                                            ? Route::has($grandchild->menu_route)
                                                            : true;
                                                @endphp
                                                @if ($gcEnabled && $gcUrlActive && $userCanAny($grandchild->if_can))
                                                    <a href="{{ route($grandchild->menu_route) }}"
                                                        class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm whitespace-nowrap transition-colors {{ $gcActive ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                                                        <i
                                                            class="{{ $grandchild->menu_icon }} w-4 text-center text-xs"></i>
                                                        {{ __($grandchild->menu_title) }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <a href="{{ route($child->menu_route) }}"
                                            class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm whitespace-nowrap transition-colors {{ $childIsActive ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                                            <i class="{{ $child->menu_icon }} w-4 text-center text-xs"></i>
                                            {{ __($child->menu_title) }}
                                        </a>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                @else
                    {{-- Simple list (no subgroups) --}}
                    <div class="space-y-0.5">
                        @foreach ($menu->children as $child)
                            @php
                                if (
                                    $child->module_name != null &&
                                    !file_exists(Module::getModulePath($child->module_name))
                                ) {
                                    continue;
                                }
                                $childEnabled = $isMenuEnabled($child, true);
                                $childPerm = $userCanAny($child->if_can);
                                $childIsActive = $isMenuActive($child);
                                $childUrlActive =
                                    $child->menu_route !== 'javascript:void(0)' &&
                                    $child->menu_route !== null &&
                                    $child->menu_route !== ''
                                        ? Route::has($child->menu_route)
                                        : true;
                            @endphp
                            @if ($childEnabled && $childPerm && $childUrlActive)
                                <a href="{{ route($child->menu_route) }}"
                                    class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm whitespace-nowrap transition-colors {{ $childIsActive ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                                    <i class="{{ $child->menu_icon }} w-4 text-center text-xs"></i>
                                    {{ __($child->menu_title) }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endforeach

</header>

{{-- Mobile sidebar drawer for horizontal mode (outside header to avoid clipping) --}}
<div id="mobile-nav-drawer" class="fixed inset-0 z-50 lg:hidden" style="display:none">
    <div class="absolute inset-0 bg-black/30" onclick="$('#mobile-nav-drawer').hide()"></div>
    <div class="absolute left-0 top-0 h-full w-72 overflow-y-auto p-3"
        style="background-color: var(--erp-bg); border-right: 1px solid var(--erp-border);">
        <div class="flex items-center justify-between mb-4 px-1">
            <span class="text-base font-semibold" style="color: var(--erp-text);">Menu</span>
            <button style="color: var(--erp-text-secondary);" onclick="$('#mobile-nav-drawer').hide()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        {{-- Reuse sidebar nav for mobile --}}
        @include('layouts-tw.sidebar-nav-items', [
            'menus' => $menus,
            'isMenuActive' => $isMenuActive,
            'isMenuEnabled' => $isMenuEnabled,
            'hasPermissionForAnyChild' => $hasPermissionForAnyChild,
            'userCanAny' => $userCanAny,
        ])
    </div>
</div>

{{-- Horizontal nav: overflow arrows + mega dropdown --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* ── Overflow arrows ── */
        var sc = document.getElementById('hnav-scroll');
        var l = document.getElementById('hnav-left');
        var r = document.getElementById('hnav-right');
        if (sc && l && r) {
            function ck() {
                var overflows = sc.scrollWidth > sc.clientWidth + 1;
                l.style.display = (!overflows || sc.scrollLeft <= 0) ? 'none' : 'flex';
                r.style.display = (!overflows || sc.scrollLeft + sc.clientWidth >= sc.scrollWidth - 1) ?
                    'none' : 'flex';
            }
            l.addEventListener('click', function() {
                sc.scrollBy({
                    left: -200,
                    behavior: 'smooth'
                });
            });
            r.addEventListener('click', function() {
                sc.scrollBy({
                    left: 200,
                    behavior: 'smooth'
                });
            });
            sc.addEventListener('scroll', ck);
            window.addEventListener('resize', ck);
            ck();
            setTimeout(ck, 300);
        }

        /* ── Dropdown toggle ── */
        var activeMenuId = null;
        var header = document.getElementById('erp-top-bar');

        function closeDropdowns() {
            document.querySelectorAll('.erp-hnav-dropdown').forEach(function(p) {
                p.style.display = 'none';
            });
            document.querySelectorAll('[data-hnav-menu]').forEach(function(b) {
                b.classList.remove('erp-hnav-dropdown-open');
            });
            activeMenuId = null;
        }

        function openDropdown(menuId, btn) {
            closeDropdowns();
            var panel = document.querySelector('.erp-hnav-dropdown[data-hnav-panel="' + menuId + '"]');
            if (!panel || !btn || !header) return;

            // Position under the button
            var btnRect = btn.getBoundingClientRect();
            var headerRect = header.getBoundingClientRect();
            var leftPos = btnRect.left - headerRect.left;

            panel.style.display = '';
            panel.style.top = headerRect.bottom + 'px';
            panel.style.left = leftPos + 'px';
            panel.style.position = 'fixed';

            // Keep within viewport
            var panelRect = panel.getBoundingClientRect();
            if (panelRect.right > window.innerWidth - 8) {
                panel.style.left = Math.max(8, window.innerWidth - panelRect.width - 8) + 'px';
            }

            btn.classList.add('erp-hnav-dropdown-open');
            activeMenuId = menuId;
        }

        // Click on nav buttons with children
        document.querySelectorAll('[data-hnav-menu]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                var menuId = this.getAttribute('data-hnav-menu');
                if (activeMenuId === menuId) {
                    closeDropdowns();
                } else {
                    openDropdown(menuId, this);
                }
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (activeMenuId === null) return;
            if (e.target.closest('.erp-hnav-dropdown') || e.target.closest('[data-hnav-menu]')) return;
            closeDropdowns();
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDropdowns();
        });
    });
</script>

{{-- Style year dropdown items --}}
<style>
    #year-dropdown .dropdown-item {
        display: block;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        color: #3f3f46;
        text-decoration: none;
        border-radius: 0.375rem;
        margin: 0 0.25rem;
        cursor: pointer;
        transition: background-color 0.15s;
    }

    #year-dropdown .dropdown-item:hover {
        background-color: #f4f4f5;
    }

    #year-dropdown .dropdown-item.active {
        background-color: var(--erp-primary);
        color: var(--erp-primary-fg);
    }
</style>
