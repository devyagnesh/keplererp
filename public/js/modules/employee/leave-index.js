/**
 * Employee leave applications DataTable.
 */
$(function () {
    var $table = $('#employeeLeaveTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined' || !window.employeeLeaveDataUrl) {
        return;
    }

    function leaveStatusBadge(status) {
        var map = {
            approved: 'success',
            rejected: 'danger',
            pending: 'warning',
        };
        var cls = map[status] || 'secondary';
        return '<span class="badge bg-' + cls + '-transparent">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }

    $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.employeeLeaveDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        },
        columns: [
            { data: 'period', name: 'period', orderable: false },
            { data: 'leave_type', name: 'leave_type', orderable: false },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                render: function (data) {
                    return leaveStatusBadge(data);
                },
            },
            { data: 'reason_note', name: 'reason_note', orderable: false },
            { data: 'created_at', name: 'created_at' },
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No leave applications yet.',
            zeroRecords: 'No matching records found.',
        },
    });
});
