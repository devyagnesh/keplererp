<x-layouts.app title="Batch / serial traceability">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Batch / serial traceability</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.balances.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Traceability</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.inventory.traceability.export-fefo') }}" class="btn btn-outline-secondary btn-wave"
                id="exportFefoBtn">Export FEFO CSV</a>
            <a href="{{ route('admin.inventory.traceability.export-history') }}" class="btn btn-outline-secondary btn-wave"
                id="exportHistoryBtn">Export history CSV</a>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-5 g-3 mb-4">
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Tracked SKUs</div>
                    <div class="fs-20 fw-semibold">{{ $summary['tracked_items'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Batches on hand</div>
                    <div class="fs-20 fw-semibold">{{ $summary['batches_on_hand'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Serials on hand</div>
                    <div class="fs-20 fw-semibold">{{ $summary['serials_on_hand'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0 border-danger">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Expired batches</div>
                    <div class="fs-20 fw-semibold text-danger">{{ $summary['expired_batches'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card mb-0 border-warning">
                <div class="card-body py-3">
                    <div class="text-muted fs-12">Expiring ≤ {{ $expiryWarnDays }} days</div>
                    <div class="fs-20 fw-semibold text-warning">{{ $summary['expiring_soon'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card mb-3">
        <div class="card-body">
            <form id="traceFilters" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item (tracked)</label>
                    <select name="item_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($items as $it)
                            <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tracking</label>
                    <select name="tracking" class="form-select">
                        <option value="">All</option>
                        <option value="batch">Batch only</option>
                        <option value="serial">Serial only</option>
                    </select>
                </div>
                <div class="col-md-2 js-history-only d-none">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control"
                        value="{{ now()->subMonth()->toDateString() }}">
                </div>
                <div class="col-md-2 js-history-only d-none">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-wave" id="applyTraceFilters">Apply filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabFefo" type="button">FEFO on-hand</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabExpiry" type="button">Expiry alerts</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHistory" type="button">Movement history</button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content">
            <div class="tab-pane fade show active" id="tabFefo">
                <p class="text-muted fs-13">On-hand batches and serials sorted by earliest expiry first (FEFO).</p>
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap w-100" id="fefoTable">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Item</th>
                                <th>Tracking</th>
                                <th>Batch</th>
                                <th>Serial</th>
                                <th>On hand</th>
                                <th>Expiry</th>
                                <th>Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="tabExpiry">
                <p class="text-muted fs-13">Batches that are expired or expiring within {{ $expiryWarnDays }} days.</p>
                <div class="mb-2">
                    <select id="expiryStatusFilter" class="form-select form-select-sm w-auto d-inline-block">
                        <option value="all">All alerts</option>
                        <option value="expired">Expired only</option>
                        <option value="expiring">Expiring soon only</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap w-100" id="expiryTable">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Item</th>
                                <th>Batch</th>
                                <th>On hand</th>
                                <th>Expiry</th>
                                <th>Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="tabHistory">
                <p class="text-muted fs-13">Immutable stock ledger rows with batch or serial numbers.</p>
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap w-100" id="historyTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Warehouse</th>
                                <th>Item</th>
                                <th>Transaction</th>
                                <th>Batch</th>
                                <th>Serial</th>
                                <th>Expiry</th>
                                <th>Qty</th>
                                <th>Balance</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    @endpush

    @push('scripts')
        <script>
            window.batchTraceability = {
                fefoDataUrl: @json(route('admin.inventory.traceability.fefo-data')),
                expiryDataUrl: @json(route('admin.inventory.traceability.expiry-data')),
                historyDataUrl: @json(route('admin.inventory.traceability.history-data')),
                exportFefoUrl: @json(route('admin.inventory.traceability.export-fefo')),
                exportHistoryUrl: @json(route('admin.inventory.traceability.export-history')),
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/batch-traceability.js') }}"></script>
    @endpush
</x-layouts.app>
