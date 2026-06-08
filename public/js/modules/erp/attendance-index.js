/**
 * Attendance listing DataTable with work date filter.
 */
$(function () {
    var $table = $('#attendanceTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined' || !window.attendanceDataUrl) {
        return;
    }

    function currentWorkDate() {
        var v = $('#attendanceFilterDate').val();
        return v || window.attendanceWorkDate;
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
            },
        },
        columns: [
            { data: 'employee', name: 'employee', orderable: false },
            { data: 'emp_code', name: 'emp_code', orderable: false },
            { data: 'work_date', name: 'work_date' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'created_at', name: 'created_at' },
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No attendance for this date.',
            zeroRecords: 'No matching records found.',
        },
    });

    $('#attendanceFilterDate').on('change', function () {
        table.ajax.reload();
    });
});
