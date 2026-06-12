'use strict';

(function () {
    var charts = {};
    var tables = {};
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

    function formatCurrency(val) {
        var num = parseFloat(val) || 0;
        return '\u20B9' + num.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function renderKpis(d) {
        d = d || {};
        setText('kpi-total-pg', d.total_pg || 0);
        setText('kpi-total-rooms', d.total_rooms || 0);
        setText('kpi-occupied-rooms', d.occupied_rooms || 0);
        setText('kpi-vacant-rooms', d.vacant_rooms || 0);
        setText('kpi-active-tenants', d.active_tenants || 0);
        setText('kpi-monthly-revenue', formatCurrency(d.monthly_revenue));
    }

    function drawChart(id, type, data, opts) {
        if (charts[id]) {
            try { charts[id].destroy(); } catch (e) { }
            charts[id] = null;
        }
        if (typeof erpChart === 'function') {
            charts[id] = erpChart(id, type, data, opts);
        }
    }

    function renderCharts(c) {
        c = c || {};

        var rm = c.revenue_by_month || { labels: [], data: [] };
        drawChart('revenueChart', 'bar', {
            labels: rm.labels,
            datasets: [{ label: 'Revenue (' + '\u20B9)', data: rm.data, borderRadius: 6 }],
        });

        var or = c.occupancy_rate || { labels: [], data: [] };
        drawChart('occupancyChart', 'doughnut', {
            labels: or.labels,
            datasets: [{ data: or.data }],
        }, { plugins: { legend: { display: true, position: 'bottom' } }, cutout: '65%' });

        var tp = c.top_pg_tenants || { labels: [], data: [] };
        drawChart('topPgChart', 'bar', {
            labels: tp.labels,
            datasets: [{ label: 'Tenants', data: tp.data, borderRadius: 6 }],
        }, { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } });

        var pm = c.payment_methods || { labels: [], data: [] };
        drawChart('paymentMethodChart', 'doughnut', {
            labels: pm.labels,
            datasets: [{ data: pm.data }],
        }, { plugins: { legend: { display: true, position: 'bottom' } }, cutout: '65%' });

        var rc = c.room_category_dist || { labels: [], data: [] };
        drawChart('categoryChart', 'bar', {
            labels: rc.labels,
            datasets: [{ label: 'Rooms', data: rc.data, borderRadius: 6 }],
        }, { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } });
    }

    function renderStatusPill(d) {
        var map = {
            'active': 'background:var(--erp-success-bg);border-color:var(--erp-success-border);color:var(--erp-success-text);',
            'inactive': 'background:var(--erp-warning-bg);border-color:var(--erp-warning-border);color:var(--erp-warning-text);',
            'paid': 'background:var(--erp-success-bg);border-color:var(--erp-success-border);color:var(--erp-success-text);',
            'pending': 'background:var(--erp-warning-bg);border-color:var(--erp-warning-border);color:var(--erp-warning-text);',
            'refunded': 'background:var(--erp-info-bg);border-color:var(--erp-info-border);color:var(--erp-info-text);',
        };
        var s = map[d ? d.toLowerCase() : ''] || 'background:var(--erp-bg-muted);border-color:var(--erp-border);color:var(--erp-text-secondary);';
        return '<span class="dashboard-status" style="' + s + '">' + (d || '') + '</span>';
    }

    function renderCodeBadge(d) {
        return '<code class="erp-code-badge">' + (d || '') + '</code>';
    }

    function renderAvatarCell(d) {
        return '<div class="flex items-center gap-2">' +
            '<div class="dashboard-avatar"><i class="fa-solid fa-user" style="font-size:11px;"></i></div>' +
            '<span class="dashboard-client">' + (d || '') + '</span>' +
            '</div>';
    }

    function serialColumn() {
        return { data: null, render: function (d, t, r, m) { return m.row + 1; }, orderable: false, width: '50px' };
    }

    var tenantColumns = [
        serialColumn(),
        { data: 'name', render: renderAvatarCell },
        { data: 'pg_name', render: renderCodeBadge },
        { data: 'room_no', render: renderCodeBadge },
        { data: 'phone' },
        { data: 'checkin_date' },
        { data: 'status', render: renderStatusPill },
    ];

    var paymentColumns = [
        serialColumn(),
        { data: 'ref_no', render: renderCodeBadge },
        { data: 'tenant', render: renderAvatarCell },
        { data: 'pg', render: renderCodeBadge },
        { data: 'amount' },
        { data: 'method' },
        { data: 'date' },
        { data: 'status', render: renderStatusPill },
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

    function loadDashboard() {
        var p = dateParams();
        $.get('/dashboard/kpi-data', p).done(renderKpis);
        $.get('/dashboard/chart-data', p).done(renderCharts);
        loadTable('#recentTenantsTable', '/dashboard/table-data/recent-tenants', tenantColumns);
        loadTable('#recentPaymentsTable', '/dashboard/table-data/recent-payments', paymentColumns);
    }

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
        else { return; }

        setRange(s, e);
    }

    function startAuto() { stopAuto(); refreshTimer = setInterval(loadDashboard, REFRESH_MS); }
    function stopAuto() { if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; } }

    $(function () {
        if (typeof flatpickr === 'function') {
            fpS = flatpickr('#dashboard-s-date', { altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d' });
            fpE = flatpickr('#dashboard-e-date', { altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d' });
        }

        if (typeof erpSearchSelect === 'function') {
            presetInst = erpSearchSelect('#dashboard-date-preset', {
                options: PRESET_OPTIONS,
                placeholder: 'Select\u2026',
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
