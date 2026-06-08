<x-layouts.app title="Vendors">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Vendors</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Vendors</li>
                </ol>
            </nav>
        </div>
        @can('create', \App\Models\Vendor::class)
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary btn-wave">Add vendor</a>
        @endcan
    </div>

    <div class="card custom-card">
        <div class="card-header justify-content-between">
            <div class="card-title">Suppliers</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="vendorsTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>GSTIN</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    @endpush

    @push('scripts')
        <script>
            window.vendorsDataUrl = @json(route('admin.vendors.data'));
            window.vendorsIndexUrl = @json(route('admin.vendors.index'));
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/vendors/index.js') }}"></script>
    @endpush
</x-layouts.app>
