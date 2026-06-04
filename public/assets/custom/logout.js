/**
 * Logout Modal Handler
 * Handles logout confirmation modal.
 * Uses Bootstrap modal shim for consistency with delete modal.
 */

// Handle logout form submission
$(document).on('submit', '#globalLogoutForm', function (e) {
    e.preventDefault();

    var form = $(this);
    var modal = $('#globalLogoutModal');
    var submitBtn = $('#globalLogoutSubmitBtn');

    // Get form data
    var formData = {
        _token: $('input[name="_token"]', form).val(),
    };

    // Disable submit button
    submitBtn.prop('disabled', true);
    var originalText = submitBtn.html();
    submitBtn.html('<i class="ri-loader-4-line me-1"></i> Logging out...');

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        success: function (response) {
            // Close modal
            modal.modal('hide');

            // Show success message if available
            if (response.message || response.success) {
                toastr.success(response.message || response.success, 'Success');
            }

            // Redirect to login page
            setTimeout(function () {
                window.location.href = response.redirect || '/login';
            }, 500);
        },
        error: function (xhr) {
            console.error('Logout error:', xhr.status, xhr.responseText);

            // Handle validation errors
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#globalLogoutErrorList');
                errorList.empty();

                $.each(errors, function (key, messages) {
                    $.each(messages, function (index, message) {
                        errorList.append('<li>' + message + '</li>');
                    });
                });

                $('#globalLogoutErrors').show();
            } else {
                toastr.error('Logout failed. Please try again.', 'Error');
            }

            // Re-enable submit button
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
        },
    });
});

// Reset modal on close
$(document).on('hidden.bs.modal', '#globalLogoutModal', function () {
    var form = $('#globalLogoutForm');

    // Clear form
    form[0].reset();
    $('#globalLogoutErrors').hide();
    $('#globalLogoutErrorList').empty();
});
