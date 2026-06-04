'use strict';

(function () {
    const mode = window.PDF_MODE || 'create';
    const initial = window.PDF_INITIAL || null;

    const $orderSelect = $('#orderFormSelect');
    const $blocks = $('#machineBlocks');
    const $orderMeta = $('#orderMeta');
    const $metaClient = $('#metaClient');
    const $metaJob = $('#metaJob');
    const $summary = $('#rowsSummary');
    const template = document.getElementById('blockTemplate');

    function buildBlock(row, totalMachines, index) {
        const node = template.content.firstElementChild.cloneNode(true);
        // Show the machine context for every machine-sourced row so the dynamic
        // load is visible — even when the order has a single machine. When there
        // are several, add a "#i of N" badge and a bordered card (theme parity).
        if (row.machine_name) {
            node.querySelector('.machine-header').classList.remove('hidden');
            node.querySelector('.machine-label').textContent = row.machine_name;
            if (totalMachines > 1) {
                const idxEl = node.querySelector('.machine-idx');
                if (idxEl) {
                    idxEl.textContent = '#' + (index + 1) + ' of ' + totalMachines;
                    idxEl.classList.remove('hidden');
                }
                node.classList.add('rounded-md', 'border', 'border-zinc-200');
                node.style.backgroundColor = 'rgba(250,250,250,.4)';
            }
        }
        node.querySelector('.machine-id').value = row.machine_id || '';
        if (row.no_of_job != null) node.querySelector('.field-no-of-job').value = row.no_of_job;
        if (row.plates != null) node.querySelector('.field-plates').value = row.plates;
        if (row.extra_plate_client != null) node.querySelector('.field-extra-client').value = row.extra_plate_client;
        if (row.extra_plate_vinayak != null) node.querySelector('.field-extra-vinayak').value = row.extra_plate_vinayak;
        if (row.screen) node.querySelector('.field-screen').value = row.screen;
        recompute(node);
        return node;
    }

    function recompute(node) {
        const p = parseInt(node.querySelector('.field-plates').value || 0, 10);
        const ec = parseInt(node.querySelector('.field-extra-client').value || 0, 10);
        const ev = parseInt(node.querySelector('.field-extra-vinayak').value || 0, 10);
        node.querySelector('.field-total').value = (p + ec + ev) || '';
    }

    function refreshSummary() {
        const n = $blocks.find('.pdf-block').length;
        $summary.text(n + ' machine' + (n === 1 ? '' : 's'));
    }

    function setBlocks(rows) {
        $blocks.empty();
        rows.forEach((r, i) => $blocks.append(buildBlock(r, rows.length, i)));
        refreshSummary();
    }

    // Default blank block (shown before any order is picked) — matches client theme.
    function renderBlank() {
        setBlocks([{}]);
    }

    // Order Form searchable select (AJAX via erpSearchSelect)
    const orderInst = erpSearchSelect('#orderFormSelect', {
        options: [],
        placeholder: 'Select Order Form',
        // In create mode, hide orders that already have a Plate Detail Form.
        freshPrefetch: { url: '/lookup/order-forms', limit: 300, extraData: { exclude_with_plate: mode === 'create' ? 1 : 0 } },
        onSearch: function (term, cb) {
            $.get('/lookup/order-forms', { q: term, limit: 50, exclude_with_plate: mode === 'create' ? 1 : 0 }, function (data) {
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
            if (mode === 'edit') return;
            if (!val) return;
            $.get('/lookup/order-forms/' + val + '/plate-detail-prefill').done(function (rows) {
                if (rows && rows.length) {
                    setBlocks(rows);
                } else {
                    renderBlank();
                }
            });
        },
    });

    if (mode === 'edit' && initial) {
        orderInst.setOptions([{ value: String(initial.order_form_id), label: initial.order_no + ' — ' + initial.job_name }], true);
        orderInst.setValue(String(initial.order_form_id), true);
        $metaClient.text(initial.client_name || '');
        $metaJob.text(initial.job_name || '');
        $orderMeta.show();
        setBlocks(initial.items || []);
    } else {
        // Create mode: show a single blank block by default.
        renderBlank();
    }

    $blocks.on('input', '.field-plates, .field-extra-client, .field-extra-vinayak', function () {
        recompute($(this).closest('.pdf-block')[0]);
    });

    $('#pdfSubmit').on('click', function () {
        $('#error_order_form_id').text('');
        $blocks.find('.erp-invalid').removeClass('erp-invalid');

        const orderFormId = $orderSelect.val();
        let invalid = false;
        if (!orderFormId) { $('#error_order_form_id').text('Please select an Order Form'); invalid = true; }

        const items = [];
        $blocks.find('.pdf-block').each(function () {
            const $job = $(this).find('.field-no-of-job');
            const $plates = $(this).find('.field-plates');
            const job = $job.val(), plates = $plates.val();
            if (job === '') { $job.addClass('erp-invalid'); invalid = true; }
            if (plates === '') { $plates.addClass('erp-invalid'); invalid = true; }
            items.push({
                machine_id: parseInt($(this).find('.machine-id').val() || 0, 10),
                no_of_job: parseInt(job || 0, 10),
                plates: parseInt(plates || 0, 10),
                extra_plate_client: parseInt($(this).find('.field-extra-client').val() || 0, 10),
                extra_plate_vinayak: parseInt($(this).find('.field-extra-vinayak').val() || 0, 10),
                screen: $(this).find('.field-screen').val() || null,
            });
        });
        if (!items.length && !invalid) { if (typeof erpToast === 'function') erpToast({ type: 'error', title: 'Error', message: 'Add at least one row' }); return; }
        if (invalid) { if (typeof erpToast === 'function') erpToast({ type: 'error', title: 'Validation', message: 'Please fill all required fields (highlighted).' }); return; }

        const isEdit = mode === 'edit';
        const url = isEdit ? '/platedetailforms/' + initial.id : '/platedetailforms';
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: { _token: $('meta[name="csrf-token"]').attr('content'), order_form_id: orderFormId, items },
        }).done(function (resp) {
            if (resp.status_code === 200) {
                if (typeof erpToast === 'function') {
                    erpToast({ type: 'success', title: 'Success', message: resp.message });
                }
                setTimeout(function () { window.location.href = resp.data; }, 800);
            } else {
                if (typeof erpToast === 'function') {
                    erpToast({ type: 'error', title: 'Error', message: resp.message || 'Save failed' });
                }
            }
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors || {};
                Object.keys(errors).forEach(function (field) {
                    const msg = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                    const el = document.getElementById('error_' + field);
                    if (el) el.textContent = msg;
                    else if (typeof erpToast === 'function') erpToast({ type: 'error', title: 'Error', message: msg });
                });
            } else if (typeof erpToast === 'function') {
                erpToast({ type: 'error', title: 'Error', message: 'Save failed' });
            }
        });
    });
})();
