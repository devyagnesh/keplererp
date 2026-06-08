/**
 * GRN returns list and create form.
 */
$(function () {
    $('#grnReturnTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: window.grnReturnDataUrl, type: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } },
        columns: [
            { data: 'return_number' },
            { data: 'grn', orderable: false },
            { data: 'vendor', orderable: false },
            { data: 'status' },
            { data: 'posted_at' },
        ],
        order: [[0, 'desc']],
    });

    $('#grnReturnForm').validate({
        submitHandler: function (form) {
            var $btn = $(form).find('[type="submit"]');
            $btn.prop('disabled', true);
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function (r) {
                    Notify.success(r.message);
                    $('#grnReturnTable').DataTable().ajax.reload(null, false);
                    form.reset();
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Failed');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                },
            });
        },
    });
});
