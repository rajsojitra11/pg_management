/**
 * Sample Dispatch Cascade — ItemType → Item (stock-filtered) → Batch (AJAX)
 *
 * Usage: SampleDispatchCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};

    window.SampleDispatchCascade = {

        init: function(config) {
            cfg = config;

            // Item type change → populate item dropdown from pre-loaded data
            $(document).on('change', '.item-type', function() {
                var row = $(this).closest('tr');
                var type = $(this).val();
                var itemSelect = row.find('.item-select');
                var batchSelect = row.find('.batch-select');

                itemSelect.empty().append('<option value="">Select Item</option>');
                batchSelect.empty().append('<option value="">Select Batch</option>');
                row.find('.product-id-field').val('');
                row.find('.rm-id-field').val('');

                var data = type === 'Product' ? cfg.products : cfg.rawMaterials;
                $.each(data, function(i, item) {
                    if (parseFloat(item.unrestricted_stock) > 0) {
                        var unitName = item.unit ? item.unit.name : '';
                        itemSelect.append('<option value="' + item.id + '" data-unit="' + unitName + '">' + item.code + ' - ' + item.name + ' (' + parseFloat(item.unrestricted_stock).toFixed(2) + ' ' + unitName + ')</option>');
                    }
                });
            });

            // Item select change → load batches via AJAX
            $(document).on('change', '.item-select', function() {
                var row = $(this).closest('tr');
                var type = row.find('.item-type').val();
                var itemId = $(this).val();
                var batchSelect = row.find('.batch-select');

                batchSelect.empty().append('<option value="">Select Batch</option>');

                if (type === 'Product') {
                    row.find('.product-id-field').val(itemId);
                    row.find('.rm-id-field').val('');
                } else {
                    row.find('.rm-id-field').val(itemId);
                    row.find('.product-id-field').val('');
                }

                if (!itemId) return;

                var url = type === 'Product'
                    ? cfg.routes.productBatches + '/' + itemId
                    : cfg.routes.rmBatches + '/' + itemId;

                $.get(url, function(response) {
                    if (response && response.length > 0) {
                        $.each(response, function(i, batch) {
                            var stock = parseFloat(batch.unrestricted_stock);
                            if (stock > 0) {
                                batchSelect.append('<option value="' + batch.id + '" data-stock="' + stock + '">' + batch.batch_no + ' (' + stock.toFixed(4) + ')</option>');
                            }
                        });
                    }
                });
            });

            // Batch select change → set max qty
            $(document).on('change', '.batch-select', function() {
                var row = $(this).closest('tr');
                var stock = $(this).find(':selected').data('stock') || 0;
                row.find('.qty-input').attr('max', stock);
            });
        }
    };

})();
