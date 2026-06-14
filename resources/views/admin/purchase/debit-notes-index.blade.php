<x-layouts.app title="Debit Notes">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-18 mb-2">Debit Notes</h1>
            <p class="text-muted mb-0 fs-13">Vendor debit notes are created automatically when a GRN return is posted.</p>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="debitNoteTable">
                    <thead>
                        <tr>
                            <th>DN #</th>
                            <th>Vendor</th>
                            <th>GRN return</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Created</th>
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
                tableSelector: '#debitNoteTable',
                dataUrl: @json(route('admin.purchase.debit-notes.data')),
                columns: [
                    { data: 'debit_note_number', name: 'debit_note_number' },
                    { data: 'vendor', name: 'vendor', orderable: false },
                    { data: 'grn_return', name: 'grn_return', orderable: false, searchable: false },
                    { data: 'amount', name: 'amount' },
                    { data: 'status', name: 'status' },
                    { data: 'reason', name: 'reason', orderable: false },
                    { data: 'created_at', name: 'created_at' },
                ],
                order: [[0, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
    @endpush
</x-layouts.app>
