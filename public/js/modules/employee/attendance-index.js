/**
 * Employee attendance history DataTable with month and status filters.
 */
$(function () {
    var $table = $('#employeeAttendanceTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined' || !window.employeeAttendanceDataUrl) {
        return;
    }

    function currentMonth() {
        return $('#attendanceMonth').val() || window.employeeAttendanceMonth;
    }

    function currentStatus() {
        return $('#attendanceStatusFilter').val() || '';
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
                d.status = currentStatus();
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
            { data: 'check_in', name: 'check_in', orderable: false },
            { data: 'check_out', name: 'check_out', orderable: false },
            { data: 'check_in_location', name: 'check_in_location', orderable: false },
            { data: 'check_out_location', name: 'check_out_location', orderable: false },
            { data: 'source', name: 'source', orderable: false },
            { data: 'created_at', name: 'created_at' },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No attendance for this period.',
            zeroRecords: 'No matching records found.',
        },
    });

    $('#attendanceMonth, #attendanceStatusFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#attendanceFilterApply').on('click', function () {
        var month = $('#attendanceMonth').val();
        var status = $('#attendanceStatusFilter').val();
        var params = new URLSearchParams(window.location.search);
        if (month) {
            params.set('month', month);
        }
        if (status) {
            params.set('status', status);
        } else {
            params.delete('status');
        }
        window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());
        table.ajax.reload();
    });
});
