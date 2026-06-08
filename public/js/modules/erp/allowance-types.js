/**
 * Allowance types listing and create.
 */
$(function () {
    if (!window.allowanceTypeDataUrl) {
        return;
    }

    function loadRows() {
        $.get(window.allowanceTypeDataUrl, function (r) {
            var $tb = $('#allowanceTypeTable tbody').empty();
            (r.data || []).forEach(function (row) {
                var esi = row.included_in_esi_gross ? 'Yes' : 'No';
                var active = row.is_active ? 'Yes' : 'No';
                $tb.append(
                    '<tr><td>' +
                        row.code +
                        '</td><td>' +
                        row.name +
                        '</td><td>' +
                        row.sort_order +
                        '</td><td>' +
                        esi +
                        '</td><td>' +
                        active +
                        '</td></tr>'
                );
            });
        });
    }

    loadRows();

    $('#allowanceTypeForm').on('submit', function (e) {
        e.preventDefault();
        $.post(window.allowanceTypeStoreUrl, $(this).serialize())
            .done(function (r) {
                if (typeof notifySuccess === 'function') {
                    notifySuccess(r.message);
                }
                $('#allowanceTypeForm')[0].reset();
                $('#esiGross').prop('checked', true);
                loadRows();
            })
            .fail(function (x) {
                var msg = x.responseJSON && x.responseJSON.message ? x.responseJSON.message : 'Error';
                if (typeof notifyError === 'function') {
                    notifyError(msg);
                }
            });
    });
});
