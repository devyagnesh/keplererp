<x-layouts.app title="Audit Log">
    <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="page-title fw-medium fs-18 mb-0">Audit Log</h1>
    </div>

    <div class="card custom-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="card-title mb-0">Critical business events</div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-muted fs-13" for="filterAuditAction">Action</label>
                <select id="filterAuditAction" class="form-select form-select-sm w-auto">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="auditLogTable">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Description</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>IP</th>
                            <th>When</th>
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
                tableSelector: '#auditLogTable',
                dataUrl: @json(route('admin.audit-logs.data')),
                extraAjaxData: function () {
                    return { action: $('#filterAuditAction').val() || '' };
                },
                columns: [
                    { data: 'action', name: 'action' },
                    { data: 'description', name: 'description', orderable: false },
                    { data: 'user', name: 'user', orderable: false },
                    { data: 'subject', name: 'subject', orderable: false, searchable: false },
                    { data: 'ip_address', name: 'ip_address', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                ],
                order: [[5, 'desc']],
            };
        </script>
        <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ asset('js/modules/erp/configurable-dt.js') }}"></script>
        <script src="{{ asset('assets/admin/js/admin/audit-logs.js') }}"></script>
    @endpush
</x-layouts.app>
