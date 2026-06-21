@php
    use Nwidart\Modules\Facades\Module;

    // Helper: check permissions with comma-separated if_can strings
    $userCanAny = $userCanAny ?? function ($ifCan) {
        if (empty($ifCan)) return true;
        if ($ifCan === 'system-administration-access') return auth()->user()->hasRole('Super_Admin');
        return auth()->user()->canany(array_map('trim', explode(',', $ifCan)));
    };

    $hasPermissionForAnyGrandchild = function ($child) use ($isMenuEnabled, $userCanAny) {
        if (!$child->children || $child->children->count() == 0) return false;
        foreach ($child->children as $grandchild) {
            $gcEnabled = $isMenuEnabled($grandchild, true);
            if ($gcEnabled && $userCanAny($grandchild->if_can)) return true;
        }
        return false;
    };
@endphp

{{-- Dashboard --}}
<a href="{{ route('dashboard') }}"
   class="flex items-center gap-2 px-2 py-2 rounded-md text-sm mb-1 transition-colors
          {{ request()->routeIs('dashboard') ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100' }}">
    <i class="fa-solid fa-house w-4 text-center text-xs"></i>
    <span>{{ __('lang.dashboard') }}</span>
</a>

@foreach ($menus as $menu)
    @php
        // Dashboard is rendered as a fixed link above; skip its menu row to avoid duplication.
        if ($menu->menu_route === 'dashboard') continue;
        if ($menu->module_name != null && !file_exists(Module::getModulePath($menu->module_name))) continue;
        $enabled = $isMenuEnabled($menu, false);
        $isActive = $isMenuActive($menu);
        $menuUrlActive = ($menu->menu_route != 'javascript:void(0)' && !is_null($menu->menu_route) && $menu->menu_route != '') ? Route::has($menu->menu_route) : true;
        $hasOwnPermission = $userCanAny($menu->if_can);
        $shouldShowMenu = $enabled && ($hasOwnPermission || $hasPermissionForAnyChild($menu));
    @endphp

    @if ($shouldShowMenu && $menuUrlActive)
        @if ($menu->children->count() > 0)
            <div class="mb-1">
                <button class="w-full flex items-center gap-2 px-2 py-2 rounded-md text-sm transition-colors {{ $isActive ? 'text-zinc-900 font-medium' : 'text-zinc-500 hover:bg-zinc-100' }}"
                        onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'':'none'; this.querySelector('.erp-chevron').classList.toggle('-rotate-90')">
                    <i class="{{ $menu->menu_icon }} w-4 text-center text-xs"></i>
                    <span class="flex-1 text-left">{{ __($menu->menu_title) }}</span>
                    <i class="erp-chevron fa-solid fa-chevron-down text-[10px] transition-transform {{ $isActive ? '' : '-rotate-90' }}"></i>
                </button>
                <div class="pl-4 mt-0.5" {!! $isActive ? '' : 'style="display:none"' !!}>
                    @foreach ($menu->children as $child)
                        @php
                            if ($child->module_name != null && !file_exists(Module::getModulePath($child->module_name))) continue;
                            $cEnabled = $child->children->count() > 0 ? $isMenuEnabled($child, false) : $isMenuEnabled($child, true);
                            $cActive = $isMenuActive($child);
                            $cPerm = $userCanAny($child->if_can);
                            $cGrandPerm = $hasPermissionForAnyGrandchild($child);
                            $cShow = $cEnabled && ($cPerm || $cGrandPerm);
                            $cUrlActive = ($child->menu_route !== 'javascript:void(0)' && $child->menu_route !== null && $child->menu_route !== '') ? Route::has($child->menu_route) : true;
                        @endphp
                        @if ($cShow && $cUrlActive)
                            <a href="{{ $child->children->count() > 0 ? 'javascript:void(0)' : route($child->menu_route) }}"
                               class="flex items-center gap-2 px-2 py-1.5 rounded-md text-sm mb-0.5 transition-colors {{ $cActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-500 hover:bg-zinc-100' }}">
                                <i class="{{ $child->menu_icon }} w-4 text-center text-xs"></i>
                                {{ __($child->menu_title) }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ route($menu->menu_route) }}"
               class="flex items-center gap-2 px-2 py-2 rounded-md text-sm mb-1 transition-colors {{ $isActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-600 hover:bg-zinc-100' }}">
                <i class="{{ $menu->menu_icon }} w-4 text-center text-xs"></i>
                {{ __($menu->menu_title) }}
            </a>
        @endif
    @endif
@endforeach
