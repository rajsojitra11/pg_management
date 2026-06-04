/**
 * Formulation Cascade — Product/RawMaterial → Child Units
 *
 * Usage: FormulationCascade.init({ ... })
 */
(function() {
    'use strict';

    var cfg = {};

    window.FormulationCascade = {

        init: function(config) {
            cfg = config;

            // Product change → load child units for main product
            $(document).on('change', cfg.productSelector || '#fm_product_id', function() {
                FormulationCascade.loadChildUnits('Product', $(this).val(), cfg.mainUnitSelector || '#fm_unit_id_main');
            });

            // Raw material change (repeater row) → load child units
            $(document).on('change', '.raw-material-select', function() {
                var row = $(this).closest('tr');
                FormulationCascade.loadChildUnits('Raw Material', $(this).val(), row.find('.raw-unit'));
            });
        },

        loadChildUnits: function(type, materialId, targetSelector) {
            if (!materialId) return;

            var $target = typeof targetSelector === 'string' ? $(targetSelector) : targetSelector;
            var lastSelected = $target.val();

            $.ajax({
                type: 'POST',
                url: cfg.routes.getChildUnit,
                dataType: 'json',
                data: {
                    _token: cfg.token,
                    type: type,
                    product_id: type === 'Product' ? materialId : undefined,
                    raw_material_id: type === 'Raw Material' ? materialId : undefined,
                },
                success: function(data) {
                    if (data.status_code == 200) {
                        $target.empty();
                        $.each(data.data, function(index, row) {
                            $target.append('<option value="' + row.id + '">' + row.name + '</option>');
                        });
                        if (lastSelected) $target.val(lastSelected);
                        $target.trigger('change');
                    }
                }
            });
        }
    };

})();
