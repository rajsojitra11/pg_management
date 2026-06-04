<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon"
        href="{{ setting()->favicon != '' ? asset('setting/favicon/' . setting()->favicon) : asset('assets-tw/img/fav.png') }}">

    {{-- Dark mode + sidebar state: apply before paint to prevent flash --}}
    <script>
        var _savedTheme = localStorage.getItem('erp-dark-mode');
        if (_savedTheme === 'true' ||
            (_savedTheme === null && '{{ auth()->user()->theme ?? 'light' }}' === 'dark') ||
            (_savedTheme === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        // Pre-compute sidebar width for initial CSS to avoid layout shift
        window.__erpLayoutMode = '{{ auth()->user()->menu_style ?? 'vertical' }}';
        window.__erpSidebarWidth = '0px';
        if (window.__erpLayoutMode === 'vertical' && window.innerWidth >= 1024) {
            window.__erpSidebarWidth = localStorage.getItem('erp-sidebar-collapsed') === 'true' ? '64px' : '256px';
        }
    </script>
    <style>
        /* Prevent layout flash on desktop — JS refines after load */
        @media (min-width: 1024px) {
            body[data-layout="vertical"] #erp-content {
                margin-left: var(--erp-sidebar-w, 256px);
            }

            body[data-layout="vertical"] .erp-footer {
                margin-left: var(--erp-sidebar-w, 256px);
            }

            body[data-layout="vertical"] #erp-top-bar {
                left: var(--erp-sidebar-w, 256px);
            }
        }
    </style>
    <script>
        document.documentElement.style.setProperty('--erp-sidebar-w', window.__erpSidebarWidth);
    </script>

    {{-- Alpine.js --}}
    <script defer src="{{ asset('assets-tw/vendor/js/alpine.min.js') }}"></script>

    {{-- CSS --}}
    <link href="{{ asset('assets-tw/css/tailwind-output.css') }}?v={{ config('app.version', time()) }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/fonts/inter/inter.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/css/fontawesome/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/css/responsive.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/css/flatpickr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/vendor/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/css/erp-themes.css') }}?v={{ config('app.version', time()) }}" rel="stylesheet">
    <link href="{{ asset('assets-tw/css/erp-overrides.css') }}?v={{ config('app.version', time()) }}" rel="stylesheet">
    <link href="{{ route('css.config') }}" rel="stylesheet">

    @yield('pagecss')
</head>
@php $menuStyle = auth()->user()->menu_style ?? 'vertical'; @endphp

<body class="bg-zinc-50 font-sans antialiased" data-layout="{{ $menuStyle }}">

    @if ($menuStyle === 'vertical')
        {{-- Vertical: sidebar + slim header --}}
        @include('layouts-tw.sidebar')
        @include('layouts-tw.header')
    @else
        {{-- Horizontal: no sidebar, full-width header + horizontal nav --}}
        @include('layouts-tw.header-horizontal')
    @endif

    {{-- Main content --}}
    <main id="erp-content" class="erp-content-area min-h-screen p-3 sm:p-6"
        style="margin-top: {{ $menuStyle === 'horizontal' ? '6rem' : '3.5rem' }};" data-module="@yield('nav-module')"
        data-page="@yield('nav-page')" data-breadcrumb="@yield('breadcrumb')">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('layouts-tw.footer')

    {{-- Global Modals (Tailwind versions) --}}
    @include('partials-tw.delete-modal')
    @include('partials-tw.logout-modal')

    {{-- Core JS --}}
    <script src="{{ asset('assets-tw/vendor/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets-tw/js/erp-layout-laravel.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets-tw/js/erp-components.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets-tw/js/erp-cascade.js') }}?v={{ config('app.version', time()) }}"></script>

    {{-- Vendor JS --}}
    <script src="{{ asset('assets-tw/vendor/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets-tw/vendor/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets-tw/vendor/js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets-tw/vendor/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('assets-tw/vendor/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets-tw/js/erp-datatable.js') }}?v={{ config('app.version', time()) }}"></script>

    {{-- Page-specific scripts --}}
    @yield('pagescript')

    {{-- Custom JS (existing CRUD helpers — loaded from old path during transition) --}}
    <script src="{{ asset('assets/custom/dynamic-validation.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/edit-loader.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/password.js') }}?v={{ config('app.version', time()) }}"></script>
    {{-- changelayout.js removed — new theme uses vertical sidebar only --}}
    <script src="{{ asset('assets/custom/filter.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/logout.js') }}?v={{ config('app.version', time()) }}"></script>
    {{-- Must be defined BEFORE session-monitor.js loads — it captures this into a const at load time --}}
    <script>window.SESSION_LIFETIME_MINUTES = {{ config('session.lifetime', 120) }};</script>
    <script src="{{ asset('assets/custom/session-monitor.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/save.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/update.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/delete.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/impersonate.js') }}?v={{ config('app.version', time()) }}"></script>

    {{-- Compatibility shim: toastr → erpToast, BlockUI → opacity toggle --}}
    <script>
        // Swal shim for impersonate.js — maps to erpConfirm
        window.Swal = {
            fire: function(opts) {
                return new Promise(function(resolve) {
                    if (typeof erpConfirm === 'function') {
                        erpConfirm({
                            title: opts.title || 'Confirm',
                            message: opts.text || '',
                            confirmText: opts.confirmButtonText || 'Confirm',
                            cancelText: opts.cancelButtonText || 'Cancel',
                            type: 'default'
                        }).then(function(confirmed) {
                            resolve({
                                isConfirmed: confirmed
                            });
                        });
                    } else {
                        var confirmed = confirm(opts.text || opts.title || 'Are you sure?');
                        resolve({
                            isConfirmed: confirmed
                        });
                    }
                });
            }
        };

        // Bootstrap modal shim — maps .modal('show'/'hide') to hidden class toggle.
        // Also fires shown.bs.modal / hidden.bs.modal so legacy listeners (logout,
        // status, print-label, allow-reprint, password, edit-loader,
        // dynamic-validation) keep working without a Bootstrap dep.
        if (typeof jQuery !== 'undefined') {
            jQuery.fn.modal = function(action) {
                if (action === 'show') {
                    this.removeClass('hidden').trigger('shown.bs.modal');
                } else if (action === 'hide') {
                    this.addClass('hidden').trigger('hidden.bs.modal');
                }
                return this;
            };

            // Bridge for the inline `onclick="$('#x').removeClass('hidden')"` pattern
            // used by per-module modals and global TW modals (#globalLogoutModal, etc.).
            // Watches `class` mutations on elements that look like modals and fires the
            // matching bs.modal events when the `hidden` class toggles.
            (function() {
                if (!('MutationObserver' in window)) return;
                var isModalEl = function(el) {
                    if (!el || el.nodeType !== 1) return false;
                    var id = el.id || '';
                    var cls = el.className || '';
                    if (typeof cls !== 'string') return false;
                    if (id === 'inlineModal') return true;
                    if (/^global[A-Z].*Modal$/.test(id)) return true;
                    if (cls.indexOf('erp-inline-modal') !== -1) return true;
                    return false;
                };
                var observer = new MutationObserver(function(mutations) {
                    for (var i = 0; i < mutations.length; i++) {
                        var m = mutations[i];
                        if (m.type !== 'attributes' || m.attributeName !== 'class') continue;
                        var el = m.target;
                        if (!isModalEl(el)) continue;
                        var wasHidden = (m.oldValue || '').indexOf('hidden') !== -1;
                        var isHidden = el.classList.contains('hidden');
                        if (wasHidden && !isHidden) jQuery(el).trigger('shown.bs.modal');
                        else if (!wasHidden && isHidden) jQuery(el).trigger('hidden.bs.modal');
                    }
                });
                observer.observe(document.body, {
                    attributes: true,
                    attributeOldValue: true,
                    attributeFilter: ['class'],
                    subtree: true,
                });
            })();
        }

        // Global Escape key handler for inline modals (#inlineModal pattern)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var modal = document.getElementById('inlineModal');
                if (modal && !modal.classList.contains('hidden')) {
                    if (typeof resetInlineModal === 'function') {
                        resetInlineModal();
                    } else {
                        modal.classList.add('hidden');
                    }
                }
            }
        });

        // Close inline modal when clicking outside the modal card
        document.addEventListener('click', function(e) {
            var modal = document.getElementById('inlineModal');
            if (!modal || modal.classList.contains('hidden')) return;
            var card = modal.querySelector('.shadow-xl');
            if (card && card.contains(e.target)) return;
            if (modal.contains(e.target)) {
                if (typeof resetInlineModal === 'function') resetInlineModal();
                else modal.classList.add('hidden');
            }
        });

        // Toastr shim
        window.toastr = {
            success: function(msg, title) {
                if (typeof erpToast === 'function') erpToast({
                    type: 'success',
                    title: title || 'Success',
                    message: msg
                });
            },
            error: function(msg, title) {
                if (typeof erpToast === 'function') erpToast({
                    type: 'error',
                    title: title || 'Error',
                    message: msg
                });
            },
            warning: function(msg, title) {
                if (typeof erpToast === 'function') erpToast({
                    type: 'warning',
                    title: title || 'Warning',
                    message: msg
                });
            },
            info: function(msg, title) {
                if (typeof erpToast === 'function') erpToast({
                    type: 'info',
                    title: title || 'Info',
                    message: msg
                });
            },
            clear: function() {}
        };
        // BlockUI shim
        if (typeof jQuery !== 'undefined') {
            jQuery.fn.block = function() {
                this.addClass('opacity-50 pointer-events-none');
                return this;
            };
            jQuery.fn.unblock = function() {
                this.removeClass('opacity-50 pointer-events-none');
                return this;
            };
        }

        // Global config
        // SESSION_LIFETIME_MINUTES is defined earlier (before session-monitor.js) — see above.

        @if (auth()->user()->canImpersonate())
            window.impersonateUsersUrl = "{{ route('impersonate.users') }}";
            window.impersonateTakeUrl = "{{ url('impersonate/take') }}/__ID__";
        @endif

        // Session flash → erpToast
        @if ($message = Session::get('error'))
            if (typeof erpToast === 'function') erpToast({
                type: 'error',
                title: 'Error',
                message: "{{ addslashes($message) }}"
            });
        @endif
        @if ($message = Session::get('warning'))
            if (typeof erpToast === 'function') erpToast({
                type: 'warning',
                title: 'Warning',
                message: "{{ addslashes($message) }}"
            });
        @endif
        @if ($message = Session::get('info'))
            if (typeof erpToast === 'function') erpToast({
                type: 'info',
                title: 'Info',
                message: "{{ addslashes($message) }}"
            });
        @endif
        @if ($message = Session::get('success'))
            if (typeof erpToast === 'function') erpToast({
                type: 'success',
                title: 'Success',
                message: "{{ addslashes($message) }}"
            });
        @endif

        // Password toggle
        $(document).on('click', '.toggle-password', function() {
            var icon = $(this).find('i');
            var input = $(this).siblings('input').length ? $(this).siblings('input') : $(this).parent().find(
                'input');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Year selection
        $(document).on('click', '.year-change', function() {
            var yearId = $(this).data('value');
            var yearName = $(this).data('name');
            var displayText = $(this).text();

            localStorage.setItem('selected_year_id', yearId);
            localStorage.setItem('selected_year_name', yearName);
            localStorage.setItem('selected_year_display', displayText);

            // Close dropdown and show loading immediately
            $('#year-dropdown').hide();
            $('#selected_year').text(displayText);
            if (typeof showPageLoader === 'function') {
                showPageLoader('Switching to ' + displayText.trim() + '...');
            }

            $.ajax({
                url: "{{ url('years/session') }}/" + yearId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status_code === 200) {
                        $('.year-change').removeClass('active');
                        $('[data-value="' + yearId + '"]').addClass('active');
                        window.location.reload();
                    } else {
                        var loader = document.getElementById('erp-page-loader');
                        if (loader) loader.remove();
                        toastr.error(response.message || 'Failed to set year', 'Error');
                    }
                },
                error: function() {
                    var loader = document.getElementById('erp-page-loader');
                    if (loader) loader.remove();
                    toastr.error('Failed to set year. Please try again.', 'Error');
                }
            });
        });

        // Year search
        var searchTimeout;
        $(document).on('input', '.year-search-input', function(e) {
            e.stopPropagation();
            var query = $(this).val();
            var $results = $('.year-search-results');
            clearTimeout(searchTimeout);
            if (query.length >= 1) {
                $results.html(
                    '<div class="text-zinc-400 text-sm"><i class="fa fa-spinner fa-spin mr-1"></i>Searching...</div>'
                );
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: "{{ route('years.search') }}",
                        type: 'GET',
                        data: {
                            q: query,
                            limit: 10
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status_code === 200 && response.html.trim() !== '') {
                                $results.html(response.html);
                            } else {
                                $results.html(
                                    '<div class="text-zinc-400 text-sm">No years found</div>'
                                );
                            }
                        },
                        error: function() {
                            $results.html(
                                '<div class="text-red-500 text-sm">Search failed</div>');
                        }
                    });
                }, 300);
            } else {
                $results.empty();
            }
        });

        // Layout switch (vertical ↔ horizontal) — saves via AJAX then reloads
        function switchLayout(mode) {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;

            if (typeof showPageLoader === 'function') {
                showPageLoader('Switching to ' + mode + ' layout...');
            }

            fetch("{{ route('change-layout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token.content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    menu_style: mode,
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                })
            }).then(function() {
                window.location.reload();
            }).catch(function() {
                window.location.reload();
            });
        }

        // Horizontal mode: hamburger opens mobile drawer
        $(document).on('click', '#hamburger-btn', function() {
            if (window.__erpLayoutMode === 'horizontal') {
                $('#mobile-nav-drawer').show();
            }
        });

        // Close horizontal mega dropdowns on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.erp-hnav-group').length) {
                $('.erp-hnav-mega').hide();
            }
        });

        // Image preview helper
        function previewImage(event, imagePreview) {
            var input = event.target;
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>
