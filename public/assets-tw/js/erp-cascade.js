/**
 * ERP Cascade Dropdown Engine & Shared Presets
 *
 * Core engine for all dependent/cascading dropdowns in the ERP system.
 * Depends on: erpSearchSelect(), getOptionsFromSelect() from erp-components.js
 *
 * Contents:
 *   - setSelectLoading()          — loading spinner for erpSearchSelect
 *   - initSearchSelect()          — edit-page helper (init + suppress onChange during setValue)
 *   - erpCascadeChain()           — generic cascade engine (handles any chain)
 *   - erpLocationCascade()        — Country→State→City preset
 *   - erpEntityLocationCascade()  — Customer/Supplier→Location preset
 *   - erpBatchCascade()           — Material→Batch preset (repeater-safe)
 *   - erpFormulationCascade()     — Formulation→BatchQty preset
 *
 * Per-module cascade files are in /cascades/*.js and loaded per-page.
 */
(function() {
  'use strict';

  // Parse a `key=value&key=value` querystring fragment into a plain object.
  // Used by cascade-engine fresh-prefetch wiring (data-prefetch-extra).
  function parseDataAttrQuery(qs) {
    if (!qs) return {};
    var out = {};
    qs.split('&').forEach(function(pair) {
      var idx = pair.indexOf('=');
      if (idx === -1) return;
      var k = decodeURIComponent(pair.slice(0, idx));
      var v = decodeURIComponent(pair.slice(idx + 1));
      if (k) out[k] = v;
    });
    return out;
  }

  /* ── Select Loading Spinner ──────────────────────────────────── */
  /**
   * setSelectLoading(selector) — Show loading spinner inside an erpSearchSelect wrapper.
   * Call this before AJAX to indicate the dropdown is loading.
   */
  window.setSelectLoading = function(selector) {
    var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!el) return;
    var wrapper = el.nextElementSibling;
    if (wrapper && wrapper.classList.contains('erp-select-wrapper')) {
      var display = wrapper.querySelector('.erp-select-display');
      if (display) display.innerHTML = '<span class="text-zinc-400"><i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i>Loading...</span>';
    }
  };

  /* ── Init Search Select (with initializing guard) ──────────── */
  /**
   * initSearchSelect(selector, options, placeholder, selectedVal, onChangeFn)
   * Convenience wrapper for edit pages: inits erpSearchSelect, sets pre-selected
   * value WITHOUT triggering the onChange callback during init.
   * Returns the erpSearchSelect instance.
   */
  window.initSearchSelect = function(selector, options, placeholder, selectedVal, onChangeFn) {
    var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!el) return null;
    var initializing = true;
    var inst = erpSearchSelect(selector, {
      options: options,
      placeholder: placeholder || 'Select...',
      onChange: function(val) {
        el.value = val;
        if (!initializing && onChangeFn) onChangeFn(val);
      }
    });
    if (selectedVal && inst) {
      inst.setValue(String(selectedVal));
      el.value = selectedVal;
    }
    initializing = false;
    return inst;
  };

  /* ── Cascade Chain Engine ──────────────────────────────────── */
  /**
   * erpCascadeChain(config) — Generic cascading dropdown engine.
   *
   * config.token:  CSRF token (auto-added to POST requests)
   * config.chain:  Array of step definitions (parent → ... → leaf):
   *   {
   *     selector:     '#country_id',       // CSS selector or DOM element
   *     placeholder:  'Select...',         // placeholder text
   *     value:        '5' | null,          // pre-selected value (edit) or null (create)
   *     url:          '/change-state',     // AJAX URL to load NEXT step's options (omit for leaf)
   *     method:       'POST',             // HTTP method (default: 'POST')
   *     paramName:    'id',               // param name for this step's value (default: 'id')
   *     paramStyle:   'data',             // 'data' = query/body param, 'url' = append to URL
   *     extraData:    {},                 // extra params merged into AJAX data
   *     responseKey:  'result',           // key in JSON response containing option array (null = root)
   *     formatLabel:  function(row) {},   // custom label builder (default: row.name)
   *     formatValue:  function(row) {},   // custom value builder (default: String(row.id))
   *     onChange:      function(val) {},   // fires ONLY on user interaction, not during init
   *     disabled:      false,             // make wrapper readonly (pointer-events: none, opacity: 0.6)
   *     sort:          true,              // sort options alphabetically (default: true)
   *   }
   *
   * Returns: Array of erpSearchSelect instances in chain order.
   */
  window.erpCascadeChain = function(config) {
    var chain = config.chain || [];
    var token = config.token || '';
    var instances = [];
    var _suppress = true; // suppress cascade during init

    // Create all instances
    for (var i = 0; i < chain.length; i++) {
      (function(idx) {
        var step = chain[idx];
        var el = typeof step.selector === 'string' ? document.querySelector(step.selector) : step.selector;
        if (!el) { instances.push(null); return; }

        var initOpts = getOptionsFromSelect(el);

        // Resolve freshPrefetch: explicit step config OR the <select>'s
        // data-fresh-prefetch attribute. Lets cascade-engine forms opt into
        // the per-session prefetch + hybrid fallback by adding a single
        // attribute to the entity select (e.g. customer / supplier picker).
        var freshPrefetch = step.freshPrefetch || null;
        if (!freshPrefetch && el.getAttribute && el.getAttribute('data-fresh-prefetch')) {
          freshPrefetch = {
            url: el.getAttribute('data-fresh-prefetch'),
            limit: parseInt(el.getAttribute('data-prefetch-limit'), 10) || 300,
            sort: el.getAttribute('data-prefetch-sort') || '-updated_at',
            extraData: parseDataAttrQuery(el.getAttribute('data-prefetch-extra'))
          };
        }

        var inst = erpSearchSelect(el, {
          options: initOpts,
          placeholder: step.placeholder || 'Select...',
          freshPrefetch: freshPrefetch,
          onChange: function(val) {
            el.value = val;

            if (_suppress) return;

            // User-facing onChange callback
            if (step.onChange) step.onChange(val, instances[idx]);

            // If this step has a URL, it loads the NEXT step's options
            if (step.url && idx + 1 < chain.length) {
              // Clear all downstream steps
              for (var d = idx + 1; d < chain.length; d++) {
                if (instances[d]) {
                  if (d === idx + 1) setSelectLoading(chain[d].selector);
                  else instances[d].setOptions([]);
                }
              }

              if (!val) {
                // No value selected — just clear downstream
                for (var d2 = idx + 1; d2 < chain.length; d2++) {
                  if (instances[d2]) instances[d2].setOptions([]);
                }
                return;
              }

              // Build AJAX request
              var method = (step.method || 'POST').toUpperCase();
              var url = step.url;
              var ajaxData = {};

              if (step.paramStyle === 'url') {
                // Append value to URL path
                url = url.replace(/\/+$/, '') + '/' + encodeURIComponent(val);
              } else {
                ajaxData[step.paramName || 'id'] = val;
              }

              if (method === 'POST' && token) ajaxData._token = token;
              if (step.extraData) $.extend(ajaxData, step.extraData);

              $.ajax({
                url: url,
                type: method,
                data: ajaxData,
                dataType: 'json',
                success: function(resp) {
                  var rows = step.responseKey ? resp[step.responseKey] : (resp.result || resp);
                  if (!Array.isArray(rows)) rows = [];

                  var fmtLabel = step.formatLabel || function(r) { return r.name || r.label || ''; };
                  var fmtValue = step.formatValue || function(r) { return String(r.id || r.value || ''); };

                  var opts = [];
                  for (var r = 0; r < rows.length; r++) {
                    opts.push({ value: fmtValue(rows[r]), label: fmtLabel(rows[r]) });
                  }

                  var shouldSort = step.sort !== false;
                  if (shouldSort) opts.sort(function(a, b) { return a.label.localeCompare(b.label); });

                  if (instances[idx + 1]) instances[idx + 1].setOptions(opts);
                }
              });
            }
          }
        });

        // Apply disabled state
        if (step.disabled && el) {
          var wrapper = el.nextElementSibling;
          if (wrapper && wrapper.classList.contains('erp-select-wrapper')) {
            wrapper.style.pointerEvents = 'none';
            wrapper.style.opacity = '0.6';
          }
        }

        instances.push(inst);
      })(i);
    }

    // Set pre-selected values (silent — no onChange fires)
    for (var j = 0; j < chain.length; j++) {
      if (chain[j].value && instances[j]) {
        instances[j].setValue(String(chain[j].value), true);  // silent
        var el2 = typeof chain[j].selector === 'string' ? document.querySelector(chain[j].selector) : chain[j].selector;
        if (el2) el2.value = chain[j].value;
      }
    }

    // Auto-load: if a parent has value + url but next step has no options and needs a value,
    // trigger the AJAX chain to populate downstream steps on page load (edit pages without
    // server-rendered child options, e.g., Setting edit).
    // NOTE: _suppress stays true during autoLoad — unlocked only when autoLoad finishes.
    (function autoLoad(fromIdx) {
      if (fromIdx >= chain.length) { _suppress = false; return; }
      var step = chain[fromIdx];
      if (!step.url || !step.value || !instances[fromIdx]) { _suppress = false; return; }
      var nextIdx = fromIdx + 1;
      if (nextIdx >= chain.length || !instances[nextIdx]) { _suppress = false; return; }

      // Check if next step has empty options (not pre-rendered by server)
      var nextEl = typeof chain[nextIdx].selector === 'string' ? document.querySelector(chain[nextIdx].selector) : chain[nextIdx].selector;
      var hasOptions = nextEl && nextEl.querySelectorAll('option[value]:not([value=""])').length > 0;
      if (hasOptions) {
        // Options already loaded — check further downstream
        autoLoad(nextIdx);
        return;
      }

      // Need to fetch next step's options
      var method = (step.method || 'POST').toUpperCase();
      var url = step.url;
      var ajaxData = {};
      if (step.paramStyle === 'url') {
        url = url.replace(/\/+$/, '') + '/' + encodeURIComponent(step.value);
      } else {
        ajaxData[step.paramName || 'id'] = step.value;
      }
      if (method === 'POST' && token) ajaxData._token = token;
      if (step.extraData) $.extend(ajaxData, step.extraData);

      setSelectLoading(chain[nextIdx].selector);

      $.ajax({
        url: url, type: method, data: ajaxData, dataType: 'json',
        success: function(resp) {
          var rows = step.responseKey ? resp[step.responseKey] : (resp.result || resp);
          if (!Array.isArray(rows)) rows = [];
          var fmtLabel = step.formatLabel || function(r) { return r.name || r.label || ''; };
          var fmtValue = step.formatValue || function(r) { return String(r.id || r.value || ''); };
          var opts = [];
          for (var r = 0; r < rows.length; r++) {
            opts.push({ value: fmtValue(rows[r]), label: fmtLabel(rows[r]) });
          }
          if (step.sort !== false) opts.sort(function(a, b) { return a.label.localeCompare(b.label); });
          instances[nextIdx].setOptions(opts);  // silent by default (setOptions uses silent updateDisplay)

          // Set the next step's pre-selected value (silent — no cascade)
          if (chain[nextIdx].value) {
            instances[nextIdx].setValue(String(chain[nextIdx].value), true);  // silent
            var nextEl2 = typeof chain[nextIdx].selector === 'string' ? document.querySelector(chain[nextIdx].selector) : chain[nextIdx].selector;
            if (nextEl2) nextEl2.value = chain[nextIdx].value;
          }

          // Continue auto-loading downstream
          autoLoad(nextIdx);
        }
      });
    })(0);

    return instances;
  };

  /* ── Preset: Country → State → City ────────────────────────── */
  /**
   * erpLocationCascade(selectors, values, opts)
   *
   * selectors: { country: '#country_id', state: '#state_id', city: '#city_id' }
   * values:    { country: '1', state: '5', city: '12' } or {} for create pages
   * opts:      { token, stateUrl, cityUrl, placeholder, onCountryChange, onStateChange, onCityChange }
   *
   * Returns: { country: inst, state: inst, city: inst }
   */
  window.erpLocationCascade = function(selectors, values, opts) {
    opts = opts || {};
    values = values || {};
    var ph = opts.placeholder || 'Select...';

    var instances = erpCascadeChain({
      token: opts.token,
      chain: [
        {
          selector: selectors.country,
          placeholder: ph,
          value: values.country || null,
          url: opts.stateUrl,
          method: 'POST',
          paramName: 'id',
          responseKey: 'result',
          formatLabel: function(row) { return row.name + (row.code ? ' | ' + row.code : ''); },
          onChange: opts.onCountryChange || null,
        },
        {
          selector: selectors.state,
          placeholder: ph,
          value: values.state || null,
          url: opts.cityUrl,
          method: 'POST',
          paramName: 'id',
          responseKey: 'result',
          formatLabel: function(row) { return row.name; },
          onChange: opts.onStateChange || null,
        },
        {
          selector: selectors.city,
          placeholder: ph,
          value: values.city || null,
          onChange: opts.onCityChange || null,
        }
      ]
    });

    return { country: instances[0], state: instances[1], city: instances[2] };
  };

  /* ── Preset: Entity → Location (Customer/Supplier) ─────────── */
  /**
   * erpEntityLocationCascade(selectors, values, opts)
   *
   * selectors: { entity: '#customer_id', location: '#location_id' }
   * values:    { entity: '10', location: '3' } or {} for create pages
   * opts:      { token, url, method, responseKey, formatLabel, formatValue,
   *             onEntityChange, onLocationChange, entityDisabled, locationDisabled }
   *
   * Returns: { entity: inst, location: inst }
   */
  window.erpEntityLocationCascade = function(selectors, values, opts) {
    opts = opts || {};
    values = values || {};
    var ph = opts.placeholder || 'Select...';

    var instances = erpCascadeChain({
      token: opts.token,
      chain: [
        {
          selector: selectors.entity,
          placeholder: ph,
          value: values.entity || null,
          url: opts.url,
          method: opts.method || 'GET',
          paramName: opts.paramName || 'id',
          responseKey: opts.responseKey || 'locations',
          formatLabel: opts.formatLabel || function(row) { return row.location_name || row.name || ''; },
          formatValue: opts.formatValue || function(row) { return String(row.id); },
          onChange: opts.onEntityChange || null,
          disabled: opts.entityDisabled || false,
        },
        {
          selector: selectors.location,
          placeholder: ph,
          value: values.location || null,
          onChange: opts.onLocationChange || null,
          disabled: opts.locationDisabled || false,
        }
      ]
    });

    return { entity: instances[0], location: instances[1] };
  };

  /* ── Preset: Material → Batch (repeater-safe) ─────────────── */
  /**
   * erpBatchCascade(container, selectors, opts)
   *
   * container:  DOM element (row) to scope selectors to, or null for global
   * selectors:  { material: '.material-select', batch: '.batch-select' }
   * opts:       { token, batchUrl, method, paramStyle, paramName, responseKey,
   *              formatLabel, formatValue, onMaterialChange, onBatchChange }
   *
   * Returns: { material: inst, batch: inst }
   */
  window.erpBatchCascade = function(container, selectors, opts) {
    opts = opts || {};

    // Resolve selectors within container
    var matEl = container ? container.querySelector(selectors.material) : document.querySelector(selectors.material);
    var batchEl = container ? container.querySelector(selectors.batch) : document.querySelector(selectors.batch);

    var instances = erpCascadeChain({
      token: opts.token,
      chain: [
        {
          selector: matEl,
          placeholder: opts.placeholder || 'Select...',
          value: opts.materialValue || null,
          url: opts.batchUrl,
          method: opts.method || 'GET',
          paramName: opts.paramName || 'id',
          paramStyle: opts.paramStyle || 'url',
          responseKey: opts.responseKey || null,
          formatLabel: opts.formatLabel || function(row) {
            return row.batch_no + (row.unrestricted_stock != null ? ' (' + row.unrestricted_stock + ')' : '');
          },
          formatValue: opts.formatValue || function(row) { return String(row.id); },
          onChange: opts.onMaterialChange || null,
        },
        {
          selector: batchEl,
          placeholder: opts.batchPlaceholder || 'Select batch...',
          value: opts.batchValue || null,
          onChange: opts.onBatchChange || null,
        }
      ]
    });

    return { material: instances[0], batch: instances[1] };
  };

  /* ── Preset: Formulation → Batch Qty ───────────────────────── */
  /**
   * erpFormulationCascade(selectors, values, opts)
   *
   * selectors: { formulation: '#formulation_id', batch: '#batch_qty_id' }
   * values:    { formulation: '8', batch: '2' } or {} for create pages
   * opts:      { token, batchUrl, responseKey, formatLabel, onFormulationChange, onBatchChange }
   *
   * Returns: { formulation: inst, batch: inst }
   */
  window.erpFormulationCascade = function(selectors, values, opts) {
    opts = opts || {};
    values = values || {};
    var ph = opts.placeholder || 'Select...';

    var instances = erpCascadeChain({
      token: opts.token,
      chain: [
        {
          selector: selectors.formulation,
          placeholder: ph,
          value: values.formulation || null,
          url: opts.batchUrl,
          method: opts.method || 'POST',
          paramName: opts.paramName || 'id',
          responseKey: opts.responseKey || 'formulationBatch',
          formatLabel: opts.formatLabel || function(row) {
            return row.batch_qty + (row.name ? ' ' + row.name : '');
          },
          onChange: opts.onFormulationChange || null,
        },
        {
          selector: selectors.batch,
          placeholder: ph,
          value: values.batch || null,
          onChange: opts.onBatchChange || null,
        }
      ]
    });

    return { formulation: instances[0], batch: instances[1] };
  };

})();
