<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vendor portal</title>
    <link rel="stylesheet" href="{{ asset('libs/bootstrap/css/bootstrap.min.css') }}">
</head>
<body class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $vendor->name }}</h1>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="vendorLogout">Log out</button>
    </div>

    <h2 class="h5">Purchase orders</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>PO</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Delivery</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $po)
                    <tr>
                        <td>{{ $po->po_number }}</td>
                        <td>{{ $po->order_date?->format('Y-m-d') }}</td>
                        <td>{{ $po->status }}</td>
                        <td>{{ $po->vendor_delivery_status ?? '—' }}</td>
                        <td>{{ $po->total_amount }}</td>
                        <td>
                            @if ($po->status === 'sent')
                                <button type="button" class="btn btn-sm btn-success js-po-accept" data-url="{{ route('vendor.portal.po.accept', $po) }}">Accept</button>
                                <button type="button" class="btn btn-sm btn-outline-danger js-po-reject" data-url="{{ route('vendor.portal.po.reject', $po) }}">Reject</button>
                            @endif
                            @if (in_array($po->status, ['sent', 'accepted', 'approved'], true))
                                <button type="button" class="btn btn-sm btn-outline-primary js-po-delivery" data-url="{{ route('vendor.portal.po.delivery', $po) }}">Update delivery</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No purchase orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h5">Payment history</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm">
            <thead><tr><th>Payment #</th><th>Date</th><th>Amount</th><th>Method</th><th>UTR</th></tr></thead>
            <tbody>
                @forelse ($payments as $pay)
                    <tr>
                        <td>{{ $pay->payment_number }}</td>
                        <td>{{ $pay->payment_date?->format('Y-m-d') }}</td>
                        <td>{{ $pay->amount }}</td>
                        <td>{{ $pay->payment_method }}</td>
                        <td>{{ $pay->utr_reference ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h5">Payables &amp; invoice upload</h2>
    <p class="text-muted fs-13">Upload your tax invoice against an open GRN payable. Payment status reflects finance records.</p>
    <div class="table-responsive mb-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>GRN / Payable</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Your invoices</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payables as $p)
                    <tr>
                        <td>{{ $p->goodsReceipt?->grn_number ?? '#' . $p->id }}</td>
                        <td>{{ $p->amount }}</td>
                        <td>{{ $p->amount_paid ?? '0.00' }}</td>
                        <td>{{ $p->status }}</td>
                        <td>
                            @forelse ($p->vendorInvoices as $vi)
                                <span class="badge bg-{{ $vi->match_status === 'matched' ? 'success' : 'warning' }}-transparent">
                                    {{ $vi->vendor_invoice_number }} ({{ $vi->match_status ?? $vi->status }})
                                </span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No open payables.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="h6">Upload tax invoice</h3>
            <form id="vendorInvoiceForm" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Payable (GRN)</label>
                        <select name="vendor_payable_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($payables as $p)
                                <option value="{{ $p->id }}">{{ $p->goodsReceipt?->grn_number }} — {{ $p->amount }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invoice no.</label>
                        <input type="text" name="vendor_invoice_number" class="form-control" required maxlength="50">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="invoice_date" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required min="0.01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">File</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" id="vendorInvoiceSubmit">Upload</button>
                    </div>
                </div>
            </form>
            <div id="vendorInvoiceMsg" class="mt-2 small"></div>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(function () {
            $('#vendorLogout').on('click', function () {
                $.post(@json(route('vendor.portal.logout')), { _token: $('meta[name="csrf-token"]').attr('content') }, function (r) {
                    window.location = r.redirect;
                });
            });
            $('.js-po-accept').on('click', function () {
                $.post($(this).data('url'), { _token: $('meta[name="csrf-token"]').attr('content') }, function () { location.reload(); });
            });
            $('.js-po-reject').on('click', function () {
                $.post($(this).data('url'), { _token: $('meta[name="csrf-token"]').attr('content'), rejected_reason: 'Rejected by vendor' }, function () { location.reload(); });
            });
            $('.js-po-delivery').on('click', function () {
                var status = window.prompt('Delivery status: in_transit, delivered, or delayed');
                if (!status) { return; }
                var notes = window.prompt('Notes (optional)') || '';
                $.post($(this).data('url'), {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    vendor_delivery_status: status,
                    vendor_delivery_notes: notes,
                }, function () { location.reload(); });
            });
            $('#vendorInvoiceForm').on('submit', function (e) {
                e.preventDefault();
                var $btn = $('#vendorInvoiceSubmit');
                $btn.prop('disabled', true);
                var data = new FormData(this);
                $.ajax({
                    url: @json(route('vendor.portal.vendor-invoices.store')),
                    type: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json' },
                    success: function (r) {
                        $('#vendorInvoiceMsg').text(r.message || 'Uploaded.').addClass('text-success');
                        setTimeout(function () { location.reload(); }, 800);
                    },
                    error: function (xhr) {
                        var m = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Upload failed.';
                        $('#vendorInvoiceMsg').text(m).addClass('text-danger');
                    },
                    complete: function () { $btn.prop('disabled', false); }
                });
            });
        });
    </script>
</body>
</html>
