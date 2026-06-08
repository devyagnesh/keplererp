/**
 * Login form client-side validation (focusout).
 */
$(function () {
    var $form = $('#loginForm');
    if (!$form.length) {
        return;
    }

    $form.validate({
        onfocusout: function (element) {
            this.element(element);
        },
        rules: {
            email: { required: true, email: true, maxlength: 255 },
            password: { required: true },
        },
        messages: {
            email: { required: 'Email is required.', email: 'Enter a valid email address.' },
            password: { required: 'Password is required.' },
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
            var name = $(element).attr('name');
            if (name === 'email') {
                $('#email-error').text('');
            } else if (name === 'password') {
                $('#password-error').text('');
            }
        },
        errorPlacement: function (error, element) {
            var name = element.attr('name');
            var $holder = name ? $('#' + name + '-error') : $();
            if ($holder.length) {
                $holder.text(error.text());
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {
            form.submit();
        },
    });
});
