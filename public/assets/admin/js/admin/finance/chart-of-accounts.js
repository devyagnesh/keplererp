$(function () {
    $.get(window.coaDataUrl, function (r) {
        var $tb = $('#coaTable tbody').empty();
        (r.data || []).forEach(function (row) {
            $tb.append('<tr><td>' + row.account_code + '</td><td>' + row.account_name + '</td><td>' + row.account_type + '</td><td>' + (row.is_system ? 'Yes' : 'No') + '</td></tr>');
        });
    });
    $('#coaForm').on('submit', function (e) {
        e.preventDefault();
        $.post(window.coaStoreUrl, $(this).serialize())
            .done(function (r) { Notify.success(r.message); location.reload(); })
            .fail(function (x) { Notify.error(x.responseJSON?.message || 'Error'); });
    });
});
