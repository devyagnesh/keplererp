/**
 * Company master form: client validation and AJAX save.
 */
$(function () {
    var $form = $('#companyForm');
    if (!$form.length) {
        return;
    }

    if (!$('#companySubmit').length) {
        return;
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#financial_year_start', {
            dateFormat: 'Y-m-d',
            allowInput: true,
        });
    }

    var validator = $form.validate({
        onfocusout: function (element) {
            this.element(element);
        },
        rules: {
            company_name: { required: true, minlength: 3, maxlength: 255 },
            legal_name: { required: true, maxlength: 255 },
            gstin: { required: true, maxlength: 15 },
            pan: { required: true, maxlength: 10 },
            address_line1: { required: true, minlength: 5, maxlength: 255 },
            city: { required: true, minlength: 2, maxlength: 100 },
            state_code: { required: true },
            pincode: { required: true, digits: true, minlength: 6, maxlength: 6 },
            phone: { required: true, digits: true, minlength: 10, maxlength: 10 },
            email: { required: true, email: true, maxlength: 255 },
            logo: { extension: 'jpg|jpeg|png' },
            financial_year_start: { required: true, dateISO: true },
            currency: { required: true, maxlength: 3 },
            invoice_prefix: { required: true, maxlength: 20 },
            po_prefix: { required: true, maxlength: 20 },
            default_tax_type: { required: true },
        },
        messages: {
            company_name: { required: 'Company name is required.', minlength: 'Enter at least 3 characters.' },
            gstin: { required: 'GSTIN is required.' },
            pan: { required: 'PAN is required.' },
            pincode: { digits: 'Pincode must be numeric.', minlength: 'Enter 6 digits.', maxlength: 'Enter 6 digits.' },
            phone: { digits: 'Phone must be numeric.', minlength: 'Enter 10 digits.', maxlength: 'Enter 10 digits.' },
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
            error.addClass('invalid-feedback d-block');
            if (element.attr('name') === 'logo') {
                error.insertAfter(element.closest('.col-md-6'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {
            var $btn = $('#companySubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData(form),
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
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 600);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            var $field = $form.find('[name="' + field + '"]');
                            if ($field.length) {
                                validator.showErrors({
                                    [field]: messages[0],
                                });
                            }
                        });
                        notifyError('Please correct the highlighted fields.');
                        return;
                    }
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not save company settings.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
