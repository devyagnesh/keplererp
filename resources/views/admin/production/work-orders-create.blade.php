<x-layouts.app title="New work order">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New work order</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.production.work-orders.index') }}">WO</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <form id="lineDocumentForm" novalidate>
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Item to produce</label>
                        <select name="item_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($items as $it)
                                <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warehouse (for completion)</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BOM id (optional)</label>
                        <input type="number" name="bom_id" class="form-control" min="1" placeholder="Auto if empty">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Qty planned</label>
                        <input type="number" step="0.0001" name="qty_planned" class="form-control" required min="0.0001">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Planned start</label>
                        <input type="date" name="planned_start" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Planned end</label>
                        <input type="date" name="planned_end" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" maxlength="5000">
                </div>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Create</button>
                <a href="{{ route('admin.production.work-orders.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.production.work-orders.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.production.work-orders.index'));
        </script>
        <script src="{{ asset('js/modules/erp/line-document-form.js') }}"></script>
    @endpush
</x-layouts.app>
