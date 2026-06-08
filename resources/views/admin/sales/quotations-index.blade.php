<x-layouts.app title="Quotations">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Sales quotations</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quotations</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\SalesQuotation::class)
            <a href="{{ route('admin.sales.quotations.create') }}" class="btn btn-primary btn-wave">New quotation</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="qtTable">
                    <thead>
                        <tr>
                            <th>Quote</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    @endpush

    @push('scripts')
        <script>
            window.erpTableConfig = {
                tableSelector: '#qtTable',
                dataUrl: @json(route('admin.sales.quotations.data')),
                columns: [
                    { data: 'quote_number', name: 'quote_number' },
                    { data: 'customer', name: 'customer', orderable: false },
                    { data: 'quote_date', name: 'quote_date' },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[4, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script>
            $(function () {
                $(document).on('click', '.js-qt-send', function () {
                    var url = $(this).data('url');
                    if (!url || !window.confirm($(this).data('confirm') || 'Send quotation?')) { return; }
                    $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') })
                        .done(function (r) {
                            notifySuccess(r.message);
                            if (r.data && r.data.pdf_url) {
                                window.open(r.data.pdf_url, '_blank');
                            }
                            $('#qtTable').DataTable().ajax.reload(null, false);
                        })
                        .fail(function (xhr) { notifyError(xhr.responseJSON?.message || 'Failed'); });
                });
                $(document).on('click', '.js-qt-convert', function () {
                    var url = $(this).data('url');
                    var warehouseId = window.prompt('Warehouse ID to dispatch from?');
                    if (!url || !warehouseId) { return; }
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: { _token: $('meta[name="csrf-token"]').attr('content'), warehouse_id: warehouseId },
                        dataType: 'json',
                        success: function (r) {
                            Toastify({ text: r.message, style: { background: '#28a745' } }).showToast();
                            if (window.erpTableConfig && $.fn.DataTable.isDataTable(window.erpTableConfig.tableSelector)) {
                                $(window.erpTableConfig.tableSelector).DataTable().ajax.reload(null, false);
                            }
                        },
                        error: function (xhr) {
                            var r = xhr.responseJSON;
                            Toastify({ text: r && r.message ? r.message : 'Error', style: { background: '#dc3545' } }).showToast();
                        }
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>
