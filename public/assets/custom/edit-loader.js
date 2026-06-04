/**
 * Global Edit Button Loader
 * Shows a full-screen loader when edit button is clicked and AJAX request is in progress
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Create the loader HTML if it doesn't exist
        if (!$('#global-edit-loader').length) {
            var loaderHtml = `
                <div id="global-edit-loader" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 9999;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2.5rem; color: var(--erp-primary);"></i>
                        <div style="margin-top: 0.75rem; color: var(--erp-primary);">
                            <strong>Loading...</strong>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(loaderHtml);
        }

        // Function to show loader
        window.showEditLoader = function (message) {
            var $loader = $('#global-edit-loader');
            if (message) {
                $loader.find('strong').text(message);
            } else {
                $loader.find('strong').text('Loading...');
            }
            $loader.fadeIn(200);
        };

        // Function to hide loader
        window.hideEditLoader = function () {
            $('#global-edit-loader').fadeOut(200);
        };

        // Intercept all edit button clicks
        $(document).on('click', '.edit', function (e) {
            // Show loader immediately when edit is clicked
            showEditLoader('Loading record details...');
        });

        // Hide loader when modal is shown (Bootstrap event for old theme)
        $(document).on('shown.bs.modal', '.modal', function () {
            hideEditLoader();
        });

        // Hide loader when Tailwind modal becomes visible (new theme)
        // Uses MutationObserver to detect when 'hidden' class is removed from a modal
        var modalObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    var el = m.target;
                    if (el.id && !el.classList.contains('hidden') && el.classList.contains('fixed')) {
                        hideEditLoader();
                    }
                }
            });
        });
        // Observe all modal-like elements (fixed inset-0)
        document.querySelectorAll('.fixed.inset-0, [id*="Modal"], [id*="modal"]').forEach(function (el) {
            modalObserver.observe(el, { attributes: true, attributeFilter: ['class'] });
        });
        // Also observe dynamically: re-scan after AJAX completes
        $(document).ajaxComplete(function () {
            document.querySelectorAll('.fixed.inset-0, [id*="Modal"], [id*="modal"]').forEach(function (el) {
                modalObserver.observe(el, { attributes: true, attributeFilter: ['class'] });
            });
        });

        // Also hide loader if any error toastr is shown
        var originalToastrError = toastr.error;
        var originalToastrWarning = toastr.warning;

        toastr.error = function () {
            hideEditLoader();
            return originalToastrError.apply(toastr, arguments);
        };

        toastr.warning = function () {
            hideEditLoader();
            return originalToastrWarning.apply(toastr, arguments);
        };

        // Intercept AJAX to handle edit requests
        $(document).ajaxComplete(function (event, xhr, settings) {
            // Check if this was likely an edit request (GET request that returns data for editing)
            if (settings.type === 'GET' && xhr.responseJSON) {
                var response = xhr.responseJSON;
                // If response has status_code and it's not 200, hide loader
                if (response.status_code && response.status_code !== 200) {
                    hideEditLoader();
                }
            }
        });

        // Fallback: Hide loader after 10 seconds if nothing else hides it
        $(document).on('click', '.edit', function () {
            setTimeout(function () {
                if ($('#global-edit-loader').is(':visible')) {
                    hideEditLoader();
                    toastr.warning('The operation is taking longer than expected.', 'Notice');
                }
            }, 10000);
        });

        // Also handle delete operations with loader
        $(document).on('click', '.delete', function () {
            // This will be handled by Sweet Alert, but we can enhance if needed
        });
    });
})(jQuery);
