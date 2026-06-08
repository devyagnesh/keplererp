/**
 * Inventory transfer POST (JSON).
 */
$(function () {
    var $form = $('#inventoryTransferForm');
    if (!$form.length) {
        return;
    }

    if (window.batchSerialTrackingMapUrl) {
        BatchSerial.loadTrackingMap(window.batchSerialTrackingMapUrl);
    }

    function refreshTransferFields() {
        var itemId = $form.find('[name="item_id"]').val();
        var warehouseId = $form.find('[name="from_warehouse_id"]').val();
        var flags = BatchSerial.trackingFor(itemId);
        $form.find('.js-batch-wrap').toggleClass('d-none', !flags.is_batch_tracked);
        $form.find('.js-serial-wrap').toggleClass('d-none', !flags.is_serial_tracked);
        if (flags.is_batch_tracked && warehouseId) {
            BatchSerial.loadOptions(
                $form.find('.js-batch-select'),
                window.batchSerialBatchesUrl,
                warehouseId,
                itemId,
                'batch_no',
                'batch_no'
            );
        }
        if (flags.is_serial_tracked && warehouseId) {
            BatchSerial.loadOptions(
                $form.find('.js-serial-select'),
                window.batchSerialSerialsUrl,
                warehouseId,
                itemId,
                'serial_no',
                'serial_no'
            );
        }
    }

    $form.on('change', '[name="item_id"], [name="from_warehouse_id"]', refreshTransferFields);

    $form.validate({
        rules: {
            from_warehouse_id: { required: true },
            to_warehouse_id: { required: true },
            item_id: { required: true },
            quantity: { required: true, number: true, min: 0.0001 },
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
            var $btn = $('#inventoryTransferSubmit');
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...'
            );

            $.ajax({
                url: window.inventoryTransferSubmitUrl,
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
                            : 'Could not transfer stock.';
                    notifyError(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                },
            });
        },
    });
});
