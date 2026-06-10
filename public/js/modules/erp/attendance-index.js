/**
 * HR attendance listing with filters and extended GPS columns.
 */
$(function () {
    var $table = $('#attendanceTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined' || !window.attendanceDataUrl) {
        return;
    }

    function currentWorkDate() {
        return $('#attendanceFilterDate').val() || window.attendanceWorkDate;
    }

    function filterEmployeeId() {
        return $('#attendanceFilterEmployee').val() || '';
    }

    function filterStatus() {
        return $('#attendanceFilterStatus').val() || '';
    }

    function filterSource() {
        return $('#attendanceFilterSource').val() || '';
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.attendanceDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: function (d) {
                d.work_date = currentWorkDate();
                d.employee_id = filterEmployeeId();
                d.status = filterStatus();
                d.source = filterSource();
            },
        },
        columns: [
            { data: 'employee', name: 'employee', orderable: false },
            { data: 'emp_code', name: 'emp_code', orderable: false },
            { data: 'work_date', name: 'work_date' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'source', name: 'source', orderable: false },
            { data: 'check_in', name: 'check_in', orderable: false },
            { data: 'check_out', name: 'check_out', orderable: false },
            { data: 'check_in_location', name: 'check_in_location', orderable: false },
            { data: 'check_out_location', name: 'check_out_location', orderable: false },
            { data: 'created_at', name: 'created_at' },
        ],
        order: [[9, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No attendance for this date.',
            zeroRecords: 'No matching records found.',
        },
    });

    $('#attendanceFilterDate, #attendanceFilterEmployee, #attendanceFilterStatus, #attendanceFilterSource').on(
        'change',
        function () {
            table.ajax.reload();
            if (typeof window.reloadAttendanceMap === 'function') {
                window.reloadAttendanceMap();
            }
        }
    );
});
