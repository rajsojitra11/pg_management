/**
 * Dynamic Validation System for Laravel Modules
 *
 * This file provides dynamic validation for all module forms using
 * configuration values from the backend. It ensures consistent validation
 * across all modules without hardcoding values.
 *
 * Usage:
 * - Include this script in module views that need validation
 * - Use data attributes to pass config values to JavaScript
 * - Follow the patterns established in CLAUDE.md
 */

class ValidationLoader {
    constructor() {
        this.config = {};
        this.loadConfig();
    }

    /**
     * Load validation configuration from data attributes
     */
    loadConfig() {
        // Get config values from data attributes or use defaults
        this.config = {
            minCommentLength: parseInt($('body').attr('data-min-comment-length')) || 3,
            maxCommentLength: parseInt($('body').attr('data-max-comment-length')) || 1000,
            dateFormat: $('body').attr('data-date-format') || 'dd-mm-yyyy',
            dateTimeFormat: $('body').attr('data-datetime-format') || 'dd-mm-yyyy hh:ii:ss'
        };
    }

    /**
     * Get validation rules for user_remark field
     */
    getUserRemarkRules(isRequired = true) {
        const rules = {
            maxlength: this.config.maxCommentLength
        };

        if (isRequired) {
            rules.required = true;
            rules.minlength = this.config.minCommentLength;
        }

        return rules;
    }

    /**
     * Get validation messages for user_remark field
     */
    getUserRemarkMessages(isRequired = true, fieldName = 'User remark') {
        const messages = {};

        if (isRequired) {
            messages.required = this.getValidationMessage('user_remark_required') || `${fieldName} is required`;
            messages.minlength = this.getValidationMessage('user_remark_min') || `${fieldName} must be at least ${this.config.minCommentLength} characters`;
        }

        messages.maxlength = this.getValidationMessage('user_remark_max') || `${fieldName} may not be greater than ${this.config.maxCommentLength} characters`;

        return messages;
    }

    /**
     * Get validation message from language files
     */
    getValidationMessage(key) {
        // Check if message exists in global validation messages
        if (typeof window.validationMessages !== 'undefined' && window.validationMessages[key]) {
            return window.validationMessages[key];
        }
        return null;
    }

    /**
     * Get date validation rules
     */
    getDateRules() {
        return {
            date: true,
            dateITA: true,
            // Add more date validation rules as needed
        };
    }

    /**
     * Get date validation messages
     */
    getDateMessages() {
        return {
            date: this.getValidationMessage('date') || 'Please enter a valid date',
            dateITA: this.getValidationMessage('date_format') || 'Please enter a valid date format'
        };
    }

    /**
     * Initialize validation for a form
     */
    initializeValidation(formSelector, options = {}) {
        const defaultOptions = {
            errorElement: 'span',
            errorClass: 'invalid-feedback d-block',
            errorPlacement: function(error, element) {
                if (element.attr('name') === 'user_remark') {
                    error.insertAfter(element.closest('.form-group'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        };

        const finalOptions = $.extend(true, defaultOptions, options);
        $(formSelector).validate(finalOptions);
    }

    /**
     * Setup validation for delete modal
     */
    setupDeleteValidation() {
        const self = this;

        // Wait for modal to be shown before initializing validation
        $('#globalDeleteModal').on('shown.bs.modal', function() {
            const $form = $('#globalDeleteForm');

            // Remove existing validation to prevent duplicates
            $form.removeData('validator');

            // Initialize validation
            self.initializeValidation('#globalDeleteForm', {
                rules: {
                    user_remark: self.getUserRemarkRules(true)
                },
                messages: {
                    user_remark: self.getUserRemarkMessages(true, 'Reason for deletion')
                },
                submitHandler: function(form) {
                    // Handle form submission
                    self.handleDeleteSubmit(form);
                }
            });
        });
    }

    /**
     * Handle delete form submission
     */
    handleDeleteSubmit(form) {
        const $form = $(form);
        const $submitBtn = $('#globalDeleteSubmitBtn');
        const originalText = $submitBtn.html();

        // Show loading state
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Deleting...');

        // Submit form via AJAX
        $.ajax({
            url: $form.attr('action'),
            type: 'DELETE',
            data: $form.serialize(),
            success: function(response) {
                if (response.status_code === 200) {
                    // Close modal
                    $('#globalDeleteModal').modal('hide');

                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Deleted successfully');
                    }

                    // Reload table if it exists
                    if (typeof table !== 'undefined' && $.fn.DataTable.isDataTable('#table')) {
                        table.ajax.reload(null, false);
                    }
                } else {
                    // Show error message
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Delete failed');
                    }
                }
            },
            error: function(xhr) {
                let errorMessage = 'Delete failed';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join(', ');
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage);
                }
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Get dynamic validation configuration for forms
     */
    getFormValidationConfig(formType = 'create') {
        const config = {
            create: {
                userRemarkRequired: false
            },
            update: {
                userRemarkRequired: true
            },
            delete: {
                userRemarkRequired: true
            }
        };

        return config[formType] || config.create;
    }
}

// Initialize validation system when document is ready
$(document).ready(function() {
    window.validationLoader = new ValidationLoader();

    // Setup delete validation if delete modal exists
    if ($('#globalDeleteModal').length > 0) {
        window.validationLoader.setupDeleteValidation();
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ValidationLoader;
}