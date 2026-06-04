'use strict';

// Driver for the LaminationOrder form.
// Dynamic rows via the jQuery Repeater plugin; the per-row "type" column is an
// erpSearchSelect that must be stripped + re-initialised on every clone.
// Client validation via the shared save.js helpers (validateFormFields / handleAjaxErrors).
// Reads from window.LO_MODE, LO_INITIAL.
(function () {
    const mode = window.LO_MODE || 'create';
    const categorySlug = 'lamination';
    const prefillPath = 'lamination-prefill';
    const initial = window.LO_INITIAL || null;
    const resourceRoute = (initial && initial.resource_route) || 'laminationorders';
    const singleItemId = window.LO_ITEM_ID || null;

    const $form = $('#loForm');
    const $orderSelect = $('#orderFormSelect');
    const $rowsWrap = $('#rowsWrap');
    const $summary = $('#rowsSummary');
    const $orderMeta = $('#orderMeta');
    const $metaClient = $('#metaClient');
    const $metaJob = $('#metaJob');

    // Collect post_press ids selected in every row EXCEPT the given one, so a type
    // already chosen elsewhere is hidden from this row's dropdown.
    function selectedPpIdsExcept(currentRow) {
        const taken = new Set();
        $rowsWrap.find('.lo-row').each(function () {
            if (currentRow && this === currentRow) return;
            const v = $(this).find('.row-post-press').val();
            if (v) taken.add(String(v));
        });
        return taken;
    }

    // Full type list for this category, loaded once; rows render it on open + filter locally.
    let allTypes = [];

    // A row's options = all types minus those chosen in the other rows.
    function rowOptions(rowEl) {
        const taken = selectedPpIdsExcept(rowEl);
        return allTypes.filter(function (o) { return !taken.has(o.value); });
    }

    // (Re)initialise the searchable "type" select for one row; stores the instance on the element.
    function initRowSelect(rowEl) {
        const sel = rowEl.querySelector('.row-post-press');
        if (!sel) return null;
        const inst = erpSearchSelect(sel, {
            options: rowOptions(rowEl),
            placeholder: 'Select…',
        });
        rowEl._ppInst = inst;
        return inst;
    }

    // Only the last row shows "+"; earlier rows show "−". Also updates the summary.
    function refreshButtons() {
        const rows = $rowsWrap.find('.lo-row');
        rows.each(function (i) {
            const isLast = i === rows.length - 1;
            $(this).find('.row-add').toggle(isLast);
            $(this).find('.row-remove').toggle(!isLast);
        });
        $summary.text(rows.length + ' row' + (rows.length === 1 ? '' : 's'));
    }

    // ── jQuery Repeater (dynamic rows) ──
    $('#loRepeater').repeater({
        initEmpty: false,
        show: function () {
            const item = this;
            // Strip the dead erpSearchSelect cloned from the template row, then re-init fresh.
            cleanupErpSelect(item);
            $(item).find('.erp-field-error').remove();
            $(item).find('.border-red-500').removeClass('border-red-500');
            initRowSelect(item);
            $(item).show();
            refreshButtons();
        },
        hide: function (deleteElement) {
            if ($rowsWrap.find('.lo-row').length <= 1) return; // always keep one row
            cleanupErpSelect(this);
            $(this).slideUp(120, function () {
                deleteElement();
                refreshButtons();
            });
        },
    });

    // Prefill / edit rehydrate: build N rows then bind each row's type-select.
    function setRows(items) {
        const list = (items && items.length) ? items : [{}];
        const existing = $rowsWrap.find('.lo-row');
        const needed = list.length;

        // Remove excess rows; keep and clean those we need.
        for (let i = existing.length - 1; i >= 0; i--) {
            const row = existing[i];
            if (i < needed) {
                cleanupErpSelect(row);
                $(row).find('.row-size-1, .row-size-2, .row-qty').val('');
                $(row).find('.erp-field-error').remove();
                $(row).find('.border-red-500').removeClass('border-red-500');
                initRowSelect(row);
            } else {
                cleanupErpSelect(row);
                $(row).remove();
            }
        }

        // Add missing rows via the repeater create trigger (goes through `show`).
        const currentAfter = $rowsWrap.find('.lo-row').length;
        for (let i = currentAfter; i < needed; i++) {
            $('#loRepeater').find('[data-repeater-create]').first().trigger('click');
        }

        // Populate each row with the prefilled data.
        $rowsWrap.find('.lo-row').each(function (i) {
            const it = list[i] || {};
            if (it.size_1 != null) $(this).find('.row-size-1').val(it.size_1);
            if (it.size_2 != null) $(this).find('.row-size-2').val(it.size_2);
            if (it.quantity != null) $(this).find('.row-qty').val(it.quantity);
            const rowEl = this;
            let inst = rowEl._ppInst;
            if (!inst) {
                cleanupErpSelect(rowEl);
                inst = initRowSelect(rowEl);
            }
            if (inst && it.post_press_id) {
                const ppId = String(it.post_press_id);
                let opts = rowOptions(rowEl);
                if (!opts.some(function (o) { return o.value === ppId; })) {
                    opts = [{ value: ppId, label: it.name || '' }].concat(opts);
                }
                inst.setOptions(opts, true);
                inst.setValue(ppId, true);
            }
        });
        refreshButtons();
    }

    // Init the first (template) row's select — initEmpty rows don't pass through `show`.
    initRowSelect($rowsWrap.find('.lo-row')[0]);

    // ── Order Form searchable select ──
    // In create mode, hide order forms where all lamination types have already
    // been added, so only those with pending types still available appear.
    const orderExclude = mode === 'create' ? { exclude_with_all_lamination: 1 } : {};
    const orderInst = erpSearchSelect('#orderFormSelect', {
        options: [],
        placeholder: 'Select Order Form',
        freshPrefetch: { url: '/lookup/order-forms', limit: 300, extraData: orderExclude },
        onSearch: function (term, cb) {
            $.get('/lookup/order-forms', Object.assign({ q: term, limit: 50 }, orderExclude), function (data) {
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
            if (mode === 'edit') return;
            if (!val) return;
            $.get('/lookup/order-forms/' + val + '/' + prefillPath).done(function (rows) {
                setRows(rows || []);
            });
        },
    });

    if (mode === 'edit' && initial) {
        orderInst.setOptions([{ value: String(initial.order_form_id), label: initial.order_no + ' — ' + initial.job_name }], true);
        orderInst.setValue(String(initial.order_form_id), true);
        $metaClient.text(initial.client_name || '');
        $metaJob.text(initial.job_name || '');
        $orderMeta.show();
    }
    refreshButtons();

    // Load the full type list for this category once, then fill every row's dropdown
    // (and rehydrate edit rows). Rows show the list on open and search locally.
    $.get('/lookup/post-press', { category_slug: categorySlug, limit: 300 }, function (resp) {
        allTypes = (resp || []).map(function (o) { return { value: String(o.value), label: o.label }; });
        if (mode === 'edit' && initial) {
            setRows(initial.items || []);
        } else {
            $rowsWrap.find('.lo-row').each(function () {
                if (this._ppInst) this._ppInst.setOptions(rowOptions(this), true);
            });
        }
    });

    // Re-filter a row's options (hide types chosen in other rows) just before its dropdown opens.
    $rowsWrap.on('mousedown', '.erp-select-trigger', function () {
        const rowEl = $(this).closest('.lo-row')[0];
        if (rowEl && rowEl._ppInst && rowEl._ppInst.setOptions) {
            rowEl._ppInst.setOptions(rowOptions(rowEl), true);
        }
    });

    // Per-row "+" → trigger the repeater's hidden create button (adds a blank row).
    $rowsWrap.on('click', '.row-add', function () {
        $('#loRepeater').find('[data-repeater-create]').first().trigger('click');
    });

    // ── Submit: client-validate (shared helper) → AJAX ──
    $('#loSubmit').on('click', function () {
        const $btn = $(this);

        // Validates every [required] field (order select + each row's type/size/qty),
        // rendering inline border + message via save.js.
        if (typeof validateFormFields === 'function') {
            const errs = validateFormFields($form, mode === 'edit');
            if (errs.length) { if (typeof setButtonError === 'function') setButtonError($btn); return; }
        }

        const items = [];
        $rowsWrap.find('.lo-row').each(function () {
            const pp = $(this).find('.row-post-press').val();
            const s1 = $(this).find('.row-size-1').val();
            const s2 = $(this).find('.row-size-2').val();
            const qty = $(this).find('.row-qty').val();
            // Ignore a fully-blank trailing row.
            if (!pp && s1 === '' && s2 === '' && qty === '') return;
            items.push({
                post_press_id: parseInt(pp || 0, 10),
                size_1: s1 === '' ? null : s1,
                size_2: s2 === '' ? null : s2,
                quantity: parseInt(qty || 0, 10),
            });
        });

        if (typeof setButtonLoading === 'function') setButtonLoading($btn);

        if (singleItemId) {
            const row = items[0] || {};
            const payload = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                post_press_id: row.post_press_id,
                size_1: row.size_1,
                size_2: row.size_2,
                quantity: row.quantity,
            };
            $.ajax({
                url: '/' + resourceRoute + '-items/' + singleItemId,
                method: 'PUT',
                data: payload,
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
        } else {
            const isEdit = mode === 'edit';
            const url = isEdit ? '/' + resourceRoute + '/' + initial.id : '/' + resourceRoute;
            const method = isEdit ? 'PUT' : 'POST';

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
        }
    });
})();
