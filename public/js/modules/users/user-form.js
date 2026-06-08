/**
 * User create/edit: jQuery Validation + AJAX submit.
 */
$(function () {
    var $form = $('#userForm');
    if (!$form.length) {
        return;
    }

    var method = window.userFormMethod || 'POST';

    $form.validate({
        onfocusout: function (element) {
            this.element(element);
        },
        rules: {
            name: { required: true, minlength: 2, maxlength: 100 },
            email: { required: true, email: true, maxlength: 255 },
            password: {
                required: function () {
                    return method === 'POST';
                },
                minlength: 8,
            },
            password_confirmation: {
                required: function () {
                    return method === 'POST' || $('#password').val().length > 0;
                },
                equalTo: '#password',
            },
            phone: { required: true, digits: true, minlength: 10, maxlength: 10 },
            role_id: { required: true },
        },
        messages: {
            phone: { digits: 'Enter a valid 10-digit mobile number.' },
            role_id: { required: 'Select a role.' },
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        errorPlacement: function (error, element) {
            error.insertAfter(element);
        },
        submitHandler: function (form) {
            var $btn = $('#userSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            var fd = new FormData(form);
            if (method === 'PUT') {
                fd.append('_method', 'PUT');
            }

            $.ajax({
                url: window.userFormSubmitUrl,
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
                    window.location.href = window.usersIndexUrl || '/admin/users';
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var validator = $form.data('validator');
                        if (validator) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                var $field = $form.find('[name="' + field + '"]');
                                if (!$field.length && field.indexOf('.') !== -1) {
                                    $field = $form.find('[name="' + field.replace(/\./g, '[') + ']"]');
                                }
                                if ($field.length) {
                                    validator.showErrors({
                                        [field]: messages[0],
                                    });
                                }
                            });
                        }
                        notifyError(
                            xhr.responseJSON.message ||
                                'Please correct the highlighted fields.'
                        );
                        return;
                    }
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not save user.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
