/**
 * Leave applications listing DataTable with approve/reject actions.
 */
$(function () {
    var $table = $('#leaveTable');
    if (!$table.length || typeof $.fn.DataTable === 'undefined' || !window.leaveDataUrl) {
        return;
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.leaveDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        },
        columns: [
            { data: 'employee', name: 'employee', orderable: false },
            { data: 'period', name: 'period', orderable: false },
            { data: 'leave_type', name: 'leave_type', orderable: false },
            { data: 'status', name: 'status', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: 'No leave applications found.',
            zeroRecords: 'No matching records found.',
        },
    });

    /**
     * POST JSON to leave approve/reject endpoint.
     *
     * @param {string} url
     * @param {object} [extraData]
     */
    function postLeaveAction(url, extraData) {
        $.ajax({
            url: url,
            type: 'POST',
            data: extraData || {},
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
                        : 'Action failed.';
                notifyError(msg);
            },
        });
    }

    $(document).on('click', '.js-leave-approve', function () {
        var url = $(this).data('url');
        if (!url) {
            return;
        }
        postLeaveAction(url);
    });

    $(document).on('click', '.js-leave-reject', function () {
        var url = $(this).data('url');
        if (!url) {
            return;
        }
        var reason = window.prompt('Rejection reason (required):');
        if (reason === null) {
            return;
        }
        if ($.trim(reason) === '') {
            notifyWarning('Rejection reason is required.');
            return;
        }
        postLeaveAction(url, { rejected_reason: reason });
    });
});
