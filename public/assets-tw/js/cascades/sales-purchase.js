/**
 * Sales/Purchase Cascade — Type (Product/RawMaterial) → Material → Unit/GST (repeater rows)
 *
 * Shared by SalesOrder and Purchase modules.
 * Usage: SalesPurchaseCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};

    window.SalesPurchaseCascade = {

        init: function(config) {
            cfg = config;

            // Type change → switch material list
            $(document).on('change', '.type-select', function() {
                var me = $(this);
                if (me.val() === 'Product') {
                    SalesPurchaseCascade.materialList(me, cfg.products);
                } else {
                    SalesPurchaseCascade.materialList(me, cfg.rawMaterials);
                }
            });

            // Material change → set unit/gst + duplicate check
            var rowSelector = cfg.rowSelector || '.clone_row';
            $(document).on('change', '.raw-material-select', function() {
                var me = $(this);
                if (me.val()) {
                    var unit = me.find(':selected').attr('data-unit');
                    var gst = me.find(':selected').attr('data-gst');
                    me.closest(rowSelector).find('.unit').text(unit || '0');
                    me.closest(rowSelector).find('.gst').text(gst || '0');

                    // Duplicate check
                    SalesPurchaseCascade.checkDuplicate(me);

                    var row = me.closest('tr');
                    if (typeof calculateTotal === 'function') calculateTotal(row);
                } else {
                    me.closest(rowSelector).find('.unit').text('0');
                    me.closest(rowSelector).find('.gst').text('0');
                    var row = me.closest('tr');
                    if (typeof calculateTotal === 'function') calculateTotal(row);
                }
            });
        },

        materialList: function(typeSelect, listData) {
            var rowSelector = cfg.rowSelector || '.clone_row';
            var rmSelect = typeSelect.closest(rowSelector).find('.raw-material-select');
            cleanupErpSelect(rmSelect.parent()[0]);
            rmSelect.empty();
            var ph = cfg.placeholders.select || 'Select...';
            rmSelect.append('<option value="">' + ph + '</option>');
            $.each(listData, function(index, row) {
                rmSelect.append('<option data-unit="' + row.unit + '" data-gst="' + row.gst + '" value="' + row.id + '">' + row.code + ' - ' + row.name + '</option>');
            });
            initErpSelect(rmSelect[0]);
        },

        checkDuplicate: function(selectEl) {
            var rowSelector = cfg.rowSelector || '.clone_row';
            var comboArr = [];
            var isDuplicate = false;
            $(rowSelector).each(function() {
                var type = $(this).find('.type-select').val();
                var value = $(this).find('.raw-material-select').val();
                var comboKey = type + '-' + value;
                if (comboKey !== '-' && comboKey !== 'Raw Material-' && comboKey !== 'Product-') {
                    if (comboArr.indexOf(comboKey) !== -1) {
                        isDuplicate = true;
                        return false;
                    }
                    comboArr.push(comboKey);
                }
            });
            if (isDuplicate) {
                erpToast({ title: 'Warning', message: 'Already Selected', type: 'warning' });
                selectEl.val('').trigger('change');
            }
        }
    };

})();
