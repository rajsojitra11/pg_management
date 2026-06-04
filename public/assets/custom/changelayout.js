if ($('.change-layout').length != 0) {
    $('#changlayoutModal').on('hidden.bs.modal', function (e) {
        $('#change_layout_form')[0].reset();
        $('.custom-error,.invalid-feedback').html('');
    });

    $('#change_layout_form').validate({
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
    $(document).on('click', '.change-layout', function () {
        if ($('#change_layout_form').valid()) {
            var formData = new FormData($('#change_layout_form')[0]);
            var route = $(this).attr('data-route');
            $.ajax({
                type: 'POST',
                url: route,
                data: formData,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('.custom-error,.invalid-feedback').html('');
                    $('.change-layout').html(
                        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wait`
                    );
                    $('.change-layout').attr('disabled', true);
                },
                success: function (response) {
                    $('.change-layout').html('Submit');
                    $('.change-layout').attr('disabled', false);
                    if (response.status_code == 500) {
                        toastr.error(response.message, 'Error');
                    } else if (response.status_code == 403) {
                        toastr.warning(response.message, 'Warning');
                    } else if (response.status_code == 201) {
                        $.each(response.errors, function (key, value) {
                            if (key.indexOf('.') !== -1) {
                                $('#error_' + key.replace(/\./g, '_')).html(
                                    '<p class="text-danger mb-0">' + value + '</p>'
                                );
                            } else {
                                $('#error_' + key).html('<p class="text-danger mb-0">' + value + '</p>');
                            }
                        });
                    } else {
                        toastr.success(response.message, 'Success');
                        location.reload(true);
                    }
                },
            });
        } else {
            return false;
        }
    });
}
