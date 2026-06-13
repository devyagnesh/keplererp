<x-layouts.app title="New GRN">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">New goods receipt</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.purchase.grns.index') }}">GRN</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <form id="lineDocumentForm" novalidate enctype="multipart/form-data">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Received at</label>
                        <input type="datetime-local" name="received_at" class="form-control" required
                            value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purchase order <span class="text-danger">*</span></label>
                    <select name="purchase_order_id" class="form-select" required>
                        <option value="">—</option>
                        @foreach ($purchaseOrders as $po)
                            <option value="{{ $po->id }}">{{ $po->po_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" maxlength="5000">
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">QC officer</label>
                        <input type="text" name="qc_officer_name" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">QC photo</label>
                        <input type="file" name="qc_photo" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>QC status</th>
                                <th>QC remarks</th>
                                <th>Batch no</th>
                                <th>Serial no</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td>
                                        <select name="lines[{{ $i }}][item_id]" class="form-select">
                                            <option value="">—</option>
                                            @foreach ($items as $it)
                                                <option value="{{ $it->id }}">{{ $it->display_label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.0001" name="lines[{{ $i }}][quantity]"
                                            class="form-control" min="0"></td>
                                    <td>
                                        <select name="lines[{{ $i }}][qc_status]" class="form-select">
                                            <option value="">—</option>
                                            <option value="pass">Pass</option>
                                            <option value="fail">Fail</option>
                                            <option value="hold">Hold</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="lines[{{ $i }}][qc_remarks]" class="form-control" maxlength="500"></td>
                                    <td class="js-batch-cell">
                                        <input type="text" name="lines[{{ $i }}][batch_no]" class="form-control"
                                            maxlength="50" placeholder="If batch tracked">
                                    </td>
                                    <td class="js-serial-cell">
                                        <input type="text" name="lines[{{ $i }}][serial_no]" class="form-control"
                                            maxlength="50" placeholder="If serial tracked">
                                    </td>
                                    <td class="js-expiry-cell">
                                        <input type="date" name="lines[{{ $i }}][expiry_date]" class="form-control">
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" id="lineDocumentSubmit">Post GRN</button>
                <a href="{{ route('admin.purchase.grns.index') }}" class="btn btn-light ms-2">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.lineDocumentSubmitUrl = @json(route('admin.purchase.grns.store'));
            window.lineDocumentRedirectUrl = @json(route('admin.purchase.grns.index'));
        </script>
        <script>
            window.batchSerialTrackingMapUrl = @json(route('admin.inventory.tracking-map'));
        </script>
        <script src="{{ asset('js/modules/erp/batch-serial.js') }}"></script>
        <script src="{{ asset('js/modules/erp/grn-create-batch-serial.js') }}"></script>
        <script src="{{ asset('js/modules/erp/grn-form.js') }}"></script>
    @endpush
</x-layouts.app>
