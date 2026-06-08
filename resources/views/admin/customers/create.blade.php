<x-layouts.app title="Add customer">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Add customer</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">New customer</div>
                </div>
                <div class="card-body">
                    <p class="text-muted fs-13 mb-4">New customers are saved as <strong>active</strong> and can be used on
                        quotations and orders.</p>
                    <form id="customerForm" method="POST" action="{{ route('admin.customers.store') }}" novalidate>
                        @csrf
                        @include('admin.customers.partials.fields', ['customer' => null, 'gstStates' => $gstStates])
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="customerSubmit">Create customer</button>
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-light ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.customerFormSubmitUrl = @json(route('admin.customers.store'));
            window.customerFormMethod = 'POST';
            window.customersIndexUrl = @json(route('admin.customers.index'));
        </script>
        <script src="{{ asset('js/modules/customers/customer-form.js') }}"></script>
    @endpush
</x-layouts.app>
