<x-layouts.app title="Reports">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Operational reports</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        @if ($stats['vendors'] !== null)
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="text-muted fs-12">Vendors</div>
                        <div class="fs-24 fw-semibold">{{ $stats['vendors'] }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if ($stats['customers'] !== null)
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="text-muted fs-12">Customers</div>
                        <div class="fs-24 fw-semibold">{{ $stats['customers'] }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if ($stats['items'] !== null)
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="text-muted fs-12">Items (SKU)</div>
                        <div class="fs-24 fw-semibold">{{ $stats['items'] }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if ($stats['open_purchase_orders'] !== null)
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="text-muted fs-12">Draft purchase orders</div>
                        <div class="fs-24 fw-semibold">{{ $stats['open_purchase_orders'] }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if ($stats['open_sales_orders'] !== null)
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="text-muted fs-12">Open sales orders</div>
                        <div class="fs-24 fw-semibold">{{ $stats['open_sales_orders'] }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if ($stats['employees'] !== null)
            <div class="col">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="text-muted fs-12">Employees</div>
                        <div class="fs-24 fw-semibold">{{ $stats['employees'] }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @can('finance.reports.view')
        <div class="row mt-4">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title mb-0">GST returns (export)</div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted fs-13 mb-3">
                            Download CSV snapshots for the selected calendar month (SRS GSTR-1 / GSTR-3B).
                        </p>
                        <form class="row g-3 align-items-end" method="get" id="gstrExportForm">
                            <div class="col-auto">
                                <label class="form-label">Year</label>
                                <input type="number" name="year" class="form-control" min="2020" max="2099"
                                    value="{{ now()->year }}" required>
                            </div>
                            <div class="col-auto">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-select" required>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" @selected($m === (int) now()->month)>
                                            {{ \Carbon\Carbon::create(null, $m)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-auto d-flex flex-wrap gap-2">
                                <a href="#" class="btn btn-primary btn-wave" id="gstr1ExportBtn">GSTR-1 CSV</a>
                                <a href="#" class="btn btn-outline-primary btn-wave" id="gstr1JsonBtn">GSTR-1 JSON</a>
                                <a href="#" class="btn btn-outline-secondary btn-wave" id="gstr1PdfBtn">GSTR-1 PDF</a>
                                <a href="#" class="btn btn-outline-primary btn-wave" id="gstr3bExportBtn">GSTR-3B CSV</a>
                                <a href="#" class="btn btn-outline-secondary btn-wave" id="gstr3bPdfBtn">GSTR-3B PDF</a>
                                <button type="button" class="btn btn-warning btn-wave" id="gstLockBtn">Lock period</button>
                                <button type="button" class="btn btn-outline-success btn-wave" id="gstFilingBtn">Record ARN</button>
                            </div>
                        </form>
                        <div class="row g-2 mt-2 d-none" id="gstArnForm">
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" id="gstr1Arn" placeholder="GSTR-1 ARN">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" id="gstr3bArn" placeholder="GSTR-3B ARN">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" class="form-control form-control-sm" id="gstr3bTaxPaid" placeholder="Tax paid">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 mt-3">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title mb-0">Financial statements</div></div>
                    <div class="card-body">
                        <form class="row g-3 align-items-end" id="finReportForm">
                            <div class="col-auto">
                                <label class="form-label">From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ now()->startOfYear()->toDateString() }}">
                            </div>
                            <div class="col-auto">
                                <label class="form-label">To / As of</label>
                                <input type="date" name="date_to" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-auto d-flex gap-2">
                                <a href="#" class="btn btn-outline-secondary btn-wave" id="plExportBtn">P&amp;L CSV</a>
                                <a href="#" class="btn btn-outline-secondary btn-wave" id="bsExportBtn">Balance sheet CSV</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                $(function () {
                    var gstr1Base = @json(route('admin.reports.gstr1'));
                    var gstr1JsonBase = @json(route('admin.reports.gstr1.json'));
                    var gstr1PdfBase = @json(route('admin.reports.gstr1.pdf'));
                    var gstr3bBase = @json(route('admin.reports.gstr3b'));
                    var gstr3bPdfBase = @json(route('admin.reports.gstr3b.pdf'));
                    var plBase = @json(route('admin.reports.profit-loss'));
                    var bsBase = @json(route('admin.reports.balance-sheet'));
                    var lockUrl = @json(route('admin.reports.gst-period.lock'));
                    var filingUrl = @json(route('admin.reports.gst-period.filing'));

                    function gstrParams() {
                        var $form = $('#gstrExportForm');
                        return '?year=' + encodeURIComponent($form.find('[name="year"]').val()) + '&month=' + encodeURIComponent($form.find('[name="month"]').val());
                    }
                    $('#gstr1ExportBtn').on('click', function (e) { e.preventDefault(); window.location.href = gstr1Base + gstrParams(); });
                    $('#gstr1JsonBtn').on('click', function (e) { e.preventDefault(); window.location.href = gstr1JsonBase + gstrParams(); });
                    $('#gstr3bExportBtn').on('click', function (e) { e.preventDefault(); window.location.href = gstr3bBase + gstrParams(); });
                    $('#gstr1PdfBtn').on('click', function (e) { e.preventDefault(); window.location.href = gstr1PdfBase + gstrParams(); });
                    $('#gstr3bPdfBtn').on('click', function (e) { e.preventDefault(); window.location.href = gstr3bPdfBase + gstrParams(); });
                    $('#gstLockBtn').on('click', function () {
                        var $f = $('#gstrExportForm');
                        $.post(lockUrl, {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            year: $f.find('[name="year"]').val(),
                            month: $f.find('[name="month"]').val(),
                        }).done(function (r) { notifySuccess(r.message); }).fail(function (x) { notifyError(x.responseJSON?.message || 'Failed'); });
                    });
                    $('#gstFilingBtn').on('click', function () {
                        $('#gstArnForm').toggleClass('d-none');
                        if ($('#gstArnForm').hasClass('d-none')) { return; }
                        var $f = $('#gstrExportForm');
                        $.post(filingUrl, {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            year: $f.find('[name="year"]').val(),
                            month: $f.find('[name="month"]').val(),
                            gstr1_arn: $('#gstr1Arn').val(),
                            gstr3b_arn: $('#gstr3bArn').val(),
                            gstr3b_tax_paid: $('#gstr3bTaxPaid').val(),
                        }).done(function (r) {
                            notifySuccess(r.message);
                            $('#gstArnForm').addClass('d-none');
                        }).fail(function (x) { notifyError(x.responseJSON?.message || 'Failed'); });
                    });
                    $('#plExportBtn').on('click', function (e) {
                        e.preventDefault();
                        var $f = $('#finReportForm');
                        window.location.href = plBase + '?date_from=' + $f.find('[name="date_from"]').val() + '&date_to=' + $f.find('[name="date_to"]').val();
                    });
                    $('#bsExportBtn').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = bsBase + '?as_of=' + $('#finReportForm').find('[name="date_to"]').val();
                    });
                });
            </script>
        @endpush
    @endcan

    @can('reports.inventory')
        <div class="row mt-3">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title mb-0">Batch / serial traceability</div></div>
                    <div class="card-body">
                        <p class="text-muted fs-13 mb-3">
                            FEFO on-hand, expiry alerts, movement history, and CSV exports.
                        </p>
                        <a href="{{ route('admin.inventory.traceability.index') }}" class="btn btn-primary btn-wave">
                            Open traceability dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @if ($items->isNotEmpty())
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card custom-card">
                        <div class="card-header"><div class="card-title mb-0">Stock ledger (PDF)</div></div>
                        <div class="card-body">
                            <form class="row g-3 align-items-end" id="stockLedgerPdfForm">
                                <div class="col-md-4">
                                    <label class="form-label">Item</label>
                                    <select name="item_id" class="form-select" required>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->display_label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="btn btn-outline-secondary btn-wave" id="stockLedgerPdfBtn">Download PDF</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @can('finance.reports.view')
        @if ($vendors->isNotEmpty())
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card custom-card">
                        <div class="card-header"><div class="card-title mb-0">Vendor statement (PDF)</div></div>
                        <div class="card-body">
                            <form class="row g-3 align-items-end" id="vendorStatementPdfForm">
                                <div class="col-md-4">
                                    <label class="form-label">Vendor</label>
                                    <select name="vendor_id" class="form-select" required>
                                        @foreach ($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ now()->startOfYear()->toDateString() }}">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="btn btn-outline-secondary btn-wave" id="vendorStatementPdfBtn">Download PDF</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="row mt-3">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title mb-0">PDF activity log</div>
                        <a href="{{ route('admin.reports.pdf-log.index') }}" class="btn btn-sm btn-outline-primary btn-wave">View log</a>
                    </div>
                    <div class="card-body text-muted fs-13">All generated PDFs with signed download links (SRS §21.13).</div>
                </div>
            </div>
        </div>
    @endcan

    @push('scripts')
        @can('reports.inventory')
            <script>
                $(function () {
                    var stockBase = @json(url('/admin/reports/stock-ledger/pdf'));
                    $('#stockLedgerPdfBtn').on('click', function (e) {
                        e.preventDefault();
                        var $f = $('#stockLedgerPdfForm');
                        window.location.href = stockBase
                            + '?item_id=' + encodeURIComponent($f.find('[name="item_id"]').val())
                            + '&date_from=' + encodeURIComponent($f.find('[name="date_from"]').val())
                            + '&date_to=' + encodeURIComponent($f.find('[name="date_to"]').val());
                    });
                });
            </script>
        @endcan
        @can('finance.reports.view')
            <script>
                $(function () {
                    var vendorBase = @json(url('/admin/reports/vendors'));
                    $('#vendorStatementPdfBtn').on('click', function (e) {
                        e.preventDefault();
                        var $f = $('#vendorStatementPdfForm');
                        var id = $f.find('[name="vendor_id"]').val();
                        window.location.href = vendorBase + '/' + id + '/statement/pdf'
                            + '?date_from=' + encodeURIComponent($f.find('[name="date_from"]').val())
                            + '&date_to=' + encodeURIComponent($f.find('[name="date_to"]').val());
                    });
                });
            </script>
        @endcan
    @endpush
</x-layouts.app>
