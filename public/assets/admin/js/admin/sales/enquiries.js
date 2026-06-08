$(function () {
    $('#enquiryTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: window.enquiryDataUrl, type: 'POST', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } },
        columns: [
            { data: 'enquiry_number' }, { data: 'customer', orderable: false },
            { data: 'contact_name' }, { data: 'phone' }, { data: 'status' },
        ],
    });
    $('#enquiryForm').validate({
        submitHandler: function (form) {
            $.post($(form).attr('action'), $(form).serialize())
                .done(function (r) { Notify.success(r.message); $('#enquiryTable').DataTable().ajax.reload(null, false); form.reset(); })
                .fail(function (x) { Notify.error(x.responseJSON?.message || 'Error'); });
        },
    });
});
