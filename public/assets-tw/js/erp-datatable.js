/* ERP DataTable — Factory with Tailwind-styled defaults + column visibility */
(function() {
  'use strict';

  var ERP_DT_DEFAULTS = {
    responsive: true,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
    language: {
      search: '',
      searchPlaceholder: 'Search records...',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'No entries found',
      infoFiltered: '(filtered from _MAX_ total)',
      paginate: {
        first: '<i class="fa-solid fa-angles-left text-xs"></i>',
        previous: '<i class="fa-solid fa-chevron-left text-xs"></i>',
        next: '<i class="fa-solid fa-chevron-right text-xs"></i>',
        last: '<i class="fa-solid fa-angles-right text-xs"></i>',
      },
      emptyTable: '<div class="py-12 text-center"><div class="h-12 w-12 rounded-full bg-zinc-100 flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-inbox text-xl text-zinc-400"></i></div><h3 class="text-sm font-semibold text-zinc-900">No records found</h3><p class="text-sm text-zinc-500 mt-1 max-w-sm mx-auto">Try adjusting your search or filter criteria to find what you\'re looking for.</p></div>',
      loadingRecords: '<div class="py-12 flex flex-col items-center justify-center gap-3"><div class="erp-loader-spinner" style="width:32px;height:32px;border:3px solid var(--erp-border);border-top-color:var(--erp-primary);border-radius:50%;animation:erp-loader-spin 0.8s linear infinite;"></div><div class="flex gap-1.5"><span style="width:6px;height:6px;border-radius:50%;background:var(--erp-primary);animation:erp-loader-bounce 1.4s ease-in-out infinite;"></span><span style="width:6px;height:6px;border-radius:50%;background:var(--erp-primary);animation:erp-loader-bounce 1.4s ease-in-out 0.16s infinite;"></span><span style="width:6px;height:6px;border-radius:50%;background:var(--erp-primary);animation:erp-loader-bounce 1.4s ease-in-out 0.32s infinite;"></span></div><p class="text-xs font-medium" style="color:var(--erp-text-secondary);">Loading data...</p></div>',
      processing: '',
    },
    /* DT 2.x uses layout instead of dom */
    layout: {
      topStart: 'pageLength',
      topEnd: 'search',
      bottomStart: 'info',
      bottomEnd: 'paging'
    },
  };

  /**
   * Build column visibility dropdown for a DataTable instance.
   * Inserts a "Columns" button next to the table's search box.
   */
  function buildColumnVisibility(table, $wrapper) {
    var columns = table.columns().header().toArray();
    if (columns.length < 3) return; // skip for tiny tables

    var uid = 'erp-colvis-' + Math.random().toString(36).substr(2, 6);

    // Build dropdown HTML
    var html = '<div class="erp-colvis-wrap relative inline-block" id="' + uid + '">' +
      '<button class="erp-colvis-btn h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-1.5" type="button">' +
        '<i class="fa-solid fa-table-columns text-xs text-zinc-400"></i>' +
        '<span>Columns</span>' +
        '<i class="fa-solid fa-chevron-down text-[10px] text-zinc-400"></i>' +
      '</button>' +
      '<div class="erp-colvis-menu hidden absolute right-0 top-full mt-1 z-50 w-56 rounded-lg border border-zinc-200 bg-white shadow-lg py-1" style="max-height:320px;overflow-y:auto">';

    columns.forEach(function(th, idx) {
      var title = $(th).text().trim();
      if (!title) return; // skip checkbox columns
      html += '<label class="flex items-center gap-2.5 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 cursor-pointer">' +
        '<input type="checkbox" checked class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 erp-colvis-check" data-col-idx="' + idx + '">' +
        '<span class="truncate">' + title + '</span>' +
      '</label>';
    });

    html += '<div class="border-t border-zinc-100 mt-1 pt-1 px-3 py-2">' +
      '<button class="text-xs font-medium text-zinc-500 hover:text-zinc-700 erp-colvis-reset">Show All</button>' +
    '</div></div></div>';

    // Insert next to the search box — handle DT 2.x (dt-search) and 1.x (dataTables_filter)
    var $searchDiv = $wrapper.find('.dt-search, .dataTables_filter').first();
    if ($searchDiv.length) {
      // For DT 2.x, target the layout cell parent for flex alignment
      var $cell = $searchDiv.closest('.dt-layout-cell');
      if ($cell.length) {
        $cell.css('display', 'flex').css('align-items', 'center').css('gap', '8px').css('justify-content', 'flex-end');
        $cell.prepend(html);
      } else {
        $searchDiv.css('display', 'flex').css('align-items', 'center').css('gap', '8px');
        $searchDiv.prepend(html);
      }
    } else {
      // Fallback: insert at top of first layout row
      var $topRow = $wrapper.find('.dt-layout-row').first();
      if ($topRow.length) {
        $topRow.find('.dt-layout-cell').last().append(html);
      } else {
        $wrapper.prepend('<div class="mb-3 flex justify-end">' + html + '</div>');
      }
    }

    var $wrap = $('#' + uid);
    var $menu = $wrap.find('.erp-colvis-menu');

    // Toggle dropdown
    $wrap.find('.erp-colvis-btn').on('click', function(e) {
      e.stopPropagation();
      $menu.toggleClass('hidden');
    });

    // Close on outside click
    $(document).on('click', function(e) {
      if (!$(e.target).closest('#' + uid).length) {
        $menu.addClass('hidden');
      }
    });

    // Toggle column visibility
    $wrap.on('change', '.erp-colvis-check', function() {
      var colIdx = parseInt($(this).data('col-idx'));
      var visible = $(this).prop('checked');
      table.column(colIdx).visible(visible);
    });

    // Show All
    $wrap.on('click', '.erp-colvis-reset', function() {
      $wrap.find('.erp-colvis-check').prop('checked', true);
      table.columns().visible(true);
    });
  }

  /**
   * initErpTable(selector, options)
   * Merges ERP defaults with custom options.
   * Automatically adds column visibility toggle unless options.colVisibility === false.
   */
  window.initErpTable = function(selector, options) {
    options = options || {};
    var showColVis = options.colVisibility !== false;
    delete options.colVisibility; // don't pass to DataTables

    // Enterprise loading overlay — inject via preDrawCallback / drawCallback
    var $tableEl = $(selector);
    var $card = $tableEl.closest('.rounded-lg, .shadow-sm, .border').first();
    if (!$card.length) $card = $tableEl.parent();
    var loaderHtml = '<div class="erp-widget-loader"><div class="erp-loader-spinner"></div><div class="erp-loader-dots"><span></span><span></span><span></span></div></div>';

    var userPreDraw = options.preDrawCallback;
    var userDrawCb = options.drawCallback;

    var merged = $.extend(true, {}, ERP_DT_DEFAULTS, options, {
      processing: true,
      preDrawCallback: function(settings) {
        $card.addClass('erp-widget-loading').css('position', 'relative');
        if (!$card.find('.erp-widget-loader').length) {
          $card.append(loaderHtml);
        }
        if (typeof userPreDraw === 'function') userPreDraw.call(this, settings);
      },
      drawCallback: function(settings) {
        $card.removeClass('erp-widget-loading');
        $card.find('.erp-widget-loader').remove();
        if (typeof userDrawCb === 'function') userDrawCb.call(this, settings);
      }
    });

    var table = $tableEl.DataTable(merged);

    // Remove sorting indicators from non-orderable columns (checkbox, actions)
    var colSettings = table.settings()[0].aoColumns;
    $(selector).find('thead th').each(function(idx) {
      if ((colSettings[idx] && colSettings[idx].bSortable === false) ||
          $(this).find('input[type="checkbox"]').length) {
        $(this).find('span.dt-column-order').remove();
        $(this).removeClass('dt-orderable-asc dt-orderable-desc sorting');
        $(this).css('padding-right', '10px');
      }
    });

    // Auto-add column visibility for full tables (not mini/embedded ones)
    if (showColVis && options.searching !== false && options.paging !== false) {
      var $wrapper = $(selector).closest('.dt-container, .dataTables_wrapper');
      if ($wrapper.length) {
        buildColumnVisibility(table, $wrapper);
      }
    }

    return table;
  };

  /* ── Helper renderers ───────────────────────────────────────── */

  /** Status badge renderer */
  window.erpBadge = function(status) {
    var map = {
      active: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
      approved: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
      completed: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
      paid: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
      pending: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700' },
      draft: { bg: 'bg-zinc-50', border: 'border-zinc-200', text: 'text-zinc-700' },
      inactive: { bg: 'bg-zinc-50', border: 'border-zinc-200', text: 'text-zinc-700' },
      rejected: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700' },
      cancelled: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700' },
      overdue: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700' },
      'in-progress': { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700' },
      processing: { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700' },
    };

    var key = (status || '').toLowerCase().replace(/\s+/g, '-');
    var style = map[key] || { bg: 'bg-zinc-50', border: 'border-zinc-200', text: 'text-zinc-700' };

    return '<span class="inline-flex items-center whitespace-nowrap rounded-md border px-2.5 py-0.5 text-xs font-medium ' +
      style.bg + ' ' + style.border + ' ' + style.text + '">' + status + '</span>';
  };

  /** Currency formatter */
  window.erpCurrency = function(amount, currency) {
    currency = currency || 'USD';
    var n = parseFloat(amount);
    if (isNaN(n)) return '$0.00';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency }).format(n);
  };

  /** Action buttons renderer */
  window.erpActionButtons = function(opts) {
    opts = opts || {};
    var html = '<div class="flex items-center gap-1">';
    if (opts.view !== false) {
      html += '<button class="p-1.5 rounded-md text-blue-500 hover:text-blue-700 hover:bg-blue-50" title="View" data-action="view"><i class="fa-solid fa-eye text-xs"></i></button>';
    }
    if (opts.edit !== false) {
      html += '<button class="p-1.5 rounded-md text-amber-500 hover:text-amber-700 hover:bg-amber-50" title="Edit" data-action="edit"><i class="fa-solid fa-pen text-xs"></i></button>';
    }
    if (opts.delete !== false) {
      html += '<button class="p-1.5 rounded-md text-red-400 hover:text-red-600 hover:bg-red-50" title="Delete" data-action="delete"><i class="fa-solid fa-trash text-xs"></i></button>';
    }
    html += '</div>';
    return html;
  };

  /** Date formatter (dd-mm-yyyy) */
  window.erpDate = function(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    var day = ('0' + d.getDate()).slice(-2);
    var month = ('0' + (d.getMonth() + 1)).slice(-2);
    var year = d.getFullYear();
    return day + '-' + month + '-' + year;
  };

})();
