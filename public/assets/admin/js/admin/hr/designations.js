$(function () {
    $.get(window.desigDataUrl, function (r) {
        var $tb = $('#desigTable tbody').empty();
        (r.data || []).forEach(function (row) {
            $tb.append('<tr><td>' + row.code + '</td><td>' + row.name + '</td></tr>');
        });
    });
    $('#desigForm').on('submit', function (e) {
        e.preventDefault();
        $.post(window.desigStoreUrl, $(this).serialize())
            .done(function (r) { Notify.success(r.message); location.reload(); })
            .fail(function (x) { Notify.error(x.responseJSON?.message || 'Error'); });
    });
});
