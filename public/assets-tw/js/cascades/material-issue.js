/**
 * Material Issue Cascade — Type → Material (AJAX search) → Batch → Stock State
 *
 * Usage in Blade:
 *   <script src="{{ asset('assets-tw/js/cascades/material-issue.js') }}"></script>
 *   <script>
 *   MaterialIssueCascade.init({
 *       token: '{{ csrf_token() }}',
 *       routes: {
 *           getProducts: '{{ route("material-issue.get-products") }}',
 *           getRawMaterials: '{{ route("material-issue.get-rawmaterials") }}',
 *           getByProducts: '{{ route("material-issue.get-byproducts") }}',
 *           productBatches: '{{ url("material-issue/get-product-batches") }}',
 *           rmBatches: '{{ url("material-issue/get-rawmaterial-batches") }}',
 *           byproductBatches: '{{ url("material-issue/get-byproduct-batches") }}',
 *           indexUrl: '{{ route("material-issue.index") }}',
 *       },
 *       units: @json($units),
 *       stockStateLabels: { unrestricted_stock: '...', quarantine_stock: '...', ... },
 *       placeholders: { select: '...', searchProduct: '...', searchRM: '...', searchBP: '...' },
 *       itemTypes: [
 *           { value: 'Product', label: '...' },
 *           { value: 'Raw Material', label: '...' },
 *           { value: 'By Product', label: '...' },
 *       ],
 *       isEdit: false,
 *       tableBodySelector: '#items_body',
 *   });
 *   </script>
 */
(function() {
    'use strict';

    var cfg = {};
    var itemIndex = 0;
    var inputClass = 'w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';
    var selectClass = 'w-full h-9 rounded-md border border-zinc-200 bg-white px-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';

    window.MaterialIssueCascade = {

        init: function(config) {
            cfg = config;
            itemIndex = config.startIndex || 0;

            // Item type change → rebuild material/batch/state cells
            $(document).on('change', '.item-type-select', function() {
                var row = $(this).closest('tr');
                var itemType = $(this).val();
                var idx = row.data('index');
                MaterialIssueCascade.updateItemFields(row, itemType, idx);
            });

            // Remove row
            $(document).on('click', '.remove-item-row', function() {
                var $tbody = $(cfg.tableBodySelector || '#items_body');
                if ($tbody.find('tr').length > 1) {
                    $(this).closest('tr').remove();
                    MaterialIssueCascade.reindexRows();
                } else {
                    erpToast({ title: 'Warning', message: 'Cannot delete the last item', type: 'warning' });
                }
            });

            // Add row button
            $('#add_item_row').on('click', function() { MaterialIssueCascade.addItemRow(); });

            // Numeric input
            $(document).on('input', '.number', function(e) {
                e.target.value = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });

            // Reset button
            $(document).on('click', '.reset', function() {
                if (cfg.routes.indexUrl) window.location.href = cfg.routes.indexUrl;
            });

            // Add first row on create
            if (!cfg.isEdit) {
                this.addItemRow();
            }
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

            var html = '<tr data-index="' + idx + '">' +
                '<td class="border border-zinc-200 px-3 py-2 text-center text-sm text-zinc-700">' + ($(cfg.tableBodySelector || '#items_body').find('tr').length + 1) + '</td>' +
                '<td class="border border-zinc-200 px-3 py-2">' +
                    '<select class="' + selectClass + ' item-type-select" name="items[' + idx + '][item_type]">' + typeOptions + '</select>' +
                '</td>' +
                '<td class="border border-zinc-200 px-3 py-2 item-description-cell"><span class="text-sm text-zinc-500">Select item type first</span></td>' +
                '<td class="border border-zinc-200 px-3 py-2 batch-cell"><span class="text-sm text-zinc-500">-</span></td>' +
                '<td class="border border-zinc-200 px-3 py-2 stock-state-cell"><span class="text-sm text-zinc-500">-</span></td>' +
                '<td class="border border-zinc-200 px-3 py-2"><input type="text" class="' + inputClass + ' number" name="items[' + idx + '][issued_qty]" placeholder="0" min="0"></td>' +
                '<td class="border border-zinc-200 px-3 py-2"><select class="' + selectClass + ' unit-select" name="items[' + idx + '][unit_id]">' + unitOptions + '</select></td>' +
                '<td class="border border-zinc-200 px-3 py-2"><input type="text" class="' + inputClass + '" name="items[' + idx + '][item_remark]" placeholder="Remark"></td>' +
                '<td class="border border-zinc-200 px-3 py-2 text-center">' +
                    '<button type="button" class="py-1.5 px-2.5 rounded-md bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100 whitespace-nowrap inline-flex items-center remove-item-row">' +
                        '<i class="fa-solid fa-trash" style="font-size:10px;"></i>' +
                    '</button>' +
                '</td>' +
            '</tr>';

            $(cfg.tableBodySelector || '#items_body').append(html);
        },

        updateItemFields: function(row, itemType, idx) {
            var descCell = row.find('.item-description-cell');
            var batchCell = row.find('.batch-cell');
            var stateCell = row.find('.stock-state-cell');
            var ph = cfg.placeholders.select || 'Select...';

            var typeMap = {
                'Product': {
                    selectClass: 'product-select',
                    fieldName: 'product_id',
                    clearFields: ['rawmaterial_id', 'by_product_id'],
                    batchFieldName: 'product_stock_id',
                    clearBatchField: 'raw_material_stock_id',
                    ajaxUrl: cfg.routes.getProducts,
                    batchUrl: cfg.routes.productBatches,
                    placeholder: cfg.placeholders.searchProduct || 'Search Product...',
                },
                'Raw Material': {
                    selectClass: 'rawmaterial-select',
                    fieldName: 'rawmaterial_id',
                    clearFields: ['product_id', 'by_product_id'],
                    batchFieldName: 'raw_material_stock_id',
                    clearBatchField: 'product_stock_id',
                    ajaxUrl: cfg.routes.getRawMaterials,
                    batchUrl: cfg.routes.rmBatches,
                    placeholder: cfg.placeholders.searchRM || 'Search Raw Material...',
                },
                'By Product': {
                    selectClass: 'byproduct-select',
                    fieldName: 'by_product_id',
                    clearFields: ['product_id', 'rawmaterial_id'],
                    batchFieldName: 'raw_material_stock_id',
                    clearBatchField: 'product_stock_id',
                    ajaxUrl: cfg.routes.getByProducts,
                    batchUrl: cfg.routes.byproductBatches,
                    placeholder: cfg.placeholders.searchBP || 'Search By-Product...',
                }
            };

            var tmpl = typeMap[itemType];
            if (!tmpl) {
                descCell.html('<span class="text-sm text-zinc-500">Select item type first</span>');
                batchCell.html('<span class="text-sm text-zinc-500">-</span>');
                stateCell.html('<span class="text-sm text-zinc-500">-</span>');
                return;
            }

            // Build hidden fields for clearing
            var hiddenFields = '<input type="hidden" name="items[' + idx + '][item_description]" value="">';
            tmpl.clearFields.forEach(function(f) {
                hiddenFields += '<input type="hidden" name="items[' + idx + '][' + f + ']" value="">';
            });

            descCell.html(
                '<select class="' + selectClass + ' ' + tmpl.selectClass + '" name="items[' + idx + '][' + tmpl.fieldName + ']" data-idx="' + idx + '">' +
                    '<option value="">' + ph + '</option>' +
                '</select>' + hiddenFields
            );

            batchCell.html(
                '<select class="' + selectClass + ' batch-select" name="items[' + idx + '][' + tmpl.batchFieldName + ']" style="display:none;"><option value="">Select Batch</option></select>' +
                '<input type="hidden" name="items[' + idx + '][batch_no]" value="">' +
                '<input type="hidden" name="items[' + idx + '][' + tmpl.clearBatchField + ']" value="">'
            );

            stateCell.html('<select class="' + selectClass + ' stock-state-select" name="items[' + idx + '][stock_state]" style="display:none;"><option value="">Select State</option></select>');

            MaterialIssueCascade.initAjaxItemSelect(descCell.find('select'), tmpl.ajaxUrl, tmpl.placeholder, idx, row, tmpl.batchUrl);
        },

        initAjaxItemSelect: function(element, ajaxUrl, placeholder, idx, row, batchUrl) {
            var selectEl = element[0];
            var lastResults = [];

            erpSearchSelect(selectEl, {
                placeholder: placeholder,
                options: [],
                onSearch: function(term, callback) {
                    if (term.length < 1) { callback([]); return; }
                    $.get(ajaxUrl, { q: term }, function(data) {
                        lastResults = data;
                        callback(data.map(function(item) {
                            return { value: String(item.id), label: item.code + ' - ' + item.name };
                        }));
                    });
                },
                onChange: function(val) {
                    selectEl.value = val;
                    if (val) {
                        var selectedItem = null;
                        for (var i = 0; i < lastResults.length; i++) {
                            if (String(lastResults[i].id) === String(val)) { selectedItem = lastResults[i]; break; }
                        }
                        if (selectedItem) {
                            row.find('input[name="items[' + idx + '][item_description]"]').val(selectedItem.code + ' - ' + selectedItem.name);
                            if (selectedItem.unit_id) row.find('.unit-select').val(selectedItem.unit_id);
                        }
                        MaterialIssueCascade.loadBatches(batchUrl + '/' + val, row, idx);
                    }
                }
            });
        },

        loadBatches: function(url, row, idx) {
            $.get(url, function(batches) {
                var batchSelect = row.find('.batch-select');
                batchSelect.html('<option value="">Select Batch</option>');
                if (batches.length > 0) {
                    batches.forEach(function(b) {
                        var totalStock = parseFloat(b.unrestricted_stock || 0) + parseFloat(b.quarantine_stock || 0) + parseFloat(b.blocked_stock || 0) + parseFloat(b.rejected_stock || 0);
                        batchSelect.append('<option value="' + b.id + '" data-batch=\'' + JSON.stringify(b).replace(/'/g, '&#39;') + '\'>' + b.batch_no + ' (Total: ' + totalStock.toFixed(4) + ')</option>');
                    });
                    batchSelect.show();
                } else {
                    batchSelect.hide();
                }
                batchSelect.off('change').on('change', function() {
                    var selected = $(this).find(':selected');
                    var batchData = selected.data('batch');
                    row.find('input[name="items[' + idx + '][batch_no]"]').val(batchData ? batchData.batch_no : '');
                    MaterialIssueCascade.updateStockStateOptions(row, idx, batchData);
                });
            });
        },

        updateStockStateOptions: function(row, idx, batchData) {
            var stateSelect = row.find('.stock-state-select');
            stateSelect.html('<option value="">Select State</option>');
            if (batchData) {
                var states = ['unrestricted_stock', 'quarantine_stock', 'under_testing_stock', 'rejected_stock', 'sample_use_stock'];
                var labels = cfg.stockStateLabels || {};
                states.forEach(function(state) {
                    var qty = parseFloat(batchData[state] || 0);
                    if (qty > 0) {
                        stateSelect.append('<option value="' + state + '">' + (labels[state] || state) + ' (' + qty.toFixed(4) + ')</option>');
                    }
                });
                stateSelect.show();
            } else {
                stateSelect.hide();
            }
        },

        reindexRows: function() {
            $(cfg.tableBodySelector || '#items_body').find('tr').each(function(i) {
                $(this).find('td:first').text(i + 1);
            });
        }
    };

})();
