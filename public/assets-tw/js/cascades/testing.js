/**
 * Testing Cascade — Type → Material → Batch → Stock Info (modal form)
 *
 * Usage: TestingCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};

    window.TestingCascade = {

        init: function(config) {
            cfg = config;

            // Type change → load materials
            $(document).on('change', '.type-select', function() {
                $('#tst_c_stock, #tst_sample_qty, #tst_sample_stock, #tst_stock_type').html('');
                if ($(this).val() === 'Product') {
                    TestingCascade.materialList(cfg.products);
                } else {
                    TestingCascade.materialList(cfg.rawMaterials);
                }
            });

            // Material change → load batches
            $(document).on('change', '.raw-material-select', function() {
                $('#tst_c_stock, #tst_sample_qty, #tst_sample_stock, #tst_stock_type').html('');
                var material_id = $(this).val();
                var type = $('.type-select').val();
                if (material_id) {
                    TestingCascade.loadBatches(type, material_id);
                }
            });

            // Batch change → show stock info
            $(document).on('change', '.batch-select', function() {
                var selectedOption = $(this).find('option:selected');
                var quintain = selectedOption.data('quintain');
                var rejected = selectedOption.data('rejected');
                var sample_unit = $('.raw-material-select').find('option:selected').data('sample_unit');
                var sample_qty = $('.raw-material-select').find('option:selected').attr('data-sampleqty');

                $('#tst_sample_stock').html('Sample Qty : ');
                $('#tst_sample_qty').html(sample_qty + ' ' + sample_unit);

                if (quintain > 0) {
                    $('#tst_c_stock').html(quintain);
                    $('#tst_stock_type').html('Quintain Stock : ');
                }
                if (rejected > 0) {
                    $('#tst_c_stock').html(rejected);
                    $('#tst_stock_type').html('Rejected Stock : ');
                }
            });
        },

        materialList: function(listData) {
            var ph = cfg.placeholders.select || 'Select...';
            $('.raw-material-select').empty().append('<option value="">' + ph + '</option>');
            $.each(listData, function(index, row) {
                $('.raw-material-select').append(
                    '<option data-unit="' + row.unit + '" data-sampleqty="' + row.sample_qty + '" data-sample_unit="' + row.sample_unit + '" value="' + row.id + '">' + row.code + ' - ' + row.name + '</option>'
                );
            });
            $('.batch-select').empty().append('<option value="">' + ph + '</option>');
        },

        loadBatches: function(type, materialId) {
            var ph = cfg.placeholders.select || 'Select...';
            $.ajax({
                type: 'POST',
                url: cfg.routes.materialBatch,
                dataType: 'json',
                data: { type: type, material_id: materialId, _token: cfg.token },
                success: function(response) {
                    if (response.status_code == 200) {
                        $('.batch-select').empty().append('<option value="">' + ph + '</option>');
                        $.each(response.stockBatches, function(index, row) {
                            $('.batch-select').append(
                                '<option value="' + row.id + '" data-quintain="' + row.quarantine_stock + '" data-unrestricted="' + row.unrestricted_stock + '" data-undertesting="' + row.under_testing_stock + '" data-rejected="' + row.rejected_stock + '">' + row.batch_no + '</option>'
                            );
                        });
                    } else {
                        erpToast({ title: 'Warning', message: response.message, type: 'warning' });
                    }
                }
            });
        }
    };

})();
