/**
 * Sales order pick list modal with barcode scanning.
 */
$(function () {
    var $modal = $('#soPickListModal');
    if (!$modal.length) {
        return;
    }

    var currentUrl = '';
    var currentConfirmUrl = '';
    var currentPdfUrl = '';
    var expectedSkus = [];

    $(document).on('click', '.js-so-pick-list', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        currentUrl = $(this).data('url');
        currentConfirmUrl = $(this).data('confirm-url');
        currentPdfUrl = $(this).data('pdf-url');
        if (!currentUrl) {
            return;
        }

        $.getJSON(currentUrl, function (payload) {
            if (!payload.status) {
                notifyError(payload.message || 'Could not load pick list.');
                return;
            }
            renderPickList(payload.data);
            $modal.modal('show');
        }).fail(function (xhr) {
            notifyError(xhr.responseJSON?.message || 'Could not load pick list.');
        });
    });

    /**
     * @param {Object} data
     */
    function renderPickList(data) {
        var $body = $modal.find('.js-pick-lines');
        $body.empty();
        expectedSkus = [];
        $.each(data.lines || [], function (_i, line) {
            expectedSkus.push((line.sku || '').toUpperCase());
            var status = parseFloat(line.on_hand || 0) >= parseFloat(line.quantity) ? 'OK' : 'SHORT';
            var $row = $('<tr></tr>');
            $row.append('<td>' + $('<div/>').text(line.item_label).html() + '<br><small class="text-muted">' + line.sku + '</small></td>');
            $row.append('<td class="text-end">' + line.quantity + '</td>');
            $row.append('<td class="text-end">' + line.on_hand + '</td>');
            $row.append('<td><span class="badge bg-' + (status === 'OK' ? 'success' : 'danger') + '-transparent">' + status + '</span></td>');
            $row.append('<td class="js-pick-check text-center">—</td>');
            $row.attr('data-sku', (line.sku || '').toUpperCase());
            $body.append($row);
        });
        $modal.find('.js-scanned-list').empty();
        $modal.find('.js-barcode-input').val('').focus();
    }

    $modal.find('.js-barcode-input').on('keypress', function (e) {
        if (e.which !== 13) {
            return;
        }
        e.preventDefault();
        var code = $(this).val().trim().toUpperCase();
        if (!code) {
            return;
        }
        $(this).val('');
        var $row = $bodyFindRow(code);
        if ($row.length) {
            $row.find('.js-pick-check').html('<span class="text-success">✓</span>');
            $modal.find('.js-scanned-list').append('<li class="list-group-item py-1">' + code + '</li>');
        } else {
            notifyWarning('Barcode not on pick list: ' + code);
        }
    });

    function $bodyFindRow(code) {
        return $modal.find('.js-pick-lines tr[data-sku="' + code + '"]').filter(function () {
            return $(this).find('.js-pick-check').text().trim() !== '✓';
        }).first();
    }

    $modal.find('.js-pick-confirm').on('click', function () {
        var scans = [];
        $modal.find('.js-scanned-list li').each(function () {
            scans.push($(this).text().trim());
        });
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(currentConfirmUrl, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            scanned_codes: scans,
            packaging_notes: $modal.find('[name="packaging_notes"]').val(),
        }).done(function (r) {
            notifySuccess(r.message);
            $modal.modal('hide');
            if (currentPdfUrl) {
                window.open(currentPdfUrl, '_blank');
            }
            var table = $('#soTable').DataTable();
            if (table) {
                table.ajax.reload(null, false);
            }
        }).fail(function (xhr) {
            notifyError(xhr.responseJSON?.message || 'Pick confirmation failed.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
