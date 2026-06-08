$(function () {
    $.get(window.deptDataUrl, function (r) {
        var $tb = $('#deptTable tbody').empty();
        (r.data || []).forEach(function (row) {
            $tb.append('<tr><td>' + row.code + '</td><td>' + row.name + '</td><td>' + (row.is_active ? 'Yes' : 'No') + '</td></tr>');
        });
    });
    $('#deptForm').on('submit', function (e) {
        e.preventDefault();
        $.post(window.deptStoreUrl, $(this).serialize())
            .done(function (r) { Notify.success(r.message); location.reload(); })
            .fail(function (x) { Notify.error(x.responseJSON?.message || 'Error'); });
    });
});
