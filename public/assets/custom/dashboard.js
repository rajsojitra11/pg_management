'use strict';

// Dashboard driver — pulls real print-shop data from the /dashboard AJAX
// endpoints (kpi-data, chart-data, table-data/{type}) and renders the KPI cards,
// 5 Chart.js charts (via erpChart) and 2 DataTables (via initErpTable). Wires the
// date-range filter, the Refresh button, and the Auto-refresh polling toggle.
(function () {
    var charts = {};   // canvasId -> Chart instance (destroyed before re-draw)
    var tables = {};   // selector -> DataTable instance (cleared+refilled on re-poll)
    var refreshTimer = null;
    var REFRESH_MS = 30000;
    var fpS = null;
    var fpE = null;
    var presetInst = null;

    var PRESET_OPTIONS = [
        { value: 'current_quarter', label: 'Current Quarter' },
        { value: 'current_year', label: 'Current Year' },
        { value: 'current_month', label: 'Current Month' },
        { value: 'last_month', label: 'Last Month' },
        { value: 'last_quarter', label: 'Last Quarter' },
        { value: 'custom', label: 'Custom Range' },
    ];

    function currentPreset() {
        return $('#dashboard-date-preset').val() || 'current_quarter';
    }

    // Set the preset without firing its onChange (caller decides what to reload).
    function setPreset(val) {
        if (presetInst) { presetInst.setValue(val, true); } else { $('#dashboard-date-preset').val(val); }
    }

    function dateParams() {
        return {
            s_date: $('#dashboard-s-date').val() || '',
            e_date: $('#dashboard-e-date').val() || '',
        };
    }

    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ── KPI cards ──
    function renderKpis(d) {
        d = d || {};
        setText('kpi-pending-form', d.pending_form || 0);
        setText('kpi-pending-delivery', d.pending_delivery || 0);
        setText('kpi-delivery-challans', d.delivery_challans || 0);
        setText('kpi-in-printing', d.in_printing || 0);
        setText('kpi-active-clients', d.active_clients || 0);
        setText('kpi-machines-online', d.machines_online || 0);
        setText('kpi-machines-total', d.machines_total != null ? '/' + d.machines_total : '');
    }

    // ── Charts ──
    function drawChart(id, type, data, opts) {
        if (charts[id]) {
            try { charts[id].destroy(); } catch (e) { /* noop */ }
            charts[id] = null;
        }
        if (typeof erpChart === 'function') {
            charts[id] = erpChart(id, type, data, opts);
        }
    }

    function renderCharts(c) {
        c = c || {};

        var om = c.orders_by_month || { labels: [], data: [] };
        drawChart('ordersChart', 'bar', {
            labels: om.labels,
            datasets: [{ label: 'Order Forms', data: om.data, borderRadius: 6 }],
        });

        var sd = c.status_distribution || { labels: [], data: [] };
        drawChart('statusChart', 'doughnut', {
            labels: sd.labels,
            datasets: [{ data: sd.data }],
        }, { plugins: { legend: { display: true, position: 'bottom' } }, cutout: '65%' });

        var tc = c.top_clients || { labels: [], data: [] };
        drawChart('clientsChart', 'bar', {
            labels: tc.labels,
            datasets: [{ label: 'Orders', data: tc.data, borderRadius: 6 }],
        }, { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } });

        var pm = c.production_by_machine || { labels: [], data: [] };
        drawChart('machineChart', 'doughnut', {
            labels: pm.labels,
            datasets: [{ data: pm.data }],
        }, { plugins: { legend: { display: true, position: 'bottom' } }, cutout: '65%' });

        var pp = c.post_press_mix || { labels: [], data: [] };
        drawChart('postPressChart', 'bar', {
            labels: pp.labels,
            datasets: [{ label: 'Jobs', data: pp.data, borderRadius: 6 }],
        }, { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } });
    }

    // ── Tables ──
    function renderStatusPill(d) {
        var map = {
            'Pending': 'background:var(--erp-warning-bg);border-color:var(--erp-warning-border);color:var(--erp-warning-text);',
            'Plate': 'background:var(--erp-accent-bg);border-color:var(--erp-accent-border);color:var(--erp-accent-text);',
            'Printed': 'background:var(--erp-info-bg);border-color:var(--erp-info-border);color:var(--erp-info-text);',
            'Post-Process': 'background:var(--erp-accent-bg);border-color:var(--erp-accent-border);color:var(--erp-accent-text);',
            'Delivered': 'background:var(--erp-success-bg);border-color:var(--erp-success-border);color:var(--erp-success-text);',
        };
        var s = map[d] || 'background:var(--erp-bg-muted);border-color:var(--erp-border);color:var(--erp-text-secondary);';
        return '<span class="dashboard-status" style="' + s + '">' + (d || '') + '</span>';
    }

    function renderCodeBadge(d) {
        return '<code class="erp-code-badge">' + (d || '') + '</code>';
    }

    function renderClientCell(d) {
        return '<div class="flex items-center gap-2">' +
            '<div class="dashboard-avatar"><i class="fa-solid fa-user" style="font-size:11px;"></i></div>' +
            '<span class="dashboard-client">' + (d || '') + '</span>' +
            '</div>';
    }

    function serialColumn() {
        return { data: null, render: function (d, t, r, m) { return m.row + 1; }, orderable: false, width: '50px' };
    }

    var jobColumns = [
        serialColumn(),
        { data: 'no', render: renderCodeBadge },
        { data: 'client', render: renderClientCell },
        { data: 'title' },
        { data: 'issue' },
        { data: 'status', render: renderStatusPill },
    ];

    var challanColumns = [
        serialColumn(),
        { data: 'no', render: renderCodeBadge },
        { data: 'client', render: renderClientCell },
        { data: 'job', render: renderCodeBadge },
        { data: 'date' },
    ];

    function loadTable(selector, url, columns) {
        $.get(url).done(function (rows) {
            rows = rows || [];
            if (tables[selector]) {
                tables[selector].clear().rows.add(rows).draw();
            } else if (typeof initErpTable === 'function') {
                tables[selector] = initErpTable(selector, {
                    data: rows,
                    columns: columns,
                    searching: false, lengthChange: false, paging: false, info: false,
                });
            }
        });
    }

    // ── Orchestration ──
    function loadDashboard() {
        var p = dateParams();
        $.get('/dashboard/kpi-data', p).done(renderKpis);
        $.get('/dashboard/chart-data', p).done(renderCharts);
        loadTable('#recentJobsTable', '/dashboard/table-data/recent-job-cards', jobColumns);
        loadTable('#recentChallansTable', '/dashboard/table-data/recent-delivery-challans', challanColumns);
    }

    // ── Date presets ──
    function fmt(dt) {
        var m = ('0' + (dt.getMonth() + 1)).slice(-2);
        var day = ('0' + dt.getDate()).slice(-2);
        return dt.getFullYear() + '-' + m + '-' + day;
    }

    function setRange(s, e) {
        if (fpS) { fpS.setDate(s, false); } else { $('#dashboard-s-date').val(s ? fmt(s) : ''); }
        if (fpE) { fpE.setDate(e, false); } else { $('#dashboard-e-date').val(e ? fmt(e) : ''); }
    }

    function applyPreset(preset) {
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth();
        var s, e, q, lq, ly;

        if (preset === 'current_month') { s = new Date(y, m, 1); e = new Date(y, m + 1, 0); }
        else if (preset === 'last_month') { s = new Date(y, m - 1, 1); e = new Date(y, m, 0); }
        else if (preset === 'current_year') { s = new Date(y, 0, 1); e = new Date(y, 11, 31); }
        else if (preset === 'current_quarter') { q = Math.floor(m / 3); s = new Date(y, q * 3, 1); e = new Date(y, q * 3 + 3, 0); }
        else if (preset === 'last_quarter') { lq = Math.floor(m / 3) - 1; ly = y; if (lq < 0) { lq = 3; ly = y - 1; } s = new Date(ly, lq * 3, 1); e = new Date(ly, lq * 3 + 3, 0); }
        else { return; } // custom — leave the user's dates untouched

        setRange(s, e);
    }

    function startAuto() { stopAuto(); refreshTimer = setInterval(loadDashboard, REFRESH_MS); }
    function stopAuto() { if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; } }

    $(function () {
        if (typeof flatpickr === 'function') {
            // Show d-m-Y to the user (altInput); keep the real value as Y-m-d for the backend.
            fpS = flatpickr('#dashboard-s-date', { altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d' });
            fpE = flatpickr('#dashboard-e-date', { altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d' });
        }

        // Searchable Quick Select (erpSearchSelect) — falls back to the native
        // <select>'s change event if the component isn't available.
        if (typeof erpSearchSelect === 'function') {
            presetInst = erpSearchSelect('#dashboard-date-preset', {
                options: PRESET_OPTIONS,
                placeholder: 'Select…',
                onChange: function (val) { applyPreset(val || 'current_quarter'); },
            });
            setPreset('current_quarter');
        } else {
            $('#dashboard-date-preset').on('change', function () { applyPreset(this.value); });
        }

        applyPreset(currentPreset());
        loadDashboard();

        $('.dashboard-search').on('click', loadDashboard);
        $('.dashboard-reset').on('click', function () {
            setPreset('current_quarter');
            applyPreset('current_quarter');
            loadDashboard();
        });
        $('#btn-refresh-dashboard').on('click', function () {
            var $icon = $('#btn-refresh-icon');
            $icon.addClass('fa-spin');
            loadDashboard();
            setTimeout(function () { $icon.removeClass('fa-spin'); }, 800);
        });

        var $toggle = $('#auto-refresh-toggle');
        if ($toggle.is(':checked')) { startAuto(); }
        $toggle.on('change', function () {
            if (this.checked) { startAuto(); } else { stopAuto(); }
            $.ajax({
                url: '/dashboard/toggle-auto-refresh',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), auto_refresh: this.checked ? 1 : 0 },
            });
        });
    });
})();
