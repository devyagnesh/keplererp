/**
 * Item (SKU) create / edit — validation and AJAX save.
 */
$(function () {
    var $form = $('#itemForm');
    if (!$form.length) {
        return;
    }

    $('#itBatch').on('change', function () {
        if ($(this).is(':checked')) {
            $('#itSerial').prop('checked', false);
        }
    });
    $('#itSerial').on('change', function () {
        if ($(this).is(':checked')) {
            $('#itBatch').prop('checked', false);
        }
    });

    $form.validate({
        rules: {
            sku: { required: true, maxlength: 64 },
            name: { required: true, maxlength: 191 },
            uom: { required: true, maxlength: 16 },
            reorder_level: { required: true, number: true, min: 0 },
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
            var $btn = $('#itemSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            var fd = new FormData(form);
            if (window.itemFormMethod === 'PUT') {
                fd.append('_method', 'PUT');
            }

            $.ajax({
                url: window.itemFormSubmitUrl,
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
                    window.location.href = window.itemsIndexUrl;
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
                            : 'Could not save item.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
