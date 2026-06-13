/**
 * Save production order material consumption.
 */
$(function () {
    var $form = $('#woMaterialsForm');
    if (!$form.length || !window.woMaterialsUrl) {
        return;
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        var materials = [];
        $form.find('tr[data-material-id]').each(function () {
            materials.push({
                id: $(this).data('material-id'),
                actual_qty: $(this).find('.js-actual-qty').val(),
            });
        });

        $.post(window.woMaterialsUrl, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            materials: materials,
        }).done(function (r) {
            notifySuccess(r.message);
        }).fail(function (xhr) {
            notifyError(xhr.responseJSON?.message || 'Could not save materials.');
        });
    });
});
