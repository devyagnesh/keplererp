<x-layouts.app title="Add item">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Add item</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.items.index') }}">Items</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">New SKU</div>
                </div>
                <div class="card-body">
                    <form id="itemForm" method="POST" action="{{ route('admin.items.store') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" required maxlength="64">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required maxlength="191">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">UOM</label>
                            <input type="text" name="uom" class="form-control" value="PCS" required maxlength="16">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reorder level</label>
                            <input type="number" step="0.0001" name="reorder_level" class="form-control" value="0"
                                required>
                        </div>
                        <input type="hidden" name="is_batch_tracked" value="0">
                        <input type="hidden" name="is_serial_tracked" value="0">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_batch_tracked" value="1"
                                id="itBatch">
                            <label class="form-check-label" for="itBatch">Batch tracked</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_serial_tracked" value="1"
                                id="itSerial">
                            <label class="form-check-label" for="itSerial">Serial tracked (qty 1 per unit)</label>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="itActive"
                                checked>
                            <label class="form-check-label" for="itActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary" id="itemSubmit">Create</button>
                        <a href="{{ route('admin.items.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.itemFormSubmitUrl = @json(route('admin.items.store'));
            window.itemFormMethod = 'POST';
            window.itemsIndexUrl = @json(route('admin.items.index'));
        </script>
        <script src="{{ asset('js/modules/erp/item-form.js') }}"></script>
    @endpush
</x-layouts.app>
