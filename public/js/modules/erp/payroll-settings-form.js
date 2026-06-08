/**
 * HR payroll rules (PF / ESI / PT) — AJAX save.
 */
$(function () {
    var $form = $('#payrollSettingsForm');
    if (!$form.length || !window.payrollSettingsSubmitUrl) {
        return;
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#payrollSettingsSubmit');
        var original = $btn.html();
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
        );

        var fd = new FormData($form[0]);
        fd.append('_method', 'PUT');

        $.ajax({
            url: window.payrollSettingsSubmitUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                if (response.message && typeof notifySuccess === 'function') {
                    notifySuccess(response.message);
                }
            },
            error: function (xhr) {
                var msg =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Could not save payroll rules.';
                if (typeof notifyError === 'function') {
                    notifyError(msg);
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html(original);
            },
        });
    });
});
