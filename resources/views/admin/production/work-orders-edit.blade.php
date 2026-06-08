<x-layouts.app title="Work order">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Work order {{ $productionOrder->wo_number }}</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.production.work-orders.index') }}">WO</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <form id="workOrderForm" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach (['planned', 'in_progress', 'completed', 'cancelled'] as $st)
                            <option value="{{ $st }}" @selected($productionOrder->status === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Actual output qty (when completing)</label>
                    <input type="number" step="0.0001" name="actual_qty" class="form-control" min="0.0001"
                        value="{{ $productionOrder->actual_qty ?? $productionOrder->qty_planned }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" maxlength="5000">{{ $productionOrder->notes }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="workOrderSubmit">Save</button>
                <a href="{{ route('admin.production.work-orders.index') }}" class="btn btn-light ms-2">Back</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.workOrderFormSubmitUrl = @json(route('admin.production.work-orders.update', $productionOrder));
            window.workOrdersIndexUrl = @json(route('admin.production.work-orders.index'));
        </script>
        <script src="{{ asset('js/modules/erp/production-work-order-form.js') }}"></script>
    @endpush
</x-layouts.app>
