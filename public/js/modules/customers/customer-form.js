/**
 * Customer create/edit: validation, AJAX save, block/reactivate actions.
 */
$(function () {
    var $form = $('#customerForm');
    var method = window.customerFormMethod || 'POST';

    /**
     * POST JSON with CSRF for state-changing actions.
     *
     * @param {string} url
     * @param {function} onSuccess
     */
    function postJsonAction(url, onSuccess) {
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                if (response.message) {
                    notifySuccess(response.message);
                }
                if (typeof onSuccess === 'function') {
                    onSuccess();
                }
            },
            error: function (xhr) {
                var msg =
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Request failed.';
                notifyError(msg);
            },
        });
    }

    $(document).on('click', '.js-customer-block', function () {
        var url = $(this).data('url');
        if (!url || !window.confirm('Block this customer? They cannot be used on new sales documents.')) {
            return;
        }
        postJsonAction(url, function () {
            window.location.reload();
        });
    });

    $(document).on('click', '.js-customer-activate', function () {
        var url = $(this).data('url');
        if (!url || !window.confirm('Reactivate this customer?')) {
            return;
        }
        postJsonAction(url, function () {
            window.location.reload();
        });
    });

    if (!$form.length) {
        return;
    }

    $form.validate({
        onfocusout: function (element) {
            this.element(element);
        },
        rules: {
            name: { required: true, minlength: 2, maxlength: 255 },
            phone: { required: true, digits: true, minlength: 10, maxlength: 10 },
            email: { email: true, maxlength: 255 },
            address_line1: { required: true, minlength: 5, maxlength: 255 },
            city: { required: true, minlength: 2, maxlength: 100 },
            state_code: { required: true },
            pincode: { required: true, digits: true, minlength: 6, maxlength: 6 },
            gstin: { maxlength: 15 },
            pan: { maxlength: 10 },
            payment_terms: { maxlength: 100 },
            notes: { maxlength: 5000 },
        },
        messages: {
            phone: { digits: 'Enter a valid 10-digit mobile number.' },
            pincode: { digits: 'Pincode must be numeric.', minlength: 'Enter 6 digits.', maxlength: 'Enter 6 digits.' },
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
            var $btn = $('#customerSubmit');
            if (!$btn.length) {
                return;
            }
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            var fd = new FormData(form);
            if (method === 'PUT') {
                fd.append('_method', 'PUT');
            }

            $.ajax({
                url: window.customerFormSubmitUrl,
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
                    window.location.href = window.customersIndexUrl || '/admin/customers';
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var validator = $form.data('validator');
                        if (validator) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                var $field = $form.find('[name="' + field + '"]');
                                if ($field.length) {
                                    validator.showErrors({
                                        [field]: messages[0],
                                    });
                                }
                            });
                        }
                        notifyError(
                            xhr.responseJSON.message || 'Please correct the highlighted fields.'
                        );
                        return;
                    }
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not save customer.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
