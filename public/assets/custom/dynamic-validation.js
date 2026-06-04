/**
 * Enhanced Dynamic Validation Manager for Modal Forms
 * Handles dynamic validation rules for remarks fields
 * Works globally across all modules by properly integrating with existing validators
 *
 * PERFORMANCE FIX: Ensures user_remark validation happens client-side
 * to prevent unnecessary AJAX calls to the server.
 */
(function ($) {
    'use strict';

    /**
     * Hook into jQuery validate initialization to add dynamic fields
     */
    function hookIntoValidatorInitialization() {
        // Store the original validate method
        if (!$.fn.originalValidate) {
            $.fn.originalValidate = $.fn.validate;
        }

        // Override the validate method
        $.fn.validate = function (options) {
            var $form = this;

            // Call the original validate method
            var validator = $.fn.originalValidate.call(this, options);

            // After validator is created, add our dynamic rules
            if (validator && $form.length) {
                setTimeout(function () {
                    addDynamicRulestoValidator(validator, $form);
                }, 50);
            }

            return validator;
        };
    }

    /**
     * Add dynamic validation rules to an existing validator
     */
    function addDynamicRulestoValidator(validator, $form) {
        if (!validator || !validator.settings) return;

        // Initialize rules and messages if they don't exist
        if (!validator.settings.rules) validator.settings.rules = {};
        if (!validator.settings.messages) validator.settings.messages = {};

        // Store original error placement for fallback
        if (!validator.originalErrorPlacement) {
            validator.originalErrorPlacement = validator.settings.errorPlacement;
        }

        // Setup enhanced error placement
        setupEnhancedErrorPlacement(validator);

        // Add rules for remarks fields
        $form.find('.remarks-field').each(function () {
            addRemarksValidationRules(validator, $(this));
        });
    }

    /**
     * Add validation rules for a remarks field
     */
    function addRemarksValidationRules(validator, $field) {
        var fieldName = $field.attr('name');
        var fieldType = $field.attr('data-field-type') || 'create';

        // Skip validation entirely for CREATE forms - remarks should not exist
        if (fieldType === 'create') {
            // Remove any existing rules for this field
            if (validator.settings.rules[fieldName]) {
                delete validator.settings.rules[fieldName];
            }
            if (validator.settings.messages[fieldName]) {
                delete validator.settings.messages[fieldName];
            }
            return;
        }

        // For UPDATE/DELETE forms, remarks are required
        var isRequired = fieldType === 'update' || fieldType === 'delete';

        var minLength = parseInt($field.attr('data-min-length')) || 3;
        var maxLength = parseInt($field.attr('data-max-length')) || 1000;

        // Remove existing rules for this field
        if (validator.settings.rules[fieldName]) {
            delete validator.settings.rules[fieldName];
        }
        if (validator.settings.messages[fieldName]) {
            delete validator.settings.messages[fieldName];
        }

        // Add new rules based on requirements
        if (isRequired) {
            validator.settings.rules[fieldName] = {
                required: true,
                minlength: minLength,
                maxlength: maxLength,
            };
            validator.settings.messages[fieldName] = {
                required: 'This field is required',
                minlength: 'Minimum ' + minLength + ' characters required',
                maxlength: 'Maximum ' + maxLength + ' characters allowed',
            };
        }

        // Mark field as having dynamic validation
        $field.data('dynamic-validation-applied', true);
    }

    /**
     * Update existing validator with new rules for a specific field
     */
    function updateFieldValidation($field) {
        var $form = $field.closest('form');
        var validator = $form.data('validator');

        if (!validator) return;

        if ($field.hasClass('remarks-field')) {
            addRemarksValidationRules(validator, $field);
        }

        // Force revalidation if field has a value
        if ($field.val() && validator.element) {
            validator.element($field[0]);
        }
    }

    /**
     * Enhanced error placement that works with both dynamic and module-specific fields
     */
    function setupEnhancedErrorPlacement(validator) {
        validator.settings.errorPlacement = function (error, element) {
            var $element = $(element);
            var elementId = $element.attr('id');

            // Handle dynamic fields (remarks)
            if ($element.hasClass('remarks-field')) {
                var errorContainer = $('#error_' + elementId);

                if (errorContainer.length) {
                    // console.log("came here to place dblock error placement");
                    errorContainer.empty().append(error).addClass('d-block').show();
                } else {
                    // console.log("came here to place after error placement");
                    // Fallback: place after the element
                    error.insertAfter($element);
                }
            } else if (validator.originalErrorPlacement) {
                // Use original error placement for module-specific fields
                // console.log("came here to place original error placement");
                validator.originalErrorPlacement(error, element);
            } else {
                // console.log("came here to place default error placement");
                // Default placement
                $element.closest('.custom-input-group, .mb-3').append(error);
            }
        };

        // Enhanced highlight function
        validator.settings.highlight = function (element, errorClass, validClass) {
            $(element).addClass('is-invalid has-error').removeClass('is-valid');
            var elementId = $(element).attr('id');
            $('#error_' + elementId).addClass('d-block');
        };

        // Enhanced unhighlight function
        validator.settings.unhighlight = function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid has-error');
            var elementId = $(element).attr('id');
            $('#error_' + elementId)
                .removeClass('d-block')
                .empty();
        };
    }

    /**
     * Handle modal switching and field type changes
     */
    function handleModalSwitching() {
        // When modal is shown, ensure validation is properly set up
        $(document).on('shown.bs.modal', '.modal', function () {
            var $modal = $(this);
            setTimeout(function () {
                $modal.find('form').each(function () {
                    var $form = $(this);
                    var validator = $form.data('validator');
                    if (validator) {
                        addDynamicRulestoValidator(validator, $form);
                    }
                });
            }, 100);
        });

        // When edit button is clicked, update field types and validation
        $(document).on('click', '.edit', function () {
            setTimeout(function () {
                // Auto-update data-is-required based on field type for update mode
                $('.remarks-field').each(function () {
                    var $field = $(this);
                    var fieldType = $field.attr('data-field-type');
                    if (fieldType === 'update' || fieldType === 'delete') {
                        $field.attr('data-is-required', 'true');
                        updateFieldValidation($field);
                    } else if (fieldType === 'create') {
                        // Ensure remarks fields are not validated in CREATE forms
                        $field.attr('data-is-required', 'false');
                        $field.prop('required', false);
                        updateFieldValidation($field);
                    }
                });
            }, 500);
        });

        // Handle field type changes via mutation observer
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes') {
                    var $target = $(mutation.target);
                    if ($target.hasClass('remarks-field')) {
                        // Auto-update data-is-required based on field type
                        if (mutation.attributeName === 'data-field-type') {
                            var fieldType = $target.attr('data-field-type');
                            if (fieldType === 'update' || fieldType === 'delete') {
                                $target.attr('data-is-required', 'true');
                            } else if (fieldType === 'create') {
                                $target.attr('data-is-required', 'false');
                            }
                        }

                        setTimeout(function () {
                            updateFieldValidation($target);
                        }, 50);
                    }
                }
            });
        });

        // Setup observer for existing and new dynamic fields
        function setupFieldObservers() {
            $('.remarks-field').each(function () {
                if (!$(this).data('mutation-observer-attached')) {
                    observer.observe(this, {
                        attributes: true,
                        attributeFilter: ['data-field-type', 'data-is-required', 'required'],
                    });
                    $(this).data('mutation-observer-attached', true);
                }
            });
        }

        // Initial setup of observers
        setupFieldObservers();

        // Re-setup observers when modals are shown (for dynamically loaded content)
        $(document).on('shown.bs.modal', '.modal', function () {
            setupFieldObservers();
        });

        // Handle form resets
        $(document).on('reset', 'form', function () {
            var $form = $(this);
            setTimeout(function () {
                // Reset field types to create mode
                $form.find('.remarks-field').each(function () {
                    var $field = $(this);
                    if ($field.attr('data-field-type') !== 'custom') {
                        $field.attr('data-field-type', 'create');
                        $field.attr('data-is-required', 'false');
                        $field.prop('required', false);
                        // Remove validation rules completely for CREATE forms
                        var validator = $form.data('validator');
                        if (validator) {
                            var fieldName = $field.attr('name');
                            if (validator.settings.rules[fieldName]) {
                                delete validator.settings.rules[fieldName];
                            }
                            if (validator.settings.messages[fieldName]) {
                                delete validator.settings.messages[fieldName];
                            }
                        }
                        updateFieldValidation($field);
                    }
                });
            }, 100);
        });
    }

    function getFormattedDateTime() {
        const now = new Date();

        // Get date components
        let day = now.getDate();
        let month = now.getMonth() + 1; // Month is 0-indexed
        let year = now.getFullYear();

        // Get time components
        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();

        // Add leading zeros if necessary
        day = day < 10 ? '0' + day : day;
        month = month < 10 ? '0' + month : month;
        hours = hours < 10 ? '0' + hours : hours;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        // Assemble the formatted string
        return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
    }

    /**
     * Initialize the enhanced dynamic validation system
     */
    $(document).ready(function () {
        // Hook into validator initialization
        hookIntoValidatorInitialization();

        // Setup modal switching handlers
        handleModalSwitching();

        // Handle any existing forms that were already initialized
        $('form').each(function () {
            var $form = $(this);
            var validator = $form.data('validator');
            if (validator) {
                addDynamicRulestoValidator(validator, $form);
            }
        });
    });

    /**
     * Public API for manual validation updates
     */
    window.DynamicValidation = {
        updateField: updateFieldValidation,
        updateForm: function ($form) {
            var validator = $form.data('validator');
            if (validator) {
                addDynamicRulestoValidator(validator, $form);
            }
        },
        updateAll: function () {
            $('form').each(function () {
                var $form = $(this);
                var validator = $form.data('validator');
                if (validator) {
                    addDynamicRulestoValidator(validator, $form);
                }
            });
        },
    };
})(jQuery);
