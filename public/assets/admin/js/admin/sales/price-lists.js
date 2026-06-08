$(function () {
    function loadItems() {
        var id = $('#plSelect').val();
        if (!id) { return; }
        $.get(window.plItemUrlBase + '/' + id + '/items', function (r) {
            var $tb = $('#pliTable tbody').empty();
            (r.data || []).forEach(function (row) {
                $tb.append('<tr><td colspan="2">' + (row.item_label || '—') + '</td><td>' + row.unit_price + '</td></tr>');
            });
        });
    }
    loadItems();
    $('#plSelect').on('change', loadItems);
    $('#plForm').on('submit', function (e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize())
            .done(function (r) { Notify.success(r.message); location.reload(); })
            .fail(function (x) { Notify.error(x.responseJSON?.message || 'Error'); });
    });
    $('#pliForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#plSelect').val();
        $.post(window.plItemUrlBase + '/' + id + '/items', $(this).serialize())
            .done(function (r) { Notify.success(r.message); loadItems(); })
            .fail(function (x) { Notify.error(x.responseJSON?.message || 'Error'); });
    });
});
