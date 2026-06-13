<x-layouts.app title="Reconciliation {{ $reconciliation->reconciliation_number }}">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">{{ $reconciliation->reconciliation_number }}</h1>
            <p class="text-muted mb-0">{{ $reconciliation->warehouse?->name }} · {{ $reconciliation->status }}</p>
        </div>
        <a href="{{ route('admin.inventory.stock-reconciliations.index') }}" class="btn btn-light btn-wave">Back</a>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <form id="stockReconCountsForm">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>System qty</th>
                                <th>Physical qty</th>
                                <th>Variance</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reconciliation->lines as $line)
                                <tr data-line-id="{{ $line->id }}">
                                    <td>{{ $line->item?->display_label ?? '—' }}</td>
                                    <td>{{ $line->system_qty }}</td>
                                    <td>
                                        @if ($reconciliation->status === 'draft')
                                            <input type="number" step="0.0001" class="form-control form-control-sm js-physical-qty" value="{{ $line->physical_qty }}" min="0">
                                        @else
                                            {{ $line->physical_qty }}
                                        @endif
                                    </td>
                                    <td class="js-variance">{{ $line->variance_qty }}</td>
                                    <td>
                                        @if ($reconciliation->status === 'draft')
                                            <input type="text" class="form-control form-control-sm js-reason" value="{{ $line->reason }}">
                                        @else
                                            {{ $line->reason ?? '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @can('update', $reconciliation)
                    <button type="submit" class="btn btn-primary btn-wave">Save counts</button>
                @endcan
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.stockReconCountsUrl = @json(route('admin.inventory.stock-reconciliations.counts', $reconciliation));
        </script>
        <script src="{{ asset('js/modules/erp/stock-reconciliation.js') }}"></script>
    @endpush
</x-layouts.app>
