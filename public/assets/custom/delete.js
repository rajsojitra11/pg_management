(function($) {
    'use strict';

    var activeDeleteModal = null;

    function closeDeleteModal() {
        if (activeDeleteModal) {
            activeDeleteModal.close();
            activeDeleteModal = null;
        }
        // Reset clicked flag on all delete buttons
        $('.delete').removeAttr('data-clicked');
    }

    // Open delete modal using erpModal()
    $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        if ($(this).attr('data-clicked') === 'true') return false;
        if (activeDeleteModal) return false;

        $(this).attr('data-clicked', 'true');

        var id = $(this).data('id');
        // Resolve base URL: data-url on button > window.URL_ROUTE > current page path (strip trailing slash)
        var baseUrl = $(this).data('url') || window.URL_ROUTE || window.location.pathname.replace(/\/+$/, '');
        var deleteUrl = baseUrl + '/' + id;
        var deleteButton = $(this);

        // Get form body from Blade-rendered template
        var template = document.getElementById('globalDeleteModalTemplate');
        var bodyHtml = template ? template.innerHTML : '<p>Delete modal template not found.</p>';

        var cancelBtn = '<button class="erp-delete-cancel px-4 py-2 text-sm font-medium rounded-md border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50">Cancel</button>';
        var deleteBtn = '<button class="erp-delete-confirm px-4 py-2 text-sm font-medium rounded-md text-white" style="background-color:var(--erp-danger-bg-solid)" ' +
            'onmouseover="this.style.backgroundColor=\'var(--erp-danger-bg-dark)\'" onmouseout="this.style.backgroundColor=\'var(--erp-danger-bg-solid)\'">' +
            '<i class="fa-solid fa-trash mr-1.5 text-xs"></i> Delete</button>';

        activeDeleteModal = erpModal({
            title: 'Confirm Delete',
            body: bodyHtml,
            size: 'md',
            footer: cancelBtn + deleteBtn,
            onClose: function() {
                activeDeleteModal = null;
                deleteButton.removeAttr('data-clicked');
            }
        });

        // Set form action
        var $form = $(activeDeleteModal.el).find('#globalDeleteForm');
        $form.attr('action', deleteUrl);

        // Store context
        $(activeDeleteModal.el).data('delete-url', deleteUrl);
        $(activeDeleteModal.el).data('delete-button', deleteButton);

        // Cancel button
        activeDeleteModal.el.querySelector('.erp-delete-cancel').addEventListener('click', function() {
            closeDeleteModal();
        });

        // Delete button
        activeDeleteModal.el.querySelector('.erp-delete-confirm').addEventListener('click', function() {
            var $modal = $(activeDeleteModal.el);

            // Disable button
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Deleting...');

            try {
                $.ajax({
                    url: $modal.data('delete-url'),
                    type: 'DELETE',
                    headers: { 'Accept': 'application/json' },
                    data: {
                        _token: $modal.find('input[name="_token"]').val(),
                        id: id
                    },
                    success: function(response) {
                        try {
                            if (response.status_code == 200) {
                                toastr.success(response.message, 'Success');
                                var dt = window.table || (typeof table !== 'undefined' ? table : null);
                                if (dt && $.fn.DataTable.isDataTable('#table')) {
                                    dt.ajax.reload(null, false);
                                } else {
                                    var row = deleteButton.closest('tr');
                                    if (row.length) row.hide(); else window.location.reload();
                                }
                                closeDeleteModal();
                            } else if (response.status_code == 201) {
                                toastr.warning(response.message, 'Warning');
                            } else {
                                toastr.error(response.message || 'Error', 'Error');
                            }
                        } catch (e) { console.error('Delete success handler error:', e); }
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-trash mr-1.5 text-xs"></i> Delete');
                    },
                    error: function(xhr) {
                        try {
                            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                                var errorList = $modal.find('#globalDeleteErrorList');
                                errorList.empty();
                                $.each(xhr.responseJSON.errors, function(key, messages) {
                                    $.each(messages, function(i, msg) { errorList.append('<li>' + msg + '</li>'); });
                                });
                                $modal.find('#globalDeleteErrors').show();
                            } else if (xhr.status === 419) {
                                toastr.error('Session expired. Please refresh the page.', 'Error');
                            } else {
                                toastr.error('Something went wrong. Please try again.', 'Error');
                            }
                        } catch (e) { console.error('Delete error handler error:', e); }
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-trash mr-1.5 text-xs"></i> Delete');
                    }
                });
            } catch (e) { console.error('Delete AJAX error:', e); }
        });
    });

})(jQuery);
