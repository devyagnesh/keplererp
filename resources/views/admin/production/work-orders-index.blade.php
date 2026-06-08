<x-layouts.app title="Work orders">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Production work orders</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Work orders</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\ProductionOrder::class)
            <a href="{{ route('admin.production.work-orders.create') }}" class="btn btn-primary btn-wave">New WO</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="woTable">
                    <thead>
                        <tr>
                            <th>WO</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Start</th>
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
                tableSelector: '#woTable',
                dataUrl: @json(route('admin.production.work-orders.data')),
                columns: [
                    { data: 'wo_number', name: 'wo_number' },
                    { data: 'item', name: 'item', orderable: false },
                    { data: 'qty_planned', name: 'qty_planned' },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'planned_start', name: 'planned_start', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[5, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
    @endpush
</x-layouts.app>
