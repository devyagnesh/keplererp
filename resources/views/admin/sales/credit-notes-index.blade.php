<x-layouts.app title="Credit Notes">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="page-title fw-medium fs-18 mb-0">Credit Notes</h1>
    </div>
    <div class="row">
        <div class="col-xl-5">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">New credit note</div></div>
                <div class="card-body">
                    <form id="creditNoteForm" action="{{ route('admin.sales.credit-notes.store') }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item</label>
                            <select name="lines[0][item_id]" class="form-select" required>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->display_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Qty</label>
                                <input type="number" name="lines[0][quantity]" class="form-control" value="1" step="0.0001" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Unit price</label>
                                <input type="number" name="lines[0][unit_price]" class="form-control" value="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-wave">Post credit note</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card custom-card">
                <div class="card-body">
                    <table class="table table-bordered w-100" id="creditNoteTable">
                        <thead>
                            <tr>
                                <th>CN #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            window.creditNoteDataUrl = @json(route('admin.sales.credit-notes.data'));
        </script>
        <script src="{{ asset('assets/admin/js/admin/sales/credit-notes.js') }}"></script>
    @endpush
</x-layouts.app>
