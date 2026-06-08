<x-layouts.app title="Purchase orders">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Purchase orders</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">PO</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\PurchaseOrder::class)
            <a href="{{ route('admin.purchase.orders.create') }}" class="btn btn-primary btn-wave">New PO</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="poTable">
                    <thead>
                        <tr>
                            <th>PO</th>
                            <th>Vendor</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
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
                tableSelector: '#poTable',
                dataUrl: @json(route('admin.purchase.orders.data')),
                postActionSelector: '.js-po-approve,.js-po-finance-approve,.js-po-mark-sent',
                columns: [
                    { data: 'po_number', name: 'po_number' },
                    { data: 'vendor', name: 'vendor', orderable: false },
                    { data: 'order_date', name: 'order_date' },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'total_amount', name: 'total_amount', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[5, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script>
            $(function () {
                $(document).on('click', '.js-po-reject', function () {
                    var url = $(this).data('url');
                    if (!url) {
                        return;
                    }
                    var reason = window.prompt('Reason for rejection?');
                    if (!reason) {
                        return;
                    }
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            rejected_reason: reason,
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        success: function (response) {
                            if (response.message) {
                                notifySuccess(response.message);
                            }
                            var $tbl = $('#poTable');
                            if ($tbl.length && $.fn.DataTable && $tbl.DataTable) {
                                $tbl.DataTable().ajax.reload(null, false);
                            }
                        },
                        error: function (xhr) {
                            var msg =
                                xhr.responseJSON && xhr.responseJSON.message
                                    ? xhr.responseJSON.message
                                    : 'Could not reject.';
                            notifyError(msg);
                        },
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>
