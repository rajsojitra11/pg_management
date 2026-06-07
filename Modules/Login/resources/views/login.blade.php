@extends('layouts.guest-tw')
@section('title', 'Sign In — ' . config('app.name'))

@section('content')
    <div class="flex min-h-screen">

        {{-- ═══════════════════════════════════════════════════
       LEFT: Desktop Branding Panel (1200px+)
       ═══════════════════════════════════════════════════ --}}
        <div
            class="auth-hero auth-hero-panel auth-desktop-only relative overflow-hidden flex-col items-center justify-center p-12 text-white">
            <div class="relative z-10 max-w-md">
                {{-- Logo --}}

                <div class="flex items-center gap-3 mb-10">
                    <div class="auth-glass-logo h-12 w-12 rounded-lg flex items-center justify-center">
                        @if (setting()->logo != '')
                            <img src="{{ asset('setting/logo/' . setting()->logo) }}" class="h-10 w-10 object-contain" alt="{{ config('app.name') }}">
                        @else
                            <i class="fa-solid fa-building text-sm text-white"></i>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-white auth-font-heading">
                            {{ config('app.name', 'ERP Suite') }}</h1>
                        <p class="text-sm auth-text-60">{{ __('login::message.hero_tagline') }}</p>
                    </div>
                </div>

                {{-- Headline --}}
                <h2 class="text-3xl font-bold leading-tight text-white mb-3 auth-font-heading">
                    {{ __('login::message.hero_headline_line1') }}<br>{{ __('login::message.hero_headline_line2') }}
                </h2>
                <p class="text-base mb-10 leading-relaxed auth-text-70">
                    {{ __('login::message.hero_subheadline') }}
                </p>

                {{-- Features --}}
                <div class="space-y-4 mb-12">
                    <div class="auth-hero-feature flex items-center gap-3">
                        <div class="auth-bg-12 h-8 w-8 rounded-lg flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-bed text-sm text-white"></i>
                        </div>
                        <span class="text-sm auth-text-90">{{ __('login::message.hero_feature_job_cards') }}</span>
                    </div>
                    <div class="auth-hero-feature flex items-center gap-3">
                        <div class="auth-bg-12 h-8 w-8 rounded-lg flex items-center justify-center shrink-0">
                             <i class="fa-solid fa-building text-sm text-white"></i>
                        </div>
                        <span class="text-sm auth-text-90">{{ __('login::message.hero_feature_machines') }}</span>
                    </div>
                    <div class="auth-hero-feature flex items-center gap-3">
                        <div class="auth-bg-12 h-8 w-8 rounded-lg flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-money-bill-transfer text-sm text-white"></i>
                        </div>
                        <span class="text-sm auth-text-90">{{ __('login::message.hero_feature_delivery') }}</span>
                    </div>
                    <div class="auth-hero-feature flex items-center gap-3">
                        <div class="auth-bg-12 h-8 w-8 rounded-lg flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-invoice-dollar text-sm text-white"></i>
                        </div>
                        <span class="text-sm auth-text-90">{{ __('login::message.hero_feature_reports') }}</span>
                    </div>
                </div>
            </div>

            {{-- Decorative circles --}}
            <div class="auth-deco-circle-tr absolute rounded-full"></div>
            <div class="auth-deco-circle-bl absolute rounded-full"></div>
        </div>

        {{-- ═══════════════════════════════════════════════════
       RIGHT: Form Panel (all screens)
       ═══════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col bg-zinc-50 relative auth-form-panel">

            {{-- Mobile/Tablet Hero Banner (below 1200px) --}}
            <div class="auth-mobile-only auth-hero-mobile relative overflow-hidden text-white">
                <div class="auth-float auth-mobile-circle-1 absolute rounded-full"></div>
                <div class="auth-float-delay auth-mobile-circle-2 absolute rounded-full"></div>
                <div class="auth-mobile-circle-center absolute rounded-full"></div>
                <div class="auth-dot-1 absolute rounded-full"></div>
                <div class="auth-dot-2 absolute rounded-full"></div>
                <div class="auth-dot-3 absolute rounded-full"></div>

                {{-- Top bar --}}
                <div class="auth-hero-topbar relative z-10 flex items-center justify-between p-4">
                    <div class="h-8 w-8"></div>
                    <div
                        class="auth-glass-logo auth-glass-logo-shadow h-9 w-10 rounded-lg flex items-center justify-center shrink-0">
                        @if (setting()->logo != '')
                            <img src="{{ asset('setting/logo/' . setting()->logo) }}" class="h-7 w-7 object-contain" alt="{{ config('app.name') }}">
                        @else
                            <i class="fa-solid fa-building text-sm text-white"></i>
                        @endif
                    </div>
                    <button id="dark-mode-btn-mobile"
                        class="auth-glass-btn h-8 w-8 rounded-lg flex items-center justify-center text-white transition-colors shrink-0"
                        title="Toggle dark mode">
                        <i class="fa-solid fa-moon text-xs erp-dark-icon"></i>
                        <i class="fa-solid fa-sun text-xs erp-light-icon" style="display:none"></i>
                    </button>
                </div>

                {{-- Branding text + pills --}}
                <div class="auth-hero-content relative z-10 flex flex-col items-center text-center px-6 pb-0 pt-1">
                    <div class="auth-hero-brand">
                        <h1 class="text-lg font-bold tracking-tight text-white mb-0.5 auth-font-heading">
                            {{ config('app.name', 'ERP Suite') }}</h1>
                        <p class="text-sm mb-3 auth-text-70">{{ __('login::message.hero_tagline') }}</p>
                    </div>
                    <div class="auth-hero-pills flex flex-wrap items-center justify-center gap-2 mb-1">
                        <span
                            class="auth-pill-animate auth-glass-pill inline-flex items-center gap-1.5 rounded-full px-3 py-1">
                            <i class="fa-solid fa-clipboard-list auth-pill-icon"></i>
                            <span class="text-xs auth-text-90">{{ __('login::message.hero_pill_job_cards') }}</span>
                        </span>
                        <span
                            class="auth-pill-animate auth-glass-pill inline-flex items-center gap-1.5 rounded-full px-3 py-1">
                            <i class="fa-solid fa-industry auth-pill-icon"></i>
                            <span class="text-xs auth-text-90">{{ __('login::message.hero_pill_machines') }}</span>
                        </span>
                        <span
                            class="auth-pill-animate auth-glass-pill inline-flex items-center gap-1.5 rounded-full px-3 py-1">
                            <i class="fa-solid fa-truck-fast auth-pill-icon"></i>
                            <span class="text-xs auth-text-90">{{ __('login::message.hero_pill_delivery') }}</span>
                        </span>
                        <span
                            class="auth-pill-animate auth-glass-pill inline-flex items-center gap-1.5 rounded-full px-3 py-1">
                            <i class="fa-solid fa-chart-pie auth-pill-icon"></i>
                            <span class="text-xs auth-text-90">{{ __('login::message.hero_pill_reports') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Desktop top bar (1200px+) --}}
            <div class="auth-desktop-only auth-desktop-topbar">
                <div></div>
                <button id="dark-mode-btn-desktop"
                    class="h-9 w-9 rounded-md bg-zinc-900 text-white hover:bg-zinc-800 flex items-center justify-center transition-colors"
                    title="Toggle dark mode">
                    <i class="fa-solid fa-moon text-sm erp-dark-icon"></i>
                    <i class="fa-solid fa-sun text-sm erp-light-icon" style="display:none"></i>
                </button>
            </div>

            {{-- Form area --}}
            <div class="flex-1 flex items-start justify-center px-5 sm:px-8 auth-form-overlap auth-form-area">
                <div class="auth-form-entrance w-full max-w-sm relative z-10">

                    {{-- Form card --}}
                    <div class="rounded-lg border bg-white shadow-sm p-6 sm:p-8">

                        {{-- Header --}}
                        <div class="text-center mb-6">
                            {{-- Desktop-only logo inside card --}}
                            <div class="auth-desktop-only items-center justify-center mb-4">
                                @if (setting()->logo != '')
                                    <span class="erp-logo-wrap erp-logo-wrap--login-card">
                                        <img src="{{ asset('setting/logo/' . setting()->logo) }}"
                                            class="h-11 object-contain" alt="{{ config('app.name') }}">
                                    </span>
                                @else
                                    <div class="h-11 w-11 rounded-lg bg-zinc-900 flex items-center justify-center">
                                        <i class="fa-solid fa-building text-white text-lg"></i>
                                    </div>
                                @endif
                            </div>
                            <h2 class="text-xl font-bold text-zinc-900 auth-font-heading">{{ __('login::message.welcome_back') }}</h2>
                            <p class="text-sm text-zinc-500 mt-1">{{ __('login::message.signin_subtitle') }}</p>
                        </div>

                        {{-- Form --}}
                        <form method="POST" action="{{ route('login') }}" id="formAuthentication" class="space-y-4"
                            novalidate>
                            @csrf

                            {{-- Username / Email --}}
                            <div>
                                <label for="login" class="block text-sm font-medium text-zinc-700 mb-1.5">
                                    {{ __('login::message.username_or_email') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 z-10 text-zinc-400">
                                        <i class="fa-solid fa-user text-xs"></i>
                                    </span>
                                    <input id="login" name="login" type="text" value="{{ old('login') }}"
                                        required autofocus autocomplete="username"
                                        class="h-9 w-full rounded-md border border-zinc-200 bg-white pr-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 transition-colors"
                                        style="padding-left: 2.25rem"
                                        placeholder="{{ __('login::message.username_or_email_placeholder') }}">
                                </div>
                            </div>

                            {{-- Password --}}
                            <div>
                                <label for="password" class="block text-sm font-medium text-zinc-700 mb-1.5">
                                    {{ __('login::message.password') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 z-10 text-zinc-400">
                                        <i class="fa-solid fa-lock text-xs"></i>
                                    </span>
                                    <input id="password" name="password" type="password" required
                                        autocomplete="current-password"
                                        class="h-9 w-full rounded-md border border-zinc-200 bg-white text-sm text-zinc-900 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 transition-colors"
                                        style="padding-left: 2.25rem; padding-right: 2.25rem"
                                        placeholder="{{ __('login::message.password_placeholder') }}">
                                    <button type="button" tabindex="-1"
                                        class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 z-10 text-zinc-400 hover:text-zinc-600 transition-colors">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Remember Me --}}
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="remember_me" name="remember" value="1"
                                        class="h-4 w-4 rounded border-zinc-200 text-zinc-900 focus:ring-1 focus:ring-zinc-500">
                                    <span class="text-sm text-zinc-600">{{ __('login::message.remember_me') }}</span>
                                </label>
                            </div>

                            {{-- Validation Errors --}}
                            @if ($errors->any())
                                <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                                    <ul class="space-y-1">
                                        @foreach ($errors->all() as $err)
                                            <li class="text-sm text-red-700"><i
                                                    class="fa-solid fa-circle-exclamation text-xs mr-1.5"></i>{{ $err }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Submit --}}
                            <button type="submit" id="loginSubmitBtn"
                                class="w-full h-9 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center justify-center transition-colors focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 disabled:opacity-70 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-right-to-bracket mr-1.5 text-xs"></i> {{ __('login::message.login') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Footer pinned to bottom --}}
            <div class="text-center py-4">
                <p class="text-xs text-zinc-400">
                    {{ __('login::message.footer_copyright', ['year' => date('Y')]) }}
                </p>
            </div>
        </div>
    </div>
@endsection

@push('page-scripts')
    <script>
        (function() {
            'use strict';

            /* ── Dark mode ──────────────────────────────────── */
            var DARK_KEY = 'erp-dark-mode';

            function isDark() {
                var v = localStorage.getItem(DARK_KEY);
                if (v !== null) return v === 'true';
                return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            function applyDark(dark) {
                document.documentElement.classList.toggle('dark', dark);
                document.querySelectorAll('.erp-dark-icon').forEach(function(el) {
                    el.style.display = dark ? 'none' : '';
                });
                document.querySelectorAll('.erp-light-icon').forEach(function(el) {
                    el.style.display = dark ? '' : 'none';
                });
            }

            applyDark(isDark());

            ['dark-mode-btn-mobile', 'dark-mode-btn-desktop'].forEach(function(id) {
                var btn = document.getElementById(id);
                if (btn) btn.addEventListener('click', function() {
                    var dark = !document.documentElement.classList.contains('dark');
                    localStorage.setItem(DARK_KEY, dark ? 'true' : 'false');
                    applyDark(dark);
                });
            });

            /* ── Password toggle ────────────────────────────── */
            document.querySelectorAll('.toggle-password').forEach(function(b) {
                b.addEventListener('click', function() {
                    var input = b.parentElement.querySelector('input');
                    var isPass = input.type === 'password';
                    input.type = isPass ? 'text' : 'password';
                    b.querySelector('i').className = isPass ?
                        'fa-solid fa-eye-slash text-xs' :
                        'fa-solid fa-eye text-xs';
                });
            });

            /* ── Entrance animations ────────────────────────── */
            var card = document.querySelector('.auth-form-entrance');
            if (card) setTimeout(function() {
                card.classList.add('visible');
            }, 100);

            document.querySelectorAll('.auth-hero-feature').forEach(function(el, i) {
                setTimeout(function() {
                    el.classList.add('visible');
                }, 300 + i * 120);
            });

            document.querySelectorAll('.auth-pill-animate').forEach(function(el, i) {
                setTimeout(function() {
                    el.classList.add('visible');
                }, 400 + i * 100);
            });

            /* ── Submit loader ──────────────────────────────── */
            var authForm = document.getElementById('formAuthentication');
            var submitBtn = document.getElementById('loginSubmitBtn');
            if (authForm && submitBtn) {
                var submitting = false;
                authForm.addEventListener('submit', function(e) {
                    if (submitting) {
                        e.preventDefault();
                        return;
                    }
                    submitting = true;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<i class="fa-solid fa-circle-notch fa-spin mr-1.5 text-xs"></i> {{ __('login::message.login') }}';
                });
            }

        })();
    </script>
@endpush
