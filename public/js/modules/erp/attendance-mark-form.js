/**
 * Mark attendance for one employee (JSON).
 */
$(function () {
    var $form = $('#attendanceMarkForm');
    if (!$form.length) {
        return;
    }

    $form.validate({
        rules: {
            employee_id: { required: true },
            work_date: { required: true, date: true },
            status: { required: true },
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function (form) {
            var $btn = $('#attendanceMarkSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            $.ajax({
                url: window.attendanceMarkSubmitUrl,
                type: 'POST',
                data: $(form).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (response.message) {
                        notifySuccess(response.message);
                    }
                    var wd = $form.find('[name="work_date"]').val();
                    if (window.attendanceIndexBaseUrl && wd) {
                        window.location.href =
                            window.attendanceIndexBaseUrl + '?work_date=' + encodeURIComponent(wd);
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not save attendance.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
