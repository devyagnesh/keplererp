<x-layouts.app title="New PR">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New purchase requisition</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.purchase.requisitions.index') }}">PR</a></li>
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
                        <label class="form-label">Required date</label>
                        <input type="date" name="required_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warehouse (optional)</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" maxlength="5000">
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width: 160px">Qty</th>
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
                <p class="text-muted fs-12 mb-3">You can add up to 30 items. Empty rows are ignored on save.</p>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Submit PR</button>
                <a href="{{ route('admin.purchase.requisitions.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.purchase.requisitions.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.purchase.requisitions.index'));
            window.lineDocumentMaxLines = 30;
        </script>
        <script src="{{ asset('js/modules/erp/line-document-form.js') }}"></script>
    @endpush
</x-layouts.app>
