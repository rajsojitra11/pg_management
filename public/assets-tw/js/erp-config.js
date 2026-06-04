/* ERP Config — loads nav data from assets/data/nav.json
   All 46 modules, 4-level: Group > Module > Page > Sub-page
   Edit nav.json to add/remove/reorder modules — no JS changes needed. */

var ERP_NAV = [];
var ERP_MODULES = {};
var _erpNavReady = false;
var _erpNavCallbacks = [];

/* Utility: normalize a page entry — returns { name, children } */
function erpNormalizePage(page) {
  if (typeof page === 'string') return { name: page, children: [] };
  return { name: page.name, children: page.children || [] };
}

/* Utility: get base path relative to current page depth */
function erpBasePath() {
  var path = window.location.pathname;
  if (path.includes('/pages/')) return '../';  /* backward compat if pages/ still used */
  if (path.includes('/modules/')) {
    // Count segments after /modules/ to determine depth
    // modules/slug/page.html → 2 levels (../../)
    // modules/slug/subdir/page.html → 3 levels (../../../)
    var afterModules = path.split('/modules/')[1] || '';
    var segments = afterModules.split('/').filter(function(s) { return s.length > 0; });
    var prefix = '';
    for (var i = 0; i < segments.length; i++) prefix += '../';
    return prefix || '../../';
  }
  return './';  /* HTML at root level */
}

/* Register a callback to run when nav data is loaded */
function erpOnNavReady(fn) {
  if (_erpNavReady) fn();
  else _erpNavCallbacks.push(fn);
}

/* Build the ERP_MODULES lookup from ERP_NAV */
function _buildModuleLookup() {
  ERP_MODULES = {};
  ERP_NAV.forEach(function(group) {
    group.modules.forEach(function(mod) {
      ERP_MODULES[mod.id] = { id: mod.id, name: mod.name, slug: mod.slug, pages: mod.pages, group: group.group, groupIcon: group.icon };
    });
  });
}

/* Fetch nav.json */
(function() {
  var base = erpBasePath();
  var url = base + 'assets/data/nav.json';

  fetch(url)
    .then(function(r) {
      if (!r.ok) throw new Error('Failed to load nav.json: ' + r.status);
      return r.json();
    })
    .then(function(data) {
      ERP_NAV = data;
      _buildModuleLookup();
      _erpNavReady = true;
      _erpNavCallbacks.forEach(function(fn) { fn(); });
      _erpNavCallbacks = [];
    })
    .catch(function(err) {
      console.error('ERP Config: Could not load nav data.', err);
      console.warn('Falling back — nav.json must be served via HTTP (use VS Code Live Server).');
    });
})();
