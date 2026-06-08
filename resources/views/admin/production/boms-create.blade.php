<x-layouts.app title="New BOM">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New bill of materials</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.production.boms.index') }}">BOM</a></li>
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
                    <div class="col-md-6">
                        <label class="form-label">Parent item (finished good)</label>
                        <select name="parent_item_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($items as $it)
                                <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Version</label>
                        <input type="number" name="version" class="form-control" value="1" required min="1" max="999">
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
                                <th>Component item</th>
                                <th>Qty per</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td>
                                        <select name="lines[{{ $i }}][component_item_id]" class="form-select">
                                            <option value="">—</option>
                                            @foreach ($items as $it)
                                                <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.000001" name="lines[{{ $i }}][quantity_per]"
                                            class="form-control" min="0"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Save BOM</button>
                <a href="{{ route('admin.production.boms.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.production.boms.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.production.boms.index'));
        </script>
        <script src="{{ asset('js/modules/erp/line-document-form.js') }}"></script>
    @endpush
</x-layouts.app>
