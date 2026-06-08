<x-layouts.app title="Sales enquiries">
    <div class="my-4"><h1 class="page-title fw-medium fs-18">Sales enquiries</h1></div>
    <div class="row">
        <div class="col-md-4">
            <div class="card custom-card">
                <div class="card-body">
                    <form id="enquiryForm" action="{{ route('admin.sales.enquiries.store') }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select">
                                <option value="">Walk-in</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Contact</label><input name="contact_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        <button type="submit" class="btn btn-primary">Save enquiry</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card custom-card"><div class="card-body">
                <table class="table table-bordered w-100" id="enquiryTable">
                    <thead><tr><th>#</th><th>Customer</th><th>Contact</th><th>Phone</th><th>Status</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div></div>
        </div>
    </div>
    @push('scripts')
        <script>window.enquiryDataUrl = @json(route('admin.sales.enquiries.data'));</script>
        <script src="{{ asset('assets/admin/js/admin/sales/enquiries.js') }}"></script>
    @endpush
</x-layouts.app>
