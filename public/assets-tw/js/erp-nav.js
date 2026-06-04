/* ERP Navigation — 4-level sidebar, horizontal nav, overflow arrows, mobile */
/* Level 1: Group | Level 2: Module | Level 3: Page | Level 4: Sub-page */

/* Tailwind v4 safe visibility toggle — uses style.display instead of hidden class */
function _erpToggleVis(el) {
  if (!el) return;
  el.style.display = el.style.display === 'none' ? '' : 'none';
}

var _navCurrentModule = '';
var _navCurrentPage = '';
var _navCurrentSubpage = '';

(function() {
  var main = document.getElementById('erp-content');
  if (main) {
    _navCurrentModule = main.getAttribute('data-module') || '';
    _navCurrentPage = main.getAttribute('data-page') || '';
    _navCurrentSubpage = main.getAttribute('data-subpage') || '';
  }
})();

/* ══════════════════════════════════════════════════════════════════
   BUILD SIDEBAR (4 levels)
   ══════════════════════════════════════════════════════════════════ */
function buildSidebar() {
  'use strict';
  var container = document.getElementById('sidebar-nav-groups');
  if (!container) return;

  var html = '';

  ERP_NAV.forEach(function(group, gi) {
    var groupHasActive = group.modules.some(function(m) { return m.id === _navCurrentModule; });

    html += '<div class="erp-nav-group mb-1">';

    // Level 1: Group
    html += '<button class="erp-sidebar-group w-full flex items-center gap-2 px-2 py-2 rounded-md text-xs font-semibold uppercase tracking-wider ' +
      (groupHasActive ? 'text-zinc-700 erp-group-active' : 'text-zinc-400') +
      ' hover:text-zinc-600 hover:bg-zinc-100/60 transition-all duration-200" data-group="' + gi + '" title="' + group.group + '">';
    html += '<i class="fa-solid ' + group.icon + ' erp-nav-icon w-5 text-center text-[13px]"></i>';
    html += '<span class="erp-sidebar-label flex-1 text-left">' + group.group + '</span>';
    html += '<i class="erp-sidebar-label fa-solid fa-chevron-down text-[10px] transition-transform duration-300 ' +
      (groupHasActive ? '' : '-rotate-90') + '"></i>';
    html += '</button>';

    // Level 2 container
    html += '<div class="erp-sidebar-submenu ' + (groupHasActive ? 'erp-anim-expand' : '" style="display:none') + '">';

    group.modules.forEach(function(mod) {
      var isActiveMod = mod.id === _navCurrentModule;
      var pages = (mod.pages || []).map(erpNormalizePage);
      var hasPages = pages.length > 0;

      if (hasPages) {
        html += '<div class="mt-0.5">';

        // Level 2: Module
        html += '<button class="erp-module-toggle w-full flex items-center gap-2 px-2 py-1.5 ml-3 pr-3 rounded-md text-[13px] ' +
          (isActiveMod ? 'text-zinc-900 font-semibold erp-nav-parent-active' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/60') +
          ' transition-all duration-200" data-module-toggle="' + mod.id + '">';
        html += '<i class="fa-solid fa-chevron-right text-[9px] text-zinc-400 transition-transform duration-300 erp-module-chevron ' +
          (isActiveMod ? 'rotate-90' : '') + '"></i>';
        html += '<span class="erp-sidebar-label truncate">' + mod.name + '</span>';
        html += '</button>';

        // Level 3 container
        html += '<div class="erp-module-pages ' + (isActiveMod ? 'erp-anim-expand' : '" style="display:none') + '">';

        pages.forEach(function(pg) {
          var pgActive = isActiveMod && pg.name === _navCurrentPage;
          var hasChildren = pg.children && pg.children.length > 0;

          if (hasChildren) {
            html += '<div class="mt-px">';

            // Level 3: Page toggle
            html += '<button class="erp-page-toggle w-full flex items-center gap-2 px-2 py-1.5 ml-7 pr-3 rounded-md text-[13px] ' +
              (pgActive ? 'text-zinc-900 font-medium erp-nav-parent-active' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100/60') +
              ' transition-all duration-200" data-page-toggle="' + pg.name + '">';
            html += '<i class="fa-solid fa-chevron-right text-[8px] text-zinc-400 transition-transform duration-300 erp-page-chevron ' +
              (pgActive ? 'rotate-90' : '') + '"></i>';
            html += '<span class="truncate">' + pg.name + '</span>';
            html += '</button>';

            // Level 4 container
            html += '<div class="erp-subpage-list ' + (pgActive ? 'erp-anim-expand' : '" style="display:none') + '">';
            pg.children.forEach(function(sub) {
              var subActive = pgActive && sub === _navCurrentSubpage;
              html += '<a href="#" class="erp-nav-link flex items-center gap-2 px-2 py-1.5 ml-10 pr-3 rounded-md text-[13px] ' +
                (subActive
                  ? 'erp-nav-active bg-zinc-900 text-white font-medium'
                  : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100/60') +
                ' transition-all duration-200" data-module="' + mod.id + '" data-page="' + pg.name + '" data-subpage="' + sub + '">';
              html += '<span class="w-1 h-1 rounded-full ' + (subActive ? 'bg-white' : 'bg-zinc-300') + ' shrink-0"></span>';
              html += '<span class="truncate">' + sub + '</span>';
              html += '</a>';
            });
            html += '</div>';
            html += '</div>';

          } else {
            var simpleActive = isActiveMod && pg.name === _navCurrentPage && !_navCurrentSubpage;
            html += '<a href="#" class="erp-nav-link flex items-center gap-2 px-2 py-1.5 ml-7 pr-3 rounded-md text-[13px] ' +
              (simpleActive
                ? 'erp-nav-active bg-zinc-900 text-white font-medium'
                : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100/60') +
              ' transition-all duration-200" data-module="' + mod.id + '" data-page="' + pg.name + '">';
            html += '<span class="w-1 h-1 rounded-full ' + (simpleActive ? 'bg-white' : 'bg-zinc-300') + ' shrink-0"></span>';
            html += '<span class="truncate">' + pg.name + '</span>';
            html += '</a>';
          }
        });

        html += '</div>'; // .erp-module-pages
        html += '</div>';
      } else {
        html += '<a href="#" class="erp-nav-link flex items-center gap-2 px-2 py-1.5 ml-5 rounded-md text-[13px] ' +
          (mod.id === _navCurrentModule ? 'erp-nav-active bg-zinc-100 text-zinc-900 font-medium' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/60') +
          ' transition-all duration-200" data-module="' + mod.id + '">';
        html += '<span class="erp-sidebar-label truncate">' + mod.name + '</span>';
        html += '</a>';
      }
    });

    html += '</div>'; // .erp-sidebar-submenu
    html += '</div>';
  });

  container.innerHTML = html;

  // Level 1 accordion
  container.querySelectorAll('.erp-sidebar-group').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var submenu = this.nextElementSibling;
      var chevron = this.querySelector('.fa-chevron-down');
      if (submenu) { _erpToggleVis(submenu); submenu.classList.toggle('erp-anim-expand'); }
      if (chevron) chevron.classList.toggle('-rotate-90');
    });
  });

  // Level 2 accordion
  container.querySelectorAll('.erp-module-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var pages = this.nextElementSibling;
      var chevron = this.querySelector('.erp-module-chevron');
      if (pages) { _erpToggleVis(pages); pages.classList.toggle('erp-anim-expand'); }
      if (chevron) chevron.classList.toggle('rotate-90');
    });
  });

  // Level 3 accordion
  container.querySelectorAll('.erp-page-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var subs = this.nextElementSibling;
      var chevron = this.querySelector('.erp-page-chevron');
      if (subs) { _erpToggleVis(subs); subs.classList.toggle('erp-anim-expand'); }
      if (chevron) chevron.classList.toggle('rotate-90');
    });
  });

  // Scroll active into view
  var activeEl = container.querySelector('.erp-nav-active');
  if (activeEl) {
    setTimeout(function() { activeEl.scrollIntoView({ block: 'center', behavior: 'smooth' }); }, 300);
  }
}


/* ══════════════════════════════════════════════════════════════════
   BUILD HORIZONTAL NAV
   ══════════════════════════════════════════════════════════════════ */
function buildHorizontalNav() {
  'use strict';
  var scrollContainer = document.getElementById('hnav-scroll');
  if (!scrollContainer) return;

  var html = '';
  ERP_NAV.forEach(function(group, gi) {
    var hasActive = group.modules.some(function(m) { return m.id === _navCurrentModule; });
    html += '<button class="erp-hnav-item flex items-center gap-1.5 px-3 py-2 text-sm font-medium whitespace-nowrap rounded-md ' +
      (hasActive ? 'erp-hnav-active' : 'text-zinc-500') +
      ' transition-all duration-200" data-group="' + gi + '">';
    html += '<i class="fa-solid ' + group.icon + ' text-xs"></i>';
    html += '<span>' + group.group + '</span>';
    html += '</button>';
  });
  scrollContainer.innerHTML = html;

  initOverflowArrows(scrollContainer);

  scrollContainer.querySelectorAll('.erp-hnav-item').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      showMegaDropdown(parseInt(this.getAttribute('data-group')));
    });
  });

  document.addEventListener('click', function() {
    var mega = document.getElementById('hnav-mega-dropdown');
    if (mega) mega.classList.remove('show');
  });
}


/* ══════════════════════════════════════════════════════════════════
   OVERFLOW ARROWS
   ══════════════════════════════════════════════════════════════════ */
function initOverflowArrows(sc) {
  var l = document.getElementById('hnav-left'), r = document.getElementById('hnav-right');
  if (!l || !r) return;
  function ck() {
    l.style.display = sc.scrollLeft <= 0 ? 'none' : '';
    r.style.display = sc.scrollLeft + sc.clientWidth >= sc.scrollWidth - 1 ? 'none' : '';
  }
  l.addEventListener('click', function(e) { e.stopPropagation(); sc.scrollBy({ left: -200, behavior: 'smooth' }); });
  r.addEventListener('click', function(e) { e.stopPropagation(); sc.scrollBy({ left: 200, behavior: 'smooth' }); });
  sc.addEventListener('scroll', ck);
  window.addEventListener('resize', ck);
  setTimeout(ck, 100);
}


/* ══════════════════════════════════════════════════════════════════
   MEGA DROPDOWN (4-level)
   ══════════════════════════════════════════════════════════════════ */
function showMegaDropdown(groupIndex) {
  var mega = document.getElementById('hnav-mega-dropdown');
  var megaContent = document.getElementById('hnav-mega-content');
  if (!mega || !megaContent) return;

  var group = ERP_NAV[groupIndex];
  if (!group) return;

  if (mega.classList.contains('show') && mega.getAttribute('data-group') === '' + groupIndex) {
    mega.classList.remove('show');
    return;
  }
  mega.setAttribute('data-group', groupIndex);

  var cols = Math.min(group.modules.length, 4);
  var html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-' + cols + ' gap-6">';

  group.modules.forEach(function(mod) {
    var isActiveMod = mod.id === _navCurrentModule;
    var pages = (mod.pages || []).map(erpNormalizePage);

    html += '<div>';
    html += '<a href="#" class="erp-mega-module flex items-center gap-2 text-sm font-semibold ' +
      (isActiveMod ? 'text-zinc-900' : 'text-zinc-700 hover:text-zinc-900') +
      ' transition-colors duration-200">';
    html += '<span class="text-xs text-zinc-400 font-normal">' + mod.id + '</span>' + mod.name + '</a>';

    if (pages.length > 0) {
      html += '<div class="mt-2 ml-6 space-y-0.5">';
      pages.forEach(function(pg) {
        var pgActive = isActiveMod && pg.name === _navCurrentPage;

        if (pg.children && pg.children.length > 0) {
          html += '<div class="mb-2">';
          html += '<p class="text-[13px] font-medium ' + (pgActive ? 'text-zinc-900' : 'text-zinc-600') + ' py-1 px-2 rounded-md ' +
            (pgActive ? 'erp-nav-parent-active' : '') + '">' + pg.name + '</p>';
          html += '<div class="ml-3 space-y-0.5">';
          pg.children.forEach(function(sub) {
            var subActive = pgActive && sub === _navCurrentSubpage;
            html += '<a href="#" class="erp-mega-link block text-[13px] py-1 px-2 rounded-md ' +
              (subActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100/60') +
              ' transition-all duration-200">' + sub + '</a>';
          });
          html += '</div></div>';
        } else {
          var spActive = isActiveMod && pg.name === _navCurrentPage && !_navCurrentSubpage;
          html += '<a href="#" class="erp-mega-link block text-[13px] py-1 px-2 rounded-md ' +
            (spActive ? 'bg-zinc-900 text-white font-medium' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100/60') +
            ' transition-all duration-200">' + pg.name + '</a>';
        }
      });
      html += '</div>';
    }
    html += '</div>';
  });

  html += '</div>';
  megaContent.innerHTML = html;
  mega.classList.add('show');
}


/* ══════════════════════════════════════════════════════════════════
   SLUG HELPER — converts "Chart of Accounts" → "chart-of-accounts"
   ══════════════════════════════════════════════════════════════════ */
function _erpToSlug(str) {
  return (str || '').toLowerCase().replace(/[&]/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

/* ══════════════════════════════════════════════════════════════════
   NAVIGATE — build URL from data attributes and go there
   ══════════════════════════════════════════════════════════════════ */
function _erpNavigate(moduleId, pageName, subpageName) {
  var mod = ERP_MODULES[moduleId];
  if (!mod) return;

  var base = erpBasePath();
  var url = base + 'modules/' + mod.slug + '/';

  if (subpageName) {
    url += _erpToSlug(pageName) + '/' + _erpToSlug(subpageName) + '.html';
  } else if (pageName) {
    url += _erpToSlug(pageName) + '.html';
  } else {
    url += 'dashboard.html';
  }

  // Pre-flight check: GET request (HEAD unreliable on some dev servers)
  fetch(url, { method: 'GET', cache: 'no-store' }).then(function(response) {
    if (response.ok) {
      window.location.href = url;
    } else {
      var params = new URLSearchParams();
      params.set('module', moduleId);
      if (pageName) params.set('page', pageName);
      if (subpageName) params.set('subpage', subpageName);
      params.set('url', url);
      window.location.href = base + '404.html?' + params.toString();
    }
  }).catch(function() {
    window.location.href = url;
  });
}

/* ══════════════════════════════════════════════════════════════════
   WIRE NAV LINK CLICKS — sidebar + mega dropdown
   ══════════════════════════════════════════════════════════════════ */
function _erpWireNavLinks() {
  // Sidebar links
  document.querySelectorAll('.erp-nav-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      var moduleId = this.getAttribute('data-module');
      var page = this.getAttribute('data-page') || '';
      var subpage = this.getAttribute('data-subpage') || '';
      _erpNavigate(moduleId, page, subpage);
    });
  });

  // Mega dropdown links — these don't have data attributes, so we need to
  // wire them after the mega dropdown is built. We use event delegation on
  // the mega dropdown container instead.
  var mega = document.getElementById('hnav-mega-dropdown');
  if (mega && !mega._erpWired) {
    mega._erpWired = true;
    mega.addEventListener('click', function(e) {
      var link = e.target.closest('.erp-mega-link');
      if (!link) {
        // Check for module-level link
        link = e.target.closest('.erp-mega-module');
      }
      if (!link) return;
      e.preventDefault();
      e.stopPropagation();

      // Walk up to find the module context from the mega dropdown structure
      var moduleCol = link.closest('[class]');
      // Find the module header link in this column
      var col = link.closest('.grid > div');
      if (!col) return;
      var modLink = col.querySelector('.erp-mega-module');
      if (!modLink) return;

      // Extract module id from the module header (the "01" span)
      var idSpan = modLink.querySelector('span.text-xs');
      var moduleId = idSpan ? idSpan.textContent.trim() : '';
      if (!moduleId) return;

      if (link.classList.contains('erp-mega-module')) {
        // Clicked the module name — go to dashboard
        _erpNavigate(moduleId, '', '');
        return;
      }

      // Determine page and subpage from DOM context
      var parentDiv = link.closest('.mb-2');
      if (parentDiv) {
        // This is a subpage (Level 4) — parent page name is in the <p> element
        var pageP = parentDiv.querySelector('p');
        var pageName = pageP ? pageP.textContent.trim() : '';
        _erpNavigate(moduleId, pageName, link.textContent.trim());
      } else {
        // Simple page (Level 3)
        _erpNavigate(moduleId, link.textContent.trim(), '');
      }
    });
  }
}


/* ══════════════════════════════════════════════════════════════════
   INIT — called by erp-layout.js
   ══════════════════════════════════════════════════════════════════ */
function initNav() {
  'use strict';
  var mode = localStorage.getItem('erp-layout-mode') || 'vertical';

  if (document.getElementById('sidebar-nav-groups')) {
    buildSidebar();
  }
  if (mode === 'horizontal') {
    buildHorizontalNav();
  }

  // Wire up click handlers for all nav links
  _erpWireNavLinks();
}
