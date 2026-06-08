/**
 * Batch/serial field helpers for GRN, adjust, transfer, dispatch, and GRN returns.
 */
window.BatchSerial = (function () {
    var trackingMap = {};

    /**
     * @param {string} url
     * @param {Function} [done]
     */
    function loadTrackingMap(url, done) {
        $.getJSON(url, function (response) {
            trackingMap = (response && response.items) || {};
            if (typeof done === 'function') {
                done();
            }
        });
    }

    /**
     * @param {number|string} itemId
     * @returns {{is_batch_tracked: boolean, is_serial_tracked: boolean}}
     */
    function trackingFor(itemId) {
        var key = String(itemId);
        return trackingMap[key] || { is_batch_tracked: false, is_serial_tracked: false };
    }

    /**
     * @param {jQuery} $batchCell
     * @param {jQuery} $serialCell
     * @param {{is_batch_tracked: boolean, is_serial_tracked: boolean}} flags
     * @param {'inbound'|'outbound'} mode
     */
    function applyTrackingVisibility($batchCell, $serialCell, flags, mode) {
        var $batchInput = $batchCell.find('input, select').first();
        var $serialInput = $serialCell.find('input, select').first();
        $batchCell.toggle(flags.is_batch_tracked);
        $serialCell.toggle(flags.is_serial_tracked);
        if (!flags.is_batch_tracked) {
            $batchInput.val('');
        }
        if (!flags.is_serial_tracked) {
            $serialInput.val('');
        }
        if (flags.is_serial_tracked && $batchInput.is('input')) {
            $batchInput.prop('readonly', true);
        } else {
            $batchInput.prop('readonly', false);
        }
        var batchLabel = mode === 'outbound' ? 'Batch (select)' : 'Batch no';
        var serialLabel = mode === 'outbound' ? 'Serial (select)' : 'Serial no';
        $batchCell.find('.js-bs-label').text(batchLabel);
        $serialCell.find('.js-bs-label').text(serialLabel);
    }

    /**
     * @param {jQuery} $select
     * @param {string} urlTemplate warehouse and item placeholders {warehouse} {item}
     * @param {number|string} warehouseId
     * @param {number|string} itemId
     * @param {string} valueKey batch_no|serial_no
     * @param {string} labelKey
     */
    function loadOptions($select, urlTemplate, warehouseId, itemId, valueKey, labelKey) {
        if (!warehouseId || !itemId) {
            $select.html('<option value="">—</option>');
            return;
        }
        var url = urlTemplate
            .replace('{warehouse}', String(warehouseId))
            .replace('{item}', String(itemId));
        $.getJSON(url, function (response) {
            var rows = (response && response.data) || [];
            var html = '<option value="">—</option>';
            $.each(rows, function (_i, row) {
                var val = row[valueKey];
                var label = row[labelKey] || val;
                if (row.on_hand !== undefined) {
                    label = val + ' (' + row.on_hand + ')';
                }
                if (row.expiry_date) {
                    label += ' exp ' + row.expiry_date;
                }
                html += '<option value="' + $('<div/>').text(val).html() + '">' + $('<div/>').text(label).html() + '</option>';
            });
            $select.html(html);
        });
    }

    /**
     * Upgrade text inputs to selects for outbound rows.
     *
     * @param {jQuery} $row
     * @param {number|string} warehouseId
     * @param {{is_batch_tracked: boolean, is_serial_tracked: boolean}} flags
     * @param {string} batchesUrl
     * @param {string} serialsUrl
     */
    function refreshOutboundRow($row, warehouseId, flags, batchesUrl, serialsUrl) {
        var itemId = $row.find('[name*="[item_id]"], select[name="item_id"], [data-item-id]').first().val();
        if (!itemId) {
            itemId = $row.data('item-id');
        }
        var $batchCell = $row.find('.js-batch-cell');
        var $serialCell = $row.find('.js-serial-cell');
        applyTrackingVisibility($batchCell, $serialCell, flags, 'outbound');

        if (flags.is_batch_tracked) {
            var $batch = $batchCell.find('select.js-batch-select');
            if (!$batch.length) {
                var name = $batchCell.find('input').attr('name') || 'batch_no';
                $batchCell.html(
                    '<label class="form-label fs-12 js-bs-label">Batch (select)</label>' +
                        '<select class="form-select js-batch-select" name="' +
                        name +
                        '"><option value="">—</option></select>'
                );
                $batch = $batchCell.find('select.js-batch-select');
            }
            loadOptions($batch, batchesUrl, warehouseId, itemId, 'batch_no', 'batch_no');
        }

        if (flags.is_serial_tracked) {
            var $serial = $serialCell.find('select.js-serial-select');
            if (!$serial.length) {
                var sName = $serialCell.find('input').attr('name') || 'serial_no';
                $serialCell.html(
                    '<label class="form-label fs-12 js-bs-label">Serial (select)</label>' +
                        '<select class="form-select js-serial-select" name="' +
                        sName +
                        '"><option value="">—</option></select>'
                );
                $serial = $serialCell.find('select.js-serial-select');
            }
            loadOptions($serial, serialsUrl, warehouseId, itemId, 'serial_no', 'serial_no');
        }
    }

    /**
     * @param {jQuery} $row
     * @param {number|string} warehouseId
     * @param {string} batchesUrl
     * @param {string} serialsUrl
     */
    function onRowItemChange($row, warehouseId, batchesUrl, serialsUrl) {
        var itemId = $row.find('select[name*="[item_id]"]').val();
        var flags = trackingFor(itemId);
        refreshOutboundRow($row, warehouseId, flags, batchesUrl, serialsUrl);
    }

    return {
        loadTrackingMap: loadTrackingMap,
        trackingFor: trackingFor,
        applyTrackingVisibility: applyTrackingVisibility,
        refreshOutboundRow: refreshOutboundRow,
        onRowItemChange: onRowItemChange,
        loadOptions: loadOptions,
    };
})();
