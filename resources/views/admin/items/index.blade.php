<x-layouts.app title="Items">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Items</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Items</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\Item::class)
            <a href="{{ route('admin.items.create') }}" class="btn btn-primary btn-wave">Add item</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-header justify-content-between">
            <div class="card-title">SKU master</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="itemsTable">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>UOM</th>
                            <th>Reorder</th>
                            <th>Tracking</th>
                            <th>Active</th>
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
                tableSelector: '#itemsTable',
                dataUrl: @json(route('admin.items.data')),
                deleteSelector: '.js-item-delete',
                columns: [
                    { data: 'sku', name: 'sku' },
                    { data: 'name', name: 'name' },
                    { data: 'uom', name: 'uom' },
                    { data: 'reorder_level', name: 'reorder_level' },
                    { data: 'is_active', name: 'is_active', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[6, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
    @endpush
</x-layouts.app>
