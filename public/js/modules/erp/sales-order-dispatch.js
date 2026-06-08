/**
 * Sales order dispatch with batch/serial allocation modal.
 */
$(function () {
    var $modal = $('#soDispatchModal');
    if (!$modal.length) {
        return;
    }

    var dispatchDataUrlTemplate = window.soDispatchDataUrlTemplate || '';
    var batchesUrlTemplate = window.batchSerialBatchesUrl || '';
    var serialsUrlTemplate = window.batchSerialSerialsUrl || '';

    $(document).on('click', '.js-so-dispatch', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var dispatchUrl = $(this).data('url');
        var dataUrl = $(this).data('dispatch-data-url');
        if (!dispatchUrl || !dataUrl) {
            return;
        }

        $.getJSON(dataUrl, function (payload) {
            if (!payload.status) {
                notifyError(payload.message || 'Could not load dispatch data.');
                return;
            }
            renderModal(payload.data);
            $modal.data('dispatch-url', dispatchUrl);
            $modal.modal('show');
        }).fail(function (xhr) {
            notifyError(xhr.responseJSON?.message || 'Could not load dispatch data.');
        });
    });

    /**
     * @param {Object} data
     */
    function renderModal(data) {
        var $body = $modal.find('.js-dispatch-lines');
        $body.empty();
        var warehouseId = data.warehouse_id;

        $.each(data.lines || [], function (_i, line) {
            var needsAlloc = line.is_batch_tracked || line.is_serial_tracked;
            var $row = $('<tr></tr>').attr('data-line-id', line.id).attr('data-item-id', line.item_id);
            $row.append('<td>' + $('<div/>').text(line.item_label || '—').html() + '</td>');
            $row.append('<td class="text-end">' + line.quantity + '</td>');

            var $batchCell = $('<td class="js-batch-cell"></td>');
            var $serialCell = $('<td class="js-serial-cell"></td>');
            if (needsAlloc) {
                $batchCell.append(
                    '<input type="hidden" name="lines[' +
                        _i +
                        '][line_id]" value="' +
                        line.id +
                        '">'
                );
                if (line.is_batch_tracked) {
                    var $batchSelect = $(
                        '<select class="form-select form-select-sm js-batch-select" name="lines[' +
                            _i +
                            '][batch_no]"><option value="">—</option></select>'
                    );
                    $.each(line.batches || [], function (_b, batch) {
                        $batchSelect.append(
                            $('<option></option>')
                                .val(batch.batch_no)
                                .text(batch.batch_no + ' (' + batch.on_hand + ')')
                        );
                    });
                    $batchCell.append($batchSelect);
                }
                if (line.is_serial_tracked) {
                    var $serialSelect = $(
                        '<select class="form-select form-select-sm js-serial-select" name="lines[' +
                            _i +
                            '][serial_no]"><option value="">—</option></select>'
                    );
                    $.each(line.serials || [], function (_s, serial) {
                        $serialSelect.append(
                            $('<option></option>').val(serial.serial_no).text(serial.serial_no)
                        );
                    });
                    $serialCell.append($serialSelect);
                }
            } else {
                $batchCell.text('—');
                $serialCell.text('—');
            }
            $row.append($batchCell).append($serialCell);
            $body.append($row);
        });
    }

    $modal.find('.js-dispatch-submit').on('click', function () {
        var url = $modal.data('dispatch-url');
        var $btn = $(this);
        var original = $btn.html();
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1"></span> Dispatching...'
        );

        var formData = $modal.find('.js-dispatch-form').serializeArray();
        var data = { _token: $('meta[name="csrf-token"]').attr('content') };
        $.each(formData, function (_i, field) {
            data[field.name] = field.value;
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            success: function (response) {
                if (response.message) {
                    notifySuccess(response.message);
                }
                $modal.modal('hide');
                var table = $('#soTable').DataTable();
                if (table) {
                    table.ajax.reload(null, false);
                }
            },
            error: function (xhr) {
                notifyError(xhr.responseJSON?.message || 'Dispatch failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(original);
            },
        });
    });
});
