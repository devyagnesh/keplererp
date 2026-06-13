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

    @if ($materials->isNotEmpty())
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title mb-0">Material consumption (SRS UC 22.3)</div></div>
            <div class="card-body">
                <form id="woMaterialsForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Planned</th>
                                    <th>Stock</th>
                                    <th>Actual consumed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($materials as $mat)
                                    @php
                                        $stock = $stockMap[$mat->item_id] ?? '0';
                                        $planned = (float) $mat->planned_qty;
                                        $ok = (float) $stock >= $planned;
                                    @endphp
                                    <tr data-material-id="{{ $mat->id }}">
                                        <td>{{ $mat->item?->display_label ?? '—' }}</td>
                                        <td>{{ $mat->planned_qty }}</td>
                                        <td>{{ $stock }}</td>
                                        <td>
                                            @if ($productionOrder->status === 'in_progress')
                                                <input type="number" step="0.0001" class="form-control form-control-sm js-actual-qty"
                                                    value="{{ $mat->actual_qty ?? $mat->planned_qty }}" min="0">
                                            @else
                                                {{ $mat->actual_qty ?? '—' }}
                                            @endif
                                        </td>
                                        <td><span class="badge bg-{{ $ok ? 'success' : 'danger' }}-transparent">{{ $ok ? 'OK' : 'SHORT' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($productionOrder->status === 'in_progress')
                        <button type="submit" class="btn btn-outline-primary btn-wave">Save consumption</button>
                    @endif
                </form>
            </div>
        </div>
    @endif

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
            window.woMaterialsUrl = @json(route('admin.production.work-orders.materials', $productionOrder));
        </script>
        <script src="{{ asset('js/modules/erp/production-work-order-form.js') }}"></script>
        <script src="{{ asset('js/modules/erp/production-materials.js') }}"></script>
    @endpush
</x-layouts.app>
