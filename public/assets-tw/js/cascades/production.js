/**
 * Production Cascade — ProcessOrder → ProductionData + Yield Calculation
 *
 * Usage: ProductionCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};

    window.ProductionCascade = {

        init: function(config) {
            cfg = config;

            // ProcessOrder change → load production data
            $(document).on('change', cfg.processOrderSelector || '#processorder_id', function() {
                var selectBox = $(this);
                var processorder_id = selectBox.val();
                var selectedOption = selectBox.find(':selected');

                $('.after-ajax').addClass('hidden');
                $('.append-data').html('');

                if (processorder_id && processorder_id !== '0') {
                    var batch_qty = selectedOption.data('batch_qty');
                    $('#product_qty').val(batch_qty);
                    $('#product_qty_main').val(batch_qty);

                    var min_yield = selectedOption.data('min_yield_percentage');
                    var max_yield = selectedOption.data('max_yield_percentage');
                    $('#yield').attr('min', min_yield).attr('max', max_yield);
                    $('.yield-range').text(' (' + min_yield + '% - ' + max_yield + '%)');

                    var unit = selectedOption.data('unit');
                    $('#product_unit_main').val(unit);
                    $('.batch_qty_span').text(' (' + batch_qty + ' ' + unit + ')');

                    ProductionCascade.loadProductionData(processorder_id);
                }
            });

            // Yield ↔ Product Qty bidirectional calculation
            $(document).on('input', '#yield', function() {
                var yieldPer = $(this).val();
                var batch_qty = $('#product_qty').val();
                if (yieldPer && batch_qty) {
                    $('#product_qty_main').val(((yieldPer * batch_qty) / 100).toFixed(2));
                } else {
                    $('#product_qty_main').val('');
                }
            });

            $(document).on('input', '#product_qty_main', function() {
                var product_qty = $(this).val();
                var batch_qty = $('#product_qty').val();
                if (product_qty && batch_qty) {
                    $('#yield').val(((product_qty * 100) / batch_qty).toFixed(2));
                } else {
                    $('#yield').val('');
                }
            });

            // Yield validation on blur
            $(document).on('focusout', '#product_qty_main', function() {
                var yieldPer = parseFloat($('#yield').val());
                var min = parseFloat($('#yield').attr('min')) || 0;
                var max = parseFloat($('#yield').attr('max')) || 100;
                if (yieldPer < min || yieldPer > max) {
                    erpToast({ title: 'Invalid Yield', message: 'Yield must be between ' + min + '% and ' + max + '%', type: 'warning' });
                    $('#yield').val('0');
                    $('#product_qty_main').val('0').focus();
                }
            });
        },

        loadProductionData: function(processOrderId) {
            var $card = $('#form').closest('.rounded-lg');
            $card.css('opacity', '0.6').css('pointer-events', 'none');

            $.ajax({
                type: 'POST',
                url: cfg.routes.getProductionData,
                dataType: 'json',
                data: { _token: cfg.token, processorder_id: processOrderId },
                success: function(data) {
                    $card.css('opacity', '1').css('pointer-events', '');
                    if (data.status_code == 200) {
                        $('.after-ajax').removeClass('hidden');
                        $('.append-data').html(data.data);
                    } else if (data.status_code == 201) {
                        erpToast({ title: 'Warning', message: data.message, type: 'warning' });
                    } else {
                        erpToast({ title: 'Error', message: data.message, type: 'error' });
                    }
                },
                error: function() {
                    $card.css('opacity', '1').css('pointer-events', '');
                }
            });
        }
    };

})();
