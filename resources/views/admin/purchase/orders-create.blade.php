<x-layouts.app title="New PO">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New purchase order</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.purchase.orders.index') }}">PO</a></li>
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
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->vendor_code }} — {{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Approved PR (optional)</label>
                        <select name="pr_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($approvedRequisitions as $pr)
                                <option value="{{ $pr->id }}">{{ $pr->pr_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Order date</label>
                        <input type="date" name="order_date" class="form-control" required
                            value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expected delivery</label>
                        <input type="date" name="expected_delivery" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment terms (days)</label>
                        <input type="number" name="payment_terms_days" class="form-control" min="0" max="365"
                            value="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" maxlength="5000">
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit cost</th>
                                <th style="width: 56px"></th>
                            </tr>
                        </thead>
                        <tbody id="lineDocumentLinesBody">
                            @for ($i = 0; $i < 3; $i++)
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
                                    <td><input type="number" step="0.0001" name="lines[{{ $i }}][unit_cost]"
                                            class="form-control" min="0"></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger js-remove-line"
                                            title="Remove line">&times;</button>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="lineDocumentAddRow">Add line</button>
                <p class="text-muted fs-12 mb-3">Up to 50 lines. Empty rows are ignored on save.</p>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Create PO</button>
                <a href="{{ route('admin.purchase.orders.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.purchase.orders.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.purchase.orders.index'));
            window.lineDocumentMaxLines = 50;
        </script>
        <script src="{{ asset('js/modules/erp/line-document-form.js') }}"></script>
    @endpush
</x-layouts.app>
