<x-layouts.app title="Create warehouse transfer">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New warehouse transfer</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.warehouse-transfers.index') }}">Transfers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <form id="warehouseTransferForm" action="{{ route('admin.inventory.warehouse-transfers.store') }}" method="POST" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">From warehouse</label>
                        <select name="from_warehouse_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To warehouse</label>
                        <select name="to_warehouse_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-3">Line items</h6>
                <div id="transferLines">
                    <div class="row g-2 mb-2 transfer-line">
                        <div class="col-md-5">
                            <select name="lines[0][item_id]" class="form-select" required>
                                <option value="">Item</option>
                                @foreach ($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->sku }} — {{ $it->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.0001" name="lines[0][qty_requested]" class="form-control" placeholder="Qty" required min="0.0001">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="lines[0][batch_no]" class="form-control" placeholder="Batch">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="lines[0][serial_no]" class="form-control" placeholder="Serial">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-wave">Save draft</button>
                    <a href="{{ route('admin.inventory.warehouse-transfers.index') }}" class="btn btn-light btn-wave">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/modules/erp/warehouse-transfers.js') }}"></script>
    @endpush
</x-layouts.app>
