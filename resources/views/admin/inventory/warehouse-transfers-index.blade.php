<x-layouts.app title="Warehouse transfers">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Warehouse transfers</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Transfers</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\WarehouseTransfer::class)
            <a href="{{ route('admin.inventory.warehouse-transfers.create') }}" class="btn btn-primary btn-wave">New transfer</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="warehouseTransfersTable">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>From</th>
                            <th>To</th>
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
                tableSelector: '#warehouseTransfersTable',
                dataUrl: @json(route('admin.inventory.warehouse-transfers.data')),
                postActionSelector: '.js-wt-approve, .js-wt-dispatch',
                columns: [
                    { data: 'transfer_number', name: 'transfer_number' },
                    { data: 'from', name: 'from', orderable: false },
                    { data: 'to', name: 'to', orderable: false },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
    @endpush
</x-layouts.app>
