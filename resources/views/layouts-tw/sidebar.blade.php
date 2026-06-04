@php
    use Modules\MenuMaster\Models\MenuMaster;
    use Nwidart\Modules\Facades\Module;

    $showConverted = config('business.show_converted_modules') == 1;
    $convertedModules = $showConverted ? array_map('strtolower', config('business.converted_modules', [])) : [];

    $menus = MenuMaster::parentMenus()
        ->with('children.children')
        ->orderBy('order_display', 'ASC')
        ->orderBy('menu_title', 'ASC')
        ->orderBy('id', 'ASC')
        ->get();

    // How specifically a menu's route matches the CURRENT route (higher = more specific).
    // An exact route-name match always beats a broad "{base}.*" match, so on
    // /orderforms/create the dedicated "Create New Order Form" item wins over the
    // "Manage Order Form" (orderform.index) item instead of both lighting up.
    $currentRouteName = request()->route()?->getName() ?? '';
    $routeMatchScore = function ($menuRoute) use ($currentRouteName) {
        if ($currentRouteName === '' || empty($menuRoute) || $menuRoute === 'javascript:void(0)') {
            return 0;
        }
        if ($currentRouteName === $menuRoute) {
            return strlen($menuRoute) + 1000; // exact match wins
        }
        // A listing item ("…​.index") also covers its record pages (show/edit/…),
        // but NOT sibling actions that have their own menu entry (e.g. create).
        $base = preg_replace('/\.index$/', '', $menuRoute);
        if ($base !== $menuRoute && ($currentRouteName === $base || str_starts_with($currentRouteName, $base . '.'))) {
            return strlen($base);
        }
        if ($base === $menuRoute && str_starts_with($currentRouteName, $menuRoute . '.')) {
            return strlen($menuRoute);
        }
        return 0;
    };

    // Best (most specific) match across the whole tree — only the winner lights up.
    $bestRouteScore = 0;
    $scanBestRoute = function ($items) use (&$scanBestRoute, $routeMatchScore, &$bestRouteScore) {
        foreach ($items as $item) {
            $score = $routeMatchScore($item->menu_route);
            if ($score > $bestRouteScore) {
                $bestRouteScore = $score;
            }
            if ($item->children && $item->children->count() > 0) {
                $scanBestRoute($item->children);
            }
        }
    };
    $scanBestRoute($menus);

    $isMenuActive = function ($menu) use (&$isMenuActive, $routeMatchScore, &$bestRouteScore) {
        if ($bestRouteScore <= 0) {
            return false;
        }
        if ($routeMatchScore($menu->menu_route) === $bestRouteScore) {
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
        if (!$moduleExists) {
            return $isChild ? false : true;
        } else {
            return Module::isEnabled(strtolower($menu->module_name));
        }
    };

    // Resolve a menu row's if_can against the current user. if_can may be a single
    // permission name, a comma-separated list (parent rows), or the special token
    // 'system-administration-access' (Super_Admin only).
    $canForMenu = function ($menu) {
        if (empty($menu->if_can)) {
            return true;
        }
        if ($menu->if_can === 'system-administration-access') {
            return auth()->user()->hasRole('Super_Admin');
        }
        $abilities = array_values(array_filter(array_map('trim', explode(',', $menu->if_can))));
        if (empty($abilities)) {
            return true;
        }
        return auth()->user()->canany($abilities);
    };

    $hasPermissionForAnyChild = function ($menu) use (&$hasPermissionForAnyChild, $isMenuEnabled, $canForMenu) {
        if (!$menu->children || $menu->children->count() == 0) {
            return false;
        }
        foreach ($menu->children as $child) {
            $childEnabled =
                $child->children->count() > 0 ? $isMenuEnabled($child, false) : $isMenuEnabled($child, true);
            if ($childEnabled) {
                if ($canForMenu($child)) {
                    return true;
                }
                if ($hasPermissionForAnyChild($child)) {
                    return true;
                }
            }
        }
        return false;
    };

    $hasPermissionForAnyGrandchild = function ($child) use ($isMenuEnabled, $canForMenu) {
        if (!$child->children || $child->children->count() == 0) {
            return false;
        }
        foreach ($child->children as $grandchild) {
            if ($isMenuEnabled($grandchild, true) && $canForMenu($grandchild)) {
                return true;
            }
        }
        return false;
    };
@endphp

{{-- Sidebar --}}
<aside id="erp-sidebar-nav"
    class="erp-sidebar fixed top-0 left-0 z-50 h-screen w-64 border-r border-zinc-200 flex flex-col overflow-hidden">

    {{-- Logo row --}}
    <div class="erp-sidebar-header flex items-center h-14 border-b border-zinc-200 shrink-0">
        {{-- Expanded state --}}
        <div id="sidebar-expanded-header" class="flex items-center w-full px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center min-w-0">
                <span class="erp-logo-wrap erp-logo-wrap--sidebar">
                    @if (setting()->logo != '')
                        <img src="{{ asset('setting/logo/' . setting()->logo) }}" class="h-8 object-contain shrink-0 erp-logo" alt="{{ config('app.name') }}">
                    @else
                        <img src="{{ asset('assets-tw/img/logo.png') }}" class="h-8 object-contain shrink-0 erp-logo" style="max-width: 160px;" alt="{{ config('app.name') }}">
                    @endif
                </span>
            </a>
            <button id="sidebar-collapse-btn"
                class="ml-auto text-zinc-400 hover:text-zinc-600 p-1 rounded-md hover:bg-zinc-100 shrink-0"
                title="Collapse sidebar">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
        </div>
        {{-- Collapsed state --}}
        <div id="sidebar-collapsed-header" class="flex items-center justify-between w-full px-2" style="display:none">
            <a href="{{ route('dashboard') }}" class="shrink-0">
                <span class="erp-logo-wrap erp-logo-wrap--sidebar-sm">
                    @if (setting()->logo != '')
                        <img src="{{ asset('setting/logo/' . setting()->logo) }}" class="h-7 w-7 object-contain erp-logo" alt="{{ config('app.name') }}">
                    @else
                        <img src="{{ asset('assets-tw/img/favicon.png') }}" class="h-7 w-7 object-contain erp-logo" alt="{{ config('app.name') }}">
                    @endif
                </span>
            </a>
            <button id="sidebar-expand-btn"
                class="shrink-0 text-zinc-400 hover:text-zinc-600 h-6 w-6 flex items-center justify-center rounded hover:bg-zinc-100"
                title="Expand sidebar">
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </button>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2">
        {{-- Menu Groups --}}
        @foreach ($menus as $menu)
            @php
                if ($menu->module_name != null) {
                    $moduleName = Module::getModulePath($menu->module_name);
                    if (!file_exists($moduleName)) {
                        continue;
                    }
                }

                $enabled = $isMenuEnabled($menu, false);
                $isActive = $isMenuActive($menu);

                $menuUrlActive = false;
                if (
                    $menu->menu_route != 'javascript:void(0)' &&
                    !is_null($menu->menu_route) &&
                    $menu->menu_route != ''
                ) {
                    if (Route::has($menu->menu_route)) {
                        $menuUrlActive = true;
                    }
                } else {
                    $menuUrlActive = true;
                }

                $hasOwnPermission = $canForMenu($menu);
                $hasChildPermission = $hasPermissionForAnyChild($menu);
                $shouldShowMenu = $enabled && ($hasOwnPermission || $hasChildPermission);
            @endphp

            @if ($shouldShowMenu && $menuUrlActive)
                @if ($menu->children->count() > 0)
                    {{-- Group with children --}}
                    <div class="erp-nav-group mb-1">
                        <button
                            class="erp-sidebar-group erp-sidebar-link w-full flex items-center gap-2 px-2 py-2 rounded-md text-sm transition-colors
                                       {{ $isActive ? 'text-zinc-900 font-medium' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900' }}"
                            title="{{ __($menu->menu_title) }}"
                            onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? '' : 'none'; this.querySelector('.erp-chevron').classList.toggle('-rotate-90')">
                            <i class="{{ $menu->menu_icon }} w-4 text-center text-xs"></i>
                            <span class="erp-sidebar-label flex-1 text-left">{{ __($menu->menu_title) }}</span>
                            @if ($showConverted)
                                @php
                                    $parentConverted = false;
                                    if ($menu->module_name && in_array(strtolower($menu->module_name), $convertedModules)) {
                                        $parentConverted = true;
                                    } elseif ($menu->children->isNotEmpty()) {
                                        $parentConverted = $menu->children->every(function ($child) use ($convertedModules) {
                                            if ($child->children->isNotEmpty()) {
                                                return $child->children->every(fn ($gc) =>
                                                    $gc->module_name && in_array(strtolower($gc->module_name), $convertedModules)
                                                );
                                            }
                                            return $child->module_name && in_array(strtolower($child->module_name), $convertedModules);
                                        });
                                    }
                                @endphp
                                @if ($parentConverted)
                                    <i class="erp-sidebar-label fa-solid fa-circle-check text-emerald-500 text-xs" title="Converted to Tailwind"></i>
                                @endif
                            @endif
                            <i
                                class="erp-sidebar-label erp-chevron fa-solid fa-chevron-down text-[10px] transition-transform {{ $isActive ? '' : '-rotate-90' }}"></i>
                        </button>
                        <div class="erp-sidebar-submenu pl-4 mt-0.5" {!! $isActive ? '' : 'style="display:none"' !!}>
                            @foreach ($menu->children as $child)
                                @php
                                    if ($child->module_name != null) {
                                        $childmoduleName = Module::getModulePath($child->module_name);
                                        if (!file_exists($childmoduleName)) {
                                            continue;
                                        }
                                    }

                                    $childEnabled =
                                        $child->children->count() > 0
                                            ? $isMenuEnabled($child, false)
                                            : $isMenuEnabled($child, true);
                                    $childIsActive = $isMenuActive($child);

                                    $hasChildOwnPermission = $canForMenu($child);
                                    $hasGrandchildPermission = $hasPermissionForAnyGrandchild($child);
                                    $shouldShowChild =
                                        $childEnabled && ($hasChildOwnPermission || $hasGrandchildPermission);

                                    $menuChildUrlActive = false;
                                    if (
                                        $child->menu_route !== 'javascript:void(0)' &&
                                        $child->menu_route !== null &&
                                        $child->menu_route !== ''
                                    ) {
                                        if (Route::has($child->menu_route)) {
                                            $menuChildUrlActive = true;
                                        }
                                    } else {
                                        $menuChildUrlActive = true;
                                    }
                                @endphp

                                @if ($shouldShowChild && $menuChildUrlActive)
                                    @if ($child->children->count() > 0)
                                        {{-- Child with grandchildren --}}
                                        <div class="mb-0.5">
                                            <button
                                                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-sm transition-colors
                                                           {{ $childIsActive ? 'text-zinc-900 font-medium' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900' }}"
                                                onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? '' : 'none'; this.querySelector('.erp-chevron').classList.toggle('-rotate-90')">
                                                <i class="{{ $child->menu_icon }} w-4 text-center text-xs"></i>
                                                <span
                                                    class="erp-sidebar-label flex-1 text-left">{{ __($child->menu_title) }}</span>
                                                @if ($showConverted && in_array(strtolower($child->module_name ?? ''), $convertedModules))
                                                    <i class="erp-sidebar-label fa-solid fa-circle-check text-emerald-500 text-xs" title="Converted to Tailwind"></i>
                                                @endif
                                                <i
                                                    class="erp-sidebar-label erp-chevron fa-solid fa-chevron-down text-[10px] transition-transform {{ $childIsActive ? '' : '-rotate-90' }}"></i>
                                            </button>
                                            <div class="pl-4 mt-0.5" {!! $childIsActive ? '' : 'style="display:none"' !!}>
                                                @foreach ($child->children as $grandchild)
                                                    @php
                                                        if ($grandchild->module_name != null) {
                                                            $grandchildmoduleName = Module::getModulePath(
                                                                $grandchild->module_name,
                                                            );
                                                            if (!file_exists($grandchildmoduleName)) {
                                                                continue;
                                                            }
                                                        }
                                                        $grandchildEnabled = $isMenuEnabled($grandchild, true);
                                                        $grandchildIsActive =
                                                            $bestRouteScore > 0 &&
                                                            $routeMatchScore($grandchild->menu_route) === $bestRouteScore;

                                                        $menuGrandChildUrlActive = false;
                                                        if (
                                                            $grandchild->menu_route !== 'javascript:void(0)' &&
                                                            $grandchild->menu_route !== null &&
                                                            $grandchild->menu_route !== ''
                                                        ) {
                                                            if (Route::has($grandchild->menu_route)) {
                                                                $menuGrandChildUrlActive = true;
                                                            }
                                                        } else {
                                                            $menuGrandChildUrlActive = true;
                                                        }
                                                    @endphp

                                                    @if ($grandchildEnabled && $menuGrandChildUrlActive && $canForMenu($grandchild) && $grandchild->menu_route !== 'javascript:void(0)')
                                                        <a href="{{ route($grandchild->menu_route) }}"
                                                            class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm transition-colors
                                                                  {{ $grandchildIsActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900' }}">
                                                            <i
                                                                class="{{ $grandchild->menu_icon }} w-4 text-center text-xs"></i>
                                                            <span
                                                                class="erp-sidebar-label">{{ __($grandchild->menu_title) }}</span>
                                                            @if ($showConverted && in_array(strtolower($grandchild->module_name ?? ''), $convertedModules))
                                                                <i class="erp-sidebar-label fa-solid fa-circle-check text-emerald-500 text-xs" title="Converted to Tailwind"></i>
                                                            @endif
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif ($child->menu_route !== 'javascript:void(0)')
                                        {{-- Child link (no grandchildren) --}}
                                        <a href="{{ route($child->menu_route) }}"
                                            class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm transition-colors mb-0.5
                                                  {{ $childIsActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900' }}">
                                            <i class="{{ $child->menu_icon }} w-4 text-center text-xs"></i>
                                            <span class="erp-sidebar-label">{{ __($child->menu_title) }}</span>
                                            @if ($showConverted && in_array(strtolower($child->module_name ?? ''), $convertedModules))
                                                <i class="erp-sidebar-label fa-solid fa-circle-check text-emerald-500 text-xs" title="Converted to Tailwind"></i>
                                            @endif
                                        </a>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @elseif ($menu->menu_route !== 'javascript:void(0)')
                    {{-- Single link (no children) --}}
                    <div class="mb-1">
                        <a href="{{ route($menu->menu_route) }}"
                            class="erp-sidebar-link flex items-center gap-2 px-2 py-2 rounded-md text-sm transition-colors
                                  {{ $isActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                            title="{{ __($menu->menu_title) }}">
                            <i class="{{ $menu->menu_icon }} w-4 text-center text-xs"></i>
                            <span class="erp-sidebar-label">{{ __($menu->menu_title) }}</span>
                            @if ($showConverted && in_array(strtolower($menu->module_name ?? ''), $convertedModules))
                                <i class="erp-sidebar-label fa-solid fa-circle-check text-emerald-500 text-xs" title="Converted to Tailwind"></i>
                            @endif
                        </a>
                    </div>
                @endif
            @endif
        @endforeach
    </nav>

    {{-- User footer --}}
    <div class="erp-sidebar-label shrink-0 p-3 border-t border-zinc-200">
        <div class="flex items-center gap-2">
            <div class="h-8 w-8 rounded-full bg-zinc-200 flex items-center justify-center shrink-0 overflow-hidden">
                @php($userPhoto = Auth::user()->profile?->profile_photo)
                @if ($userPhoto)
                    <img src="{{ asset('storage/' . $userPhoto) }}" alt="{{ Auth::user()->name }}" class="h-full w-full object-cover">
                @else
                    <span class="text-zinc-600 font-medium text-xs">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-zinc-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-zinc-500 truncate">{{ Auth::user()->roles[0]->name ?? '' }}</p>
            </div>
        </div>
    </div>
</aside>

{{-- Mobile overlay --}}
<div id="erp-overlay" class="fixed inset-0 bg-black/30 z-40" style="display:none"></div>
