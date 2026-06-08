/**
 * Vendor compliance document upload and delete.
 */
$(function () {
    var $form = $('#vendorDocumentForm');
    if ($form.length && window.vendorDocumentUploadUrl) {
        $form.on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#vendorDocumentSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...'
            );

            var data = new FormData($form[0]);
            $.ajax({
                url: window.vendorDocumentUploadUrl,
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (response.message) {
                        notifySuccess(response.message);
                    }
                    window.location.reload();
                },
                error: function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not upload document.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        });
    }

    $(document).on('click', '.js-vendor-doc-delete', function () {
        var url = $(this).data('url');
        if (!url || !window.confirm('Delete this document?')) {
            return;
        }
        $.ajax({
            url: url,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                if (response.message) {
                    notifySuccess(response.message);
                }
                window.location.reload();
            },
            error: function (xhr) {
                var msg =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Could not delete document.';
                notifyError(msg);
            },
        });
    });
});
