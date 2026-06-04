/**
 * Allow Reprint with Remarks Support
 *
 * Usage:
 * - Add class `.allow-re-print` to reprint buttons
 * - Buttons need data-id, data-type, data-route attributes
 * - Include the #globalAllowReprintModal partial on the page
 */

// Trigger allow reprint modal on .allow-re-print button click
$(document).on('click', '.allow-re-print', function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Check if already clicked to prevent double-click
    if ($(this).attr('data-clicked') === 'true') {
        return false;
    }

    // Check if modal is already shown to prevent double trigger
    if ($('#globalAllowReprintModal').hasClass('show')) {
        return false;
    }

    // Mark as clicked
    $(this).attr('data-clicked', 'true');

    var id = $(this).data('id');
    var type = $(this).data('type');
    var route = $(this).attr('data-route');
    var reprintButton = $(this);

    // Store context in modal
    var modal = $('#globalAllowReprintModal');
    modal.data('reprint-id', id);
    modal.data('reprint-type', type);
    modal.data('reprint-route', route);
    modal.data('reprint-button', reprintButton);

    // Set hidden inputs
    $('#global_reprint_id').val(id);
    $('#global_reprint_type').val(type);

    // Clear previous form data
    $('#global_reprint_reason').val('');
    $('#globalAllowReprintErrors').hide();
    $('#globalAllowReprintErrorList').empty();

    // Show modal
    modal.modal('show');
});

// Initialize jQuery validation when modal is shown
$(document).on('shown.bs.modal', '#globalAllowReprintModal', function () {
    var form = $('#globalAllowReprintForm');

    // Get validation values from data attributes
    var reasonField = $('#global_reprint_reason');
    var minLength = parseInt(reasonField.attr('data-min-length')) || 3;
    var maxLength = parseInt(reasonField.attr('data-max-length')) || 1000;

    // Destroy existing validation if any
    if (form.data('validator')) {
        form.validate().destroy();
    }

    // Initialize fresh validation
    form.validate({
        rules: {
            reason: {
                required: true,
                minlength: minLength,
                maxlength: maxLength,
            },
        },
        messages: {
            reason: {
                required: 'Please provide a reason for allowing reprint',
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

// Handle allow reprint form submission
$(document).on('submit', '#globalAllowReprintForm', function (e) {
    e.preventDefault();

    var form = $(this);

    // Check if form is valid before proceeding
    if (!form.valid()) {
        return false;
    }

    var modal = $('#globalAllowReprintModal');
    var route = modal.data('reprint-route');
    var submitBtn = $('#globalAllowReprintSubmitBtn');

    // Prevent double submission
    if (submitBtn.prop('disabled')) {
        return false;
    }

    // Get form data
    var formData = {
        id: $('#global_reprint_id').val(),
        type: $('#global_reprint_type').val(),
        reason: $('#global_reprint_reason').val(),
        _token: $('meta[name="csrf-token"]').attr('content'),
    };

    // Disable submit button
    submitBtn.prop('disabled', true);
    var originalText = submitBtn.html();
    submitBtn.html('<i class="ri-loader-4-line me-1"></i> Processing...');

    $.ajax({
        url: route,
        type: 'POST',
        data: formData,
        success: function (response) {
            if (response.status_code == 200) {
                toastr.success(response.message, 'Success');

                // Close modal
                modal.modal('hide');

                // Reload DataTable if it exists, otherwise reload page
                if (typeof table !== 'undefined' && $.fn.DataTable.isDataTable('#table')) {
                    table.ajax.reload(null, false);
                } else {
                    window.location.reload();
                }
            } else if (response.status_code == 201) {
                toastr.warning(response.message, 'Warning');
            } else {
                toastr.error(response.message, 'Error');
            }

            // Re-enable submit button
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
        },
        error: function (xhr) {
            // Handle validation errors
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#globalAllowReprintErrorList');
                errorList.empty();

                $.each(errors, function (key, messages) {
                    $.each(messages, function (index, message) {
                        errorList.append('<li>' + message + '</li>');
                    });
                });

                $('#globalAllowReprintErrors').show();
            } else {
                toastr.error('Something went wrong. Please try again.', 'Error');
            }

            // Re-enable submit button
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
        },
    });
});

// Reset modal on close
$(document).on('hidden.bs.modal', '#globalAllowReprintModal', function () {
    var modal = $(this);
    var form = $('#globalAllowReprintForm');
    var reprintButton = modal.data('reprint-button');

    modal.removeData('reprint-id');
    modal.removeData('reprint-type');
    modal.removeData('reprint-route');
    modal.removeData('reprint-button');

    // Reset clicked flag on reprint button
    if (reprintButton) {
        reprintButton.removeAttr('data-clicked');
    }

    // Destroy flatpickr instances before form reset
    form.find('.flatpickr-datetime').each(function () {
        if (this._flatpickr) {
            this._flatpickr.destroy();
        }
    });

    // Clear form
    form[0].reset();
    $('#globalAllowReprintErrors').hide();
    $('#globalAllowReprintErrorList').empty();

    // Remove validation classes
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.is-valid').removeClass('is-valid');

    // Destroy validation instance
    if (form.data('validator')) {
        form.validate().destroy();
    }
});
