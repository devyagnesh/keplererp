/**
 * Credit notes list and AJAX form.
 */
$(function () {
    $('#creditNoteTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: window.creditNoteDataUrl, type: 'POST' },
        columns: [
            { data: 'credit_note_number' },
            { data: 'customer', orderable: false },
            { data: 'credit_note_date' },
            { data: 'total_amount' },
            { data: 'status' },
        ],
        order: [[0, 'desc']],
    });

    $('#creditNoteForm').validate({
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
                    $('#creditNoteTable').DataTable().ajax.reload(null, false);
                    form.reset();
                },
                error: function (xhr) {
                    var r = xhr.responseJSON;
                    Notify.error(r && r.message ? r.message : 'Request failed.');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                },
            });
        },
    });
});
