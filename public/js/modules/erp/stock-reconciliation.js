/**
 * Stock reconciliation index and show page handlers.
 */
$(function () {
    var $createForm = $('#stockReconForm');
    if ($createForm.length && window.stockReconStoreUrl) {
        $createForm.on('submit', function (e) {
            e.preventDefault();
            var $btn = $createForm.find('[type="submit"]');
            $btn.prop('disabled', true);
            $.ajax({
                url: window.stockReconStoreUrl,
                type: 'POST',
                data: $createForm.serialize(),
                dataType: 'json',
            }).done(function (r) {
                notifySuccess(r.message);
                if (r.redirect) {
                    window.location.href = r.redirect;
                }
            }).fail(function (x) {
                notifyError(x.responseJSON?.message || 'Could not create reconciliation.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    }

    var $countsForm = $('#stockReconCountsForm');
    if ($countsForm.length && window.stockReconCountsUrl) {
        $countsForm.on('submit', function (e) {
            e.preventDefault();
            var lines = [];
            $('tr[data-line-id]').each(function () {
                lines.push({
                    id: $(this).data('line-id'),
                    physical_qty: $(this).find('.js-physical-qty').val(),
                    reason: $(this).find('.js-reason').val(),
                });
            });
            $.post(window.stockReconCountsUrl, {
                _token: $('meta[name="csrf-token"]').attr('content'),
                lines: lines,
            }).done(function (r) {
                notifySuccess(r.message);
                window.location.reload();
            }).fail(function (x) {
                notifyError(x.responseJSON?.message || 'Could not save counts.');
            });
        });
    }
});
