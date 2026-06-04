'use strict';

// Printing Job Detail — dynamic sheet rows via the jQuery Repeater plugin,
// client validation via the shared save.js helpers (validateFormFields / handleAjaxErrors).
(function () {
    const mode = window.PJD_MODE || 'create';
    const initial = window.PJD_INITIAL || null;

    const $form = $('#pjdForm');
    const $orderSelect = $('#orderFormSelect');
    const $rowsWrap = $('#rowsWrap');
    const $orderMeta = $('#orderMeta');
    const $metaClient = $('#metaClient');
    const $metaJob = $('#metaJob');
    const $summary = $('#rowsSummary');
    let setRepeaterList = null;

    function renumberRows() {
        const rows = $rowsWrap.find('.pjd-row');
        rows.each(function (i) {
            $(this).find('.row-no').val(i + 1);
            const isLast = i === rows.length - 1;
            // Only the last row shows "+"; earlier rows show "−".
            $(this).find('.row-add').toggle(isLast);
            $(this).find('.row-remove').toggle(!isLast);
        });
    }

    function recomputeRow($row) {
        const t = parseInt($row.find('.row-total').val() || 0, 10);
        const w = parseInt($row.find('.row-wastage').val() || 0, 10);
        $row.find('.row-final').val(Math.max(0, t - w));
    }

    function refreshSummary() {
        let total = 0;
        $rowsWrap.find('.pjd-row').each(function () {
            recomputeRow($(this));
            total += parseInt($(this).find('.row-final').val() || 0, 10);
        });
        const count = $rowsWrap.find('.pjd-row').length;
        $summary.text(count + ' row' + (count === 1 ? '' : 's') + ' · ' + total + ' final sheets');
    }

    // ── jQuery Repeater (project-standard dynamic rows) ──
    // Init on the wrapper that holds BOTH the list and the (hidden) create trigger —
    // data-repeater-create must be a sibling of the list, not inside an item.
    $('#pjdRepeater').repeater({
        initEmpty: false,
        show: function () {
            // Strip any validation marks cloned from the template row (values are
            // already blanked by the plugin on add, and kept intact by setList).
            $(this).find('.erp-field-error').remove();
            $(this).find('.border-red-500').removeClass('border-red-500');
            $(this).show();
            renumberRows();
            refreshSummary();
        },
        hide: function (deleteElement) {
            if ($rowsWrap.find('.pjd-row').length <= 1) return; // always keep one row
            $(this).slideUp(120, function () {
                deleteElement();
                renumberRows();
                refreshSummary();
            });
        },
        ready: function (setList) {
            setRepeaterList = setList;
        },
    });

    function setRows(items) {
        if (!setRepeaterList) return;
        const list = (items && items.length) ? items : [{}];
        setRepeaterList(list);
        // The repeater's setList does not reliably populate number inputs by name,
        // so set each row's total/wastage directly to guarantee the prefill shows.
        $rowsWrap.find('.pjd-row').each(function (i) {
            const it = list[i] || {};
            if (it.total_sheets != null) $(this).find('.row-total').val(it.total_sheets);
            if (it.wastage != null) $(this).find('.row-wastage').val(it.wastage);
        });
        renumberRows();
        refreshSummary();
    }

    // Per-row "+" button → trigger the repeater's hidden create button (adds a blank row).
    $rowsWrap.on('click', '.row-add', function () {
        $('#pjdRepeater').find('[data-repeater-create]').first().trigger('click');
    });

    // Recompute final sheets as the user types (delegated → works for repeater clones).
    $rowsWrap.on('input', '.row-total, .row-wastage', function () {
        recomputeRow($(this).closest('.pjd-row'));
        refreshSummary();
    });

    // ── Order Form searchable select ──
    const orderInst = erpSearchSelect('#orderFormSelect', {
        options: [],
        placeholder: 'Select Order Form',
        // In create mode, hide orders that already have a Printing Job Detail.
        freshPrefetch: { url: '/lookup/order-forms', limit: 300, extraData: { exclude_with_printing: mode === 'create' ? 1 : 0 } },
        onSearch: function (term, cb) {
            $.get('/lookup/order-forms', { q: term, limit: 50, exclude_with_printing: mode === 'create' ? 1 : 0 }, function (data) {
                cb((data || []).map(function (o) {
                    return {
                        value: String(o.value), label: o.label,
                        order_no: o.order_no, job_name: o.job_name, client_name: o.client_name,
                    };
                }));
            });
        },
        onChange: function (val, item) {
            if (item) {
                $metaClient.text(item.client_name || '');
                $metaJob.text(item.job_name || '');
                $orderMeta.show();
            }
            $('#error_order_form_id').html('');
            if (mode === 'edit') return; // don't clobber existing rows on edit
            if (!val) return;
            $.get('/lookup/order-forms/' + val + '/printing-job-prefill').done(function (resp) {
                setRows([{ total_sheets: resp.total_sheets || 0, wastage: resp.wastage || 0 }]);
            });
        },
    });

    if (mode === 'edit' && initial) {
        orderInst.setOptions([{ value: String(initial.order_form_id), label: initial.order_no + ' — ' + initial.job_name }], true);
        orderInst.setValue(String(initial.order_form_id), true);
        $metaClient.text(initial.client_name || '');
        $metaJob.text(initial.job_name || '');
        $orderMeta.show();
        setRows((initial.items || []).map(function (it) {
            return { total_sheets: it.total_sheets, wastage: it.wastage };
        }));
    } else {
        renumberRows(); // the initial repeater row is already in the DOM
        refreshSummary();
    }

    // ── Submit: client-validate (shared helper) → AJAX ──
    $('#pjdSubmit').on('click', function () {
        const $btn = $(this);

        // Validates every [required] field (order select + each row's total/wastage)
        // and renders inline border + message via save.js.
        if (typeof validateFormFields === 'function') {
            const errs = validateFormFields($form, mode === 'edit');
            if (errs.length) { if (typeof setButtonError === 'function') setButtonError($btn); return; }
        }

        const items = [];
        $rowsWrap.find('.pjd-row').each(function () {
            items.push({
                total_sheets: parseInt($(this).find('.row-total').val() || 0, 10),
                wastage: parseInt($(this).find('.row-wastage').val() || 0, 10),
            });
        });

        const isEdit = mode === 'edit';
        const url = isEdit ? '/printingjobdetails/' + initial.id : '/printingjobdetails';
        const method = isEdit ? 'PUT' : 'POST';

        if (typeof setButtonLoading === 'function') setButtonLoading($btn);

        $.ajax({
            url, method,
            data: { _token: $('meta[name="csrf-token"]').attr('content'), order_form_id: $orderSelect.val(), items },
        }).done(function (resp) {
            if (resp.status_code === 200) {
                if (window.toastr) toastr.success(resp.message, 'Success');
                else if (typeof erpToast === 'function') erpToast({ type: 'success', title: 'Success', message: resp.message });
                setTimeout(function () { window.location.href = resp.data; }, 700);
            } else {
                if (typeof resetButtonLoading === 'function') resetButtonLoading($btn);
                if (typeof showFormError === 'function') showFormError($form, resp.message || 'Save failed');
                else if (typeof erpToast === 'function') erpToast({ type: 'error', title: 'Error', message: resp.message || 'Save failed' });
            }
        }).fail(function (xhr) {
            if (typeof setButtonError === 'function') setButtonError($btn);
            if (typeof handleAjaxErrors === 'function') handleAjaxErrors($form, xhr);
            else if (typeof erpToast === 'function') erpToast({ type: 'error', title: 'Error', message: 'Save failed' });
        });
    });
})();
