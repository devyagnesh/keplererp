<x-layouts.app title="Payments">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Payments & receipts</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payments</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-lg-6">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">Vendor payment</div></div>
                <div class="card-body">
                    <form id="vendorPaymentForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Payable (GRN)</label>
                            <select name="vendor_payable_id" class="form-select" required>
                                <option value="">—</option>
                                @foreach ($openPayables as $p)
                                    <option value="{{ $p->id }}">{{ $p->vendor?->name }} — {{ $p->amount }} (paid {{ $p->amount_paid }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="NEFT">NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">UTR / reference</label>
                            <input type="text" name="utr_reference" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Record payment</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">Customer receipt</div></div>
                <div class="card-body">
                    <form id="customerReceiptForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Invoice</label>
                            <select name="invoice_id" class="form-select" required>
                                <option value="">—</option>
                                @foreach ($openInvoices as $inv)
                                    <option value="{{ $inv->id }}">{{ $inv->invoice_number }} — {{ $inv->customer?->name }} ({{ $inv->total_amount }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="NEFT">NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">UTR / reference</label>
                            <input type="text" name="utr_reference" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Receipt date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Record receipt</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-0">Vendor invoices (3-way match)</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>GRN</th>
                                    <th>Invoice no.</th>
                                    <th>Amount</th>
                                    <th>Match</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vendorInvoices as $vi)
                                    <tr>
                                        <td>{{ $vi->vendor?->name }}</td>
                                        <td>{{ $vi->vendorPayable?->goodsReceipt?->grn_number ?? '—' }}</td>
                                        <td>{{ $vi->vendor_invoice_number }}</td>
                                        <td>{{ $vi->amount }}</td>
                                        <td>
                                            <span class="badge bg-{{ $vi->match_status === 'matched' ? 'success' : 'warning' }}-transparent">
                                                {{ $vi->match_status ?? $vi->status }}
                                            </span>
                                            @if ($vi->match_notes)
                                                <div class="fs-11 text-muted">{{ $vi->match_notes }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary js-vi-rematch"
                                                data-url="{{ route('admin.finance.vendor-invoices.rematch', $vi) }}">Re-match</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No vendor invoices uploaded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(function () {
                function bindPaymentForm(selector, url) {
                    $(selector).validate({
                        submitHandler: function (form) {
                            var $btn = $(form).find('[type="submit"]');
                            $btn.prop('disabled', true);
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: $(form).serialize(),
                                dataType: 'json',
                                success: function (r) { Toastify({ text: r.message, style: { background: '#28a745' } }).showToast(); location.reload(); },
                                error: function (xhr) {
                                    var r = xhr.responseJSON;
                                    Toastify({ text: r && r.message ? r.message : 'Error', style: { background: '#dc3545' } }).showToast();
                                },
                                complete: function () { $btn.prop('disabled', false); }
                            });
                        }
                    });
                }
                bindPaymentForm('#vendorPaymentForm', @json(route('admin.finance.payments.vendor')));
                bindPaymentForm('#customerReceiptForm', @json(route('admin.finance.payments.customer')));
                $(document).on('click', '.js-vi-rematch', function () {
                    var url = $(this).data('url');
                    $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') }, function (r) {
                        Toastify({ text: r.message, style: { background: '#28a745' } }).showToast();
                        location.reload();
                    }).fail(function (xhr) {
                        var r = xhr.responseJSON;
                        Toastify({ text: r && r.message ? r.message : 'Error', style: { background: '#dc3545' } }).showToast();
                    });
                });
            });
        </script>
    @endpush
</x-layouts.app>


