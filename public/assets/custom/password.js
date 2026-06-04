// Toggle password visibility — global, works on any page with .toggle-password
$(document).on('click', '.toggle-password', function () {
    var $input = $(this).closest('.relative').find('input');
    var $icon = $(this).find('i');
    if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $icon.removeClass('fa-eye-slash').addClass('fa-eye');
    } else {
        $input.attr('type', 'password');
        $icon.removeClass('fa-eye').addClass('fa-eye-slash');
    }
});

if ($('.change-password').length != 0) {
    // Reset form when modal closes — support both Bootstrap (old) and inline modal (new) themes
    $('#changeModal').on('hidden.bs.modal', function (e) {
        $('#password_form')[0].reset();
        $('.custom-error,.invalid-feedback,.mt-1.text-sm.text-red-500').html('');
    });

    // jQuery Validate for old Bootstrap theme
    if (typeof $.fn.validate === 'function' && !$('#changeModal').hasClass('erp-inline-modal')) {
        $('#password_form').validate({
            rules: {
                current_password: {
                    required: true,
                },
                password: {
                    required: true,
                },
                confirm_password: {
                    required: true,
                },
            },
            messages: {
                current_password: {
                    required: 'Enter current password',
                },
                password: {
                    required: 'Enter new password',
                },
                confirm_password: {
                    required: 'Enter confirm password',
                },
            },
            errorElement: 'p',
            errorClass: 'text-danger mb-0 custom-error',

            highlight: function (element) {
                $(element).addClass('has-error');
            },
            unhighlight: function (element) {
                $(element).removeClass('has-error');
            },
            errorPlacement: function (error, element) {
                $(element).closest('.custom-input-group').append(error);
            },
        });
    }

    $(document).on('click', '.change-password', function () {
        var $btn = $(this);
        var isTW = $('#changeModal').hasClass('erp-inline-modal');

        // Validate: use validateFormFields for TW theme, jQuery Validate for old theme
        if (isTW) {
            var errors = validateFormFields($('#password_form'), false);
            if (errors.length > 0) {
                setButtonError($btn);
                return false;
            }
        } else {
            if (!$('#password_form').valid()) {
                return false;
            }
        }

        var formData = new FormData($('#password_form')[0]);
        var route = $btn.attr('data-route');
        $.ajax({
            type: 'POST',
            url: route,
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('.custom-error,.invalid-feedback,.mt-1.text-sm.text-red-500').html('');
                setButtonLoading($btn);
            },
            success: function (response) {
                resetButtonLoading($btn);
                if (response.status_code == 500) {
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Error', message: response.message, type: 'error' });
                    } else {
                        toastr.error(response.message, 'Error');
                    }
                } else if (response.status_code == 403) {
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Warning', message: response.message, type: 'warning' });
                    } else {
                        toastr.warning(response.message, 'Warning');
                    }
                } else if (response.status_code == 201) {
                    $.each(response.errors, function (key, value) {
                        var errorKey = key.indexOf('.') !== -1 ? key.replace(/\./g, '_') : key;
                        $('#error_' + errorKey).html(
                            '<p class="text-danger text-red-500 mb-0 text-sm">' + value + '</p>'
                        );
                    });
                } else {
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Success', message: response.message, type: 'success' });
                    } else {
                        toastr.success(response.message, 'Success');
                    }
                    location.reload(true);
                }
            },
        });
    });
}
