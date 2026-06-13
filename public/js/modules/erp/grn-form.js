/**
 * GRN create form with QC photo multipart upload.
 */
$(function () {
    var $form = $('#lineDocumentForm');
    if (!$form.length || !window.lineDocumentSubmitUrl) {
        return;
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#lineDocumentSubmit');
        var original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        var formData = new FormData($form[0]);

        $.ajax({
            url: window.lineDocumentSubmitUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                notifySuccess(response.message);
                if (window.lineDocumentRedirectUrl) {
                    window.location.href = window.lineDocumentRedirectUrl;
                }
            },
            error: function (xhr) {
                notifyError(xhr.responseJSON?.message || 'Could not save GRN.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(original);
            },
        });
    });
});
