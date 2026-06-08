<x-layouts.app title="Edit customer">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Edit customer</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $customer->customer_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('block', $customer)
                <button type="button" class="btn btn-warning btn-wave js-customer-block"
                    data-url="{{ route('admin.customers.block', $customer) }}">Block</button>
            @endcan
            @can('activate', $customer)
                <button type="button" class="btn btn-outline-primary btn-wave js-customer-activate"
                    data-url="{{ route('admin.customers.activate', $customer) }}">Reactivate</button>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card mb-3">
                <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
                    <span class="text-muted fs-13">Code</span>
                    <span class="fw-semibold">{{ $customer->customer_code }}</span>
                    <span class="text-muted fs-13 ms-2">Status</span>
                    <span class="badge bg-primary-transparent">{{ $customer->status->label() }}</span>
                </div>
            </div>
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Customer details</div>
                </div>
                <div class="card-body">
                    <form id="customerForm" method="POST" action="{{ route('admin.customers.update', $customer) }}"
                        novalidate>
                        @csrf
                        @method('PUT')
                        @include('admin.customers.partials.fields', ['customer' => $customer, 'gstStates' => $gstStates, 'priceLists' => $priceLists])
                        <div class="mt-4">
                            @can('update', $customer)
                                <button type="submit" class="btn btn-primary" id="customerSubmit">Save changes</button>
                            @endcan
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-light ms-2">Back to list</a>
                        </div>
                    </form>
                </div>
            </div>
            @can('update', $customer)
                <div class="card custom-card mt-3">
                    <div class="card-header"><div class="card-title">Shipping addresses</div></div>
                    <div class="card-body">
                        <ul class="list-group mb-3">
                            @forelse ($customer->addresses as $addr)
                                <li class="list-group-item">{{ $addr->label }}: {{ $addr->address_line1 }}, {{ $addr->city }} {{ $addr->pincode }}</li>
                            @empty
                                <li class="list-group-item text-muted">No extra addresses.</li>
                            @endforelse
                        </ul>
                        <form id="addressForm" action="{{ route('admin.customers.addresses.store', $customer) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-6"><input name="address_line1" class="form-control" placeholder="Address line 1" required></div>
                                <div class="col-md-3"><input name="city" class="form-control" placeholder="City" required></div>
                                <div class="col-md-2"><input name="pincode" class="form-control" placeholder="Pincode" required></div>
                                <div class="col-md-1"><button class="btn btn-outline-primary">Add</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    @push('scripts')
        <script>
            window.customerFormSubmitUrl = @json(route('admin.customers.update', $customer));
            window.customerFormMethod = 'PUT';
            window.customersIndexUrl = @json(route('admin.customers.index'));
        </script>
        <script src="{{ asset('js/modules/customers/customer-form.js') }}"></script>
    @endpush
</x-layouts.app>
