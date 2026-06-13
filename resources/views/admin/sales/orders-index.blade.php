<x-layouts.app title="Sales orders">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Sales orders</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Orders</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\SalesOrder::class)
            <a href="{{ route('admin.sales.orders.create') }}" class="btn btn-primary btn-wave">New order</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="soTable">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Dispatched</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="soPickListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pick list — scan barcodes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Scan SKU barcode</label>
                        <input type="text" class="form-control js-barcode-input" placeholder="Scan or type SKU and press Enter" autocomplete="off">
                    </div>
                    <ul class="list-group list-group-flush mb-3 js-scanned-list"></ul>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Stock</th>
                                    <th>Status</th>
                                    <th>Picked</th>
                                </tr>
                            </thead>
                            <tbody class="js-pick-lines"></tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Packaging notes</label>
                        <input type="text" name="packaging_notes" class="form-control" maxlength="500" placeholder="e.g. 10 boxes × 50 pcs">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary js-pick-confirm">Confirm pick</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="soDispatchModal" tabindex="-1" aria-labelledby="soDispatchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="soDispatchModalLabel">Dispatch — batch / serial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted fs-13">Allocate batch or serial numbers for tracked lines before stock is deducted.</p>
                    <form class="js-dispatch-form">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th>Batch</th>
                                        <th>Serial</th>
                                    </tr>
                                </thead>
                                <tbody class="js-dispatch-lines"></tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success js-dispatch-submit">Dispatch</button>
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
            window.erpTableConfig = {
                tableSelector: '#soTable',
                dataUrl: @json(route('admin.sales.orders.data')),
                postActionSelector: '.js-so-invoice,.js-so-process,.js-so-suggest-wo',
                columns: [
                    { data: 'order_number', name: 'order_number' },
                    { data: 'customer', name: 'customer', orderable: false },
                    { data: 'order_date', name: 'order_date' },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'total_amount', name: 'total_amount', orderable: false },
                    { data: 'dispatched_at', name: 'dispatched_at', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[2, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script>
            window.batchSerialBatchesUrl = @json(url('/admin/inventory/warehouses/{warehouse}/items/{item}/batches'));
            window.batchSerialSerialsUrl = @json(url('/admin/inventory/warehouses/{warehouse}/items/{item}/serials'));
        </script>
        <script src="{{ asset('js/modules/erp/batch-serial.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script src="{{ asset('js/modules/erp/sales-order-dispatch.js') }}"></script>
        <script src="{{ asset('js/modules/erp/sales-order-pick-list.js') }}"></script>
    @endpush
</x-layouts.app>
