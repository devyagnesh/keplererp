/**
 * GRN returns list and create form with batch selection from GRN lines.
 */
$(function () {
    $('#grnReturnTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.grnReturnDataUrl,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        },
        columns: [
            { data: 'return_number' },
            { data: 'grn', orderable: false },
            { data: 'vendor', orderable: false },
            { data: 'status' },
            { data: 'posted_at' },
        ],
        order: [[0, 'desc']],
    });

    if (window.batchSerialTrackingMapUrl) {
        BatchSerial.loadTrackingMap(window.batchSerialTrackingMapUrl);
    }

    var grnLinesByItem = {};

    $('#grnSelect').on('change', function () {
        var $opt = $(this).find('option:selected');
        var raw = $opt.data('lines');
        var lines = typeof raw === 'string' ? JSON.parse(raw) : raw || [];
        grnLinesByItem = {};
        var $item = $('#grnReturnItem');
        $item.empty().append('<option value="">—</option>');
        $.each(lines, function (_i, line) {
            var itemId = String(line.item_id);
            if (!grnLinesByItem[itemId]) {
                grnLinesByItem[itemId] = [];
                $item.append(
                    $('<option></option>')
                        .val(itemId)
                        .text(
                            line.item
                                ? line.item.display_label ||
                                      line.item.name + ' (' + line.item.sku + ')'
                                : 'Item ' + itemId
                        )
                );
            }
            if (line.batch_no) {
                grnLinesByItem[itemId].push(String(line.batch_no));
            }
        });
        $('#grnReturnBatch').html('<option value="">—</option>');
        $('.js-grn-return-batch').addClass('d-none');
    });

    $('#grnReturnItem').on('change', function () {
        var itemId = $(this).val();
        var flags = BatchSerial.trackingFor(itemId);
        var $batchWrap = $('.js-grn-return-batch');
        var $batch = $('#grnReturnBatch');
        $batch.html('<option value="">—</option>');
        if (!flags.is_batch_tracked || !itemId) {
            $batchWrap.addClass('d-none');
            return;
        }
        $batchWrap.removeClass('d-none');
        var batches = grnLinesByItem[itemId] || [];
        $.each(batches, function (_i, batchNo) {
            $batch.append($('<option></option>').val(batchNo).text(batchNo));
        });
    });

    $('#grnReturnForm').validate({
        submitHandler: function (form) {
            var $btn = $(form).find('[type="submit"]');
            $btn.prop('disabled', true);
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (r) {
                    if (typeof notifySuccess === 'function') {
                        notifySuccess(r.message);
                    } else if (typeof Notify !== 'undefined') {
                        Notify.success(r.message);
                    }
                    $('#grnReturnTable').DataTable().ajax.reload(null, false);
                    form.reset();
                    $('#grnReturnItem').html('<option value="">Select GRN first</option>');
                    $('.js-grn-return-batch').addClass('d-none');
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || 'Failed';
                    if (typeof notifyError === 'function') {
                        notifyError(msg);
                    } else if (typeof Notify !== 'undefined') {
                        Notify.error(msg);
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                },
            });
        },
    });
});
