/**
 * Gatepass Cascade — Dual mode: Dispatch Order items OR Manual (Type→Product/RM→Batch)
 *
 * Usage: GatepassCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};
    var itemIndex = 0;
    var isSalesDispatch = false;
    var inputClass = 'w-full h-9 rounded-md border px-3 text-sm focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';
    var selectClass = 'w-full h-9 rounded-md border px-2 text-sm focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';
    var inputStyle = 'border-color: var(--erp-border); background-color: var(--erp-bg); color: var(--erp-text);';

    window.GatepassCascade = {

        init: function(config) {
            cfg = config;
            itemIndex = config.startIndex || 0;
            isSalesDispatch = config.isSalesDispatch || false;

            // Item type change → rebuild description/batch cells
            $(document).on('change', '.gp-item-type-select', function() {
                var row = $(this).closest('tr');
                GatepassCascade.updateItemFields(row, $(this).val(), row.data('index'));
            });

            // Remove row
            $(document).on('click', '.gp-remove-item-row', function() {
                if ($('#gp_items_body tr').length > 1) {
                    $(this).closest('tr').remove();
                    GatepassCascade.reindexRows();
                } else {
                    erpConfirm({ title: 'Warning', message: 'Cannot delete the last item', type: 'warning', showCancelButton: false, confirmText: 'OK' });
                }
            });

            // Add row button
            $('#gp_add_item_row').on('click', function() { GatepassCascade.addItemRow(); });

            // Numeric input
            $(document).on('input', '.gp-number', function(e) {
                e.target.value = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });

            // Validation interception
            $(document).on('click', '.save, .update', function(e) {
                if (!GatepassCascade.validateItems()) {
                    e.stopImmediatePropagation();
                    setButtonError($(this));
                    return false;
                }
            });

            // Init Dispatch Order search
            this.initDispatchOrderSearchSelect();

            // Add first row on create
            if (!cfg.isEdit) {
                this.addItemRow();
            }

            // Load existing items on edit
            if (cfg.isEdit && cfg.existingItems) {
                cfg.existingItems.forEach(function(item) {
                    GatepassCascade.addItemRowWithData(item);
                });
            }
        },

        initDispatchOrderSearchSelect: function() {
            var el = document.getElementById('gp_dispatch_order_id');
            if (!el) return;

            var preId = cfg.preselectedDispatchOrder ? cfg.preselectedDispatchOrder.id : null;
            var preLabel = cfg.preselectedDispatchOrder ? cfg.preselectedDispatchOrder.label : '';

            // If DO-linked on edit, show as disabled static select
            if (cfg.isDoLinked && preId) {
                erpSearchSelect('#gp_dispatch_order_id', {
                    options: [{ value: String(preId), label: preLabel }],
                    placeholder: cfg.placeholders.selectDO || 'Select Dispatch Order',
                });
                var wrapper = el.nextElementSibling;
                if (wrapper && wrapper.classList.contains('erp-select-wrapper')) {
                    wrapper.style.pointerEvents = 'none';
                    wrapper.style.opacity = '0.6';
                }
                el.value = preId;
                // Load DO items
                GatepassCascade.loadDispatchOrderItems(preId);
                return;
            }

            var doSearchTimeout = null;
            erpSearchSelect('#gp_dispatch_order_id', {
                options: preId ? [{ value: String(preId), label: preLabel }] : [],
                placeholder: cfg.placeholders.selectDO || 'Select Dispatch Order',
                onSearch: function(term, callback) {
                    if (doSearchTimeout) clearTimeout(doSearchTimeout);
                    doSearchTimeout = setTimeout(function() {
                        var params = { q: term };
                        if (cfg.excludeId) params.exclude_id = cfg.excludeId;
                        $.get(cfg.routes.dispatchOrders, params, function(data) {
                            callback(data.map(function(item) {
                                var text = item.dispatch_number;
                                if (item.customer) text += ' (' + item.customer.customer_code + ' - ' + item.customer.name + ')';
                                return { value: String(item.id), label: text };
                            }));
                        });
                    }, 250);
                },
                onChange: function(val) {
                    el.value = val;
                    if (val) {
                        GatepassCascade.loadDispatchOrderItems(val);
                    } else {
                        $('#gp_do_items_section').hide();
                        $('#gp_do_items_body').html('');
                    }
                }
            });

            if (preId) {
                el.value = preId;
                GatepassCascade.loadDispatchOrderItems(preId);
            }
        },

        loadDispatchOrderItems: function(doId) {
            $.get(cfg.routes.dispatchOrderItems + '/' + doId, function(items) {
                var html = '';
                items.forEach(function(item, index) {
                    html += '<tr style="border-bottom: 1px solid var(--erp-border);">' +
                        '<td class="px-3 py-2 text-center">' + (index + 1) + '</td>' +
                        '<td class="px-3 py-2">' + item.item_type + '</td>' +
                        '<td class="px-3 py-2">' + item.item_description + '</td>' +
                        '<td class="px-3 py-2">' + parseFloat(item.quantity).toFixed(4) + '</td>' +
                        '<td class="px-3 py-2">' + (item.unit_name || 'N/A') + '</td>' +
                        '<td class="px-3 py-2">' + (item.batch_no || 'N/A') + '</td>' +
                        '<input type="hidden" name="items[' + index + '][dispatch_order_meta_id]" value="' + item.dispatch_order_meta_id + '">' +
                        '<input type="hidden" name="items[' + index + '][item_type]" value="' + item.item_type + '">' +
                        '<input type="hidden" name="items[' + index + '][product_id]" value="' + (item.product_id || '') + '">' +
                        '<input type="hidden" name="items[' + index + '][rawmaterial_id]" value="' + (item.rawmaterial_id || '') + '">' +
                        '<input type="hidden" name="items[' + index + '][product_stock_id]" value="' + (item.product_stock_id || '') + '">' +
                        '<input type="hidden" name="items[' + index + '][raw_material_stock_id]" value="' + (item.raw_material_stock_id || '') + '">' +
                        '<input type="hidden" name="items[' + index + '][item_description]" value="' + item.item_description + '">' +
                        '<input type="hidden" name="items[' + index + '][batch_no]" value="' + (item.batch_no || '') + '">' +
                        '<input type="hidden" name="items[' + index + '][quantity]" value="' + item.quantity + '">' +
                        '<input type="hidden" name="items[' + index + '][unit_id]" value="' + (item.unit_id || '') + '">' +
                        '</tr>';
                });
                $('#gp_do_items_body').html(html);
                $('#gp_do_items_section').show();
            });
        },

        addItemRow: function() {
            var idx = itemIndex++;
            var ph = cfg.placeholders.select || 'Select...';
            var unitOptions = '<option value="">' + ph + '</option>';
            (cfg.units || []).forEach(function(u) {
                unitOptions += '<option value="' + u.id + '">' + u.name + '</option>';
            });

            var typeOptions = '<option value="">' + ph + '</option>';
            (cfg.itemTypes || []).forEach(function(t) {
                typeOptions += '<option value="' + t.value + '">' + t.label + '</option>';
            });

            var html = '<tr data-index="' + idx + '" style="border-bottom: 1px solid var(--erp-border);">' +
                '<td class="px-3 py-2 text-center">' + ($('#gp_items_body tr').length + 1) + '</td>' +
                '<td class="px-3 py-2"><select class="' + selectClass + ' gp-item-type-select" name="items[' + idx + '][item_type]" style="' + inputStyle + '">' + typeOptions + '</select></td>' +
                '<td class="px-3 py-2 gp-item-description-cell"><input type="text" class="' + inputClass + '" name="items[' + idx + '][item_description]" style="' + inputStyle + '" placeholder="Description"></td>' +
                '<td class="px-3 py-2"><input type="text" class="' + inputClass + ' gp-number" name="items[' + idx + '][quantity]" style="' + inputStyle + '" placeholder="0" min="0"></td>' +
                '<td class="px-3 py-2"><select class="' + selectClass + ' gp-unit-select" name="items[' + idx + '][unit_id]" style="' + inputStyle + '">' + unitOptions + '</select></td>' +
                '<td class="px-3 py-2 gp-batch-cell"><input type="text" class="' + inputClass + '" name="items[' + idx + '][batch_no]" style="' + inputStyle + '" placeholder="Batch No"></td>' +
                '<td class="px-3 py-2"><input type="text" class="' + inputClass + '" name="items[' + idx + '][item_remark]" style="' + inputStyle + '" placeholder="Remark"></td>' +
                '<td class="px-3 py-2 text-center">' +
                    '<button type="button" class="py-1.5 px-2.5 rounded-md bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100 inline-flex items-center gp-remove-item-row">' +
                        '<i class="fa-solid fa-trash" style="font-size:10px;"></i>' +
                    '</button>' +
                '</td></tr>';

            $('#gp_items_body').append(html);
        },

        addItemRowWithData: function(item) {
            // For edit mode — creates row with pre-populated data
            this.addItemRow();
            var row = $('#gp_items_body tr:last');
            var idx = row.data('index');

            row.find('.gp-item-type-select').val(item.item_type);
            row.find('[name$="[quantity]"]').val(item.quantity);
            row.find('.gp-unit-select').val(item.unit_id);
            row.find('[name$="[item_remark]"]').val(item.item_remark || '');

            // Trigger type change to set up the description/batch cells
            if (item.item_type === 'Product' || item.item_type === 'Raw Material') {
                GatepassCascade.updateItemFields(row, item.item_type, idx);
                // After update, load batch data
                var batchUrl = item.item_type === 'Product'
                    ? cfg.routes.productBatches + '/' + item.product_id
                    : cfg.routes.rmBatches + '/' + item.rawmaterial_id;
                var fieldName = item.item_type === 'Product' ? 'product_stock_id' : 'raw_material_stock_id';
                GatepassCascade.loadBatches(row, batchUrl, fieldName, idx, item[fieldName]);
            } else {
                row.find('[name$="[item_description]"]').val(item.item_description);
                row.find('[name$="[batch_no]"]').val(item.batch_no || '');
            }
        },

        updateItemFields: function(row, itemType, idx) {
            var cell = row.find('.gp-item-description-cell');
            var batchCell = row.find('.gp-batch-cell');
            cleanupErpSelect(cell[0]);
            cleanupErpSelect(batchCell[0]);

            if (itemType === 'Product') {
                cell.html(
                    '<select class="' + selectClass + ' gp-product-select" name="items[' + idx + '][product_id]" data-idx="' + idx + '" style="' + inputStyle + '"><option value="">' + (cfg.placeholders.select || 'Select') + '</option></select>' +
                    '<input type="hidden" name="items[' + idx + '][item_description]" value="">' +
                    '<input type="hidden" name="items[' + idx + '][rawmaterial_id]" value="">'
                );
                batchCell.html('<input type="hidden" name="items[' + idx + '][product_stock_id]" value=""><input type="hidden" name="items[' + idx + '][batch_no]" value="">');
                GatepassCascade.initProductSearchSelect(cell.find('.gp-product-select'), idx);
            } else if (itemType === 'Raw Material') {
                cell.html(
                    '<select class="' + selectClass + ' gp-rawmaterial-select" name="items[' + idx + '][rawmaterial_id]" data-idx="' + idx + '" style="' + inputStyle + '"><option value="">' + (cfg.placeholders.select || 'Select') + '</option></select>' +
                    '<input type="hidden" name="items[' + idx + '][item_description]" value="">' +
                    '<input type="hidden" name="items[' + idx + '][product_id]" value="">'
                );
                batchCell.html('<input type="hidden" name="items[' + idx + '][raw_material_stock_id]" value=""><input type="hidden" name="items[' + idx + '][batch_no]" value="">');
                GatepassCascade.initRawmaterialSearchSelect(cell.find('.gp-rawmaterial-select'), idx);
            } else {
                cell.html(
                    '<input type="text" class="' + inputClass + '" name="items[' + idx + '][item_description]" style="' + inputStyle + '" placeholder="Description">' +
                    '<input type="hidden" name="items[' + idx + '][product_id]" value="">' +
                    '<input type="hidden" name="items[' + idx + '][rawmaterial_id]" value="">'
                );
                batchCell.html('<input type="text" class="' + inputClass + '" name="items[' + idx + '][batch_no]" style="' + inputStyle + '" placeholder="Batch No">');
            }
        },

        initProductSearchSelect: function(selectEl, idx) {
            var el = selectEl instanceof jQuery ? selectEl[0] : selectEl;
            var row = $(el).closest('tr');
            erpSearchSelect(el, {
                options: [],
                placeholder: cfg.placeholders.searchProduct || 'Search Product...',
                onSearch: function(term, callback) {
                    $.get(cfg.routes.products, { q: term }, function(data) {
                        callback(data.map(function(item) {
                            return { value: String(item.id), label: item.code + ' - ' + item.name, data: { unit_id: item.unit_id } };
                        }));
                    });
                },
                onChange: function(val, selectedItem) {
                    el.value = val;
                    if (!selectedItem) return;
                    row.find('input[name="items[' + idx + '][item_description]"]').val(selectedItem.label);
                    if (selectedItem.data && selectedItem.data.unit_id) row.find('.gp-unit-select').val(selectedItem.data.unit_id);
                    GatepassCascade.loadBatches(row, cfg.routes.productBatches + '/' + val, 'product_stock_id', idx);
                }
            });
        },

        initRawmaterialSearchSelect: function(selectEl, idx) {
            var el = selectEl instanceof jQuery ? selectEl[0] : selectEl;
            var row = $(el).closest('tr');
            erpSearchSelect(el, {
                options: [],
                placeholder: cfg.placeholders.searchRM || 'Search Raw Material...',
                onSearch: function(term, callback) {
                    $.get(cfg.routes.rawmaterials, { q: term }, function(data) {
                        callback(data.map(function(item) {
                            return { value: String(item.id), label: item.code + ' - ' + item.name, data: { unit_id: item.unit_id } };
                        }));
                    });
                },
                onChange: function(val, selectedItem) {
                    el.value = val;
                    if (!selectedItem) return;
                    row.find('input[name="items[' + idx + '][item_description]"]').val(selectedItem.label);
                    if (selectedItem.data && selectedItem.data.unit_id) row.find('.gp-unit-select').val(selectedItem.data.unit_id);
                    GatepassCascade.loadBatches(row, cfg.routes.rmBatches + '/' + val, 'raw_material_stock_id', idx);
                }
            });
        },

        loadBatches: function(row, url, fieldName, idx, preselectedId) {
            $.get(url, function(batches) {
                var batchCell = row.find('.gp-batch-cell');
                if (batches.length > 0) {
                    var batchOpts = batches.map(function(b) {
                        return { value: String(b.id), label: b.batch_no + ' (' + b.unrestricted_stock + ')' };
                    });
                    batchCell.html(
                        '<select class="' + selectClass + ' gp-batch-select" name="items[' + idx + '][' + fieldName + ']" style="' + inputStyle + '"><option value="">Select Batch</option></select>' +
                        '<input type="hidden" name="items[' + idx + '][batch_no]" value="">'
                    );
                    var batchEl = batchCell.find('.gp-batch-select')[0];
                    var inst = erpSearchSelect(batchEl, {
                        options: batchOpts,
                        placeholder: 'Select Batch',
                        onChange: function(val) { batchEl.value = val; }
                    });
                    if (preselectedId && inst) {
                        inst.setValue(String(preselectedId), true);  // silent — no cascade
                        batchEl.value = preselectedId;
                    }
                } else {
                    batchCell.html('<input type="hidden" name="items[' + idx + '][' + fieldName + ']" value=""><input type="hidden" name="items[' + idx + '][batch_no]" value="">');
                }
            });
        },

        reindexRows: function() {
            $('#gp_items_body tr').each(function(i) { $(this).find('td:first').text(i + 1); });
        },

        validateItems: function() {
            var isDoVisible = $('#gp_do_items_section').is(':visible');
            if (isDoVisible) {
                if ($('#gp_do_items_body tr').length === 0) {
                    erpToast({ title: 'Error', message: 'Please select a Dispatch Order with items.', type: 'error' });
                    return false;
                }
                return true;
            }
            var rows = $('#gp_items_body tr');
            if (rows.length === 0) {
                erpToast({ title: 'Error', message: 'Please add at least one item.', type: 'error' });
                return false;
            }
            var valid = true, anyFilled = false;
            rows.each(function() {
                var row = $(this);
                var itemType = row.find('.gp-item-type-select').val();
                var qty = parseFloat(row.find('[name$="[quantity]"]').val()) || 0;
                if (!itemType) { addFieldError(row.find('.gp-item-type-select'), 'Item type is required'); valid = false; }
                if (qty <= 0) { addFieldError(row.find('[name$="[quantity]"]'), 'Quantity > 0 required'); valid = false; }
                if (itemType && qty > 0) anyFilled = true;
            });
            if (!anyFilled && valid) { erpToast({ title: 'Error', message: 'Please fill at least one item.', type: 'error' }); return false; }
            if (!valid) erpToast({ title: 'Error', message: 'Please fix item errors.', type: 'error' });
            return valid;
        }
    };

})();
