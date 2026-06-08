<x-layouts.app title="New sales order">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New sales order</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.sales.orders.index') }}">Orders</a></li>
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
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ship-from warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Order date</label>
                        <input type="date" name="order_date" class="form-control" required
                            value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" maxlength="5000">
                    </div>
                </div>
                @can('company.edit')
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="credit_limit_override"
                            id="creditLimitOverride">
                        <label class="form-check-label" for="creditLimitOverride">
                            Allow order above customer credit limit (admin override)
                        </label>
                    </div>
                @endcan
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td>
                                        <select name="lines[{{ $i }}][item_id]" class="form-select">
                                            <option value="">—</option>
                                            @foreach ($items as $it)
                                                <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.0001" name="lines[{{ $i }}][quantity]"
                                            class="form-control" min="0"></td>
                                    <td><input type="number" step="0.0001" name="lines[{{ $i }}][unit_price]"
                                            class="form-control" min="0"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Create</button>
                <a href="{{ route('admin.sales.orders.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.sales.orders.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.sales.orders.index'));
        </script>
        <script src="{{ asset('js/modules/erp/line-document-form.js') }}"></script>
    @endpush
</x-layouts.app>
