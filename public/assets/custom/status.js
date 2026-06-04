$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
});
$(document).on('click', '.change-status', function () {
    let id = $(this).attr('data-id');
    if (id != '') {
        let status = $(this).attr('data-status');
        let route = $(this).attr('data-route');

        Swal.fire({
            title: 'Are you sure?',
            text: 'You wont be able to revert this!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it!',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-danger ms-1',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    type: 'POST',
                    url: route,
                    dataType: 'json',
                    data: {
                        id: id,
                        status: status,
                    },
                    success: function (response) {
                        if (response.status_code == 200) {
                            table.ajax.reload(null, false);
                            toastr.success(response.message, 'Success');
                        } else if (response.status_code == 201) {
                            toastr.warning(response.message, 'Warning');
                        } else {
                            toastr.error(response.message, 'Opps!');
                        }
                    },
                });
            }
        });
    }
});
