/**
 * Warehouse transfer create/show actions.
 */
$(function () {
    var $form = $('#warehouseTransferForm');
    if ($form.length) {
        $form.on('submit', function (e) {
            e.preventDefault();
            var $btn = $form.find('[type="submit"]');
            $btn.prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
            }).done(function (r) {
                notifySuccess(r.message);
                if (r.redirect) {
                    window.location.href = r.redirect;
                }
            }).fail(function (x) {
                notifyError(x.responseJSON?.message || 'Could not save transfer.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    }

    $(document).on('click', '.js-wt-approve, .js-wt-dispatch', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        if (!url) {
            return;
        }
        $btn.prop('disabled', true);
        $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') })
            .done(function (r) {
                notifySuccess(r.message);
                window.location.reload();
            })
            .fail(function (x) {
                notifyError(x.responseJSON?.message || 'Action failed.');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $('.js-wt-receive').on('click', function () {
        var url = $(this).data('url');
        var lines = [];
        $('tr[data-line-id]').each(function () {
            lines.push({
                id: $(this).data('line-id'),
                qty_received: $(this).find('.js-received-qty').val(),
                variance_reason: $(this).find('.js-variance-reason').val(),
            });
        });
        $.post(url, { _token: $('meta[name="csrf-token"]').attr('content'), lines: lines })
            .done(function (r) {
                notifySuccess(r.message);
                window.location.reload();
            })
            .fail(function (x) {
                notifyError(x.responseJSON?.message || 'Could not receive transfer.');
            });
    });
});
