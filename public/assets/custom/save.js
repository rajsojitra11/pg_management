// ── Safe BlockUI helper (TW theme doesn't load BlockUI) ──
function _safeUnblock() {
    try { if ($.fn.unblock) { $('.card').unblock(); } } catch(e) {}
}

// ── Shared validation helpers (used by save.js, update.js, delete.js) ──

/**
 * Set a submit button to loading state (spinner + disabled).
 * Returns the original HTML so it can be restored on error.
 *
 * Usage:
 *   var original = setButtonLoading($btn);         // start loading
 *   resetButtonLoading($btn, original);             // restore on error
 *
 * For full-page forms, call in the submit handler:
 *   $('#form').on('submit', function(e) {
 *       var errors = validateFormFields($(this), isUpdate);
 *       if (errors.length) { e.preventDefault(); return false; }
 *       setButtonLoading($(this).find('[type="submit"]'));
 *   });
 */
function _storeButtonOriginal($btn) {
    if (!$btn.data('erp-original-html')) {
        $btn.data('erp-original-html', $btn.html());
        $btn.data('erp-original-style', $btn.attr('style') || '');
    }
}

function setButtonLoading($btn) {
    _storeButtonOriginal($btn);
    $btn.prop('disabled', true)
        .removeClass('erp-btn-error')
        .attr('style', $btn.data('erp-original-style') + ';opacity:0.7;pointer-events:none;')
        .html('<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Please wait...');
    // Disable cancel/back buttons in the same action container.
    // Exclude the trigger button itself: type="button" submit triggers (e.g. the
    // lamination/UV #loSubmit) would otherwise match this selector and capture
    // their own freshly-set pointer-events:none as the "original", which the reset
    // helpers then restore — leaving the button visually reset but unclickable.
    var $container = $btn.closest('.erp-action-bar, form').length ? $btn.closest('.erp-action-bar, form') : $btn.parent();
    $container.find('a[href], button[type="button"], button[type="reset"]').not($btn).each(function() {
        if (!$(this).hasClass('erp-btn-locked')) {
            $(this).data('erp-original-pointer', $(this).css('pointerEvents'));
            $(this).css({ opacity: 0.5, pointerEvents: 'none' }).addClass('erp-btn-locked');
        }
    });
}

function resetButtonLoading($btn) {
    $btn.html($btn.data('erp-original-html') || $btn.html());
    $btn.prop('disabled', false)
        .removeClass('erp-btn-error')
        .attr('style', $btn.data('erp-original-style') || '');
    // Re-enable cancel/back buttons
    var $container = $btn.closest('.erp-action-bar, form').length ? $btn.closest('.erp-action-bar, form') : $btn.parent();
    $container.find('.erp-btn-locked').each(function() {
        $(this).css({ opacity: '', pointerEvents: $(this).data('erp-original-pointer') || '' }).removeClass('erp-btn-locked');
    });
}

function setButtonError($btn) {
    _storeButtonOriginal($btn);
    // Restore original icon + text
    $btn.html($btn.data('erp-original-html'));
    // Restore original inline style so error class layers on top
    $btn.attr('style', $btn.data('erp-original-style'));
    $btn.prop('disabled', false).addClass('erp-btn-error');

    // Re-enable cancel/back buttons
    var $container = $btn.closest('.erp-action-bar, form').length ? $btn.closest('.erp-action-bar, form') : $btn.parent();
    $container.find('.erp-btn-locked').each(function() {
        $(this).css({ opacity: '', pointerEvents: $(this).data('erp-original-pointer') || '' }).removeClass('erp-btn-locked');
    });

    // Auto-clear error state after 3 seconds — button returns to its original look
    setTimeout(function() {
        $btn.removeClass('erp-btn-error');
    }, 3000);
}

function validateFormFields($form, isUpdate) {
    var errors = [];
    var msgs = window.validationMessages || {};

    // Clear previous errors
    $form.find('.erp-field-error').remove();
    $form.find('.border-red-500').removeClass('border-red-500');
    $form.find('.erp-form-error-banner').hide();

    // 1. Validate all form fields with HTML required attribute
    $form.find('[required]').each(function() {
        var $f = $(this);
        var type = ($f.attr('type') || '').toLowerCase();

        // Skip hidden inputs
        if (type === 'hidden') return;
        // Skip fields inside hidden containers (e.g. remarks-container when in create mode)
        // But do NOT skip selects managed by erpSearchSelect or Select2
        // (they have display:none on themselves but the visible widget is next to them)
        var isManagedSelect = $f.is('select') && ($f.next('.erp-select-wrapper').length || $f.next('.select2-container').length);
        if (!isManagedSelect) {
            if ($f.closest('.hidden').length || $f.closest('[style*="display: none"]').length || $f.closest('[style*="display:none"]').length) return;
        }

        var isEmpty = false;
        if (type === 'checkbox') {
            isEmpty = !$f.is(':checked');
        } else if (type === 'radio') {
            // For radio: check if any radio with same name is selected
            isEmpty = !$form.find('[name="' + $f.attr('name') + '"]:checked').length;
        } else if (type === 'file') {
            isEmpty = !$f.val() && (!$f[0].files || !$f[0].files.length);
        } else {
            isEmpty = !$.trim($f.val());
        }

        if (isEmpty) {
            var label = $f.attr('data-label') || $f.closest('div').find('label').first().text().replace('*', '').trim() || $f.attr('placeholder') || $f.closest('.frow').find('.flabel').first().text().replace('*', '').replace(':', '').trim() || $f.attr('name');
            addFieldError($f, label + ' is required');
            if (!errors.length) $f.focus();
            errors.push($f.attr('name'));
        }
    });

    // 2. Validate user_remark — required for UPDATE mode
    if (isUpdate) {
        var $remark = $form.find('[name="user_remark"]');
        if ($remark.length) {
            var val = $.trim($remark.val());
            var minLen = parseInt($remark.attr('data-min-length')) || 3;
            if (!val) {
                addFieldError($remark, msgs.user_remark_required || 'Please provide a reason');
                if (!errors.length) $remark.focus();
                errors.push('user_remark');
            } else if (val.length < minLen) {
                addFieldError($remark, msgs.user_remark_min || 'Minimum ' + minLen + ' characters required');
                if (!errors.length) $remark.focus();
                errors.push('user_remark');
            }
        }
    }

    return errors;
}

function addFieldError($field, message) {
    var name = $field.attr('name') || '';
    var containerId = 'error_' + name.replace(/[\[\]]/g, '_').replace(/__+/g, '_').replace(/_$/, '');
    var $container = $('#' + containerId);
    if ($container.length) {
        $container.html('<p class="text-red-500 text-sm mb-0">' + message + '</p>');
        return;
    }
    var $target = $field;
    // For erpSearchSelect fields, target the wrapper trigger instead of the hidden select
    if ($field.is('select') && $field.next('.erp-select-wrapper').length) {
        var $wrapper = $field.next('.erp-select-wrapper');
        var $trigger = $wrapper.children().first();
        $trigger.addClass('border-red-500');
        $('<div class="erp-field-error mt-1 text-sm text-red-500">' + message + '</div>').insertAfter($wrapper);
        return;
    }
    // For Select2 fields, add error after the visible Select2 container
    if ($field.is('select') && $field.next('.select2-container').length) {
        $field.next('.select2-container').find('.select2-selection').addClass('border-red-500');
        $('<div class="erp-field-error mt-1 text-sm text-red-500">' + message + '</div>').insertAfter($field.next('.select2-container'));
        return;
    }
    $target.addClass('border-red-500');
    $('<div class="erp-field-error mt-1 text-sm text-red-500">' + message + '</div>').insertAfter($target);
}

function showFormError($form, message) {
    toastr.error(message, 'Error');
}

// ── Save handler (.save button — CREATE mode) ──

$(document).on('click', '.save', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var formClass = '#form';
    if ($btn.attr('data-form-class') !== undefined) {
        formClass = '.' + $btn.attr('data-form-class');
    }
    var $form = $(formClass);

    // CREATE mode — user_remark is NOT required
    if (validateFormFields($form, false).length > 0) {
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
    var status = $btn.attr('data-status');
    if (status != undefined) formData.append('status', status);

    $.ajax({
        type: 'POST',
        url: route,
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        headers: { 'Accept': 'application/json' },
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
            } else {
                toastr.success(response.message, 'Success');
                if (response.data != undefined) {
                    setTimeout(function () { location.href = response.data; }, 500);
                } else {
                    _safeUnblock();
                    resetButtonLoading($btn);
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

// ── Shared AJAX error handlers ──

function nameToBracket(key) {
    return key.replace(/\.(\d+)/g, '[$1]');
}

function showServerErrors($form, errorsObj) {
    var allErrors = [];
    $.each(errorsObj, function (key, value) {
        var msg = Array.isArray(value) ? value[0] : value;
        var fieldName = key.indexOf('.') !== -1 ? key.replace(/\./g, '_') : key;
        var $el = $('#error_' + fieldName);
        if ($el.length) {
            $el.html('<p class="text-red-500 text-sm mb-0">' + msg + '</p>');
        }
        var $field = $('[name="' + key + '"]');
        if (!$field.length) {
            $field = $('[name="' + nameToBracket(key) + '"]');
        }
        $field.addClass('border-red-500');
        if ($field.is('select') && $field.next('.select2-container').length) {
            $field.next('.select2-container').find('.select2-selection').addClass('border-red-500');
            if (!$el.length) {
                $('<div class="erp-field-error mt-1 text-sm text-red-500">' + msg + '</div>').insertAfter($field.next('.select2-container'));
                return;
            }
        }
        if ($field.length && !$el.length) {
            $('<div class="erp-field-error mt-1 text-sm text-red-500">' + msg + '</div>').insertAfter($field);
            return;
        }
        if (!$el.length) {
            allErrors.push(msg);
        }
    });
    if (allErrors.length) showFormError($form, allErrors.join('. '));
}

function handleAjaxErrors($form, xhr) {
    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        showServerErrors($form, xhr.responseJSON.errors);
    } else {
        var msg = 'Something went wrong. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        showFormError($form, msg);
    }
    // Ensure submit button is re-enabled after any error
    $form.find('[type="submit"], .save, .update').prop('disabled', false).css({ opacity: '', pointerEvents: '' });
}
