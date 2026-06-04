/**
 * Remarks Field Validation JavaScript
 *
 * Provides validation functionality for user remarks fields
 * Used by: resources/views/partials/remarks-field.blade.php
 *
 * Auto-initializes all .remarks-field elements on page load
 *
 * Global functions exposed:
 * - window.validateUserRemarkField(fieldId) - Validates a specific remarks field
 * - window.initRemarksField(fieldId, config) - Initializes validation for a field
 * - window.validateAllRemarksFields() - Validates all remarks fields on page
 * - window.getRemarksFieldValidationSummary() - Gets validation summary
 */

// Global storage for remarks field configurations
window.RemarksFieldConfig = window.RemarksFieldConfig || {};

// Auto-initialization on DOM ready
$(document).ready(function () {
    // Initialize all remarks fields automatically
    $('.remarks-field').each(function () {
        const fieldId = $(this).data('field-id') || $(this).attr('id');
        const fieldType = $(this).data('field-type') || 'create';
        const isRequired = $(this).data('is-required') === 'true' || $(this).data('is-required') === true;
        const minLength = parseInt($(this).data('min-length')) || 3;
        const maxLength = parseInt($(this).data('max-length')) || 1000;

        if (fieldId) {
            window.initRemarksField(fieldId, {
                isRequired: isRequired,
                minLength: minLength,
                maxLength: maxLength,
                type: fieldType,
            });
        }
    });
});

/**
 * Initialize remarks field validation
 * @param {string} fieldId - The ID of the remarks field
 * @param {Object} config - Field configuration
 * @param {boolean} config.isRequired - Whether the field is required
 * @param {number} config.minLength - Minimum character length
 * @param {number} config.maxLength - Maximum character length
 * @param {string} config.type - Field type (create, update, delete, custom)
 */
window.initRemarksField = function (fieldId, config) {
    // Store configuration for this field
    window.RemarksFieldConfig[fieldId] = {
        isRequired: config.isRequired || false,
        minLength: config.minLength || 3,
        maxLength: config.maxLength || 1000,
        type: config.type || 'create',
    };

    /**
     * Validate remarks field
     * @param {string} targetFieldId - Field ID to validate (optional, defaults to current field)
     * @returns {boolean} - Validation result
     */
    function validateUserRemark(targetFieldId = fieldId) {
        const remarkField = $('#' + targetFieldId);
        const errorSpan = $('#error_' + targetFieldId);
        const remarkValue = remarkField.val().trim();
        const fieldConfig = window.RemarksFieldConfig[targetFieldId];

        if (!fieldConfig) {
            console.warn('Remarks field configuration not found for:', targetFieldId);
            return true;
        }

        if (fieldConfig.isRequired) {
            if (remarkValue.length === 0) {
                remarkField.removeClass('is-valid').addClass('is-invalid');
                errorSpan.text('User remark is required');
                return false;
            }

            if (remarkValue.length < fieldConfig.minLength) {
                remarkField.removeClass('is-valid').addClass('is-invalid');
                errorSpan.text(`User remark must be at least ${fieldConfig.minLength} characters`);
                return false;
            }
        }

        remarkField.removeClass('is-invalid').addClass('is-valid');
        errorSpan.text('');
        return true;
    }

    /**
     * Update character counter
     * @param {string} targetFieldId - Field ID to update counter for
     */
    function updateCharacterCounter(targetFieldId) {
        const remarkField = $('#' + targetFieldId);
        const currentLength = remarkField.val().length;
        const fieldConfig = window.RemarksFieldConfig[targetFieldId];
        const maxLength = fieldConfig.maxLength;

        // Add or update character counter
        let counterElement = $('#' + targetFieldId + '_counter');
        if (counterElement.length === 0) {
            $('<small id="' + targetFieldId + '_counter" class="form-text float-end"></small>').insertAfter(
                '#' + targetFieldId
            );
            counterElement = $('#' + targetFieldId + '_counter');
        }

        counterElement.text(currentLength + '/' + maxLength);

        // Change color based on length
        if (currentLength > maxLength * 0.9) {
            counterElement.removeClass('text-muted text-warning').addClass('text-danger');
        } else if (currentLength > maxLength * 0.7) {
            counterElement.removeClass('text-muted text-danger').addClass('text-warning');
        } else {
            counterElement.removeClass('text-warning text-danger').addClass('text-muted');
        }
    }

    // Set up real-time validation
    $('#' + fieldId).on('input blur', function () {
        validateUserRemark(fieldId);
    });

    // Set up character counter
    $('#' + fieldId).on('input', function () {
        updateCharacterCounter(fieldId);
    });

    // Expose field-specific validation function globally
    const functionName =
        'validateUserRemarkField' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1).replace(/_/g, '');
    window[functionName] = function () {
        return validateUserRemark(fieldId);
    };

    // Initialize character counter
    updateCharacterCounter(fieldId);
};

/**
 * Global validation function for any remarks field
 * @param {string} fieldId - The field ID to validate
 * @returns {boolean} - Validation result
 */
window.validateUserRemarkField = function (fieldId = 'user_remark') {
    const config = window.RemarksFieldConfig[fieldId];
    if (!config) {
        console.warn('Remarks field not initialized:', fieldId);
        return true;
    }

    const remarkField = $('#' + fieldId);
    const errorSpan = $('#error_' + fieldId);
    const remarkValue = remarkField.val().trim();

    if (config.isRequired) {
        if (remarkValue.length === 0) {
            remarkField.removeClass('is-valid').addClass('is-invalid');
            errorSpan.text('User remark is required');
            return false;
        }

        if (remarkValue.length < config.minLength) {
            remarkField.removeClass('is-valid').addClass('is-invalid');
            errorSpan.text(`User remark must be at least ${config.minLength} characters`);
            return false;
        }
    }

    remarkField.removeClass('is-invalid').addClass('is-valid');
    errorSpan.text('');
    return true;
};

/**
 * Validate all remarks fields on the page
 * @returns {boolean} - True if all fields are valid
 */
window.validateAllRemarksFields = function () {
    let allValid = true;

    Object.keys(window.RemarksFieldConfig).forEach((fieldId) => {
        if (!window.validateUserRemarkField(fieldId)) {
            allValid = false;
        }
    });

    return allValid;
};

/**
 * Get validation summary for all fields
 * @returns {Object} - Summary of field validations
 */
window.getRemarksFieldValidationSummary = function () {
    const summary = {
        totalFields: 0,
        validFields: 0,
        invalidFields: 0,
        fieldDetails: {},
    };

    Object.keys(window.RemarksFieldConfig).forEach((fieldId) => {
        summary.totalFields++;
        const isValid = window.validateUserRemarkField(fieldId);

        if (isValid) {
            summary.validFields++;
        } else {
            summary.invalidFields++;
        }

        summary.fieldDetails[fieldId] = {
            isValid: isValid,
            value: $('#' + fieldId)
                .val()
                .trim(),
            config: window.RemarksFieldConfig[fieldId],
        };
    });

    return summary;
};

/**
 * Reset all remarks fields validation state
 */
window.resetRemarksFieldsValidation = function () {
    Object.keys(window.RemarksFieldConfig).forEach((fieldId) => {
        const field = $('#' + fieldId);
        const errorSpan = $('#error_' + fieldId);
        const counter = $('#' + fieldId + '_counter');

        field.removeClass('is-invalid is-valid');
        errorSpan.text('');

        // Reset character counter if it exists
        if (counter.length > 0) {
            const config = window.RemarksFieldConfig[fieldId];
            const currentLength = field.val().length;
            counter.text(currentLength + '/' + config.maxLength);
            counter.removeClass('text-danger text-warning').addClass('text-muted');
        }
    });
};

/**
 * Validate all form remarks fields on the page
 * This is a convenience function for form submission
 * @returns {boolean} - True if all fields are valid
 */
window.validateAllFormFields = function () {
    let allValid = true;

    // Validate remarks fields
    if (!window.validateAllRemarksFields()) {
        allValid = false;
    }

    return allValid;
};

/**
 * Get comprehensive validation summary for all form fields
 * @returns {Object} - Complete form validation summary
 */
window.getFormValidationSummary = function () {
    const summary = {
        remarksFields: {},
        overallValid: true,
        totalFields: 0,
        validFields: 0,
        invalidFields: 0,
    };

    // Get remarks fields summary
    summary.remarksFields = window.getRemarksFieldValidationSummary();
    summary.totalFields += summary.remarksFields.totalFields;
    summary.validFields += summary.remarksFields.validFields;
    summary.invalidFields += summary.remarksFields.invalidFields;

    summary.overallValid = summary.invalidFields === 0;

    return summary;
};
