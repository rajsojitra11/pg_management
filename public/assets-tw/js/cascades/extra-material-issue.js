/**
 * Extra Material Issue Cascade — ProcessOrder (AJAX search) → DispenseRM → Batch
 *
 * Usage: ExtraMaterialIssueCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};
    var itemIndex = 0;
    var dispenseRawMaterials = [];
    var processOrderSearchInst = null;
    var inputClass = 'w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';
    var selectClass = 'w-full h-9 rounded-md border border-zinc-200 bg-white px-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';

    window.ExtraMaterialIssueCascade = {

        init: function(config) {
            cfg = config;
            itemIndex = config.startIndex || 0;

            this.initProcessOrderSelect();

            // Pre-load dispense RM for edit page
            if (cfg.preloadProcessOrderId) {
                $.get(cfg.routes.getDispenseRawMaterials + '/' + cfg.preloadProcessOrderId, function(data) {
                    dispenseRawMaterials = data;
                });
            }

            $('#add_item_row').on('click', function() { ExtraMaterialIssueCascade.addItemRow(); });

            $(document).on('click', '.remove-item-row', function() {
                var $tbody = $(cfg.tableBodySelector || '#items_body');
                if ($tbody.find('tr').length > 1) {
                    $(this).closest('tr').remove();
                    ExtraMaterialIssueCascade.reindexRows();
                } else {
                    erpToast({ title: 'Warning', message: 'Cannot delete the last item', type: 'warning' });
                }
            });

            $(document).on('click', '.reset', function() {
                if (cfg.routes.indexUrl) window.location.href = cfg.routes.indexUrl;
            });

            $(document).on('input', '.number', function(e) {
                e.target.value = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });

            // Intercept save/update click for item validation
            $(document).on('click', '.save, .update', function(e) {
                if (!ExtraMaterialIssueCascade.validateItems()) {
                    e.stopImmediatePropagation();
                    setButtonError($(this));
                    return false;
                }
            });
        },

        initProcessOrderSelect: function() {
            var el = document.getElementById(cfg.processOrderSelector ? cfg.processOrderSelector.replace('#', '') : 'emi_processorder_id');
            var preId = cfg.preselectedProcessOrder ? cfg.preselectedProcessOrder.id : null;
            var preLabel = cfg.preselectedProcessOrder ? cfg.preselectedProcessOrder.label : '';

            var initialOptions = [];
            if (preId) initialOptions.push({ value: String(preId), label: preLabel });

            processOrderSearchInst = erpSearchSelect(cfg.processOrderSelector || '#emi_processorder_id', {
                placeholder: cfg.placeholders.searchPO || 'Search Process Order...',
                options: initialOptions,
                onSearch: function(term, callback) {
                    if (term.length < 1) { callback([]); return; }
                    $.get(cfg.routes.getProcessOrders, { q: term }, function(data) {
                        callback(data.map(function(item) {
                            var productInfo = item.product ? ' (' + item.product.code + ' - ' + item.product.name + ')' : '';
                            return { value: String(item.id), label: item.processorder_number + productInfo };
                        }));
                    });
                },
                onChange: function(val) {
                    el.value = val;
                    if (val && val !== String(preId)) {
                        ExtraMaterialIssueCascade.loadDispenseRawMaterials(val);
                    } else if (!val) {
                        dispenseRawMaterials = [];
                        $(cfg.tableBodySelector || '#items_body').empty();
                    }
                }
            });

            if (preId) processOrderSearchInst.setValue(String(preId), true);  // silent — no cascade
        },

        loadDispenseRawMaterials: function(processorderId) {
            $.get(cfg.routes.getDispenseRawMaterials + '/' + processorderId, function(data) {
                dispenseRawMaterials = data;
                $(cfg.tableBodySelector || '#items_body').empty();
                ExtraMaterialIssueCascade.addItemRow();
            });
        },

        addItemRow: function() {
            var idx = itemIndex++;
            var ph = cfg.placeholders.select || 'Select...';
            var unitOptions = '<option value="">' + ph + '</option>';
            (cfg.units || []).forEach(function(u) {
                unitOptions += '<option value="' + u.id + '">' + u.name + '</option>';
            });

            var rmOptions = '<option value="">' + ph + '</option>';
            dispenseRawMaterials.forEach(function(drm) {
                if (drm.rawmaterial) {
                    rmOptions += '<option value="' + drm.raw_material_id + '" data-drm-id="' + drm.id + '" data-unit-id="' + (drm.rawmaterial.unit_id || '') + '">' + drm.rawmaterial.code + ' - ' + drm.rawmaterial.name + '</option>';
                }
            });

            var $tbody = $(cfg.tableBodySelector || '#items_body');
            var html = '<tr data-index="' + idx + '">' +
                '<td class="border border-zinc-200 px-3 py-2 text-center text-sm text-zinc-700">' + ($tbody.find('tr').length + 1) + '</td>' +
                '<td class="border border-zinc-200 px-3 py-2">' +
                    '<select class="' + selectClass + ' raw-material-select" name="items[' + idx + '][raw_material_id]" data-idx="' + idx + '">' + rmOptions + '</select>' +
                    '<input type="hidden" name="items[' + idx + '][dispenseorder_raw_material_id]" class="drm-id-input" value="">' +
                '</td>' +
                '<td class="border border-zinc-200 px-3 py-2 batch-cell">' +
                    '<select class="' + selectClass + ' batch-select" name="items[' + idx + '][raw_material_stock_id]" style="display:none;"><option value="">Select Batch</option></select>' +
                    '<input type="hidden" name="items[' + idx + '][batch_no]" class="batch-no-input" value="">' +
                '</td>' +
                '<td class="border border-zinc-200 px-3 py-2"><input type="text" class="' + inputClass + ' number" name="items[' + idx + '][issued_qty]" placeholder="0" min="0"></td>' +
                '<td class="border border-zinc-200 px-3 py-2"><select class="' + selectClass + ' unit-select" name="items[' + idx + '][unit_id]">' + unitOptions + '</select></td>' +
                '<td class="border border-zinc-200 px-3 py-2"><input type="text" class="' + inputClass + '" name="items[' + idx + '][item_remark]" placeholder="Remark"></td>' +
                '<td class="border border-zinc-200 px-3 py-2 text-center">' +
                    '<button type="button" class="py-1.5 px-2.5 rounded-md bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100 whitespace-nowrap inline-flex items-center remove-item-row">' +
                        '<i class="fa-solid fa-trash" style="font-size:10px;"></i>' +
                    '</button>' +
                '</td>' +
            '</tr>';

            $tbody.append(html);

            var row = $tbody.find('tr:last');
            row.find('.raw-material-select').on('change', function() {
                var rawMaterialId = $(this).val();
                var selectedOption = $(this).find(':selected');
                row.find('.drm-id-input').val(selectedOption.data('drm-id') || '');
                if (selectedOption.data('unit-id')) row.find('.unit-select').val(selectedOption.data('unit-id'));
                if (rawMaterialId) {
                    ExtraMaterialIssueCascade.loadBatches(cfg.routes.rmBatches + '/' + rawMaterialId, row, idx);
                } else {
                    row.find('.batch-select').html('<option value="">Select Batch</option>').hide();
                    row.find('.batch-no-input').val('');
                }
            });
        },

        loadBatches: function(url, row, idx) {
            $.get(url, function(batches) {
                var batchSelect = row.find('.batch-select');
                batchSelect.html('<option value="">Select Batch</option>');
                if (batches.length > 0) {
                    batches.forEach(function(b) {
                        batchSelect.append('<option value="' + b.id + '" data-batch-no="' + b.batch_no + '">' + b.batch_no + ' (Stock: ' + parseFloat(b.unrestricted_stock).toFixed(4) + ')</option>');
                    });
                    batchSelect.show();
                } else {
                    batchSelect.hide();
                }
                batchSelect.off('change').on('change', function() {
                    row.find('.batch-no-input').val($(this).find(':selected').data('batch-no') || '');
                });
            });
        },

        reindexRows: function() {
            $(cfg.tableBodySelector || '#items_body').find('tr').each(function(i) {
                $(this).find('td:first').text(i + 1);
            });
        },

        validateItems: function() {
            var $tbody = $(cfg.tableBodySelector || '#items_body');
            var rows = $tbody.find('tr');
            if (rows.length === 0) {
                erpToast({ title: 'Error', message: 'Please add at least one item.', type: 'error' });
                return false;
            }
            var valid = true;
            var anyItemFilled = false;
            rows.each(function() {
                var row = $(this);
                var rawMaterialId = row.find('[name$="[raw_material_id]"]').val();
                var qty = parseFloat(row.find('[name$="[issued_qty]"]').val()) || 0;
                if (!rawMaterialId) {
                    var rmSelect = row.find('.raw-material-select');
                    if (rmSelect.length) addFieldError(rmSelect, 'Raw material is required');
                    valid = false;
                }
                if (qty <= 0) {
                    addFieldError(row.find('[name$="[issued_qty]"]'), 'Quantity must be greater than 0');
                    valid = false;
                }
                if (rawMaterialId && qty > 0) anyItemFilled = true;
            });
            if (!anyItemFilled && valid) {
                erpToast({ title: 'Error', message: 'Please fill at least one item with quantity.', type: 'error' });
                return false;
            }
            if (!valid) erpToast({ title: 'Error', message: 'Please fix item errors before submitting.', type: 'error' });
            return valid;
        }
    };

})();
