<x-layouts.app title="Transfer {{ $transfer->transfer_number }}">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">{{ $transfer->transfer_number }}</h1>
            <p class="text-muted mb-0">
                {{ $transfer->fromWarehouse?->code }} → {{ $transfer->toWarehouse?->code }}
                · <span class="badge bg-secondary-transparent">{{ $transfer->status }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('approve', $transfer)
                @if ($transfer->status === 'draft')
                    <button type="button" class="btn btn-success btn-wave js-wt-approve" data-url="{{ route('admin.inventory.warehouse-transfers.approve', $transfer) }}">Approve</button>
                @endif
            @endcan
            @can('dispatch', $transfer)
                <button type="button" class="btn btn-warning btn-wave js-wt-dispatch" data-url="{{ route('admin.inventory.warehouse-transfers.dispatch', $transfer) }}">Dispatch</button>
            @endcan
            @if (in_array($transfer->status, ['in_transit', 'received'], true))
                <a href="{{ route('admin.inventory.warehouse-transfers.pdf', $transfer) }}" class="btn btn-outline-secondary btn-wave" target="_blank" rel="noopener">Challan PDF</a>
            @endif
            <a href="{{ route('admin.inventory.warehouse-transfers.index') }}" class="btn btn-light btn-wave">Back</a>
        </div>
    </div>

    <div class="card custom-card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Requested</th>
                            <th>Dispatched</th>
                            <th>Received</th>
                            <th>Variance reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer->lines as $line)
                            <tr data-line-id="{{ $line->id }}">
                                <td>{{ $line->item?->display_label ?? '—' }}</td>
                                <td>{{ $line->qty_requested }}</td>
                                <td>{{ $line->qty_dispatched ?? '—' }}</td>
                                <td>
                                    @if ($transfer->status === 'in_transit')
                                        <input type="number" step="0.0001" class="form-control form-control-sm js-received-qty" value="{{ $line->qty_dispatched ?? $line->qty_requested }}" min="0">
                                    @else
                                        {{ $line->qty_received ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    @if ($transfer->status === 'in_transit')
                                        <input type="text" class="form-control form-control-sm js-variance-reason" placeholder="If qty differs">
                                    @else
                                        {{ $line->variance_reason ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('receive', $transfer)
                <button type="button" class="btn btn-primary btn-wave js-wt-receive" data-url="{{ route('admin.inventory.warehouse-transfers.receive', $transfer) }}">Confirm receipt</button>
            @endcan
        </div>
    </div>

    @push('scripts')
        <script>
            window.warehouseTransferShow = { transferId: @json($transfer->id) };
        </script>
        <script src="{{ asset('js/modules/erp/warehouse-transfers.js') }}"></script>
    @endpush
</x-layouts.app>
