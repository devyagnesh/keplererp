/**
 * Server-side DataTable driven by {@link window.erpTableConfig}.
 *
 * @typedef {Object} ErpTableConfig
 * @property {string} tableSelector
 * @property {string} dataUrl
 * @property {Array<Object>} columns
 * @property {Array<Array<number|string>>} [order]
 * @property {Object} [extraAjaxData]
 * @property {string} [deleteSelector]
 * @property {string} [postActionSelector]
 */
$(function () {
    var cfg = window.erpTableConfig;
    if (!cfg || !cfg.tableSelector || !cfg.dataUrl || !cfg.columns) {
        return;
    }

    var $table = $(cfg.tableSelector);
    if (!$table.length || typeof $.fn.DataTable === 'undefined') {
        return;
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.dataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: function (d) {
                var extra = typeof cfg.extraAjaxData === 'function'
                    ? cfg.extraAjaxData()
                    : cfg.extraAjaxData;
                if (extra) {
                    $.extend(d, extra);
                }
            },
        },
        columns: cfg.columns,
        order: cfg.order || [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...',
            emptyTable: cfg.emptyTable || 'No records found.',
            zeroRecords: cfg.zeroRecords || 'No matching records found.',
        },
    });

    if (cfg.deleteSelector) {
        $(document).on('click', cfg.deleteSelector, function () {
            var url = $(this).data('url');
            if (!url || !window.confirm('Delete this record?')) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (response.message) {
                        notifySuccess(response.message);
                    }
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not delete.';
                    notifyError(msg);
                },
            });
        });
    }

    if (cfg.postActionSelector) {
        $(document).on('click', cfg.postActionSelector, function () {
            var url = $(this).data('url');
            var confirmMsg = $(this).data('confirm') || cfg.postConfirm || 'Continue?';
            if (!url || !window.confirm(confirmMsg)) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (response.message) {
                        notifySuccess(response.message);
                    }
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Request failed.';
                    notifyError(msg);
                },
            });
        });
    }
});
