<x-layouts.app title="Sales Invoices">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="page-title fw-medium fs-18 mb-0">Sales Invoices</h1>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="card-title mb-0">Posted invoices</div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-muted fs-13" for="filterInvoiceStatus">Status</label>
                <select id="filterInvoiceStatus" class="form-select form-select-sm w-auto">
                    <option value="">All</option>
                    <option value="posted">Posted</option>
                    <option value="partially_paid">Partially paid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="invoiceTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Sales order</th>
                            <th>Invoice date</th>
                            <th>Due date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
        <link rel="stylesheet" href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    @endpush

    @push('scripts')
        <script>
            window.erpTableConfig = {
                tableSelector: '#invoiceTable',
                dataUrl: @json(route('admin.sales.invoices.data')),
                extraAjaxData: function () {
                    return { status: $('#filterInvoiceStatus').val() || '' };
                },
                columns: [
                    { data: 'invoice_number', name: 'invoice_number' },
                    { data: 'customer', name: 'customer', orderable: false },
                    { data: 'sales_order', name: 'sales_order', orderable: false, searchable: false },
                    { data: 'invoice_date', name: 'invoice_date' },
                    { data: 'due_date', name: 'due_date' },
                    { data: 'total_amount', name: 'total_amount' },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
                order: [[0, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script src="{{ asset('assets/admin/js/admin/sales/invoices.js') }}"></script>
    @endpush
</x-layouts.app>
