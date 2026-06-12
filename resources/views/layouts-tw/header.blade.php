{{-- ERP Header — Vertical Layout --}}
<header id="erp-top-bar"
    class="erp-header fixed top-0 right-0 left-0 lg:left-64 z-20 h-14 border-b border-zinc-200 flex items-center px-3 sm:px-4 gap-2 sm:gap-3">

    {{-- Mobile hamburger --}}
    <button id="hamburger-btn"
        class="lg:hidden text-zinc-500 hover:text-zinc-700 p-2 rounded-md hover:bg-zinc-100 shrink-0">
        <i class="fa-solid fa-bars"></i>
    </button>

    {{-- Mobile logo — icon-only on xs, full logo on sm+ --}}
    <a href="{{ route('dashboard') }}" class="flex lg:hidden items-center shrink-0">
        <span class="erp-logo-wrap erp-logo-wrap--mobile">
            @if (setting()->logo != '')
                <img src="{{ asset('setting/logo/' . setting()->logo) }}" class="h-8 object-contain erp-logo"
                    alt="{{ config('app.name') }}">
            @else
                <img src="{{ asset('assets-tw/img/favicon.png') }}" class="h-8 w-8 object-contain erp-logo sm:hidden"
                    alt="{{ config('app.name') }}">
                <img src="{{ asset('assets-tw/img/logo.png') }}" class="h-8 object-contain erp-logo hidden sm:block"
                    style="max-width: 160px;" alt="{{ config('app.name') }}">
            @endif
        </span>
    </a>

    {{-- Breadcrumb (desktop) --}}
    <div id="erp-breadcrumb" class="hidden lg:flex items-center text-sm text-zinc-500 min-w-0 truncate">
    </div>

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
                $currentYear = getYearList(true)->firstWhere('id', $sessionYearId);
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
                {{-- getYear() outputs <a class="dropdown-item year-change"> — style them via CSS below --}}
                {!! getYear(false, false) !!}
            </div>
            <div class="year-search-results px-2 pb-2"></div>
        </div>
    </div>

    {{-- Impersonation UI --}}
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
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-1.5">
                <i class="fa-solid fa-user-secret text-xs"></i>
                <span class="hidden sm:inline">Impersonate</span>
            </button>
            <div class="dropdown-menu absolute top-full right-0 mt-1 bg-white border border-zinc-200 rounded-lg shadow-lg p-3 z-50"
                style="display:none; width: 340px;">
                <input type="text"
                    class="impersonate-search-input w-full h-9 rounded-md border border-zinc-200 bg-zinc-50 px-3 text-sm placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-1 mb-2"
                    placeholder="Search by name, email or role..." autocomplete="off">
                <div class="impersonate-user-list max-h-72 overflow-y-auto">
                    <p class="text-sm text-zinc-400 text-center py-2">Type to search users...</p>
                </div>
            </div>
        </div>
        @endCanImpersonate
    @endif

    {{-- Dark mode toggle --}}
    <button id="dark-mode-btn" class="p-2 text-zinc-500 hover:text-zinc-700 rounded-md hover:bg-zinc-100 shrink-0"
        title="Toggle dark mode">
        <i class="fa-solid fa-moon erp-dark-icon"></i>
        <i class="fa-solid fa-sun erp-light-icon" style="display:none"></i>
    </button>

    {{-- Layout toggle (switch to horizontal) --}}
    <button class="p-2 text-zinc-500 hover:text-zinc-700 rounded-md hover:bg-zinc-100 shrink-0"
        title="Switch to horizontal layout" onclick="switchLayout('horizontal')">
        <i class="fa-solid fa-table-columns"></i>
    </button>

    {{-- Sidebar collapse/expand (desktop only) --}}
    <button id="header-sidebar-toggle"
        class="hidden lg:flex p-2 text-zinc-500 hover:text-zinc-700 rounded-md hover:bg-zinc-100 shrink-0"
        title="Collapse/expand sidebar">
        <i class="fa-solid fa-bars-staggered"></i>
    </button>

    {{-- User menu --}}
    <div class="relative shrink-0">

        <button id="user-menu-btn" class="flex items-center gap-2 p-1.5 rounded-md hover:bg-zinc-100">
            <div class="h-8 w-8 rounded-full bg-zinc-200 flex items-center justify-center shrink-0 overflow-hidden">
                @php($userPhoto = Auth::user()->profile?->profile_photo)
                @if ($userPhoto)
                    <img src="{{ asset('storage/' . $userPhoto) }}" alt="{{ Auth::user()->name }}" class="h-full w-full object-cover">
                @else
                    <span class="text-zinc-600 font-medium text-xs">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                @endif
            </div>
            <span class="hidden sm:inline text-sm font-medium text-zinc-700">{{ Auth::user()->name }}</span>
            <i class="hidden sm:inline fa-solid fa-chevron-down text-xs text-zinc-400"></i>
        </button>
        <div id="user-menu-dropdown"
            class="absolute top-full right-0 mt-1 w-48 bg-white border border-zinc-200 rounded-lg shadow-lg py-1 z-50"
            style="display:none">
            @can('password-change')
                <a href="{{ route('profile') }}"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                    <i class="fa-solid fa-user text-zinc-400 w-4"></i> {{ __('user::message.my_profile') }}
                </a>
                <a href="{{ route('profile.change-password') }}"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                    <i class="fa-solid fa-key text-zinc-400 w-4"></i> {{ __('user::message.change_password') }}
                </a>
                <a href="{{ route('menumasters.index') }}"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
                    <i class="fa-solid fa-bars text-zinc-400 w-4"></i> {{ __('menumaster::message.add') }}
                </a>
            @endcan
            <div class="border-t border-zinc-100 my-1"></div>
            <a href="javascript:void(0);" onclick="$('#globalLogoutModal').removeClass('hidden')"
                class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                <i class="fa-solid fa-right-from-bracket text-red-400 w-4"></i> {{ __('user::message.Logout') }}
            </a>
        </div>
    </div>
</header>

{{-- Style Bootstrap classes output by PHP helpers (getYear, impersonate) --}}
<style>
    /* Year dropdown items */
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

    /* Impersonate search results — Bootstrap class shims */
    .impersonate-user-list .dropdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.5rem;
        font-size: 0.875rem;
        color: #3f3f46;
        text-decoration: none;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.15s;
    }

    .impersonate-user-list .dropdown-item:hover {
        background-color: #f4f4f5;
    }

    .impersonate-user-list .d-flex {
        display: flex;
    }

    .impersonate-user-list .d-block {
        display: block;
    }

    .impersonate-user-list .justify-content-between {
        justify-content: space-between;
    }

    .impersonate-user-list .align-items-center {
        align-items: center;
    }

    .impersonate-user-list .text-muted {
        color: #71717a;
        font-size: 0.75rem;
    }

    .impersonate-user-list strong {
        font-weight: 600;
        color: var(--erp-text);
    }

    .impersonate-user-list small {
        font-size: 0.75rem;
    }
</style>
