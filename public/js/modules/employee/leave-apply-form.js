/**
 * Employee self-service leave application form (JSON).
 */
$(function () {
    var $form = $('#employeeLeaveApplyForm');
    if (!$form.length || !window.employeeLeaveStoreUrl) {
        return;
    }

    $form.validate({
        rules: {
            leave_type: { required: true },
            start_date: { required: true, date: true },
            end_date: { required: true, date: true },
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
            var $btn = $('#employeeLeaveApplySubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...'
            );

            $.ajax({
                url: window.employeeLeaveStoreUrl,
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
                    form.reset();
                    if ($('#employeeLeaveTable').length && $.fn.DataTable.isDataTable('#employeeLeaveTable')) {
                        $('#employeeLeaveTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    var body = xhr.responseJSON;
                    if (xhr.status === 422 && body && body.errors) {
                        $.each(body.errors, function (field, messages) {
                            var $field = $form.find('[name="' + field + '"]');
                            $field.addClass('is-invalid');
                            $field.closest('.col-md-3, .col-md-6, .col-12').find('.invalid-feedback').remove();
                            $field
                                .closest('.col-md-3, .col-md-6, .col-12')
                                .append(
                                    '<span class="invalid-feedback d-block">' + messages[0] + '</span>'
                                );
                        });
                        notifyWarning(body.message || 'Please correct the highlighted fields.');
                        return;
                    }
                    var msg = body && body.message ? body.message : 'Could not submit leave application.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
