<x-layouts.app title="GRN Returns">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="page-title fw-medium fs-18 mb-0">GRN Returns</h1>
    </div>
    <div class="row">
        <div class="col-xl-5">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">Post return</div></div>
                <div class="card-body">
                    <form id="grnReturnForm" action="{{ route('admin.purchase.grn-returns.store') }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Posted GRN</label>
                            <select name="goods_receipt_id" id="grnSelect" class="form-select" required>
                                <option value="">Select GRN</option>
                                @foreach ($grns as $grn)
                                    <option value="{{ $grn->id }}" data-lines="{{ $grn->lines->toJson() }}">
                                        {{ $grn->grn_number }} — {{ $grn->vendor?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item</label>
                            <select name="lines[0][item_id]" id="grnReturnItem" class="form-select" required>
                                <option value="">Select GRN first</option>
                            </select>
                        </div>
                        <div class="mb-3 js-grn-return-batch d-none">
                            <label class="form-label">Batch no</label>
                            <select name="lines[0][batch_no]" id="grnReturnBatch" class="form-select">
                                <option value="">—</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Return qty</label>
                            <input type="number" name="lines[0][quantity]" class="form-control" step="0.0001" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Debit note amount</label>
                            <input type="number" name="debit_amount" class="form-control" step="0.01" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-wave">Post return</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card custom-card">
                <div class="card-body">
                    <table class="table table-bordered w-100" id="grnReturnTable">
                        <thead>
                            <tr><th>Return #</th><th>GRN</th><th>Vendor</th><th>Status</th><th>Posted</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            window.grnReturnDataUrl = @json(route('admin.purchase.grn-returns.data'));
            window.batchSerialTrackingMapUrl = @json(route('admin.inventory.tracking-map'));
        </script>
        <script src="{{ asset('js/modules/erp/batch-serial.js') }}"></script>
        <script src="{{ asset('js/modules/erp/grn-return-form.js') }}"></script>
    @endpush
</x-layouts.app>
