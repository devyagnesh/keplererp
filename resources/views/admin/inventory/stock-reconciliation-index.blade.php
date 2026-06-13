<x-layouts.app title="Stock reconciliation">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Stock reconciliation</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reconciliation</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card mb-3">
        <div class="card-header"><div class="card-title mb-0">Start new reconciliation</div></div>
        <div class="card-body">
            <form id="stockReconForm" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">—</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" name="period_year" class="form-control" value="{{ now()->year }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Month</label>
                    <input type="number" name="period_month" class="form-control" value="{{ now()->month }}" min="1" max="12" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-wave w-100">Create draft</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="stockReconTable">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Warehouse</th>
                            <th>Period</th>
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
                tableSelector: '#stockReconTable',
                dataUrl: @json(route('admin.inventory.stock-reconciliations.data')),
                postActionSelector: '.js-sr-post',
                postConfirm: 'Post reconciliation and apply stock adjustments?',
                columns: [
                    { data: 'number', name: 'number' },
                    { data: 'warehouse', name: 'warehouse', orderable: false },
                    { data: 'period', name: 'period', orderable: false },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
            };
            window.stockReconStoreUrl = @json(route('admin.inventory.stock-reconciliations.store'));
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script src="{{ asset('js/modules/erp/stock-reconciliation.js') }}"></script>
    @endpush
</x-layouts.app>
