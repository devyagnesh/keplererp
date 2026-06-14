/**
 * Audit log listing — action filter reload.
 */
$(function () {
    $('#filterAuditAction').on('change', function () {
        var table = $('#auditLogTable').DataTable();
        if (table) {
            table.ajax.reload(null, false);
        }
    });
});
