/**
 * Generic AJAX submit for multi-line ERP documents (serialize form).
 * Supports dynamic line rows when #lineDocumentLinesBody is present.
 */
$(function () {
    var $form = $('#lineDocumentForm');
    if (!$form.length || !window.lineDocumentSubmitUrl || !window.lineDocumentRedirectUrl) {
        return;
    }

    initDynamicLineRows();

    $form.validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback d-block',
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function (form) {
            var $btn = $('#lineDocumentSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );

            $.ajax({
                url: window.lineDocumentSubmitUrl,
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
                    window.location.href = window.lineDocumentRedirectUrl;
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
                            : 'Could not save document.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});

/**
 * Add/remove line rows; re-indexes lines[n] field names after each change.
 */
function initDynamicLineRows() {
    var $body = $('#lineDocumentLinesBody');
    if (!$body.length) {
        return;
    }

    var maxLines = window.lineDocumentMaxLines || 30;

    /**
     * @param {jQuery} $row
     */
    function clearRow($row) {
        $row.find('select').val('');
        $row.find('input').val('');
        $row.find('.is-invalid').removeClass('is-invalid');
    }

    function reindexRows() {
        $body.find('tr').each(function (idx) {
            $(this)
                .find('[name^="lines["]')
                .each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/lines\[\d+]/, 'lines[' + idx + ']'));
                    }
                });
        });
        var count = $body.find('tr').length;
        $body.find('.js-remove-line').toggle(count > 1);
        $('#lineDocumentAddRow').prop('disabled', count >= maxLines);
    }

    $('#lineDocumentAddRow').on('click', function () {
        var count = $body.find('tr').length;
        if (count >= maxLines) {
            if (typeof notifyError === 'function') {
                notifyError('Maximum ' + maxLines + ' lines allowed.');
            }
            return;
        }
        var $row = $body.find('tr:first').clone();
        clearRow($row);
        $body.append($row);
        reindexRows();
    });

    $body.on('click', '.js-remove-line', function () {
        if ($body.find('tr').length <= 1) {
            return;
        }
        $(this).closest('tr').remove();
        reindexRows();
    });

    reindexRows();
}
