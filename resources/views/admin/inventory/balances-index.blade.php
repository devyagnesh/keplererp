<x-layouts.app title="Stock balances">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Stock balances</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inventory</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('inventory.adjust')
                <a href="{{ route('admin.inventory.adjust.form') }}" class="btn btn-outline-primary btn-wave">Adjust</a>
            @endcan
            @can('inventory.transfer')
                <a href="{{ route('admin.inventory.transfer.form') }}" class="btn btn-outline-primary btn-wave">Transfer</a>
            @endcan
            @can('reports.inventory')
                <a href="{{ route('admin.inventory.traceability.index') }}" class="btn btn-outline-info btn-wave">Batch traceability</a>
            @endcan
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header justify-content-between">
            <div class="card-title">On-hand by warehouse</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="balancesTable">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th>Name</th>
                            <th>Item</th>
                            <th>Qty</th>
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
                tableSelector: '#balancesTable',
                dataUrl: @json(route('admin.inventory.balances.data')),
                columns: [
                    { data: 'warehouse_code', name: 'warehouse_code' },
                    { data: 'warehouse_name', name: 'warehouse_name', orderable: false },
                    { data: 'item_name', name: 'item_name', orderable: false },
                    { data: 'quantity', name: 'quantity' },
                ],
                order: [[0, 'asc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
    @endpush
</x-layouts.app>
