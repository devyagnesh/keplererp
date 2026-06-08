/**
 * Users listing: DataTables server-side + delete via AJAX.
 */
$(function () {
    var $table = $('#usersTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined') {
        return;
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.usersDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'roles', name: 'roles', orderable: false, searchable: false },
            { data: 'is_active', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No users found.',
            zeroRecords: 'No matching users found.',
        },
    });

    $(document).on('click', '.js-delete-user', function () {
        var url = $(this).data('delete-url');
        if (!url) {
            return;
        }
        if (!window.confirm('Delete this user?')) {
            return;
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                if (response.message) {
                    notifySuccess(response.message);
                }
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Could not delete user.';
                notifyError(msg);
            },
        });
    });
});
