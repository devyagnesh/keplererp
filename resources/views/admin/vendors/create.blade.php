<x-layouts.app title="Add vendor">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Add vendor</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">New supplier</div>
                </div>
                <div class="card-body">
                    <p class="text-muted fs-13 mb-4">New vendors are created in <strong>pending approval</strong> status
                        until authorised.</p>
                    <form id="vendorForm" method="POST" action="{{ route('admin.vendors.store') }}" novalidate>
                        @csrf
                        @include('admin.vendors.partials.fields', ['vendor' => null, 'gstStates' => $gstStates])
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="vendorSubmit">Create vendor</button>
                            <a href="{{ route('admin.vendors.index') }}" class="btn btn-light ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.vendorFormSubmitUrl = @json(route('admin.vendors.store'));
            window.vendorFormMethod = 'POST';
            window.vendorsIndexUrl = @json(route('admin.vendors.index'));
        </script>
        <script src="{{ asset('js/modules/vendors/vendor-form.js') }}"></script>
    @endpush
</x-layouts.app>
