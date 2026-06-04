/**
 * Status Change with Remarks Support
 *
 * Replaces the bare status.js pattern for modules that need
 * remarks on status changes.
 *
 * Usage:
 * - Add class `.change-status-with-remarks` to status buttons
 * - Buttons need data-id, data-status, data-route attributes
 * - Include the #globalStatusChangeModal partial on the page
 */

function _statusModalShow() {
    var modal = $('#globalStatusChangeModal');
    if (typeof isTailwindTheme === 'function' && isTailwindTheme()) {
        modal.removeClass('hidden');
    } else {
        modal.modal('show');
    }
}

function _statusModalHide() {
    var modal = $('#globalStatusChangeModal');
    if (typeof isTailwindTheme === 'function' && isTailwindTheme()) {
        modal.addClass('hidden');
    } else {
        modal.modal('hide');
    }
}

function _statusToast(type, message, title) {
    if (typeof erpToast === 'function') {
        erpToast({ title: title || type.charAt(0).toUpperCase() + type.slice(1), message: message, type: type });
    } else if (typeof toastr !== 'undefined') {
        toastr[type](message, title);
    }
}

function _initStatusValidation() {
    var form = $('#globalStatusChangeForm');
    var userRemarkField = $('#global_status_user_remark');
    var minLength = parseInt(userRemarkField.attr('data-min-length')) || 3;
    var maxLength = parseInt(userRemarkField.attr('data-max-length')) || 1000;
    var validationMessages = window.validationMessages || {};
    var isTw = typeof isTailwindTheme === 'function' && isTailwindTheme();

    if (form.data('validator')) {
        form.validate().destroy();
    }

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
                required:
                    validationMessages.user_remark_required || 'Please provide a reason for this status change',
                minlength:
                    validationMessages.user_remark_min || 'Reason must be at least ' + minLength + ' characters',
                maxlength:
                    validationMessages.user_remark_max || 'Reason must not exceed ' + maxLength + ' characters',
            },
        },
        errorElement: 'span',
        errorClass: isTw ? 'mt-1 text-sm text-red-500' : 'invalid-feedback d-block',
        errorPlacement: function (error, element) {
            if (element.closest('.input-group').length) {
                error.insertAfter(element.closest('.input-group'));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            if (isTw) {
                $(element).addClass('border-red-500').removeClass('border-zinc-200');
            } else {
                $(element).addClass('is-invalid').removeClass('is-valid');
            }
        },
        unhighlight: function (element) {
            if (isTw) {
                $(element).removeClass('border-red-500').addClass('border-zinc-200');
            } else {
                $(element).removeClass('is-invalid').addClass('is-valid');
            }
        },
    });
}

// Trigger status change modal on .change-status-with-remarks button click
$(document).on('click', '.change-status-with-remarks', function (e) {
    e.preventDefault();

    // Check if already clicked to prevent double-click
    if ($(this).attr('data-clicked') === 'true') {
        return false;
    }

    // Mark as clicked
    $(this).attr('data-clicked', 'true');

    var id = $(this).data('id');
    var status = $(this).data('status');
    var route = $(this).data('route');
    var statusButton = $(this);

    // Store context in modal
    var modal = $('#globalStatusChangeModal');
    modal.data('status-id', id);
    modal.data('status-value', status);
    modal.data('status-route', route);
    modal.data('status-button', statusButton);

    // Clear previous form data
    $('#global_status_user_remark').val('');
    $('#globalStatusChangeErrors').hide();
    $('#globalStatusChangeErrorList').empty();

    // Show modal and init validation
    _statusModalShow();
    _initStatusValidation();
});

// Also init validation on Bootstrap modal show (for old theme)
$(document).on('shown.bs.modal', '#globalStatusChangeModal', function () {
    _initStatusValidation();
});

// Handle status change form submission
$(document).on('submit', '#globalStatusChangeForm', function (e) {
    e.preventDefault();

    var form = $(this);

    // Check if form is valid before proceeding
    if (!form.valid()) {
        return false;
    }

    var modal = $('#globalStatusChangeModal');
    var id = modal.data('status-id');
    var status = modal.data('status-value');
    var route = modal.data('status-route');
    var submitBtn = $('#globalStatusChangeSubmitBtn');

    // Prevent double submission
    if (submitBtn.prop('disabled')) {
        return false;
    }

    // Get form data
    var formData = {
        id: id,
        status: status,
        user_remark: $('#global_status_user_remark').val(),
        _token: $('meta[name="csrf-token"]').attr('content'),
    };

    // Disable submit button
    if (typeof setButtonLoading === 'function') {
        setButtonLoading(submitBtn);
    } else {
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Processing...');
    }

    $.ajax({
        url: route,
        type: 'POST',
        data: formData,
        success: function (response) {
            if (response.status_code == 200) {
                _statusToast('success', response.message, 'Success');

                // Reload DataTable if it exists
                if (typeof table !== 'undefined' && $.fn.DataTable.isDataTable('#table')) {
                    table.ajax.reload(null, false);
                } else {
                    window.location.reload();
                }

                _statusModalHide();
            } else if (response.status_code == 201) {
                _statusToast('warning', response.message, 'Warning');
            } else {
                _statusToast('error', response.message, 'Error');
            }

            if (typeof resetButtonLoading === 'function') {
                resetButtonLoading(submitBtn);
            } else {
                submitBtn.prop('disabled', false);
            }
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#globalStatusChangeErrorList');
                errorList.empty();

                $.each(errors, function (key, messages) {
                    $.each(messages, function (index, message) {
                        errorList.append('<li>' + message + '</li>');
                    });
                });

                $('#globalStatusChangeErrors').show();
            } else {
                _statusToast('error', 'Something went wrong. Please try again.', 'Error');
            }

            if (typeof setButtonError === 'function') {
                setButtonError(submitBtn);
            } else if (typeof resetButtonLoading === 'function') {
                resetButtonLoading(submitBtn);
            } else {
                submitBtn.prop('disabled', false);
            }
        },
    });
});

// Close modal via .status-modal-close buttons/backdrop (Tailwind theme)
$(document).on('click', '.status-modal-close', function () {
    _resetStatusModal();
    _statusModalHide();
});

// Reset modal on close — Bootstrap theme
$(document).on('hidden.bs.modal', '#globalStatusChangeModal', function () {
    _resetStatusModal();
});

function _resetStatusModal() {
    var modal = $('#globalStatusChangeModal');
    var form = $('#globalStatusChangeForm');
    var statusButton = modal.data('status-button');

    modal.removeData('status-id');
    modal.removeData('status-value');
    modal.removeData('status-route');
    modal.removeData('status-button');

    if (statusButton) {
        statusButton.removeAttr('data-clicked');
    }

    form.find('.flatpickr-datetime').each(function () {
        if (this._flatpickr) {
            this._flatpickr.destroy();
        }
    });

    form[0].reset();
    $('#globalStatusChangeErrors').hide();
    $('#globalStatusChangeErrorList').empty();

    // Remove validation classes — both themes
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.is-valid').removeClass('is-valid');
    form.find('.border-red-500').removeClass('border-red-500').addClass('border-zinc-200');

    if (form.data('validator')) {
        form.validate().destroy();
    }
}
