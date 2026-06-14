/**
 * Sales invoice listing — status filter reload.
 */
$(function () {
    $('#filterInvoiceStatus').on('change', function () {
        var table = $('#invoiceTable').DataTable();
        if (table) {
            table.ajax.reload(null, false);
        }
    });
});
