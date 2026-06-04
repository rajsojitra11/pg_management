/**
 * Print Label with Remarks + New Tab + Auto-Refresh
 *
 * Usage:
 * - Add class `.print-label-action` to print buttons
 * - Buttons need data-url attribute (the print label URL)
 * - Include the #globalPrintLabelModal partial on the page
 */

// Trigger print label modal on .print-label-action button click
$(document).on('click', '.print-label-action', function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Check if already clicked to prevent double-click
    if ($(this).attr('data-clicked') === 'true') {
        return false;
    }

    // Check if modal is already shown to prevent double trigger
    if ($('#globalPrintLabelModal').hasClass('show')) {
        return false;
    }

    // Mark as clicked
    $(this).attr('data-clicked', 'true');

    var printUrl = $(this).data('url');
    var printButton = $(this);

    // Store context in modal
    var modal = $('#globalPrintLabelModal');
    modal.data('print-url', printUrl);
    modal.data('print-button', printButton);

    // Set form action
    $('#globalPrintLabelForm').attr('action', printUrl);

    // Clear previous form data
    $('#global_print_user_remark').val('');
    $('#globalPrintLabelErrors').hide();
    $('#globalPrintLabelErrorList').empty();

    // Show modal
    modal.modal('show');
});

// Initialize jQuery validation when modal is shown
$(document).on('shown.bs.modal', '#globalPrintLabelModal', function () {
    var form = $('#globalPrintLabelForm');

    // Destroy existing validation if any
    if (form.data('validator')) {
        form.validate().destroy();
    }

    // Get validation values from data attributes
    var remarkField = $('#global_print_user_remark');
    var minLength = parseInt(remarkField.attr('data-min-length')) || 3;
    var maxLength = parseInt(remarkField.attr('data-max-length')) || 1000;

    // Initialize fresh validation
    form.validate({
        rules: {
            user_remark: {
                required: true,
                minlength: minLength,
                maxlength: maxLength,
            },
        },
        messages: {
            user_remark: {
                required: 'Please enter a reason for printing',
                minlength: 'Reason must be at least ' + minLength + ' characters',
                maxlength: 'Reason must not exceed ' + maxLength + ' characters',
            },
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        errorPlacement: function (error, element) {
            if (element.closest('.input-group').length) {
                error.insertAfter(element.closest('.input-group'));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
    });
});

// Handle print label form submission
$(document).on('submit', '#globalPrintLabelForm', function (e) {
    var form = $(this);

    // Check if form is valid before proceeding
    if (!form.valid()) {
        e.preventDefault();
        return false;
    }

    var modal = $('#globalPrintLabelModal');
    var submitBtn = $('#globalPrintLabelSubmitBtn');

    // Disable submit button to prevent double-click
    submitBtn.prop('disabled', true);
    var originalText = submitBtn.html();
    submitBtn.html('<i class="ri-loader-4-line me-1"></i> Printing...');

    // Allow the native form submit to proceed (target="_blank" opens new tab)
    // After a short delay, refresh the DataTable or page
    setTimeout(function () {
        // Close modal
        modal.modal('hide');

        // Re-enable submit button
        submitBtn.prop('disabled', false);
        submitBtn.html(originalText);

        // Reload DataTable if it exists, otherwise reload page
        if (typeof table !== 'undefined' && $.fn.DataTable.isDataTable('#table')) {
            table.ajax.reload(null, false);
        } else {
            window.location.reload();
        }
    }, 1000);
});

// Reset modal on close
$(document).on('hidden.bs.modal', '#globalPrintLabelModal', function () {
    var modal = $(this);
    var form = $('#globalPrintLabelForm');
    var printButton = modal.data('print-button');

    modal.removeData('print-url');
    modal.removeData('print-button');

    // Reset clicked flag on print button
    if (printButton) {
        printButton.removeAttr('data-clicked');
    }

    // Destroy flatpickr instances before form reset
    form.find('.flatpickr-datetime').each(function () {
        if (this._flatpickr) {
            this._flatpickr.destroy();
        }
    });

    // Clear form
    form[0].reset();
    $('#globalPrintLabelErrors').hide();
    $('#globalPrintLabelErrorList').empty();

    // Remove validation classes
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.is-valid').removeClass('is-valid');

    // Destroy validation instance
    if (form.data('validator')) {
        form.validate().destroy();
    }
});
