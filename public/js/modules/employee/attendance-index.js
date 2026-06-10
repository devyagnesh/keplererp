/**
 * Employee attendance history DataTable with month filter.
 */
$(function () {
    var $table = $('#employeeAttendanceTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined' || !window.employeeAttendanceDataUrl) {
        return;
    }

    function currentMonth() {
        var v = $('#attendanceMonth').val();
        return v || window.employeeAttendanceMonth;
    }

    function statusBadge(status) {
        var map = {
            present: 'success',
            absent: 'danger',
            half: 'warning',
            leave: 'info',
        };
        var cls = map[status] || 'secondary';
        var label = status === 'half' ? 'Half day' : status.charAt(0).toUpperCase() + status.slice(1);
        return '<span class="badge bg-' + cls + '-transparent">' + label + '</span>';
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.employeeAttendanceDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: function (d) {
                d.month = currentMonth();
            },
        },
        columns: [
            { data: 'work_date', name: 'work_date' },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                render: function (data) {
                    return statusBadge(data);
                },
            },
            { data: 'created_at', name: 'created_at' },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No attendance for this month.',
            zeroRecords: 'No matching records found.',
        },
    });

    $('#attendanceMonth').on('change', function () {
        var month = $(this).val();
        if (month) {
            window.location.href = window.location.pathname + '?month=' + encodeURIComponent(month);
        }
    });
});
