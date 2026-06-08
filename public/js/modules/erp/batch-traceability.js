/**
 * Batch/serial traceability dashboard DataTables.
 */
$(function () {
    var cfg = window.batchTraceability;
    if (!cfg) {
        return;
    }

    function filterParams(extra) {
        var $f = $('#traceFilters');
        var data = {
            warehouse_id: $f.find('[name="warehouse_id"]').val(),
            item_id: $f.find('[name="item_id"]').val(),
            tracking: $f.find('[name="tracking"]').val(),
            date_from: $f.find('[name="date_from"]').val(),
            date_to: $f.find('[name="date_to"]').val(),
        };
        return $.extend(data, extra || {});
    }

    function dtAjax(url, extraFn) {
        return {
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                var extra = typeof extraFn === 'function' ? extraFn() : extraFn || {};
                $.extend(d, filterParams(extra));
            },
        };
    }

    var fefoTable = $('#fefoTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: dtAjax(cfg.fefoDataUrl),
        columns: [
            { data: 'warehouse_code' },
            { data: 'item_label' },
            { data: 'tracking', orderable: false },
            { data: 'batch_no' },
            { data: 'serial_no' },
            { data: 'on_hand' },
            { data: 'expiry_date' },
            { data: 'days_to_expiry', orderable: false },
            { data: 'status', orderable: false },
        ],
        order: [[6, 'asc']],
        pageLength: 25,
    });

    var expiryTable = $('#expiryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: dtAjax(cfg.expiryDataUrl, function () {
            return { expiry_status: $('#expiryStatusFilter').val() || 'all' };
        }),
        columns: [
            { data: 'warehouse_code' },
            { data: 'item_label' },
            { data: 'batch_no' },
            { data: 'on_hand' },
            { data: 'expiry_date' },
            { data: 'days_to_expiry', orderable: false },
            { data: 'status', orderable: false },
        ],
        order: [[4, 'asc']],
        pageLength: 25,
    });

    var historyTable = $('#historyTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: dtAjax(cfg.historyDataUrl),
        columns: [
            { data: 'created_at' },
            { data: 'warehouse', orderable: false },
            { data: 'item_label', orderable: false },
            { data: 'transaction_type' },
            { data: 'batch_no' },
            { data: 'serial_no' },
            { data: 'expiry_date', orderable: false },
            { data: 'quantity', orderable: false },
            { data: 'balance_qty', orderable: false },
            { data: 'reference', orderable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
    });

    $('#applyTraceFilters').on('click', function () {
        fefoTable.ajax.reload();
        expiryTable.ajax.reload();
        historyTable.ajax.reload();
    });

    $('#expiryStatusFilter').on('change', function () {
        expiryTable.ajax.reload();
    });

    $('button[data-bs-target="#tabHistory"]').on('shown.bs.tab', function () {
        $('.js-history-only').removeClass('d-none');
    });
    $('button[data-bs-target="#tabFefo"], button[data-bs-target="#tabExpiry"]').on('shown.bs.tab', function () {
        $('.js-history-only').addClass('d-none');
    });

    function buildExportUrl(base) {
        var q = filterParams();
        return (
            base +
            '?' +
            $.param({
                warehouse_id: q.warehouse_id,
                item_id: q.item_id,
                tracking: q.tracking,
                date_from: q.date_from,
                date_to: q.date_to,
            })
        );
    }

    $('#exportFefoBtn').on('click', function (e) {
        e.preventDefault();
        window.location.href = buildExportUrl(cfg.exportFefoUrl);
    });
    $('#exportHistoryBtn').on('click', function (e) {
        e.preventDefault();
        window.location.href = buildExportUrl(cfg.exportHistoryUrl);
    });
});
