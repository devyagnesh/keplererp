<x-layouts.app title="Stock transfer">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Stock transfer</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.balances.index') }}">Balances</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Transfer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body">
                    <form id="inventoryTransferForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">From warehouse</label>
                            <select name="from_warehouse_id" class="form-select" required>
                                <option value="">—</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To warehouse</label>
                            <select name="to_warehouse_id" class="form-select" required>
                                <option value="">—</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item</label>
                            <select name="item_id" class="form-select" required>
                                <option value="">—</option>
                                @foreach ($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.0001" name="quantity" class="form-control" required min="0.0001">
                        </div>
                        <div class="mb-3 js-batch-wrap d-none">
                            <label class="form-label">Batch no</label>
                            <select name="batch_no" class="form-select js-batch-select">
                                <option value="">—</option>
                            </select>
                        </div>
                        <div class="mb-3 js-serial-wrap d-none">
                            <label class="form-label">Serial no</label>
                            <select name="serial_no" class="form-select js-serial-select">
                                <option value="">—</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" id="inventoryTransferSubmit">Transfer</button>
                        <a href="{{ route('admin.inventory.balances.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.inventoryTransferSubmitUrl = @json(route('admin.inventory.transfer'));
            window.inventoryBalancesUrl = @json(route('admin.inventory.balances.index'));
        </script>
        <script>
            window.batchSerialTrackingMapUrl = @json(route('admin.inventory.tracking-map'));
            window.batchSerialBatchesUrl = @json(url('/admin/inventory/warehouses/{warehouse}/items/{item}/batches'));
            window.batchSerialSerialsUrl = @json(url('/admin/inventory/warehouses/{warehouse}/items/{item}/serials'));
        </script>
        <script src="{{ asset('js/modules/erp/batch-serial.js') }}"></script>
        <script src="{{ asset('js/modules/erp/inventory-transfer-form.js') }}"></script>
    @endpush
</x-layouts.app>
