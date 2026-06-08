<x-layouts.app title="Edit warehouse">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Edit warehouse</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.warehouses.index') }}">Warehouses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">{{ $warehouse->name }}</div>
                </div>
                <div class="card-body">
                    <form id="warehouseForm" method="POST" action="{{ route('admin.warehouses.update', $warehouse) }}"
                        novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ $warehouse->code }}" required
                                maxlength="32">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $warehouse->name }}" required
                                maxlength="120">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="{{ $warehouse->city }}"
                                maxlength="80">
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="whActive"
                                {{ $warehouse->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="whActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary" id="warehouseSubmit">Save</button>
                        <a href="{{ route('admin.warehouses.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.warehouseFormSubmitUrl = @json(route('admin.warehouses.update', $warehouse));
            window.warehouseFormMethod = 'PUT';
            window.warehousesIndexUrl = @json(route('admin.warehouses.index'));
        </script>
        <script src="{{ asset('js/modules/erp/warehouse-form.js') }}"></script>
    @endpush
</x-layouts.app>
