<x-layouts.app title="Dashboard">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Business dashboard</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
        </div>
    </div>

    @if ($licenseDaysRemaining !== null && $licenseDaysRemaining <= 30)
        <div class="alert alert-warning">License expires in {{ $licenseDaysRemaining }} day(s). Run <code>php artisan license:refresh</code> after renewal.</div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Sales today (INR)</div>
                    <div class="fs-24 fw-semibold">{{ $stats['sales_today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Pending purchase orders</div>
                    <div class="fs-24 fw-semibold">{{ $stats['pending_purchase_orders'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Low-stock SKUs</div>
                    <div class="fs-24 fw-semibold">{{ $stats['low_stock_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Overdue receivables (INR)</div>
                    <div class="fs-24 fw-semibold">{{ $stats['overdue_receivables'] }}</div>
                    <div class="fs-11 text-muted">{{ $stats['overdue_invoice_count'] }} invoice(s)</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="text-muted fs-12">Production in progress</div>
                    <div class="fs-24 fw-semibold">{{ $stats['production_in_progress'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (count($stats['low_stock_items']) > 0)
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Low stock items</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>On hand</th>
                                <th>Reorder level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['low_stock_items'] as $row)
                                <tr>
                                    <td>{{ $row['item_label'] }}</td>
                                    <td>{{ $row['qty'] }}</td>
                                    <td>{{ $row['reorder_level'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>

