/**
 * Inventory adjustment POST (JSON).
 */
$(function () {
    var $form = $('#inventoryAdjustForm');
    if (!$form.length) {
        return;
    }

    if (window.batchSerialTrackingMapUrl) {
        BatchSerial.loadTrackingMap(window.batchSerialTrackingMapUrl);
    }

    function refreshAdjustFields() {
        var itemId = $form.find('[name="item_id"]').val();
        var warehouseId = $form.find('[name="warehouse_id"]').val();
        var delta = parseFloat($form.find('[name="signed_delta"]').val() || '0');
        var flags = BatchSerial.trackingFor(itemId);
        var outbound = delta < 0;
        $form.find('.js-batch-wrap').toggleClass('d-none', !flags.is_batch_tracked);
        $form.find('.js-serial-wrap').toggleClass('d-none', !flags.is_serial_tracked);
        if (outbound && flags.is_batch_tracked && warehouseId && window.batchSerialBatchesUrl) {
            var $batch = $form.find('.js-batch-select');
            if (!$batch.length) {
                $batch = $('<select class="form-select js-batch-select" name="batch_no"></select>');
                $form.find('.js-batch-wrap .js-batch-input').replaceWith($batch);
            }
            BatchSerial.loadOptions($batch, window.batchSerialBatchesUrl, warehouseId, itemId, 'batch_no', 'batch_no');
        }
        if (outbound && flags.is_serial_tracked && warehouseId && window.batchSerialSerialsUrl) {
            var $serial = $form.find('.js-serial-select');
            if (!$serial.length) {
                $serial = $('<select class="form-select js-serial-select" name="serial_no"></select>');
                $form.find('.js-serial-wrap .js-serial-input').replaceWith($serial);
            }
            BatchSerial.loadOptions($serial, window.batchSerialSerialsUrl, warehouseId, itemId, 'serial_no', 'serial_no');
        }
    }

    $form.on('change', '[name="item_id"], [name="warehouse_id"], [name="signed_delta"]', refreshAdjustFields);

    $form.validate({
        rules: {
            warehouse_id: { required: true },
            item_id: { required: true },
            signed_delta: { required: true, number: true },
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
            var $btn = $('#inventoryAdjustSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...'
            );

            $.ajax({
                url: window.inventoryAdjustSubmitUrl,
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
                    window.location.href = window.inventoryBalancesUrl;
                },
                error: function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not adjust stock.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
