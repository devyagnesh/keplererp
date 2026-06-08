/**
 * GRN create — show batch/serial fields based on item tracking flags.
 */
$(function () {
    var $form = $('#lineDocumentForm');
    if (!$form.length || !window.batchSerialTrackingMapUrl) {
        return;
    }

    BatchSerial.loadTrackingMap(window.batchSerialTrackingMapUrl, function () {
        $form.find('tbody tr').each(function () {
            refreshGrnRow($(this));
        });
    });

    $form.on('change', 'select[name*="[item_id]"]', function () {
        refreshGrnRow($(this).closest('tr'));
    });

    /**
     * @param {jQuery} $row
     */
    function refreshGrnRow($row) {
        var itemId = $row.find('select[name*="[item_id]"]').val();
        var flags = BatchSerial.trackingFor(itemId);
        var $batchCell = $row.find('.js-batch-cell');
        var $serialCell = $row.find('.js-serial-cell');
        var $expiryCell = $row.find('.js-expiry-cell');
        BatchSerial.applyTrackingVisibility($batchCell, $serialCell, flags, 'inbound');
        $expiryCell.toggle(flags.is_batch_tracked);
        if (!flags.is_batch_tracked) {
            $expiryCell.find('input').val('');
        }
        if (flags.is_serial_tracked) {
            $row.find('input[name*="[quantity]"]').val('1').attr('max', '1');
        }
    }
});
