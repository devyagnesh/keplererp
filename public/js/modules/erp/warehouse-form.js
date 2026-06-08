/**
 * Warehouse create / edit — validation and AJAX save.
 */
$(function () {
    var $form = $('#warehouseForm');
    if (!$form.length) {
        return;
    }

    $form.validate({
        rules: {
            code: { required: true, maxlength: 32 },
            name: { required: true, maxlength: 120 },
            city: { maxlength: 80 },
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
            var $btn = $('#warehouseSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            var fd = new FormData(form);
            if (window.warehouseFormMethod === 'PUT') {
                fd.append('_method', 'PUT');
            }

            $.ajax({
                url: window.warehouseFormSubmitUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (response.message) {
                        notifySuccess(response.message);
                    }
                    window.location.href = window.warehousesIndexUrl;
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var validator = $form.data('validator');
                        if (validator) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                var $field = $form.find('[name="' + field + '"]');
                                if ($field.length) {
                                    validator.showErrors({ [field]: messages[0] });
                                }
                            });
                        }
                        notifyError(xhr.responseJSON.message || 'Please correct the highlighted fields.');
                        return;
                    }
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not save warehouse.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
