/**
 * Customers listing: DataTables server-side, delete.
 */
$(function () {
    var $table = $('#customersTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined') {
        return;
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.customersDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        },
        columns: [
            { data: 'customer_code', name: 'customer_code' },
            { data: 'name', name: 'name' },
            { data: 'phone', name: 'phone' },
            { data: 'gstin', name: 'gstin', orderable: false },
            { data: 'city', name: 'city' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No customers found.',
            zeroRecords: 'No matching customers found.',
        },
    });

    $(document).on('click', '.js-customer-delete', function () {
        var url = $(this).data('url');
        if (!url || !window.confirm('Delete this customer?')) {
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
                        : 'Could not delete customer.';
                notifyError(msg);
            },
        });
    });
});
