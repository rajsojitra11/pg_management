$(document).on('click', '.delete', function () {
    var id = $(this).data('id');
    var me = $(this);
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        confirmButtonText: 'Yes, delete it!',
        showCancelButton: true,
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: URL + '/' + id,
                type: 'DELETE',
                success: function (response) {
                    if (response.status_code == 200) {
                        toastr.success(response.message, 'Success');
                        if (me.attr('data-del-class') !== undefined) {
                            $('.' + me.attr('data-del-class')).hide();
                        } else {
                            me.parent().parent().hide();
                        }
                    } else if (response.status_code == 201) {
                        toastr.warning(response.message, 'Warning');
                    } else {
                        toastr.error(response.message, 'Error');
                    }
                },
                error: function () {
                    toastr.error('Something went wrong. Please try again.', 'Error');
                },
            });
        }
    });
});
