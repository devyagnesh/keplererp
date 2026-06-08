<x-layouts.app title="Edit vendor">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Edit vendor</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.vendors.index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('approve', $vendor)
                <button type="button" class="btn btn-success btn-wave js-vendor-approve"
                    data-url="{{ route('admin.vendors.approve', $vendor) }}">Approve</button>
            @endcan
            @can('block', $vendor)
                <button type="button" class="btn btn-warning btn-wave js-vendor-block"
                    data-url="{{ route('admin.vendors.block', $vendor) }}">Block</button>
            @endcan
            @can('activate', $vendor)
                <button type="button" class="btn btn-outline-primary btn-wave js-vendor-activate"
                    data-url="{{ route('admin.vendors.activate', $vendor) }}">Reactivate</button>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card custom-card mb-3">
                <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
                    <span class="text-muted fs-13">Code</span>
                    <span class="fw-semibold">{{ $vendor->vendor_code }}</span>
                    <span class="text-muted fs-13 ms-2">Status</span>
                    <span class="badge bg-primary-transparent">{{ $vendor->status->label() }}</span>
                </div>
            </div>
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Supplier details</div>
                </div>
                <div class="card-body">
                    <form id="vendorForm" method="POST" action="{{ route('admin.vendors.update', $vendor) }}"
                        novalidate>
                        @csrf
                        @method('PUT')
                        @include('admin.vendors.partials.fields', ['vendor' => $vendor, 'gstStates' => $gstStates])
                        <div class="mt-4">
                            @can('update', $vendor)
                                <button type="submit" class="btn btn-primary" id="vendorSubmit">Save changes</button>
                            @endcan
                            <a href="{{ route('admin.vendors.index') }}" class="btn btn-light ms-2">Back to list</a>
                        </div>
                    </form>
                </div>
            </div>
            @can('update', $vendor)
                <div class="card custom-card mt-3">
                    <div class="card-header">
                        <div class="card-title">Compliance documents</div>
                    </div>
                    <div class="card-body">
                        <form id="vendorDocumentForm" enctype="multipart/form-data" novalidate>
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select name="document_type" class="form-select" required>
                                        <option value="GST_CERT">GST certificate</option>
                                        <option value="PAN">PAN</option>
                                        <option value="MSME">MSME</option>
                                        <option value="CONTRACT">Contract</option>
                                        <option value="OTHER">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">File (PDF/JPG/PNG, max 5 MB)</label>
                                    <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-outline-primary w-100" id="vendorDocumentSubmit">
                                        Upload
                                    </button>
                                </div>
                            </div>
                        </form>
                        @if ($documents->isNotEmpty())
                            <ul class="list-group list-group-flush mt-3">
                                @foreach ($documents as $doc)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $doc->document_type }} — {{ $doc->original_name }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger js-vendor-doc-delete"
                                            data-url="{{ route('admin.vendors.documents.destroy', [$vendor, $doc]) }}">
                                            Delete
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>

    @push('scripts')
        <script>
            window.vendorFormSubmitUrl = @json(route('admin.vendors.update', $vendor));
            window.vendorFormMethod = 'PUT';
            window.vendorsIndexUrl = @json(route('admin.vendors.index'));
            window.vendorDocumentUploadUrl = @json(route('admin.vendors.documents.store', $vendor));
        </script>
        <script src="{{ asset('js/modules/vendors/vendor-form.js') }}"></script>
        <script src="{{ asset('js/modules/vendors/vendor-documents.js') }}"></script>
    @endpush
</x-layouts.app>
