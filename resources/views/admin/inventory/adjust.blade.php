<x-layouts.app title="Stock adjustment">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Stock adjustment</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.balances.index') }}">Balances</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Adjust</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body">
                    <p class="text-muted fs-13">Use a <strong>positive</strong> delta to increase stock and a
                        <strong>negative</strong> value to decrease.</p>
                    <form id="inventoryAdjustForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id" class="form-select" required>
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
                            <label class="form-label">Quantity delta</label>
                            <input type="number" step="0.0001" name="signed_delta" class="form-control" required>
                        </div>
                        <div class="mb-3 js-batch-wrap d-none">
                            <label class="form-label">Batch no</label>
                            <input type="text" name="batch_no" class="form-control js-batch-input" maxlength="50">
                        </div>
                        <div class="mb-3 js-serial-wrap d-none">
                            <label class="form-label">Serial no</label>
                            <input type="text" name="serial_no" class="form-control js-serial-input" maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" id="inventoryAdjustSubmit">Apply</button>
                        <a href="{{ route('admin.inventory.balances.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.inventoryAdjustSubmitUrl = @json(route('admin.inventory.adjust'));
            window.inventoryBalancesUrl = @json(route('admin.inventory.balances.index'));
        </script>
        <script>
            window.batchSerialTrackingMapUrl = @json(route('admin.inventory.tracking-map'));
            window.batchSerialBatchesUrl = @json(url('/admin/inventory/warehouses/{warehouse}/items/{item}/batches'));
            window.batchSerialSerialsUrl = @json(url('/admin/inventory/warehouses/{warehouse}/items/{item}/serials'));
        </script>
        <script src="{{ asset('js/modules/erp/batch-serial.js') }}"></script>
        <script src="{{ asset('js/modules/erp/inventory-adjust-form.js') }}"></script>
    @endpush
</x-layouts.app>
