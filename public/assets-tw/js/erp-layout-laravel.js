/* ERP Layout (Laravel) — Sidebar, dark mode, breadcrumb, responsive
   Forked from erp-layout.js: removed loadPartial() (Blade handles server rendering) */
(function() {
  'use strict';

  var SIDEBAR_KEY = 'erp-sidebar-collapsed';
  var DARK_KEY = 'erp-dark-mode';

  /* ── Full-screen loader (blocks all interaction during save+reload) ── */
  function showPageLoader(message) {
    if (document.getElementById('erp-page-loader')) return;
    var overlay = document.createElement('div');
    overlay.id = 'erp-page-loader';
    overlay.className = 'erp-page-loader';
    var faviconSrc = document.querySelector('link[rel="icon"]');
    var logoHtml = faviconSrc ? '<img src="' + faviconSrc.href + '" style="width:48px;height:48px;object-fit:contain;" alt="">' : '';
    overlay.innerHTML =
      '<div class="erp-page-loader-content">' +
        logoHtml +
        '<div class="erp-loader-spinner" style="width:32px;height:32px;border-width:3px;"></div>' +
        '<div class="erp-loader-dots"><span></span><span></span><span></span></div>' +
        '<p class="erp-page-loader-text">' + (message || 'Applying changes...') + '</p>' +
      '</div>';
    document.body.appendChild(overlay);
  }

  /* ── Dark mode ──────────────────────────────────────── */
  function isDarkMode() {
    var val = localStorage.getItem(DARK_KEY);
    if (val !== null) return val === 'true';
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  function applyDarkMode(dark) {
    document.documentElement.classList.toggle('dark', dark);
    document.querySelectorAll('.erp-dark-icon').forEach(function(el) {
      el.style.display = dark ? 'none' : '';
    });
    document.querySelectorAll('.erp-light-icon').forEach(function(el) {
      el.style.display = dark ? '' : 'none';
    });
  }

  function toggleDarkMode() {
    var dark = !document.documentElement.classList.contains('dark');
    localStorage.setItem(DARK_KEY, dark ? 'true' : 'false');
    applyDarkMode(dark);
    showPageLoader(dark ? 'Switching to dark mode...' : 'Switching to light mode...');

    var token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
      fetch('/change-theme', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token.content, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ theme: dark ? 'dark' : 'light' })
      }).then(function() {
        window.location.reload();
      }).catch(function() {
        window.location.reload();
      });
    }
  }

  // Apply dark mode before anything renders
  applyDarkMode(isDarkMode());

  /* ── Sidebar state ──────────────────────────────────── */
  function isSidebarCollapsed() {
    return localStorage.getItem(SIDEBAR_KEY) === 'true';
  }

  function setSidebarCollapsed(val) {
    localStorage.setItem(SIDEBAR_KEY, val ? 'true' : 'false');
  }

  function applySidebarState() {
    var sidebar = document.getElementById('erp-sidebar-nav');
    var topBar = document.getElementById('erp-top-bar');
    var content = document.getElementById('erp-content');
    var footer = document.querySelector('.erp-footer');
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
      // Mobile: sidebar off-screen, no margin
      sidebar.classList.remove('collapsed');
      sidebar.style.width = '256px';
      sidebar.removeAttribute('data-mobile-open');
      var ov = document.getElementById('erp-overlay');
      if (ov) ov.style.display = 'none';
      if (topBar) topBar.style.left = '0';
      if (content) content.style.marginLeft = '0';
      if (footer) footer.style.marginLeft = '0';
      showExpanded();
    } else if (isSidebarCollapsed()) {
      sidebar.classList.add('collapsed');
      sidebar.style.width = '64px';
      sidebar.style.transform = '';
      if (topBar) topBar.style.left = '64px';
      if (content) content.style.marginLeft = '64px';
      if (footer) footer.style.marginLeft = '64px';
      showCollapsed();
    } else {
      sidebar.classList.remove('collapsed');
      sidebar.style.width = '256px';
      sidebar.style.transform = '';
      if (topBar) topBar.style.left = '256px';
      if (content) content.style.marginLeft = '256px';
      if (footer) footer.style.marginLeft = '256px';
      showExpanded();
    }
  }

  function toggleSidebarCollapse() {
    var sidebar = document.getElementById('erp-sidebar-nav');
    if (sidebar) sidebar.classList.remove('hover-expanded');
    var collapsed = !isSidebarCollapsed();
    setSidebarCollapsed(collapsed);
    applySidebarState();
  }

  function toggleMobileSidebar() {
    // Horizontal mode: toggle mobile-nav-drawer
    var mobileDrawer = document.getElementById('mobile-nav-drawer');
    if (mobileDrawer) {
      var isOpen = mobileDrawer.style.display !== 'none';
      mobileDrawer.style.display = isOpen ? 'none' : '';
      return;
    }

    // Vertical mode: toggle erp-sidebar-nav
    var sidebar = document.getElementById('erp-sidebar-nav');
    var overlay = document.getElementById('erp-overlay');
    if (!sidebar || window.innerWidth >= 1024) return;

    var header = document.getElementById('erp-top-bar');
    var isOpen = sidebar.getAttribute('data-mobile-open') === '1';

    if (isOpen) {
      sidebar.removeAttribute('data-mobile-open');
      if (overlay) overlay.style.display = 'none';
      if (header) header.style.zIndex = '';
    } else {
      sidebar.classList.remove('collapsed');
      sidebar.style.width = '256px';
      sidebar.setAttribute('data-mobile-open', '1');
      if (overlay) overlay.style.display = '';
      if (header) header.style.zIndex = '45';
    }
  }

  /* ── Breadcrumb ─────────────────────────────────────── */
  function renderBreadcrumb() {
    var main = document.getElementById('erp-content');
    if (!main) return;
    var bc = main.getAttribute('data-breadcrumb');
    if (!bc) return;

    var parts = bc.split('>').map(function(s) { return s.trim(); });
    var container = document.getElementById('erp-breadcrumb');
    if (!container) return;

    var html = '<a href="/" class="text-zinc-400 hover:text-zinc-600"><i class="fa-solid fa-house text-xs"></i></a>';
    parts.forEach(function(part, i) {
      html += '<i class="fa-solid fa-chevron-right text-[10px] text-zinc-300 mx-2"></i>';
      if (i === parts.length - 1) {
        html += '<span class="text-zinc-900 font-medium">' + part + '</span>';
      } else {
        html += '<span class="text-zinc-500">' + part + '</span>';
      }
    });
    container.innerHTML = html;
  }

  /* ── Header dropdown management ─────────────────────── */
  // Simple: close all known dropdown panels by ID
  function closeAllHeaderDropdowns(exceptId) {
    var panels = [
      'year-dropdown',
      'user-menu-dropdown',
      'notification-dropdown'
    ];
    for (var i = 0; i < panels.length; i++) {
      if (panels[i] !== exceptId) {
        var el = document.getElementById(panels[i]);
        if (el) el.style.display = 'none';
      }
    }
    // Impersonate uses class selector
    if (exceptId !== 'impersonate-menu') {
      var impMenu = document.querySelector('#impersonate-dropdown .dropdown-menu');
      if (impMenu) impMenu.style.display = 'none';
    }
  }

  function setupDropdownCloseHandlers() {
    // Single document click — no stopPropagation, just closest() checks
    document.addEventListener('click', function(e) {
      if (!e.target.closest('#year-selector-wrapper')) {
        var yd = document.getElementById('year-dropdown');
        if (yd) yd.style.display = 'none';
      }
      if (!e.target.closest('#user-menu-btn') && !e.target.closest('#user-menu-dropdown')) {
        var ud = document.getElementById('user-menu-dropdown');
        if (ud) ud.style.display = 'none';
      }
      if (!e.target.closest('#impersonate-dropdown')) {
        var im = document.querySelector('#impersonate-dropdown .dropdown-menu');
        if (im) im.style.display = 'none';
      }
      if (!e.target.closest('#notification-btn') && !e.target.closest('#notification-dropdown')) {
        var nd = document.getElementById('notification-dropdown');
        if (nd) nd.style.display = 'none';
      }
      // Close user menu when clicking a link inside it
      if (e.target.closest('#user-menu-dropdown a')) {
        var ud2 = document.getElementById('user-menu-dropdown');
        if (ud2) ud2.style.display = 'none';
      }
    });
  }

  /* ── Init on DOM ready ──────────────────────────────── */
  function init() {
    // Apply sidebar state
    applySidebarState();

    // Render breadcrumb
    renderBreadcrumb();

    // Dark mode button
    var darkBtn = document.getElementById('dark-mode-btn');
    if (darkBtn) darkBtn.addEventListener('click', toggleDarkMode);

    // Sidebar collapse buttons
    var collapseBtn = document.getElementById('sidebar-collapse-btn');
    if (collapseBtn) collapseBtn.addEventListener('click', toggleSidebarCollapse);

    var expandBtn = document.getElementById('sidebar-expand-btn');
    if (expandBtn) expandBtn.addEventListener('click', toggleSidebarCollapse);

    // Hamburger (mobile)
    var hamburger = document.getElementById('hamburger-btn');
    if (hamburger) hamburger.addEventListener('click', toggleMobileSidebar);

    // Header sidebar toggle (desktop — table-columns icon)
    var headerToggle = document.getElementById('header-sidebar-toggle');
    if (headerToggle) headerToggle.addEventListener('click', toggleSidebarCollapse);

    // Overlay click closes mobile sidebar
    var overlay = document.getElementById('erp-overlay');
    if (overlay) overlay.addEventListener('click', toggleMobileSidebar);

    // Sidebar hover-expand: expand on mouseenter, collapse on mouseleave
    var sidebar = document.getElementById('erp-sidebar-nav');
    if (sidebar) {
      var hoverTimer = null;
      sidebar.addEventListener('mouseenter', function() {
        clearTimeout(hoverTimer);
        if (isSidebarCollapsed() && window.innerWidth >= 1024) {
          sidebar.classList.add('hover-expanded');
        }
      });
      sidebar.addEventListener('mouseleave', function() {
        hoverTimer = setTimeout(function() {
          sidebar.classList.remove('hover-expanded');
        }, 200);
      });
    }

    // Year selector toggle
    var yearBtn = document.getElementById('year-selector-btn');
    var yearDrop = document.getElementById('year-dropdown');
    if (yearBtn && yearDrop) {
      yearBtn.addEventListener('click', function() {
        var isOpen = yearDrop.style.display !== 'none';
        closeAllHeaderDropdowns('year-dropdown');
        yearDrop.style.display = isOpen ? 'none' : '';
        if (!isOpen) {
          var si = yearDrop.querySelector('.year-search-input');
          if (si) setTimeout(function() { si.focus(); }, 100);
        }
      });
    }

    // User menu toggle
    var userMenuBtn = document.getElementById('user-menu-btn');
    var userMenuDrop = document.getElementById('user-menu-dropdown');
    if (userMenuBtn && userMenuDrop) {
      userMenuBtn.addEventListener('click', function() {
        var isOpen = userMenuDrop.style.display !== 'none';
        closeAllHeaderDropdowns('user-menu-dropdown');
        userMenuDrop.style.display = isOpen ? 'none' : '';
      });
    }

    // Impersonate toggle
    var impBtn = document.querySelector('#impersonate-dropdown > button');
    var impMenu = document.querySelector('#impersonate-dropdown .dropdown-menu');
    if (impBtn && impMenu) {
      impBtn.addEventListener('click', function() {
        var isOpen = impMenu.style.display !== 'none';
        closeAllHeaderDropdowns('impersonate-menu');
        impMenu.style.display = isOpen ? 'none' : '';
        if (!isOpen) {
          var si = impMenu.querySelector('.impersonate-search-input');
          if (si) setTimeout(function() { si.val ? si.val('').focus() : (si.value = '', si.focus()); }, 100);
        }
      });
    }

    // Close dropdowns on outside click (single listener, no stopPropagation)
    setupDropdownCloseHandlers();

    // Window resize — reapply sidebar
    var resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(applySidebarState, 150);
    });
  }

  // Run init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for external use
  // Expose globally
  window.showPageLoader = showPageLoader;
  window.erpLayout = {
    toggleDarkMode: toggleDarkMode,
    toggleSidebar: toggleSidebarCollapse,
    toggleMobileSidebar: toggleMobileSidebar,
    showPageLoader: showPageLoader
  };
})();
