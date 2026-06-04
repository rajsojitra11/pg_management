/* ERP Layout — Partial loader, layout toggle, breadcrumb, dark mode, responsive */
(function () {
    'use strict';

    var LAYOUT_KEY = 'erp-layout-mode';
    var SIDEBAR_KEY = 'erp-sidebar-collapsed';
    var DARK_KEY = 'erp-dark-mode';

    /* ── Dark mode — apply immediately to prevent flash ────────── */
    function isDarkMode() {
        var val = localStorage.getItem(DARK_KEY);
        if (val !== null) return val === 'true';
        // Respect OS preference if no explicit choice
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applyDarkMode(dark) {
        document.documentElement.classList.toggle('dark', dark);
        // Update icons — use inline style to avoid Tailwind layer specificity issues
        document.querySelectorAll('.erp-dark-icon').forEach(function (el) {
            el.style.display = dark ? 'none' : '';
        });
        document.querySelectorAll('.erp-light-icon').forEach(function (el) {
            el.style.display = dark ? '' : 'none';
        });
    }

    function toggleDarkMode() {
        var dark = !document.documentElement.classList.contains('dark');
        localStorage.setItem(DARK_KEY, dark ? 'true' : 'false');
        applyDarkMode(dark);
    }

    // Apply dark mode before anything renders
    if (isDarkMode()) {
        document.documentElement.classList.add('dark');
    }

    function getBasePath() {
        return erpBasePath();
    }

    function getLayoutMode() {
        return localStorage.getItem(LAYOUT_KEY) || 'vertical';
    }

    function setLayoutMode(mode) {
        localStorage.setItem(LAYOUT_KEY, mode);
    }

    function isSidebarCollapsed() {
        return localStorage.getItem(SIDEBAR_KEY) === 'true';
    }

    function setSidebarCollapsed(val) {
        localStorage.setItem(SIDEBAR_KEY, val ? 'true' : 'false');
    }

    /* Offline skeleton placeholders for header, sidebar, footer */
    var _offlineSkeletons = {
        'erp-header': '<header class="erp-header fixed top-0 right-0 left-0 lg:left-64 z-20 h-14 border-b border-zinc-200 flex items-center px-4 gap-3">' +
            '<div class="h-4 w-4 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="hidden sm:flex items-center gap-2"><div class="h-3 w-20 rounded bg-zinc-200 animate-pulse"></div><div class="h-3 w-16 rounded bg-zinc-200 animate-pulse"></div></div>' +
            '<div class="flex-1"></div>' +
            '<div class="h-9 w-36 rounded-md bg-zinc-200 animate-pulse hidden sm:block"></div>' +
            '<div class="h-8 w-8 rounded-md bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-8 w-8 rounded-full bg-zinc-200 animate-pulse"></div>' +
            '</header>',

        'erp-sidebar': '<aside class="erp-sidebar fixed top-0 left-0 z-50 h-screen w-64 border-r border-zinc-200 flex flex-col overflow-hidden">' +
            '<div class="flex items-center h-14 border-b border-zinc-200 px-4 gap-2">' +
            '<div class="h-8 w-8 rounded-md bg-zinc-200 animate-pulse shrink-0"></div>' +
            '<div class="h-4 w-24 rounded bg-zinc-200 animate-pulse"></div>' +
            '</div>' +
            '<div class="flex-1 p-3 space-y-4 overflow-hidden">' +
            '<div class="space-y-2">' +
            '<div class="h-3 w-28 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '</div>' +
            '<div class="space-y-2">' +
            '<div class="h-3 w-32 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '</div>' +
            '<div class="space-y-2">' +
            '<div class="h-3 w-24 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '</div>' +
            '<div class="space-y-2">' +
            '<div class="h-3 w-20 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '<div class="h-8 w-full rounded-md bg-zinc-100 animate-pulse"></div>' +
            '</div>' +
            '</div>' +
            '</aside>',

        'erp-footer': '<footer class="erp-footer border-t border-zinc-200 py-3 sm:py-4 px-3 sm:px-6">' +
            '<div class="flex flex-col sm:flex-row items-center justify-between gap-2">' +
            '<div class="h-3 w-48 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="flex items-center gap-4">' +
            '<div class="h-3 w-12 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-3 w-10 rounded bg-zinc-200 animate-pulse"></div>' +
            '<div class="h-3 w-14 rounded bg-zinc-200 animate-pulse"></div>' +
            '</div>' +
            '</div>' +
            '</footer>'
    };

    /* Fetch and inject a partial (no cache-bust — SW handles freshness) */
    function loadPartial(url, targetId) {
        return fetch(url)
            .then(function (r) { return r.ok ? r.text() : ''; })
            .then(function (html) {
                var el = document.getElementById(targetId);
                if (el) {
                    if (html) {
                        el.innerHTML = html;
                    } else if (_offlineSkeletons[targetId]) {
                        el.innerHTML = _offlineSkeletons[targetId];
                    }
                }
            })
            .catch(function (e) {
                console.warn('Failed to load partial:', url, e);
                // Show skeleton placeholder when offline
                var el = document.getElementById(targetId);
                if (el && _offlineSkeletons[targetId]) {
                    el.innerHTML = _offlineSkeletons[targetId];
                }
            });
    }

    /* Render breadcrumb from data attribute */
    function renderBreadcrumb() {
        var main = document.getElementById('erp-content');
        if (!main) return;
        var bc = main.getAttribute('data-breadcrumb');
        if (!bc) return;

        var parts = bc.split('>').map(function (s) { return s.trim(); });
        var container = document.getElementById('erp-breadcrumb');
        if (!container) return;

        var html = '<a href="' + getBasePath() + 'index.html" class="text-zinc-400 hover:text-zinc-600"><i class="fa-solid fa-house text-xs"></i></a>';
        parts.forEach(function (part, i) {
            html += '<i class="fa-solid fa-chevron-right text-[10px] text-zinc-300 mx-2"></i>';
            if (i === parts.length - 1) {
                html += '<span class="text-zinc-900 font-medium">' + part + '</span>';
            } else {
                html += '<span class="text-zinc-500">' + part + '</span>';
            }
        });
        container.innerHTML = html;
    }

    /* Apply sidebar collapse state */
    function applySidebarState() {
        var sidebar = document.getElementById('erp-sidebar-nav');
        var topBar = document.getElementById('erp-top-bar');
        var content = document.getElementById('erp-content');
        if (!sidebar) return;

        var expandedHeader = document.getElementById('sidebar-expanded-header');
        var collapsedHeader = document.getElementById('sidebar-collapsed-header');
        var w = window.innerWidth;

        function showExpanded() {
            if (expandedHeader) expandedHeader.style.display = '';
            if (collapsedHeader) collapsedHeader.style.display = 'none';
        }
        function showCollapsed() {
            if (expandedHeader) expandedHeader.style.display = 'none';
            if (collapsedHeader) collapsedHeader.style.display = '';
        }

        if (w < 1024) {
            sidebar.classList.remove('collapsed');
            sidebar.style.width = '256px';
            // On mobile, always keep sidebar hidden (CSS handles it via data-mobile-open)
            sidebar.removeAttribute('data-mobile-open');
            var ov = document.getElementById('erp-overlay');
            if (ov) ov.classList.remove('active');
            if (topBar) topBar.style.left = '0';
            if (content) content.style.marginLeft = '0';
            var ftr = document.querySelector('.erp-footer');
            if (ftr) ftr.style.marginLeft = '0';
            showExpanded();
        } else if (isSidebarCollapsed()) {
            sidebar.classList.add('collapsed');
            sidebar.style.width = '64px';
            sidebar.style.transform = '';
            if (topBar) topBar.style.left = '64px';
            if (content) content.style.marginLeft = '64px';
            var ftr = document.querySelector('.erp-footer');
            if (ftr) ftr.style.marginLeft = '64px';
            showCollapsed();
        } else {
            sidebar.classList.remove('collapsed');
            sidebar.style.width = '256px';
            sidebar.style.transform = '';
            if (topBar) topBar.style.left = '256px';
            if (content) content.style.marginLeft = '256px';
            var ftr = document.querySelector('.erp-footer');
            if (ftr) ftr.style.marginLeft = '256px';
            showExpanded();
        }
    }

    /* Toggle sidebar collapse */
    function toggleSidebarCollapse() {
        var collapsed = !isSidebarCollapsed();
        setSidebarCollapsed(collapsed);
        applySidebarState();
    }

    /* Mobile sidebar toggle */
    function toggleMobileSidebar() {
        var sidebar = document.getElementById('erp-sidebar-nav');
        var overlay = document.getElementById('erp-overlay');
        if (!sidebar) return;
        if (window.innerWidth >= 1024) return;

        var header = document.getElementById('erp-top-bar') || document.querySelector('.erp-header');
        var isOpen = sidebar.getAttribute('data-mobile-open') === '1';

        if (isOpen) {
            // Close — CSS rule hides it when data-mobile-open is removed
            sidebar.removeAttribute('data-mobile-open');
            if (overlay) overlay.classList.remove('active');
            if (header) header.style.zIndex = '';
        } else {
            // Open — CSS rule shows it when data-mobile-open="1"
            sidebar.classList.remove('collapsed');
            sidebar.style.width = '256px';
            sidebar.setAttribute('data-mobile-open', '1');
            if (overlay) overlay.classList.add('active');
            // Raise header above overlay (z-40) so hamburger can close sidebar
            if (header) header.style.zIndex = '45';
        }
    }

    /* Apply horizontal layout offsets */
    function applyHorizontalState() {
        var content = document.getElementById('erp-content');
        var ftr = document.querySelector('.erp-footer');
        if (ftr) ftr.style.marginLeft = '0';
        if (content) {
            content.style.marginLeft = '0';
            content.style.marginTop = '0';
        }
    }

    /* ── PWA — inject manifest, meta tags, register service worker ── */
    function initPWA() {
        var base = getBasePath();

        // Inject manifest link
        if (!document.querySelector('link[rel="manifest"]')) {
            var manifest = document.createElement('link');
            manifest.rel = 'manifest';
            manifest.href = base + 'manifest.webmanifest';
            document.head.appendChild(manifest);
        }

        // Inject theme-color meta
        if (!document.querySelector('meta[name="theme-color"]')) {
            var theme = document.createElement('meta');
            theme.name = 'theme-color';
            theme.content = getComputedStyle(document.documentElement).getPropertyValue('--erp-bg-page').trim() || '#18181b';
            document.head.appendChild(theme);
        }

        // Inject apple-touch-icon
        if (!document.querySelector('link[rel="apple-touch-icon"]')) {
            var apple = document.createElement('link');
            apple.rel = 'apple-touch-icon';
            apple.href = base + 'assets/icons/icon-192.png';
            document.head.appendChild(apple);
        }

        // Inject mobile-web-app-capable meta tag
        if (!document.querySelector('meta[name="mobile-web-app-capable"]')) {
            var capable = document.createElement('meta');
            capable.name = 'mobile-web-app-capable';
            capable.content = 'yes';
            document.head.appendChild(capable);
        }

        // Register service worker — resolve to absolute root URL to prevent duplicate registrations
        if ('serviceWorker' in navigator) {
            var rootUrl = new URL(base, location.href).href;
            navigator.serviceWorker.register(rootUrl + 'sw.js', { scope: rootUrl })
                .catch(function (err) {
                    console.warn('SW registration failed:', err);
                });
        }
    }

    /* Main init */
    function initLayout() {
        initPWA();

        // One-time reset: clear stale sidebar collapsed state from development
        if (!localStorage.getItem('erp-init-v2')) {
            localStorage.removeItem(SIDEBAR_KEY);
            localStorage.setItem('erp-init-v2', '1');
        }

        var base = getBasePath();
        var mode = getLayoutMode();
        var content = document.getElementById('erp-content');

        // Create overlay for mobile sidebar
        if (!document.getElementById('erp-overlay')) {
            var overlay = document.createElement('div');
            overlay.id = 'erp-overlay';
            overlay.className = 'erp-overlay';
            overlay.addEventListener('click', toggleMobileSidebar);
            document.body.insertBefore(overlay, document.body.firstChild);
        }

        var promises = [];

        if (mode === 'vertical') {
            promises.push(loadPartial(base + 'partials/header-vertical.html', 'erp-header'));
            promises.push(loadPartial(base + 'partials/sidebar.html', 'erp-sidebar'));
        } else {
            promises.push(loadPartial(base + 'partials/header-horizontal.html', 'erp-header'));
            // Clear sidebar container for horizontal mode
            var sidebarEl = document.getElementById('erp-sidebar');
            if (sidebarEl) sidebarEl.innerHTML = '';
        }

        promises.push(loadPartial(base + 'partials/footer.html', 'erp-footer'));

        Promise.all(promises).then(function () {
            // Add layout mode class to body for CSS targeting
            document.body.classList.remove('erp-layout-vertical', 'erp-layout-horizontal');
            document.body.classList.add('erp-layout-' + mode);

            if (mode === 'vertical') {
                applySidebarState();

                // Bind sidebar collapse
                var collapseBtn = document.getElementById('sidebar-collapse-btn');
                var expandBtn = document.getElementById('sidebar-expand-btn');
                if (collapseBtn) collapseBtn.addEventListener('click', function () {
                    if (window.innerWidth < 1024) { toggleMobileSidebar(); } else { toggleSidebarCollapse(); }
                });
                if (expandBtn) expandBtn.addEventListener('click', toggleSidebarCollapse);
            } else {
                applyHorizontalState();
            }

            // Bind hamburger
            var hamburgerBtn = document.getElementById('hamburger-btn');
            if (hamburgerBtn) {
                hamburgerBtn.addEventListener('click', function () {
                    if (mode === 'vertical') {
                        toggleMobileSidebar();
                    } else {
                        // For horizontal mode, open a mobile sidebar too
                        var sidebarEl = document.getElementById('erp-sidebar');
                        if (sidebarEl && !sidebarEl.innerHTML.trim()) {
                            loadPartial(base + 'partials/sidebar.html', 'erp-sidebar').then(function () {
                                if (typeof initNav === 'function') initNav();
                                // Bind the close button inside the dynamically loaded sidebar
                                var closeBtn = document.getElementById('sidebar-collapse-btn');
                                if (closeBtn) closeBtn.addEventListener('click', function () {
                                    toggleMobileSidebar();
                                });
                                toggleMobileSidebar();
                            });
                        } else {
                            toggleMobileSidebar();
                        }
                    }
                });
            }

            // Bind layout toggle
            var toggleBtn = document.getElementById('layout-toggle-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    var newMode = mode === 'vertical' ? 'horizontal' : 'vertical';
                    setLayoutMode(newMode);
                    window.location.reload();
                });
            }

            // Bind dark mode toggle
            var darkBtn = document.getElementById('dark-mode-btn');
            if (darkBtn) {
                darkBtn.addEventListener('click', toggleDarkMode);
            }
            // Apply icons for current state (partials just loaded)
            applyDarkMode(isDarkMode());

            // Bind notifications
            var notifBtn = document.getElementById('notification-btn');
            var notifDrop = document.getElementById('notification-dropdown');
            if (notifBtn && notifDrop) {
                notifBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var isOpen = notifDrop.style.display !== 'none';
                    notifDrop.style.display = isOpen ? 'none' : 'block';
                    // Close user menu if open
                    var ud = document.getElementById('user-menu-dropdown');
                    if (ud) ud.classList.remove('show');
                });
                // Mark all read
                var markRead = document.getElementById('notif-mark-read');
                if (markRead) {
                    markRead.addEventListener('click', function () {
                        // Remove unread indicators
                        notifDrop.querySelectorAll('[class*="border-l-2"]').forEach(function (el) {
                            el.style.backgroundColor = '';
                            el.style.borderLeftColor = 'transparent';
                            el.style.borderLeftWidth = '0';
                            var dot = el.querySelector('.h-2.w-2');
                            if (dot) dot.style.display = 'none';
                        });
                        // Remove dots
                        notifDrop.querySelectorAll('.h-2.w-2.rounded-full.bg-blue-500').forEach(function (d) { d.style.display = 'none'; });
                        // Hide badge
                        var badge = document.getElementById('notif-badge');
                        if (badge) badge.style.display = 'none';
                        if (typeof erpToast === 'function') erpToast({ title: 'Done', message: 'All notifications marked as read', type: 'success' });
                    });
                }
            }

            // Bind user menu
            var userBtn = document.getElementById('user-menu-btn');
            var userDropdown = document.getElementById('user-menu-dropdown');
            if (userBtn && userDropdown) {
                userBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                    // Close notifications if open
                    if (notifDrop) notifDrop.style.display = 'none';
                });
            }

            // Close dropdowns on outside click
            document.addEventListener('click', function (e) {
                if (userDropdown && !e.target.closest('#user-menu-btn') && !e.target.closest('#user-menu-dropdown')) {
                    userDropdown.classList.remove('show');
                }
                if (notifDrop && !e.target.closest('#notification-btn') && !e.target.closest('#notification-dropdown')) {
                    notifDrop.style.display = 'none';
                }
            });

            // Fix logo/home links to use dynamic base path
            var homeUrl = base + 'index.html';
            document.querySelectorAll('a[href="index.html"]').forEach(function (a) {
                a.setAttribute('href', homeUrl);
            });

            // Render breadcrumb
            renderBreadcrumb();

            // Init navigation — wait for nav.json to load
            function doInitNav() {
                if (typeof initNav === 'function') initNav();
            }
            if (typeof erpOnNavReady === 'function') {
                erpOnNavReady(doInitNav);
            } else {
                doInitNav();
            }

            // Bind global search (both full and mobile icon)
            ['global-search-btn', 'global-search-btn-mobile'].forEach(function (id) {
                var btn = document.getElementById(id);
                if (btn) {
                    btn.addEventListener('click', function () {
                        if (typeof erpCommandPalette === 'function') erpCommandPalette();
                    });
                }
            });
        });
    }

    /* Responsive handler */
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            var mode = getLayoutMode();
            if (mode === 'vertical') {
                applySidebarState();
            } else {
                applyHorizontalState();
            }
        }, 100);
    });

    /* Init on DOM ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLayout);
    } else {
        initLayout();
    }

    /* ── Scroll-triggered reveal animations ──────────────────────── */
    function initScrollReveal() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (!('IntersectionObserver' in window)) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('erp-in-view');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.erp-scroll-reveal').forEach(function (el) {
            observer.observe(el);
        });
    }

    // Run after DOM is settled
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(initScrollReveal, 100); });
    } else {
        setTimeout(initScrollReveal, 100);
    }

    // Expose for external use
    window.erpLayout = {
        getMode: getLayoutMode,
        toggleSidebar: toggleMobileSidebar,
        getBasePath: getBasePath,
        toggleDarkMode: toggleDarkMode,
        isDarkMode: isDarkMode,
        initScrollReveal: initScrollReveal,
    };

    /* ── PWA Install Prompt ────────────────────────────────────────
       Chrome/Edge: uses beforeinstallprompt event
       iOS Safari:  no event — detect iOS and show manual instructions
       Both orderings handled (event can fire before or after load). */
    var _pwaDeferredPrompt = null;
    var _PWA_DISMISS_KEY = 'erp-pwa-install-dismissed';
    var _PWA_INSTALLED_KEY = 'erp-pwa-installed';
    var _pageFullyLoaded = false;

    function _getPlatform() {
        var ua = navigator.userAgent;
        if (/iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) return 'ios';
        if (/Android/.test(ua)) return 'android';
        if (/Macintosh/.test(ua)) return 'macos';
        return 'desktop'; // Windows, Linux, ChromeOS
    }

    function _isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
    }

    function _shouldShowPrompt() {
        if (localStorage.getItem(_PWA_DISMISS_KEY) || localStorage.getItem(_PWA_INSTALLED_KEY)) return false;
        if (_isStandalone()) return false;
        return true;
    }

    function _needsManualInstall() {
        // Only iOS and macOS Safari lack beforeinstallprompt support
        // Android Chrome and desktop browsers fire beforeinstallprompt natively
        var p = _getPlatform();
        return p === 'ios' || p === 'macos';
    }

    function _tryShowInstallPrompt() {
        if (!_pageFullyLoaded || !_shouldShowPrompt()) return;
        if (!_pwaDeferredPrompt && !_needsManualInstall()) return;
        setTimeout(function () {
            if (_pwaDeferredPrompt) {
                _erpShowInstallPrompt(); // Chrome/Edge native prompt
            } else {
                _erpShowManualInstallPrompt(); // Platform-specific instructions
            }
        }, 2000);
    }

    // Chrome/Edge — capture the event
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        _pwaDeferredPrompt = e;
        _tryShowInstallPrompt();
    });

    window.addEventListener('load', function () {
        _pageFullyLoaded = true;
        _tryShowInstallPrompt();
    });

    window.addEventListener('appinstalled', function () {
        localStorage.setItem(_PWA_INSTALLED_KEY, 'true');
        _pwaDeferredPrompt = null;
    });

    /* ── Chrome/Edge install modal ── */
    function _erpShowInstallPrompt() {
        if (!_pwaDeferredPrompt) return;

        var body = '<div class="text-center py-2">' +
            '<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">' +
            '<i class="fa-solid fa-download text-2xl text-zinc-600"></i>' +
            '</div>' +
            '<h3 class="text-lg font-semibold text-zinc-900">Install ERP Suite</h3>' +
            '<p class="mt-2 text-sm text-zinc-500 leading-relaxed">' +
            'Install this app on your device for quick access, offline support, and a native app experience.' +
            '</p>' +
            '<div class="mt-4 flex flex-col gap-2 text-left text-sm text-zinc-600">' +
            '<div class="flex items-center gap-2"><i class="fa-solid fa-bolt text-xs text-zinc-400 w-4"></i> Faster load times</div>' +
            '<div class="flex items-center gap-2"><i class="fa-solid fa-wifi text-xs text-zinc-400 w-4"></i> Works offline</div>' +
            '<div class="flex items-center gap-2"><i class="fa-solid fa-up-right-from-square text-xs text-zinc-400 w-4"></i> Opens like a native app</div>' +
            '</div>' +
            '</div>';

        var footer = '<button class="pwa-dismiss-btn h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">Not Now</button>' +
            '<button class="pwa-install-btn h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">' +
            '<i class="fa-solid fa-download text-xs mr-1.5"></i>Install App' +
            '</button>';

        var modal = erpModal({
            title: '',
            body: body,
            footer: footer,
            size: 'sm',
            onClose: function () {
                localStorage.setItem(_PWA_DISMISS_KEY, Date.now().toString());
            }
        });

        modal.el.querySelector('.pwa-install-btn').addEventListener('click', function () {
            if (!_pwaDeferredPrompt) return;
            _pwaDeferredPrompt.prompt();
            _pwaDeferredPrompt.userChoice.then(function (result) {
                if (result.outcome === 'accepted') {
                    localStorage.setItem(_PWA_INSTALLED_KEY, 'true');
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Installed', message: 'ERP Suite has been installed successfully!', type: 'success' });
                    }
                }
                _pwaDeferredPrompt = null;
                modal.close();
            });
        });

        modal.el.querySelector('.pwa-dismiss-btn').addEventListener('click', function () {
            localStorage.setItem(_PWA_DISMISS_KEY, Date.now().toString());
            modal.close();
        });
    }

    /* ── Platform-specific manual install instructions ── */
    function _getInstallSteps() {
        var platform = _getPlatform();

        if (platform === 'ios') {
            return {
                icon: 'fa-solid fa-mobile-screen',
                steps: [
                    'Tap the <strong>Share</strong> button <i class="fa-solid fa-arrow-up-from-bracket text-xs text-zinc-400"></i> in the Safari toolbar',
                    'Scroll down and tap <strong>Add to Home Screen</strong> <i class="fa-solid fa-plus-square text-xs text-zinc-400"></i>',
                    'Tap <strong>Add</strong> to confirm'
                ]
            };
        }

        if (platform === 'android') {
            return {
                icon: 'fa-brands fa-android',
                steps: [
                    'Tap the <strong>Menu</strong> button <i class="fa-solid fa-ellipsis-vertical text-xs text-zinc-400"></i> (three dots) in Chrome',
                    'Tap <strong>Add to Home screen</strong> or <strong>Install app</strong>',
                    'Tap <strong>Install</strong> to confirm'
                ]
            };
        }

        if (platform === 'macos') {
            return {
                icon: 'fa-brands fa-apple',
                steps: [
                    'In Safari, click <strong>File</strong> in the menu bar',
                    'Click <strong>Add to Dock</strong>',
                    'Click <strong>Add</strong> to confirm'
                ]
            };
        }

        // Desktop — Windows, Linux, ChromeOS
        return {
            icon: 'fa-solid fa-display',
            steps: [
                'Click the <strong>Install</strong> icon <i class="fa-solid fa-arrow-down-to-line text-xs text-zinc-400"></i> in the browser address bar',
                'Or click <strong>Menu</strong> <i class="fa-solid fa-ellipsis-vertical text-xs text-zinc-400"></i> then <strong>Install ERP Suite</strong>',
                'Click <strong>Install</strong> to confirm'
            ]
        };
    }

    function _erpShowManualInstallPrompt() {
        var info = _getInstallSteps();

        var stepsHtml = '';
        info.steps.forEach(function (step, i) {
            stepsHtml += '<div class="flex items-start gap-3">' +
                '<span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-white text-xs font-bold">' + (i + 1) + '</span>' +
                '<span>' + step + '</span>' +
                '</div>';
        });

        var body = '<div class="text-center py-2">' +
            '<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">' +
            '<i class="' + info.icon + ' text-2xl text-zinc-600"></i>' +
            '</div>' +
            '<h3 class="text-lg font-semibold text-zinc-900">Install ERP Suite</h3>' +
            '<p class="mt-2 text-sm text-zinc-500 leading-relaxed">' +
            'Add this app to your device for quick access and a native app experience.' +
            '</p>' +
            '<div class="mt-5 rounded-lg border border-zinc-200 bg-zinc-50 p-4">' +
            '<p class="text-xs font-medium text-zinc-700 mb-3">Follow these steps:</p>' +
            '<div class="flex flex-col gap-3 text-left text-sm text-zinc-600">' +
            stepsHtml +
            '</div>' +
            '</div>' +
            '</div>';

        var footer = '<button class="pwa-dismiss-btn h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">Got It</button>';

        var modal = erpModal({
            title: '',
            body: body,
            footer: footer,
            size: 'sm',
            onClose: function () {
                localStorage.setItem(_PWA_DISMISS_KEY, Date.now().toString());
            }
        });

        modal.el.querySelector('.pwa-dismiss-btn').addEventListener('click', function () {
            localStorage.setItem(_PWA_DISMISS_KEY, Date.now().toString());
            modal.close();
        });
    }

    // Expose for manual trigger
    window.erpShowInstallPrompt = function () {
        if (_pwaDeferredPrompt) {
            _erpShowInstallPrompt();
        } else {
            _erpShowManualInstallPrompt();
        }
    };
})();
