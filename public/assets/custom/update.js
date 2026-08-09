// validateFormFields(), setButtonLoading(), setButtonError(), resetButtonLoading(),
// showFormError(), addFieldError(), showServerErrors(), handleAjaxErrors()
// are defined in save.js (loaded before update.js)

$(document).on('click', '.update', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var formClass = '#form';
    if ($btn.attr('data-form-class') !== undefined) {
        formClass = '.' + $btn.attr('data-form-class');
    }
    var $form = $(formClass);

    if (validateFormFields($form).length > 0) {
        setButtonError($btn);
        return false;
    }

    try { if ($.fn.block) { $('.card').block({
        message: '<div class="spinner-border text-primary" role="status"></div>',
        css: { backgroundColor: 'transparent', border: '0' },
        overlayCSS: { backgroundColor: '#fff', opacity: 0.8 },
    }); } } catch(e) {}

    var formData = new FormData($form[0]);
    var route = $btn.attr('data-route');

    $.ajax({
        type: 'POST',
        url: route,
        data: formData,
        dataType: 'json',
        cache: true,
        contentType: false,
        processData: false,
        headers: { 'X-HTTP-Method-Override': 'PUT', 'Accept': 'application/json' },
        beforeSend: function () {
            $form.find('.erp-field-error').remove();
            $form.find('.erp-form-error-banner').hide();
            $form.find('.border-red-500').removeClass('border-red-500');
            $form.find('[id^="error_"]').html('');
            setButtonLoading($btn);
        },
        success: function (response) {
            if (response.status_code == 500) {
                _safeUnblock();
                setButtonError($btn);
                showFormError($form, response.message);
            } else if (response.status_code == 403 || response.status_code == 404) {
                _safeUnblock();
                setButtonError($btn);
                showFormError($form, response.message);
            } else if (response.status_code == 201) {
                _safeUnblock();
                setButtonError($btn);
                showServerErrors($form, response.errors);
            } else if (response.status_code == 202) {
                _safeUnblock();
                resetButtonLoading($btn);
                toastr.info(response.message, 'Info');
                if (response.data != undefined) {
                    setTimeout(function () { location.href = response.data; }, 500);
                } else {
                    $('#inlineModal').modal('hide');
                    table.ajax.reload(null, true);
                }
            } else {
                _safeUnblock();
                resetButtonLoading($btn);
                toastr.success(response.message, 'Success');
                if (response.data != undefined) {
                    setTimeout(function () { location.href = response.data; }, 500);
                } else {
                    $('#inlineModal').modal('hide');
                    table.ajax.reload(null, true);
                }
            }
        },
        error: function (xhr) {
            _safeUnblock();
            setButtonError($btn);
            handleAjaxErrors($form, xhr);
        },
    });
});
